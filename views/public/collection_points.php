<?php
// View: Collection Points
// Renderizada por PublicController::collectionPoints()
SessionManager::start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pontos de Coleta - ConectaSolidária</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .pontos-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .pontos-container > h2 {
            margin-bottom: 10px;
        }

        .pontos-container > p {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .pontos-grid {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
            scroll-behavior: smooth;
            margin-top: 30px;
            align-items: stretch;
            scrollbar-width: none;
        }

        .pontos-grid::-webkit-scrollbar {
            display: none;
        }

        .card-ponto {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: 0.3s;
            border-left: 4px solid #2563eb;
            min-height: 560px;
            min-width: 520px;
            flex: 0 0 520px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-ponto:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .card-ponto h3 {
            color: #111827;
            margin-bottom: 15px;
            margin-top: 0;
        }

        .info-ponto {
            margin-bottom: 10px;
            color: #374151;
            font-size: 15px;
        }

        .info-ponto i {
            color: #2563eb;
            margin-right: 8px;
            width: 20px;
        }

        .info-ponto a {
            color: #2563eb;
            text-decoration: none;
        }

        .info-ponto a:hover {
            text-decoration: underline;
        }

        .vazio {
            text-align: center;
            grid-column: 1 / -1;
            color: #6b7280;
            padding: 40px;
        }

        .map-wrap {
            margin-top: 16px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #dbeafe;
        }

        .map-wrap iframe {
            width: 100%;
            height: 320px;
            min-height: 320px;
            border: 0;
            display: block;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="pontos-container">
        <h2>Pontos de Coleta</h2>
        <p>Conheça os locais onde você pode fazer sua doação.</p>

        <?php if (empty($pontos)): ?>
            <div class="vazio">
                <p><i class="fas fa-info-circle"></i> Nenhum ponto de coleta disponível no momento.</p>
            </div>
        <?php else: ?>
            <div class="pontos-grid">
                <?php foreach ($pontos as $ponto): ?>
                    <div class="card-ponto">
                        <h3><?php echo htmlspecialchars($ponto['nome']); ?></h3>
                        
                        <div class="info-ponto">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($ponto['logradouro']); ?>, 
                            <?php echo htmlspecialchars($ponto['numero']); ?>
                        </div>

                        <?php if (!empty($ponto['complemento'])): ?>
                            <div class="info-ponto">
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($ponto['complemento']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="info-ponto">
                            <i class="fas fa-city"></i>
                            <?php echo htmlspecialchars($ponto['cidade']); ?>, 
                            <?php echo htmlspecialchars($ponto['estado']); ?> - 
                            <?php echo htmlspecialchars($ponto['cep']); ?>
                        </div>

                        <?php if (!empty($ponto['telefone'])): ?>
                            <div class="info-ponto">
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?php echo htmlspecialchars($ponto['telefone']); ?>">
                                    <?php echo htmlspecialchars($ponto['telefone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($ponto['map_query'])): ?>
                            <div class="map-wrap">
                                <iframe
                                    title="Mapa de <?php echo htmlspecialchars($ponto['nome']); ?>"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps?q=<?php echo urlencode($ponto['map_query']); ?>&output=embed">
                                </iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
