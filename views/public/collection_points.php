<?php
// View: Collection Points
// Renderizada por PublicController::collectionPoints()
SessionManager::start();

$cidadeSelecionada = isset($cidadeSelecionada) ? trim((string)$cidadeSelecionada) : '';
$cidadesDisponiveis = isset($cidadesDisponiveis) && is_array($cidadesDisponiveis) ? $cidadesDisponiveis : [];
$totalPontos = isset($totalPontos) ? (int)$totalPontos : count($pontos ?? []);
$totalCidades = isset($totalCidades) ? (int)$totalCidades : count($cidadesDisponiveis);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pontos de Coleta - ConectaSolidária</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="collection-page">
        <header class="collection-hero">
            <p class="collection-eyebrow">Rede oficial de recebimento</p>
            <h1>Pontos de Coleta</h1>
            <p class="collection-subtitle">
                Encontre o local mais próximo para entregar sua doação com segurança e rastreabilidade.
            </p>

            <div class="collection-stats">
                <div class="collection-stat">
                    <strong><?php echo (int)$totalPontos; ?></strong>
                    <span>pontos ativos</span>
                </div>
                <div class="collection-stat">
                    <strong><?php echo (int)$totalCidades; ?></strong>
                    <span>cidades atendidas</span>
                </div>
            </div>

            <div class="collection-hero-actions">
                <a class="btn-primary" href="index.php?page=donation_create">
                    <i class="fas fa-hand-holding-heart"></i>
                    Fazer uma doação
                </a>
                <a class="collection-link" href="index.php?page=contact">
                    Ver contatos da rede
                </a>
            </div>
        </header>

        <?php if (!empty($cidadesDisponiveis)): ?>
            <nav class="collection-filters" aria-label="Filtro por cidade">
                <?php $allActive = ($cidadeSelecionada === ''); ?>
                <a
                    class="collection-chip <?php echo $allActive ? 'is-active' : ''; ?>"
                    href="index.php?page=collection_points">
                    Todas as cidades
                </a>

                <?php foreach ($cidadesDisponiveis as $cidade): ?>
                    <?php
                        $isActive = mb_strtolower($cidadeSelecionada) === mb_strtolower((string)$cidade);
                        $cidadeLabel = (string)$cidade;
                    ?>
                    <a
                        class="collection-chip <?php echo $isActive ? 'is-active' : ''; ?>"
                        href="index.php?page=collection_points&cidade=<?php echo urlencode($cidadeLabel); ?>">
                        <?php echo htmlspecialchars($cidadeLabel); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if (empty($pontos)): ?>
            <div class="collection-empty">
                <p><i class="fas fa-info-circle"></i> Nenhum ponto de coleta encontrado para esse filtro.</p>
                <?php if ($cidadeSelecionada !== ''): ?>
                    <a class="collection-empty-action" href="index.php?page=collection_points">Limpar filtro</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="collection-grid">
                <?php foreach ($pontos as $ponto): ?>
                    <?php
                        $nome = (string)($ponto['nome'] ?? '');
                        $cidade = (string)($ponto['cidade'] ?? '');
                        $estado = (string)($ponto['estado'] ?? '');
                        $telefone = trim((string)($ponto['telefone'] ?? ''));
                        $mapQuery = trim((string)($ponto['map_query'] ?? ''));
                        $mapSearchUrl = $mapQuery !== ''
                            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery)
                            : '';
                        $endereco = trim((string)($ponto['endereco'] ?? ''));
                        $cep = trim((string)($ponto['cep'] ?? ''));
                    ?>
                    <article class="collection-card">
                        <div class="collection-card-top">
                            <h2><?php echo htmlspecialchars($nome); ?></h2>
                            <?php if ($cidade !== '' || $estado !== ''): ?>
                                <span class="collection-city-badge">
                                    <?php echo htmlspecialchars(trim($cidade . ($estado !== '' ? '/' . $estado : ''))); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <ul class="collection-info-list">
                            <?php if ($endereco !== ''): ?>
                                <li class="collection-info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($endereco); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if ($cep !== ''): ?>
                                <li class="collection-info-item">
                                    <i class="fas fa-mail-bulk"></i>
                                    <span>CEP: <?php echo htmlspecialchars($cep); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if ($telefone !== ''): ?>
                                <li class="collection-info-item">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?php echo htmlspecialchars($telefone); ?>">
                                        <?php echo htmlspecialchars($telefone); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="collection-actions">
                            <?php if ($mapSearchUrl !== ''): ?>
                                <a
                                    class="collection-action is-primary"
                                    href="<?php echo htmlspecialchars($mapSearchUrl); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fas fa-route"></i>
                                    Ver rota
                                </a>
                            <?php endif; ?>

                            <a class="collection-action" href="index.php?page=donation_create">
                                <i class="fas fa-box-open"></i>
                                Entregar doação
                            </a>
                        </div>

                        <?php if ($mapQuery !== ''): ?>
                            <div class="collection-map-wrap">
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
            </div>
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
