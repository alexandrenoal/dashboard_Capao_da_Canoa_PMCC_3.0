<?php
require_once "conexao.php";

// Filtros
$filtro_ano       = $_GET['ano']       ?? '';
$filtro_situacao  = $_GET['situacao']  ?? '';
$filtro_busca     = $_GET['busca']     ?? '';
$filtro_status    = $_GET['status']   ?? '';
$filtro_mes       = $_GET['mes']      ?? '';

$where = []; $params = []; $types = '';

if ($filtro_ano) { $where[] = "o.Ods_ano = ?"; $params[] = $filtro_ano; $types .= 's'; }
if ($filtro_mes) { $where[] = "MONTH(o.Ods_entrada) = ?"; $params[] = (int)$filtro_mes; $types .= 'i'; }
if ($filtro_situacao) { $where[] = "o.Ods_situacao = ?"; $params[] = $filtro_situacao; $types .= 's'; }
if ($filtro_status === 'aberto') { $where[] = "o.Ods_solucao IS NULL"; }
if ($filtro_busca) {
    $where[] = "(o.Ods_patrimonio LIKE ? OR o.Ods_problema LIKE ? OR s.Sec_descricao LIKE ? OR t.Tec_nome LIKE ?)";
    $like = "%$filtro_busca%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT o.Ods_ano, o.Ods_nro, o.Ods_atende, o.Ods_entrada, o.Ods_saida, o.Ods_patrimonio, o.Ods_lacre, o.Ods_problema, o.Ods_solucao, o.Ods_situacao, t.Tec_nome, s.Sec_descricao, p.Pre_prestadora
    FROM ordemservic o
    LEFT JOIN tecnico t ON t.Tec_codigo = o.Ods_tecnico
    LEFT JOIN secretaria s ON s.Sec_codigo = o.Ods_secretaria
    LEFT JOIN prestadora p ON p.Pre_codigo = o.Ods_prestadora
    $where_sql ORDER BY o.Ods_ano DESC, o.Ods_nro DESC
";

$stmt = $mysqli->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$ordens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = count($ordens);

$anos = $mysqli->query("SELECT DISTINCT Ods_ano FROM ordemservic ORDER BY Ods_ano DESC")->fetch_all(MYSQLI_ASSOC);
$situacoes = $mysqli->query("SELECT DISTINCT Ods_situacao FROM ordemservic WHERE Ods_situacao IS NOT NULL ORDER BY Ods_situacao")->fetch_all(MYSQLI_ASSOC);

function badge($sit) {
    $map = [
        'ATIVO'      => ['label' => 'ATIVO',      'color' => '#00e5a0', 'bg' => '#00e5a015'],
        'ENCERRADO'  => ['label' => 'ENCERRADO',  'color' => '#4a5270', 'bg' => '#4a527015'],
        'PENDENTE'   => ['label' => 'PENDENTE',   'color' => '#f59e0b', 'bg' => '#f59e0b15'],
        'CANCELADO'  => ['label' => 'CANCELADO',  'color' => '#ef4444', 'bg' => '#ef444415'],
    ];
    $s = strtoupper(trim($sit ?? ''));
    $found = null;
    foreach ($map as $key => $val) { if (str_contains($s, $key)) { $found = $val; break; } }
    if (!$found) $found = ['label' => $s ?: '—', 'color' => '#7c8499', 'bg' => '#7c849915'];
    return "<span class='badge' style='color:{$found['color']};background:{$found['bg']}'>{$found['label']}</span>";
}

function fmt_date($d) { if (!$d) return '—'; $ts = strtotime($d); return $ts ? date('d/m/Y', $ts) : $d; }

$page_title = "Ordens de Serviço · TI";
include 'includes/header.php'; 
?>

<div class="topbar" style="flex-direction: column; align-items: flex-start; gap: 16px;">
    <div>
        <div class="page-eyebrow">// gestão de ti</div>
        <div class="page-title">Ordens de Serviço</div>
    </div>
    
    <form method="GET" class="topbar-filters" style="width: 100%;">
        <input type="text" name="busca" placeholder="Buscar patrimônio, secretaria, técnico..." value="<?= htmlspecialchars($filtro_busca) ?>" style="background:var(--p1); border:1px solid var(--border); border-radius:7px; padding:8px 14px; color:var(--text); font-family:'JetBrains Mono',monospace; font-size:11px; outline:none; flex: 1; min-width: 200px;">
        <select name="ano">
            <option value="">Todos os anos</option>
            <?php foreach ($anos as $a): ?>
            <option value="<?= $a['Ods_ano'] ?>" <?= $filtro_ano == $a['Ods_ano'] ? 'selected' : '' ?>><?= $a['Ods_ano'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="situacao">
            <option value="">Todas as situações</option>
            <?php foreach ($situacoes as $sit): ?>
            <option value="<?= htmlspecialchars($sit['Ods_situacao']) ?>" <?= $filtro_situacao == $sit['Ods_situacao'] ? 'selected' : '' ?>><?= htmlspecialchars($sit['Ods_situacao']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-apply">Filtrar</button>
        <a href="ordens.php" class="btn-apply" style="background: var(--p3); color: var(--text); text-decoration: none; text-align: center; line-height: 1.4;">Limpar</a>
    </form>
</div>

<?php
$total_aberto = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_solucao IS NULL")->fetch_row()[0];
$total_encerrado = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_solucao IS NOT NULL")->fetch_row()[0];
?>
<div class="kpi-row">
    <div class="kpi">
        <div class="kpi-glow" style="background:var(--g)"></div>
        <div class="kpi-val" style="color:var(--g)"><?= $total ?></div>
        <div class="kpi-label">Filtradas</div>
    </div>
    <div class="kpi">
        <div class="kpi-glow" style="background:var(--g3)"></div>
        <div class="kpi-val" style="color:var(--g3)"><?= $total_aberto ?></div>
        <div class="kpi-label">Em Aberto</div>
    </div>
    <div class="kpi">
        <div class="kpi-glow" style="background:var(--muted)"></div>
        <div class="kpi-val" style="color:var(--muted2)"><?= $total_encerrado ?></div>
        <div class="kpi-label">Encerradas</div>
    </div>
</div>

<div class="card span3">
    <div style="overflow-x:auto">
        <?php if ($total > 0): ?>
        <table class="os-table">
            <thead>
                <tr>
                    <th>OS</th>
                    <th>Entrada</th>
                    <th>Patrimônio</th>
                    <th>Secretaria</th>
                    <th>Técnico</th>
                    <th>Problema</th>
                    <th>Situação</th>
                    <th>Saída</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ordens as $i => $o): ?>
                <tr onclick="abrirModal(<?= $i ?>)">
                    <td class="td-os"><?= $o['Ods_ano'] ?>-<?= str_pad($o['Ods_nro'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td class="td-date"><?= fmt_date($o['Ods_entrada']) ?></td>
                    <td class="td-pat"><?= htmlspecialchars($o['Ods_patrimonio'] ?? '—') ?></td>
                    <td class="td-sec"><?= htmlspecialchars($o['Sec_descricao'] ?? '—') ?></td>
                    <td class="td-tec" style="color: var(--muted2);"><?= htmlspecialchars($o['Tec_nome'] ?? '—') ?></td>
                    <td class="td-prob" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($o['Ods_problema'] ?? '—') ?></td>
                    <td><?= badge($o['Ods_situacao']) ?></td>
                    <td class="td-date"><?= fmt_date($o['Ods_saida']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--muted);">
            <span style="font-size: 24px;">📋</span>
            <p style="margin-top:10px;">Nenhuma ordem de serviço encontrada.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" onclick="fecharModal(event)">
    <div class="modal">
        <div class="modal-hdr">
            <div class="modal-title">OS <span id="m-nro"></span></div>
            <button class="modal-x" onclick="document.getElementById('modalOverlay').classList.remove('open')">✕</button>
        </div>
        <div class="m-grid">
            <div class="m-field"><label>Situação</label><value id="m-sit"></value></div>
            <div class="m-field"><label>Atendimento</label><value id="m-atende"></value></div>
            <div class="m-field"><label>Patrimônio</label><value id="m-pat"></value></div>
            <div class="m-field"><label>Lacre</label><value id="m-lacre"></value></div>
            <div class="m-field"><label>Secretaria</label><value id="m-sec"></value></div>
            <div class="m-field"><label>Técnico</label><value id="m-tec"></value></div>
            <div class="m-field"><label>Prestadora</label><value id="m-pre"></value></div>
            <div class="m-field"><label>Entrada / Saída</label><value id="m-datas"></value></div>
        </div>
        <div class="m-block">
            <div class="m-block-title">Problema</div>
            <div class="m-text" id="m-prob"></div>
        </div>
        <div class="m-block" id="solucao-wrap">
            <div class="m-block-title" style="color: var(--g3)">Solução</div>
            <div class="m-text" id="m-sol" style="border-color: rgba(16,185,129,0.3)"></div>
        </div>
    </div>
</div>

<script>
const ordens = <?= json_encode($ordens, JSON_UNESCAPED_UNICODE) ?>;

function fmt(d) {
    if (!d) return '—';
    const m = d.match(/(\d{4})-(\d{2})-(\d{2})/);
    return m ? m[3]+'/'+m[2]+'/'+m[1] : d;
}

function abrirModal(i) {
    const o = ordens[i];
    document.getElementById('m-nro').textContent = o.Ods_ano + '-' + String(o.Ods_nro).padStart(4,'0');
    document.getElementById('m-sit').innerHTML = `<span style="color:var(--g)">${o.Ods_situacao || '—'}</span>`;
    document.getElementById('m-atende').textContent = o.Ods_atende || '—';
    document.getElementById('m-pat').textContent = o.Ods_patrimonio || '—';
    document.getElementById('m-lacre').textContent = o.Ods_lacre || '—';
    document.getElementById('m-sec').textContent = o.Sec_descricao || '—';
    document.getElementById('m-tec').textContent = o.Tec_nome || '—';
    document.getElementById('m-pre').textContent = o.Pre_prestadora || '—';
    document.getElementById('m-datas').textContent = fmt(o.Ods_entrada) + ' → ' + fmt(o.Ods_saida);
    document.getElementById('m-prob').textContent = o.Ods_problema || '—';
    document.getElementById('m-sol').textContent = o.Ods_solucao || '—';
    document.getElementById('solucao-wrap').style.display = o.Ods_solucao ? 'block' : 'none';
    document.getElementById('modalOverlay').classList.add('open');
}

function fecharModal(e) { if (e.target === document.getElementById('modalOverlay')) document.getElementById('modalOverlay').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('modalOverlay').classList.remove('open'); });
</script>

<?php include 'includes/footer.php'; ?>