<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php
        $appBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $appBasePath = ($appBasePath === '/' || $appBasePath === '.') ? '' : rtrim($appBasePath, '/');

        $buildVersionedAssetUrl = function ($relativePath) use ($appBasePath) {
            $filePath = __DIR__ . '/../../' . $relativePath;
            $version = file_exists($filePath) ? filemtime($filePath) : time();
            $baseUrl = ($appBasePath !== '') ? $appBasePath . '/' : '/';

            return $baseUrl . $relativePath . '?v=' . $version;
        };

        $favicon32Url = $buildVersionedAssetUrl('assets/uploads/favicon-32x32.png');
        $favicon16Url = $buildVersionedAssetUrl('assets/uploads/favicon-16x16.png');
        $appleTouchIconUrl = $buildVersionedAssetUrl('assets/uploads/apple-touch-icon.png');
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? "ConectaSolidária"; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Bootstrap (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($favicon32Url); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($favicon16Url); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($appleTouchIconUrl); ?>">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($favicon32Url); ?>" type="image/png">
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
