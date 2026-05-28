<?php
require_once "conexao.php";

$ano_sel = $_GET['ano'] ?? date('Y');
$mes_sel = $_GET['mes'] ?? date('m');

echo "<h2>Debug Dashboard</h2><pre>";

// 1. Total geral
$r = $mysqli->query("SELECT COUNT(*) FROM ordemservic");
echo "Total OS: " . $r->fetch_row()[0] . "\n\n";

// 2. Amostra de datas
echo "-- Amostra de Ods_entrada e Ods_ano:\n";
$r = $mysqli->query("SELECT Ods_ano, Ods_entrada, Ods_situacao FROM ordemservic LIMIT 10");
while($row = $r->fetch_assoc()) {
    echo "  ano={$row['Ods_ano']} | entrada={$row['Ods_entrada']} | sit={$row['Ods_situacao']}\n";
}

// 3. Quantas têm Ods_entrada NULL
$r = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_entrada IS NULL");
echo "\nOS com Ods_entrada NULL: " . $r->fetch_row()[0] . "\n";

$r = $mysqli->query("SELECT COUNT(*) FROM ordemservic WHERE Ods_entrada IS NOT NULL");
echo "OS com Ods_entrada preenchida: " . $r->fetch_row()[0] . "\n";

// 4. Anos distintos com entrada
echo "\n-- Anos distintos (Ods_ano):\n";
$r = $mysqli->query("SELECT DISTINCT Ods_ano, COUNT(*) as total FROM ordemservic GROUP BY Ods_ano ORDER BY Ods_ano DESC");
while($row = $r->fetch_assoc()) {
    echo "  {$row['Ods_ano']}: {$row['total']} OS\n";
}

// 5. Testar query por mês
echo "\n-- Query por mês (ano=$ano_sel):\n";
$s = $mysqli->prepare("SELECT DATE_FORMAT(Ods_entrada,'%m') AS mes_num, COUNT(*) AS total FROM ordemservic WHERE Ods_ano=? AND Ods_entrada IS NOT NULL GROUP BY mes_num ORDER BY mes_num ASC");
$s->bind_param('s', $ano_sel);
$s->execute();
$res = $s->get_result()->fetch_all(MYSQLI_ASSOC);
if($res) {
    foreach($res as $r) echo "  mes={$r['mes_num']} total={$r['total']}\n";
} else {
    echo "  NENHUM RESULTADO\n";
}

// 6. Testar query por dia
echo "\n-- Query por dia (ano=$ano_sel, mes=$mes_sel):\n";
$s2 = $mysqli->prepare("SELECT DAY(Ods_entrada) AS dia, COUNT(*) AS total FROM ordemservic WHERE Ods_ano=? AND MONTH(Ods_entrada)=? AND Ods_entrada IS NOT NULL GROUP BY DAY(Ods_entrada) ORDER BY DAY(Ods_entrada) ASC");
$s2->bind_param('ss', $ano_sel, $mes_sel);
$s2->execute();
$res2 = $s2->get_result()->fetch_all(MYSQLI_ASSOC);
if($res2) {
    foreach($res2 as $r) echo "  dia={$r['dia']} total={$r['total']}\n";
} else {
    echo "  NENHUM RESULTADO\n";
}

// 7. Verificar tipo da coluna
echo "\n-- Tipo da coluna Ods_entrada:\n";
$r = $mysqli->query("SHOW COLUMNS FROM ordemservic LIKE 'Ods_entrada'");
$col = $r->fetch_assoc();
echo "  Tipo: {$col['Type']} | Null: {$col['Null']} | Default: {$col['Default']}\n";

// 8. Amostra dos valores reais de Ods_entrada
echo "\n-- Valores distintos de Ods_entrada (top 10):\n";
$r = $mysqli->query("SELECT Ods_entrada FROM ordemservic WHERE Ods_entrada IS NOT NULL LIMIT 10");
while($row = $r->fetch_assoc()) {
    echo "  '{$row['Ods_entrada']}'\n";
}

echo "</pre>";
?>
