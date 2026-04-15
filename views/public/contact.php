<?php
// View: Contact
// Renderizada por PublicController::contact()
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
        .contact-container {
            max-width: 1200px;
            margin: 20px auto 40px;
        }

        .contact-header {
            margin-bottom: 24px;
        }

        .contact-header h2 {
            margin-bottom: 8px;
        }

        .contact-header p {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .contact-channels {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 14px 16px;
            margin: 14px 0 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .channel-item {
            color: #1f2937;
            font-size: 15px;
        }

        .channel-item i {
            color: #2563eb;
            margin-right: 6px;
        }

        .channel-item a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 700;
        }

        .channel-item a:hover {
            text-decoration: underline;
        }

        .points-grid {
            display: flex;
            gap: 24px;
            overflow-x: auto;
            padding-bottom: 16px;
            scroll-behavior: smooth;
            align-items: stretch;
            scrollbar-width: none;
            margin-top: 20px;
        }

        .points-grid::-webkit-scrollbar {
            display: none;
        }

        .point-card {
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            min-width: 520px;
            flex: 0 0 520px;
            min-height: 580px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .point-card h3 {
            margin-top: 0;
            margin-bottom: 16px;
            color: #111827;
            font-size: 24px;
        }

        .point-line {
            margin-bottom: 12px;
            color: #374151;
            font-size: 15px;
        }

        .point-line i {
            color: #2563eb;
            margin-right: 8px;
            width: 18px;
        }

        .map-wrap {
            margin-top: 18px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #dbeafe;
            min-height: 320px;
        }

        .map-wrap iframe {
            width: 100%;
            height: 340px;
            border: 0;
            display: block;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="contact-container">
        <div class="contact-header">
            <h2>Contato e localização</h2>
            <p>Confira os endereços oficiais e a localização no mapa.</p>
        </div>

        <div class="contact-channels">
            <div class="channel-item">
                <i class="fab fa-whatsapp"></i>
                WhatsApp:
                <a href="https://wa.me/5561986810428" target="_blank" rel="noopener noreferrer">(61) 98681-0428</a>
            </div>
            <div class="channel-item">
                <i class="fas fa-envelope"></i>
                E-mail:
                <a href="mailto:ConectaSolidaria@gmail.com">ConectaSolidária@gmail.com</a>
            </div>
            <div class="channel-item">
                <i class="fas fa-clock"></i>
                Atendimento presencial:
                <strong>segunda a sábado, das 8h às 22h</strong>
            </div>
        </div>

        <div class="points-grid">
            <?php foreach ($pontosContato as $ponto): ?>
                <article class="point-card">
                    <h3><?php echo htmlspecialchars($ponto['nome']); ?></h3>

                    <div class="point-line">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($ponto['endereco']); ?>
                    </div>

                    <div class="point-line">
                        <i class="fas fa-city"></i>
                        <?php echo htmlspecialchars($ponto['cidade']); ?> / <?php echo htmlspecialchars($ponto['estado']); ?>
                    </div>

                    <div class="point-line">
                        <i class="fas fa-envelope"></i>
                        CEP: <?php echo htmlspecialchars($ponto['cep']); ?>
                    </div>

                    <div class="map-wrap">
                        <iframe
                            title="Mapa de <?php echo htmlspecialchars($ponto['nome']); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q=<?php echo urlencode($ponto['map_query']); ?>&output=embed">
                        </iframe>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
