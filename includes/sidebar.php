<?php
// includes/sidebar.php
$script_atual = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="logo">
        <div class="logo-mark">CPD · ti</div> 
        <div class="logo-name">Capão da canoa</div>        
    </div>
    
    <div class="nav-section">
        <div class="nav-label">Menu</div>
        <a href="dashboard.php" class="nav-item <?= $script_atual == 'dashboard.php' ? 'active' : '' ?>"><span class="nav-dot"></span> Dashboard</a>
        <a href="ordens.php" class="nav-item <?= $script_atual == 'ordens.php' ? 'active' : '' ?>"><span class="nav-dot"></span> Ordens de Serviço</a>        
        <a href="secretarias.php" class="nav-item <?= $script_atual == 'secretarias.php' ? 'active' : '' ?>"><span class="nav-dot"></span> Setores</a>
        <a href="importarsql.php" class="nav-item <?= $script_atual == 'importarsql.php' ? 'active' : '' ?>"><span class="nav-dot"></span> Importar SQL</a>
    </div>

    <div class="sidebar-footer">
        <?= date('d/m/Y H:i') ?><br>
        <span style="color:var(--g)">● online</span>
    </div>
</aside>