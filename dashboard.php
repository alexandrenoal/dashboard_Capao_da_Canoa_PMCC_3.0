<?php
require_once "conexao.php";

$ano_padrão = $mysqli->query("SELECT MAX(Ods_ano) FROM ordemservic")->fetch_row()[0];
$ano_sel = $_GET['ano'] ?? $ano_padrão;
$mes_sel = $_GET['mes'] ?? date('m');

// ── KPIs ─────────────────────────────────────────────────
$tot_geral = $mysqli->query("SELECT COUNT(*) FROM ordemservic")->fetch_row()[0];
$tot_ano   = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_ano='$ano_sel'")->fetch_row()[0];
$r = $mysqli->prepare("SELECT COUNT(*) FROM ordemservic WHERE Ods_ano=? AND MONTH(Ods_entrada)=?");
$r->bind_param('ss',$ano_sel,$mes_sel); $r->execute();
$tot_mes = $r->get_result()->fetch_row()[0];

$r2 = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_solucao IS NULL");
$tot_aberto = $r2->fetch_row()[0];

// ── Queries de Gráficos ──────────────────────────────────
$por_ano = $mysqli->query("SELECT Ods_ano AS label, COUNT(*) AS total FROM ordemservic WHERE Ods_ano IS NOT NULL GROUP BY Ods_ano ORDER BY Ods_ano ASC")->fetch_all(MYSQLI_ASSOC);

$s = $mysqli->prepare("SELECT DATE_FORMAT(Ods_entrada,'%m') AS mes_num, COUNT(*) AS total FROM ordemservic WHERE Ods_ano=? AND Ods_entrada IS NOT NULL GROUP BY mes_num ORDER BY mes_num ASC");
$s->bind_param('s',$ano_sel); $s->execute();
$por_mes_raw = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$meses_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$meses_full = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$por_mes = [];
foreach($por_mes_raw as $m) { $m['label']=$meses_pt[(int)$m['mes_num']-1]; $por_mes[]=$m; }

$s2=$mysqli->prepare("SELECT DAY(Ods_entrada) AS label, COUNT(*) AS total FROM ordemservic WHERE Ods_ano=? AND MONTH(Ods_entrada)=? AND Ods_entrada IS NOT NULL GROUP BY DAY(Ods_entrada) ORDER BY DAY(Ods_entrada) ASC");
$s2->bind_param('ss',$ano_sel,$mes_sel); $s2->execute();
$por_dia = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

