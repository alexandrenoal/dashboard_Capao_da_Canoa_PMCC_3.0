<?php
$hostname = "localhost";
$usuario  = "root";
$senha    = "";
$bancodedados = "ti";

// Criando a conexão
$mysqli = new mysqli($hostname, $usuario, $senha, $bancodedados);

// Verificando se houve erro
if ($mysqli->connect_errno) {
    echo "Falha ao conectar: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
} else {
    //echo "Conectado com sucesso!";
}
?>