<?php
// View: Contact
// Renderizada por PublicController::contact()
SessionManager::start();

$pontosContato = isset($pontosContato) && is_array($pontosContato) ? $pontosContato : [];
$totalPontosContato = isset($totalPontosContato) ? (int)$totalPontosContato : count($pontosContato);
$totalCidadesContato = isset($totalCidadesContato) ? (int)$totalCidadesContato : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - ConectaSolidária</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="contact-page">
        <header class="contact-hero">
            <p class="contact-eyebrow">Atendimento ConectaSolidária</p>
            <h1>Contato e Localização</h1>
            <p class="contact-subtitle">
                Fale com nossa equipe e veja no mapa os pontos oficiais para entrega de doações.
            </p>

            <div class="contact-stats">
                <div class="contact-stat">
                    <strong><?php echo (int)$totalPontosContato; ?></strong>
                    <span>pontos oficiais</span>
                </div>
                <div class="contact-stat">
                    <strong><?php echo (int)$totalCidadesContato; ?></strong>
                    <span>cidades com cobertura</span>
                </div>
            </div>
        </header>

        <section class="contact-channel-grid" aria-label="Canais de contato">
            <article class="contact-channel-card">
                <div class="contact-channel-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="contact-channel-body">
                    <h2>WhatsApp</h2>
                    <p>Atendimento rápido para dúvidas sobre doação e entrega.</p>
                    <a href="https://wa.me/5561986810428" target="_blank" rel="noopener noreferrer">
                        (61) 98681-0428
                    </a>
                </div>
            </article>

            <article class="contact-channel-card">
                <div class="contact-channel-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-channel-body">
                    <h2>E-mail</h2>
                    <p>Envie solicitações, parcerias e comunicações institucionais.</p>
                    <a href="mailto:ConectaSolidaria@gmail.com">ConectaSolidaria@gmail.com</a>
                    <a class="contact-instagram-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
                    </a>
                </div>
            </article>

            <article class="contact-channel-card">
                <div class="contact-channel-icon">
                    <i class="fab fa-instagram"></i>
                </div>
                <div class="contact-channel-body">
                    <h2>Instagram</h2>
                    <p>Acompanhe nossas campanhas, ações e novidades.</p>
                    <a href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer">
                        @conecta_solidaria
                    </a>
                </div>
            </article>

            <article class="contact-channel-card">
                <div class="contact-channel-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="contact-channel-body">
                    <h2>Horário de atendimento</h2>
                    <p>Atendimento presencial nos pontos oficiais.</p>
                    <strong>Segunda a sábado, das 8h às 22h</strong>
                </div>
            </article>
        </section>

        <?php if (empty($pontosContato)): ?>
            <div class="contact-empty">
                <p><i class="fas fa-info-circle"></i> Nenhum ponto oficial foi encontrado no momento.</p>
                <a class="contact-empty-action" href="index.php?page=collection_points">Ver página de pontos</a>
            </div>
        <?php else: ?>
            <section class="contact-point-grid" aria-label="Pontos com mapa">
                <?php foreach ($pontosContato as $ponto): ?>
                    <?php
                        $nome = (string)($ponto['nome'] ?? '');
                        $endereco = trim((string)($ponto['endereco'] ?? ''));
                        $cidade = (string)($ponto['cidade'] ?? '');
                        $estado = (string)($ponto['estado'] ?? '');
                        $cep = trim((string)($ponto['cep'] ?? ''));
                        $mapQuery = trim((string)($ponto['map_query'] ?? ''));
                        $mapSearchUrl = $mapQuery !== ''
                            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery)
                            : '';
                    ?>
                    <article class="contact-point-card">
                        <div class="contact-point-top">
                            <h3><?php echo htmlspecialchars($nome); ?></h3>
                            <?php if ($cidade !== '' || $estado !== ''): ?>
                                <span class="contact-point-badge">
                                    <?php echo htmlspecialchars(trim($cidade . ($estado !== '' ? '/' . $estado : ''))); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="contact-point-line">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($endereco); ?></span>
                        </div>

                        <?php if ($cep !== ''): ?>
                            <div class="contact-point-line">
                                <i class="fas fa-mail-bulk"></i>
                                <span>CEP: <?php echo htmlspecialchars($cep); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="contact-point-actions">
                            <?php if ($mapSearchUrl !== ''): ?>
                                <a
                                    class="contact-point-action is-primary"
                                    href="<?php echo htmlspecialchars($mapSearchUrl); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fas fa-route"></i>
                                    Abrir rota
                                </a>
                            <?php endif; ?>
                            <a class="contact-point-action" href="index.php?page=donation_create">
                                <i class="fas fa-hand-holding-heart"></i>
                                Entregar doação
                            </a>
                        </div>

                        <?php if ($mapQuery !== ''): ?>
                            <div class="contact-map-wrap">
                                <iframe
                                    title="Mapa de <?php echo htmlspecialchars($nome); ?>"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps?q=<?php echo urlencode($mapQuery); ?>&output=embed">
                                </iframe>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; 2026 ConectaSolidária</p>
    <a class="footer-social-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer" aria-label="Instagram do ConectaSolidária">
        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
    </a>
</footer>

</body>
</html>
