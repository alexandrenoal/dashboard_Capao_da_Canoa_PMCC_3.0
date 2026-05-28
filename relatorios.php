<?php
require_once "conexao.php";

// ── 1. DIRECIONAMENTO DE DATAS FILTRADAS ──────────────────
$ano_padrão = $mysqli->query("SELECT MAX(Ods_ano) FROM ordemservic")->fetch_row()[0] ?? date('Y');
$ano_sel = $_GET['ano'] ?? $ano_padrão;
$mes_sel = $_GET['mes'] ?? date('m');

// ── 2. CONSULTAS DOS CONTADORES (KPIS NO TOPO) ────────────
$tot_geral = $mysqli->query("SELECT COUNT(*) FROM ordemservic")->fetch_row()[0];
$tot_ano   = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_ano='$ano_sel'")->fetch_row()[0];

$r_mes = $mysqli->prepare("SELECT COUNT(*) FROM ordemservic WHERE Ods_ano=? AND MONTH(Ods_entrada)=?");
$r_mes->bind_param('ss', $ano_sel, $mes_sel); 
$r_mes->execute();
$tot_mes = $r_mes->get_result()->fetch_row()[0];

// ── 3. QUERY HISTÓRICO POR ANO (GRÁFICO) ──────────────────
$r = $mysqli->query("
    SELECT Ods_ano AS label, COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano IS NOT NULL
    GROUP BY Ods_ano
    ORDER BY Ods_ano ASC
");
$por_ano = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// ── 4. QUERY POR MÊS (GRÁFICO) ─────────────────────────────
$stmt = $mysqli->prepare("
    SELECT DATE_FORMAT(Ods_entrada, '%m') AS mes_num,
           DATE_FORMAT(Ods_entrada, '%b') AS label,
           COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano = ? AND Ods_entrada IS NOT NULL
    GROUP BY mes_num, label
    ORDER BY mes_num ASC
");
$por_mes = [];
if ($stmt) {
    $stmt->bind_param('s', $ano_sel);
    $stmt->execute();
    $por_mes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Nomes dos meses em PT
$meses_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
foreach ($por_mes as &$m) {
    $m['label'] = $meses_pt[(int)$m['mes_num'] - 1];
}
unset($m);

// ── 5. QUERY POR DIA (GRÁFICO) ─────────────────────────────
$stmt2 = $mysqli->prepare("
    SELECT DAY(Ods_entrada) AS label, COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano = ? AND MONTH(Ods_entrada) = ? AND Ods_entrada IS NOT NULL
    GROUP BY DAY(Ods_entrada)
    ORDER BY DAY(Ods_entrada) ASC
");
$por_dia = [];
if ($stmt2) {
    $stmt2->bind_param('ss', $ano_sel, $mes_sel);
    $stmt2->execute();
    $por_dia = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Busca anos para os filtros do topo
$r_anos = $mysqli->query("SELECT DISTINCT Ods_ano FROM ordemservic WHERE Ods_ano IS NOT NULL ORDER BY Ods_ano DESC");
$anos_disp = $r_anos ? $r_anos->fetch_all(MYSQLI_ASSOC) : [];
$meses_full = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$page_title = "Relatórios · TI";
include 'includes/header.php'; 
?>

<div class="topbar">
    <div class="topbar-left">
        <div class="page-eyebrow">// estatísticas e dados</div>
        <div class="page-title">Relatórios Gráficos</div>
    </div>
    <form method="GET" class="topbar-filters">
        <select name="ano">
            <?php foreach($anos_disp as $a): ?>
            <option value="<?=$a['Ods_ano']?>" <?=$ano_sel==$a['Ods_ano']?'selected':''?>><?=$a['Ods_ano']?></option>
            <?php endforeach; ?>
        </select>
        <select name="mes">
            <?php for($i=1;$i<=12;$i++): ?>
            <option value="<?=str_pad($i,2,'0',STR_PAD_LEFT)?>" <?=(int)$mes_sel===$i?'selected':''?>><?=$meses_full[$i]?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn-apply">Filtrar</button>
    </form>
</div>

<div class="grid-kpi" style="margin-top: 20px;">
    <div class="kpi-card">
        <div class="kpi-hdr">
            <span class="kpi-label">TOTAL GERAL</span>
            <span class="kpi-icon" style="color: var(--g)">🗲</span>
        </div>
        <div class="kpi-num"><?= number_format($tot_geral, 0, ',', '.') ?></div>
        <div class="kpi-sub">Ordens de serviço históricas</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-hdr">
            <span class="kpi-label">TOTAL DO ANO (<?= $ano_sel ?>)</span>
            <span class="kpi-icon" style="color: #e8f020">⚡</span>
        </div>
        <div class="kpi-num" style="color: #e8f020"><?= number_format($tot_ano, 0, ',', '.') ?></div>
        <div class="kpi-sub">Registradas em todo o ano</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-hdr">
            <span class="kpi-label">TOTAL DO MÊS (<?= $meses_pt[(int)$mes_sel - 1] ?>)</span>
            <span class="kpi-icon" style="color: #20f0b8">📈</span>
        </div>
        <div class="kpi-num" style="color: #20f0b8"><?= number_format($tot_mes, 0, ',', '.') ?></div>
        <div class="kpi-sub">Entradas no período filtrado</div>
    </div>
</div>

<div class="grid-main" style="margin-top: 20px;">
    <div class="card span2">
        <div class="card-head">
            <span class="card-title">Ordens de Serviço por Mês</span>
            <span class="card-sub">Ano selecionado: <?=$ano_sel?></span>
        </div>
        <div class="chart-wrap" style="position: relative; height: 240px; width: 100%;">
            <canvas id="chartMes"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">Evolução por Ano</span>
            <span class="card-sub">Histórico geral</span>
        </div>
        <div class="chart-wrap" style="position: relative; height: 240px; width: 100%;">
            <canvas id="chartAno"></canvas>
        </div>
    </div>
</div>

<div class="grid-bottom" style="margin-top: 20px; grid-template-columns: 1fr;">
    <div class="card">
        <div class="card-head">
            <span class="card-title">Produtividade Diária</span>
            <span class="card-sub"><?=$meses_full[(int)$mes_sel]?> de <?=$ano_sel?></span>
        </div>
        <div class="chart-wrap" style="position: relative; height: 200px; width: 100%;">
            <canvas id="chartDia"></canvas>
        </div>
    </div>
</div>

<script>
// Captura dos dados vindos do PHP
const dAno = {
    labels: <?= json_encode(array_column($por_ano, 'label')) ?>,
    data: <?= json_encode(array_map('intval', array_column($por_ano, 'total'))) ?>
};
const dMes = {
    labels: <?= json_encode(array_column($por_mes, 'label')) ?>,
    data: <?= json_encode(array_map('intval', array_column($por_mes, 'total'))) ?>
};
const dDia = {
    labels: <?= json_encode(array_map(fn($d) => ''.$d['label'], $por_dia)) ?>,
    data: <?= json_encode(array_map('intval', array_column($por_dia, 'total'))) ?>
};

// O código só roda quando os elementos canvas existirem na árvore do HTML
document.addEventListener("DOMContentLoaded", function() {
    
    // Configurações Globais de Design do Chart.js
    Chart.defaults.color = '#4a526a';
    Chart.defaults.font.family = "'JetBrains Mono', monospace";
    Chart.defaults.font.size = 10;
    const gridColor = 'rgba(29, 34, 53, 0.6)';

    // Função construtora das opções estruturadas para evitar falhas de leitura
    function obterOpcoes(corTooltip) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0e1018',
                    borderColor: '#1d2235',
                    borderWidth: 1,
                    titleColor: '#e2e8f8',
                    bodyColor: corTooltip,
                    padding: 10,
                    displayColors: false,
                    callbacks: { label: ctx => `  ${ctx.parsed.y} OS` }
                }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { maxRotation: 0 } },
                y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }
            }
        };
    }

    // ── 1. GRÁFICO ANO (Barras Amarelas) ─────────────────────────
    const ctxAno = document.getElementById('chartAno');
    if(ctxAno) {
        new Chart(ctxAno, {
            type: 'bar',
            data: {
                labels: dAno.labels,
                datasets: [{
                    data: dAno.data,
                    backgroundColor: 'rgba(232, 240, 32, 0.15)',
                    borderColor: '#e8f020',
                    borderWidth: 1,
                    borderRadius: 4,
                    hoverBackgroundColor: '#e8f020',
                }]
            },
            options: obterOpcoes('#e8f020')
        });
    }

    // ── 2. GRÁFICO MÊS (Linha Menta) ───────────────────────────
    const ctxMes = document.getElementById('chartMes');
    if(ctxMes) {
        new Chart(ctxMes, {
            type: 'line',
            data: {
                labels: dMes.labels,
                datasets: [{
                    data: dMes.data,
                    borderColor: '#20f0b8',
                    backgroundColor: 'rgba(32, 240, 184, 0.08)',
                    pointBackgroundColor: '#20f0b8',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: obterOpcoes('#20f0b8')
        });
    }

    // ── 3. GRÁFICO DIA (Barras Laranja) ──────────────────────────
    const ctxDia = document.getElementById('chartDia');
    if(ctxDia) {
        new Chart(ctxDia, {
            type: 'bar',
            data: {
                labels: dDia.labels,
                datasets: [{
                    data: dDia.data,
                    backgroundColor: 'rgba(240, 96, 32, 0.35)',
                    borderColor: '#f06020',
                    borderWidth: 1,
                    borderRadius: 3,
                    hoverBackgroundColor: '#f06020',
                }]
            },
            options: obterOpcoes('#f06020')
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>