$por_sit = $mysqli->query("SELECT Ods_situacao AS label, COUNT(*) AS total FROM ordemservic WHERE Ods_situacao IS NOT NULL GROUP BY Ods_situacao ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);
$top_sec = $mysqli->query("SELECT s.Sec_descricao AS label, COUNT(*) AS total FROM ordemservic o LEFT JOIN secretaria s ON s.Sec_codigo=o.Ods_secretaria GROUP BY o.Ods_secretaria, s.Sec_descricao ORDER BY total DESC LIMIT 13")->fetch_all(MYSQLI_ASSOC);
$top_tec = $mysqli->query("SELECT t.Tec_nome AS label, COUNT(*) AS total FROM ordemservic o LEFT JOIN tecnico t ON t.Tec_codigo=o.Ods_tecnico GROUP BY o.Ods_tecnico, t.Tec_nome ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);

$ultimas = $mysqli->query("
    SELECT o.Ods_ano, o.Ods_nro, o.Ods_entrada, o.Ods_patrimonio, o.Ods_problema, o.Ods_situacao, t.Tec_nome, s.Sec_descricao
    FROM ordemservic o
    LEFT JOIN tecnico t ON t.Tec_codigo=o.Ods_tecnico
    LEFT JOIN secretaria s ON s.Sec_codigo=o.Ods_secretaria
    ORDER BY o.Ods_ano DESC, o.Ods_nro DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$anos_disp = $mysqli->query("SELECT DISTINCT Ods_ano FROM ordemservic WHERE Ods_ano IS NOT NULL ORDER BY Ods_ano DESC")->fetch_all(MYSQLI_ASSOC);

function fmt_date($d){ if(!$d) return '—'; $ts=strtotime($d); return $ts?date('d/m/Y',$ts):$d; }
function sit_color($s){
    $s=strtoupper(trim($s??''));
    if(str_contains($s,'ATIVO')) return '#00e5a0';
    if(str_contains($s,'ENCERR')) return '#4a5270';
    if(str_contains($s,'PEND')) return '#f59e0b';
    return '#7c8499';
}

$page_title = "Dashboard · TI";
include 'includes/header.php'; 
?>

<div class="topbar">
    <div class="topbar-left">
        <div class="page-eyebrow">// visão geral</div>
        <div class="page-title">Dashboard</div>
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
        <button type="submit" class="btn-apply">Aplicar</button>
    </form>
</div>

<div class="kpi-row">
    <div class="kpi">
        <a href="ordens.php" style="text-decoration:none; color:inherit;">
            <div class="kpi-glow" style="background:var(--g)"></div>
            <div class="kpi-icon">📋</div>
            <div class="kpi-val" style="color:var(--g)"><?=$tot_geral?> </div>
            <div class="kpi-label">Total de OS</div>
            <div class="kpi-delta">todos os anos</div>
        </a>
    </div>
    <div class="kpi">
        <a href="ordens.php?ano=<?=$ano_sel?>" style="text-decoration:none; color:inherit;">
            <div class="kpi-glow" style="background:var(--g2)"></div>
            <div class="kpi-icon">📅</div>
            <div class="kpi-val" style="color:var(--g2)"><?=$tot_ano?></div>
            <div class="kpi-label">OS em <?=$ano_sel?></div>            
            <div class="kpi-delta">ano selecionado</div>
        </a>
    </div>
    <div class="kpi">
        <a href="ordens.php?mes=<?=$mes_sel?>&ano=<?=$ano_sel?>" style="text-decoration:none; color:inherit;">
            <div class="kpi-glow" style="background:var(--g3)"></div>
            <div class="kpi-icon">🗓️</div>
            <div class="kpi-val" style="color:var(--g3)"><?=$tot_mes?></div>
            <div class="kpi-label"><?=$meses_full[(int)$mes_sel]?></div>
            <div class="kpi-delta"><?=$ano_sel?></div>
        </a>
    </div>
    <div class="kpi">
        <a href="ordens.php?status=aberto" style="text-decoration:none; color:inherit;">
            <div class="kpi-glow" style="background:var(--g4)"></div>
            <div class="kpi-icon">⚡</div>
            <div class="kpi-val" style="color:var(--g4)"><?=$tot_aberto?></div>
            <div class="kpi-label">Em aberto</div>
            <div class="kpi-delta">não encerradas</div>
        </a>
    </div>
</div>

<div class="grid-main">
    <div class="card span2">
        <div class="card-head">
            <span class="card-title">OS por Mês</span>
            <span class="card-sub"><?=$ano_sel?></span>
        </div>
        <div class="chart-wrap"><canvas id="chartMes" height="50"></canvas></div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">Por Situação</span>
            <span class="card-sub">geral</span>
        </div>
        <div class="donut-wrap">
            <canvas id="chartSit" width="150" height="150" class="donut-canvas"></canvas>
            <div class="donut-legend" id="sitLegend"></div>
        </div>
    </div>
</div>

<div class="grid-bottom">
    <div class="card">
        <div class="card-head">
            <span class="card-title">Top Clientes</span>
            <span class="card-sub">por nº de OS</span>
        </div>
        <div class="bar-list">
        <?php
        $max_sec = $top_sec ? max(array_column($top_sec,'total')) : 1;
        foreach($top_sec as $sec):
            $pct = round($sec['total']/$max_sec*100);
        ?>
        <div class="bar-item">
            <div class="bar-item-top">
                <span class="bar-item-label"><?=htmlspecialchars($sec['label']??'—')?></span>
                <span class="bar-item-val"><?=$sec['total']?></span>
            </div>
            <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%;background:linear-gradient(90deg,var(--g2),var(--g))"></div></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">Últimas OS</span>
            <a href="ordens.php" class="nav-link">ver todas →</a>
        </div>
        <div style="overflow-x:auto">
        <table class="os-table">
            <thead>
                <tr>
                    <th>OS</th>
                    <th>Data</th>
                    <th>Patrimônio</th>
                    <th>Secretaria</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($ultimas as $i=>$o): $c = sit_color($o['Ods_situacao']); ?>
            <tr onclick="abrirModal(<?=$i?>)">
                <td class="td-os"><?=$o['Ods_ano']?>-<?=str_pad($o['Ods_nro'],4,'0',STR_PAD_LEFT)?></td>
                <td class="td-date"><?=fmt_date($o['Ods_entrada'])?></td>
                <td class="td-pat"><?=htmlspecialchars($o['Ods_patrimonio']??'—')?></td>
                <td class="td-sec"><?=htmlspecialchars($o['Sec_descricao']??'—')?></td>
                <td><span class="badge" style="color:<?=$c?>;background:<?=$c?>18"><?=htmlspecialchars($o['Ods_situacao']??'—')?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">OS por Ano</span>
            <span class="card-sub">histórico</span>
        </div>
        <div class="chart-wrap"><canvas id="chartAno" height="160"></canvas></div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">OS por Dia</span>
            <span class="card-sub"><?=$meses_full[(int)$mes_sel]?> / <?=$ano_sel?></span>
        </div>
        <div class="chart-wrap"><canvas id="chartDia" height="160"></canvas></div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="modal">
        <div class="modal-hdr">
            <div class="modal-title">OS <span id="m-nro"></span></div>
            <button class="modal-x" onclick="document.getElementById('modalOverlay').classList.remove('open')">✕</button>
        </div>
        <div class="m-grid">
            <div class="m-field"><label>Situação</label><value id="m-sit"></value></div>
            <div class="m-field"><label>Data Entrada</label><value id="m-entrada"></value></div>
            <div class="m-field"><label>Patrimônio</label><value id="m-pat"></value></div>
            <div class="m-field"><label>Técnico</label><value id="m-tec"></value></div>
            <div class="m-field"><label>Secretaria</label><value id="m-sec"></value></div>
        </div>
        <div class="m-block">
            <div class="m-block-title">Problema</div>
            <div class="m-text" id="m-prob"></div>
        </div>
    </div>
</div>

<script>
const dAno  = { labels:<?=json_encode(array_column($por_ano,'label'))?>, data:<?=json_encode(array_map('intval',array_column($por_ano,'total')))?> };
const dMes  = { labels:<?=json_encode(array_column($por_mes,'label'))?>, data:<?=json_encode(array_map('intval',array_column($por_mes,'total')))?> };
const dDia  = { labels:<?=json_encode(array_map(fn($d)=>''.$d['label'], $por_dia))?>, data:<?=json_encode(array_map('intval',array_column($por_dia,'total')))?> };
const dSit  = { labels:<?=json_encode(array_column($por_sit,'label'))?>, data:<?=json_encode(array_map('intval',array_column($por_sit,'total')))?> };
const ultimas = <?=json_encode($ultimas,JSON_UNESCAPED_UNICODE)?>;

Chart.defaults.color='#4a526a';
Chart.defaults.font.family="'JetBrains Mono',monospace";
Chart.defaults.font.size=10;
const grid='rgba(29,34,53,.9)';

const tooltip={
    backgroundColor:'#0e1018',borderColor:'#1d2235',borderWidth:1,
    titleColor:'#e2e8f8',bodyColor:'#00d4ff',padding:10,
    callbacks:{label:c=>`  ${c.parsed.y??c.parsed} OS`}
};

const ctxMes=document.getElementById('chartMes').getContext('2d');
const gradMes=ctxMes.createLinearGradient(0,0,0,200);
gradMes.addColorStop(0,'rgba(0,212,255,.25)');gradMes.addColorStop(1,'rgba(0,212,255,0)');
new Chart(ctxMes,{type:'line',data:{labels:dMes.labels,datasets:[{data:dMes.data,borderColor:'#00d4ff',backgroundColor:gradMes,pointBackgroundColor:'#00d4ff',pointRadius:4,pointHoverRadius:6,tension:.35,fill:true}]},options:{responsive:true,plugins:{legend:{display:false},tooltip},scales:{x:{grid:{color:grid}},y:{grid:{color:grid},beginAtZero:true}}}});

const maxAno=Math.max(...dAno.data);
new Chart(document.getElementById('chartAno'),{type:'bar',data:{labels:dAno.labels,datasets:[{data:dAno.data,backgroundColor:dAno.data.map(v=>v===maxAno?'#7c3aed':'rgba(124,58,237,.2)'),borderColor:'#7c3aed',borderWidth:1,borderRadius:4,hoverBackgroundColor:'#7c3aed'}]},options:{responsive:true,plugins:{legend:{display:false},tooltip},scales:{x:{grid:{color:grid}},y:{grid:{color:grid},beginAtZero:true}}}});

new Chart(document.getElementById('chartDia'),{type:'bar',data:{labels:dDia.labels,datasets:[{data:dDia.data,backgroundColor:'rgba(16,185,129,.25)',borderColor:'#10b981',borderWidth:1,borderRadius:3,hoverBackgroundColor:'#10b981'}]},options:{responsive:true,plugins:{legend:{display:false},tooltip},scales:{x:{grid:{color:grid},ticks:{maxRotation:0}},y:{grid:{color:grid},beginAtZero:true}}}});

const sitColors=['#00d4ff','#10b981','#f59e0b','#ef4444','#7c3aed','#6b7492'];
new Chart(document.getElementById('chartSit'),{type:'doughnut',data:{labels:dSit.labels,datasets:[{data:dSit.data,backgroundColor:sitColors.slice(0,dSit.data.length),borderWidth:0,hoverOffset:4}]},options:{responsive:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>`  ${c.label}: ${c.parsed}`}}}}});

