<?php
// ===== CONFIG =====
$SENHA = "";  // <-- mude se necessário!
$DB_HOST = "localhost";
$DB_NAME = "ti";          
$DB_USER = "root";            
$DB_PASS = "";                
// ==================

// Proteção por senha (via URL ou formulário)
$senhaInformada = $_GET['senha'] ?? $_POST['senha'] ?? '';
if ($senhaInformada !== $SENHA) {
    http_response_code(403);
    die('Acesso negado. Use ?senha=SUA_SENHA na URL.');
}

$mensagem_resultado = "";

// Processamento da Importação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['sql']['tmp_name'])) {
    try {
        $pdo = new PDO(
            "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
            $DB_USER, $DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $sql = file_get_contents($_FILES['sql']['tmp_name']);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        $mensagem_resultado = "<div style='background: rgba(16,185,129,0.1); border: 1px solid #10b981; color: #10b981; padding: 16px; border-radius: 6px; font-family: \"JetBrains Mono\", monospace; font-size: 13px; margin-bottom: 20px;'>✅ Banco de dados importado com sucesso!</div>";
    } catch (Exception $e) {
        $mensagem_resultado = "<div style='background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 16px; border-radius: 6px; font-family: \"JetBrains Mono\", monospace; font-size: 13px; margin-bottom: 20px;'>❌ Erro na importação: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$page_title = "Importar SQL · TI";
include 'includes/header.php'; 
?>

<div class="topbar">
    <div class="topbar-left">
        <div class="page-eyebrow">// administração do sistema</div>
        <div class="page-title">Sincronizar Banco</div>
    </div>
</div>

<div style="max-width: 600px; margin-top: 20px;">
    
    <?= $mensagem_resultado ?>

    <div class="card">
        <div class="card-head" style="margin-bottom: 20px;">
            <span class="card-title">Upload de Arquivo de Estrutura (.sql)</span>
            <span class="card-sub">O envio desativará temporariamente as restrições de chaves estrangeiras.</span>
        </div>

        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="senha" value="<?= htmlspecialchars($senhaInformada) ?>">
            
            <div style="border: 2px dashed var(--border2); padding: 30px; text-align: center; border-radius: 8px; background: var(--p1); transition: border-color 0.2s;">
                <span style="font-size: 32px; display: block; margin-bottom: 10px;">💾</span>
                <input 
                    type="file" 
                    name="sql" 
                    accept=".sql" 
                    required 
                    style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--muted2);"
                >
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-apply" style="padding: 10px 24px; font-size: 12px; cursor: pointer;">
                    Iniciar Importação
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>