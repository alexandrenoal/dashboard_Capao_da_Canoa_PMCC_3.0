<?php
require_once "conexao.php";

$result = $mysqli->query("SELECT Sec_codigo, Sec_descricao FROM secretaria ORDER BY Sec_descricao");
$secretarias = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $secretarias[] = $row;
    }
}
$total = count($secretarias);

$page_title = "Secretarias · TI";
include 'includes/header.php'; 
?>

<div class="topbar">
    <div class="topbar-left">
        <div class="page-eyebrow">// sistema de gestão</div>
        <div class="page-title">Secretarias</div>
    </div>
    <div class="search-wrap">
        <input
            type="text"
            class="topbar-filters"
            style="background:var(--p1); border:1px solid var(--border); border-radius:7px; padding:10px 14px; color:var(--text); font-family:'JetBrains Mono',monospace; font-size:12px; outline:none; min-width:280px;"
            id="busca"
            placeholder="Filtrar secretarias..."
            onkeyup="filtrar()"
            autocomplete="off"
        >
    </div>
</div>

<div class="kpi-row" style="grid-template-columns: repeat(2, 1fr); max-width: 500px;">
    <div class="kpi">
        <div class="kpi-glow" style="background:var(--g3)"></div>
        <div class="kpi-icon">🗂️</div>
        <div class="kpi-val" style="color:var(--g3)"><?= $total ?></div>
        <div class="kpi-label">Total Cadastradas</div>
    </div>
</div>

<div class="card span3" style="margin-top: 20px;">
    <div class="card-head">
        <span class="card-title">Listagem de Setores</span>
        <span class="card-sub">ordem alfabética</span>
    </div>

    <div style="overflow-x:auto">
        <?php if ($total > 0): ?>
        <table class="os-table" id="tabela">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secretarias as $i => $s): ?>
                <tr>
                    <td class="td-date"><?= $i + 1 ?></td>
                    <td class="td-os"><?= htmlspecialchars($s['Sec_codigo']) ?></td>
                    <td class="td-pat" style="white-space: normal;"><?= htmlspecialchars($s['Sec_descricao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="noResults" style="display:none; padding: 20px; text-align: center; color: var(--muted);">Nenhuma secretaria encontrada.</div>
        <?php else: ?>
        <div style="text-align: center; padding: 40px 0; color: var(--muted);">
            <span style="font-size: 24px;">📋</span>
            <p style="margin-top: 10px;">Nenhuma secretaria cadastrada.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filtrar() {
    const termo = document.getElementById('busca').value.toLowerCase();
    const linhas = document.querySelectorAll('#tabela tbody tr');
    let visiveis = 0;

    linhas.forEach(tr => {
        const texto = tr.innerText.toLowerCase();
        const mostrar = texto.includes(termo);
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });

    document.getElementById('noResults').style.display = visiveis === 0 ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>