const leg=document.getElementById('sitLegend');
dSit.labels.forEach((l,i)=>{
    const tot=dSit.data.reduce((a,b)=>a+b,0);
    leg.innerHTML+=`<div class="legend-item"><div class="legend-dot" style="background:${sitColors[i]||'#666'}"></div><span class="legend-label">${l}</span><span class="legend-val">${dSit.data[i]}</span></div>`;
});

function fmt(d){if(!d)return'—';const m=d.match(/(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+'/'+m[2]+'/'+m[1]:d;}
function abrirModal(i){
    const o=ultimas[i];
    const nro=o.Ods_ano+'-'+String(o.Ods_nro).padStart(4,'0');
    document.getElementById('m-nro').textContent=nro;
    document.getElementById('m-sit').innerHTML=`<span style="color:#00d4ff">${o.Ods_situacao||'—'}</span>`;
    document.getElementById('m-entrada').textContent=fmt(o.Ods_entrada);
    document.getElementById('m-pat').textContent=o.Ods_patrimonio||'—';
    document.getElementById('m-tec').textContent=o.Tec_nome||'—';
    document.getElementById('m-sec').textContent=o.Sec_descricao||'—';
    document.getElementById('m-prob').textContent=o.Ods_problema||'—';
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal(e){if(e.target===document.getElementById('modalOverlay'))document.getElementById('modalOverlay').classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('modalOverlay').classList.remove('open');});
</script>

<?php include 'includes/footer.php'; ?>