<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? "ConectaSolidária"; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Bootstrap (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Conteúdo Principal -->
    <main>
        <?php 
            // Exibe mensagens de feedback
            $msg = SessionManager::getMessage();
            if (!empty($msg['mensagem'])) {
        ?>
            <div class="mensagem <?php echo htmlspecialchars($msg['tipo']); ?>">
                <?php echo htmlspecialchars($msg['mensagem']); ?>
            </div>
        <?php 
            }
            
            // Renderiza conteúdo específico da view
            if (isset($content)) {
                echo $content;
            }
        ?>
    </main>

    <!-- Rodapé -->
    <footer>
        <p>© 2026 ConectaSolidária - Coordenação de Doações para Calamidades</p>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
