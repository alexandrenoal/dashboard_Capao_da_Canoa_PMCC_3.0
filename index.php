teste

<a href="dashborad.php">dashborad</a>

<?php
include("conexao.php");

// Exemplo de uma consulta simples
$sql = "SELECT * FROM usuarios";
$query = $mysqli->query($sql);

while($dados = $query->fetch_assoc()){
    echo $dados['nome'] . "<br>";
}
?>