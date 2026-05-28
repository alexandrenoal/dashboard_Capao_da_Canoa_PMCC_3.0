<?php
// includes/header.php
require_once "conexao.php";
date_default_timezone_set('America/Sao_Paulo');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Sistema · TI' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/styledashboard.css">
    </head>
<body>
<div class="shell">

    <?php include "includes/sidebar.php"; ?>

    <main class="main">