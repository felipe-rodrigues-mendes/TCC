<?php
SessionManager::requireRole('admin');
$totalCampanhas = is_array($campanhas ?? null) ? count($campanhas) : 0;
$campanhasAtivas = 0;
$campanhasEncerradas = 0;
foreach (($campanhas ?? []) as $campanhaResumo) {
    if (strtoupper((string)$campanhaResumo->status) === 'ENCERRADA') {
        $campanhasEncerradas++;
    } else {
        $campanhasAtivas++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanhas - Admin</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-campaign-main { width: 100%; max-width: none; margin: 0; padding: 24px 16px 36px; }
        .admin-container { width: 100%; max-width: none; margin: 0; }
        .admin-page-heading { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 28px; align-items: end; margin-bottom: 22px; }
        .admin-page-heading h1 { margin: 0 0 8px; font-size: 34px; letter-spacing: 0; }
        .admin-page-heading p { max-width: 680px; margin: 0; color: #64748b; line-height: 1.55; }
        .campaign-stats { display: grid; grid-template-columns: repeat(3, minmax(118px, 1fr)); gap: 10px; }
        .campaign-stat { min-width: 118px; padding: 12px 14px; border: 1px solid #dbe4f0; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06); }
        .campaign-stat span { display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .campaign-stat strong { display: block; margin-top: 3px; color: #0f172a; font-size: 24px; line-height: 1; }
        .campaign-gallery { margin-bottom: 22px; display: grid; gap: 12px; }
        .campaign-gallery-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; }
        .campaign-gallery-header h2 { margin: 0; font-size: 20px; color: #0f172a; }
        .campaign-gallery-header p { margin: 4px 0 0; color: #64748b; }
        .campaign-filter-tabs { display: inline-flex; padding: 4px; gap: 4px; border: 1px solid #dbe4f0; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06); }
        .campaign-filter-tabs button { border: 0; border-radius: 6px; padding: 9px 12px; background: transparent; color: #475569; font-weight: 800; cursor: pointer; }
        .campaign-filter-tabs button.is-active { background: #2563eb; color: #fff; }
        .campaign-gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .campaign-gallery-grid.is-filtering { grid-template-columns: repeat(auto-fit, minmax(260px, 320px)); }
        .campaign-preview-card.is-hidden { display: none; }
        .campaign-preview-card { min-height: 254px; border: 1px solid #dbe4f0; border-radius: 8px; overflow: hidden; background: #fff; color: inherit; text-decoration: none; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08); display: grid; grid-template-rows: 152px 1fr; transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease; }
        .campaign-preview-card:hover, .campaign-preview-card:focus-visible { transform: translateY(-2px); border-color: #93c5fd; box-shadow: 0 18px 42px rgba(37, 99, 235, 0.16); outline: none; }
        .campaign-preview-card.is-selected { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18), 0 18px 42px rgba(37, 99, 235, 0.14); }
        .campaign-preview-card.is-closed { opacity: 0.78; }
        .campaign-preview-media { position: relative; min-height: 152px; background: #e2e8f0; overflow: hidden; }
        .campaign-preview-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .campaign-preview-media::after { content: ""; position: absolute; inset: auto 0 0; height: 64%; background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, 0.72)); }
        .campaign-preview-status { position: absolute; left: 12px; bottom: 10px; z-index: 1; display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 800; background: rgba(22, 101, 52, 0.92); color: #fff; }
        .campaign-preview-status.is-closed { background: rgba(71, 85, 105, 0.94); }
        .campaign-preview-body { padding: 13px 14px 14px; display: grid; gap: 8px; align-content: start; }
        .campaign-preview-body strong { color: #0f172a; font-size: 15px; line-height: 1.25; }
        .campaign-preview-body span { color: #64748b; font-size: 13px; line-height: 1.35; }
        .selected-campaign-visual { min-height: 260px; max-height: 380px; border: 1px solid #dbe4f0; border-radius: 8px; overflow: hidden; position: relative; background: #e2e8f0; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08); }
        .selected-campaign-visual img { width: 100%; height: 100%; min-height: 260px; max-height: 380px; object-fit: cover; display: block; }
        .selected-campaign-visual::after { content: ""; position: absolute; inset: auto 0 0; height: 70%; background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, 0.76)); }
        .selected-campaign-caption { position: absolute; left: 16px; right: 16px; bottom: 14px; z-index: 1; display: flex; align-items: end; justify-content: space-between; gap: 12px; color: #fff; }
        .selected-campaign-caption strong { display: block; font-size: 22px; line-height: 1.1; }
        .selected-campaign-caption span { display: block; margin-top: 4px; color: rgba(255, 255, 255, 0.82); font-size: 13px; }
        .admin-grid { display: grid; gap: 24px; align-items: start; }
        .stacked-panels { display: grid; grid-template-columns: 1fr; gap: 30px; align-items: start; border-top: 1px solid rgba(148, 163, 184, 0.28); padding-top: 22px; }
        .panel-card { background: transparent; border: 0; border-radius: 0; box-shadow: none; padding: 0; min-height: 100%; display: grid; gap: 18px; align-content: start; }
        .create-campaign-card { position: static; max-width: none; }
        .create-campaign-card > .action-form {
            max-width: 980px;
            padding-top: 14px;
            border-top: 1px solid rgba(148, 163, 184, 0.24);
        }
        .create-campaign-card .need-row {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.24);
        }
        .campaign-manage-card { display: grid; grid-template-columns: 1fr; grid-template-areas: "header" "selector" "editor" "items"; gap: 18px; background: transparent; border: 0; box-shadow: none; padding: 0; }
        .campaign-manage-card > .panel-title,
        .campaign-manage-card > .selector-form,
        .campaign-manage-card .campaign-edit-stack,
        .campaign-manage-card .cards-side-panel {
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }
        .campaign-manage-card > .panel-title { grid-area: header; padding: 0 0 14px; border-bottom: 1px solid rgba(148, 163, 184, 0.24); }
        .campaign-manage-card > .selector-form { grid-area: selector; padding: 0 0 14px; margin: 0; border-bottom: 1px solid rgba(148, 163, 184, 0.24); }
        .campaign-manage-card > .selector-form > div { max-width: 680px; }
        .panel-title { display: flex; gap: 12px; align-items: flex-start; }
        .panel-title i { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #e0f2fe; color: #0369a1; }
        .panel-card h2, .panel-card h3 { margin: 0; color: #0f172a; }
        .panel-card > p:first-of-type, .panel-title p { margin: 4px 0 0; }
        .panel-card p { color: #475569; }
        .mensagem { margin-bottom: 20px; padding: 14px 16px; border-radius: 10px; font-weight: bold; }
        .mensagem.sucesso { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .mensagem.erro { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .selector-form, .action-form { display: grid; gap: 14px; }
        .selector-form { margin-bottom: 4px; }
        .selector-form select, .action-form select, .action-form input, .action-form textarea {
            width: 100%; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; background: #fff;
        }
        .action-form textarea { min-height: 110px; resize: vertical; }
        .action-form button, .selector-form button, .btn-secondary, .btn-danger {
            border: 0; border-radius: 8px; padding: 12px 16px; color: #fff; font-weight: 700; cursor: pointer;
        }
        .action-form button, .selector-form button { background: #2563eb; }
        .action-form button:hover, .selector-form button:hover { background: #1d4ed8; }
        .btn-secondary { background: #0f766e; }
        .btn-secondary:hover { background: #115e59; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .campaign-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .campaign-info.is-closed { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }
        .status-badge { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .status-badge.is-active { background: #dcfce7; color: #166534; }
        .status-badge.is-closed { background: #e2e8f0; color: #475569; }


        .manage-layout { display: contents; }
        .campaign-edit-stack { grid-area: editor; display: grid; gap: 16px; padding: 0; align-content: start; max-width: 980px; }
        .campaign-action-row { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 12px; }
        .campaign-action-row .action-form { margin: 0; }
        .cards-toggle { display: none; }
        .campaign-edit-stack > .action-form,
        .campaign-edit-stack > .campaign-action-row > .action-form {
            padding: 16px 0 0;
            border: 0;
            border-top: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 0;
            background: transparent;
        }
        .campaign-edit-stack > .action-form.form-section-box {
            padding: 18px;
            border: 1px solid rgba(59, 130, 246, 0.34);
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.08);
        }
        .campaign-manage-card .campaign-info { display: none; }
        .cards-side-panel { grid-area: items; display: block; padding: 22px 0 0; border-left: 0 !important; border-top: 1px solid rgba(148, 163, 184, 0.28) !important; position: static; max-height: none; overflow: visible; align-self: start; }
        .cards-side-panel h3 { margin-bottom: 12px; }
        .cards-side-panel > h3 { display: none; }
        .cards-side-panel-header { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 14px; }
        .cards-side-panel-header h3 { margin: 0; }
        .items-count { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 28px; padding: 0 9px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 800; }
        .cards-toggle summary { padding: 14px 16px; cursor: pointer; font-weight: 700; color: #0f172a; list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .cards-toggle summary::-webkit-details-marker { display: none; }
        .cards-toggle summary::after { content: '+'; font-size: 18px; color: #2563eb; }
        .cards-toggle[open] summary::after { content: '-'; }
        .cards-scroll {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 18px;
            padding: 0;
        }
        .need-card { border: 1px solid rgba(148, 163, 184, 0.22); border-radius: 8px; padding: 16px; background: rgba(15, 23, 42, 0.12); display: grid; gap: 10px; align-content: start; }
        .need-card strong { display: block; margin-bottom: 6px; color: #0f172a; }
        .need-card div { color: #475569; line-height: 1.5; }
        .need-card span { display: inline-block; font-size: 14px; font-weight: 700; color: #1d4ed8; }
        .need-card-edit { display: grid; gap: 10px; margin-top: 8px; }
        .need-card-edit textarea,
        .need-card-edit input {
            width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit;
        }
        .need-card-edit textarea { min-height: 90px; resize: vertical; }
        .need-card-edit button { border: 0; border-radius: 8px; padding: 10px 14px; color: #fff; font-weight: 700; cursor: pointer; background: #2563eb; }
        .need-card-edit button:hover { background: #1d4ed8; }
        .card-actions { margin-top: 14px; display: flex; justify-content: flex-end; }
        .muted { color: #64748b; font-size: 14px; }
        .empty-state { padding: 18px; border-radius: 8px; background: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1; }
        .need-builder { display: grid; gap: 12px; }
        .need-row { border: 1px solid #dbeafe; background: #f8fbff; border-radius: 8px; padding: 14px; display: grid; gap: 10px; }
        .need-row-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .need-row-title { font-size: 14px; font-weight: 700; color: #0f172a; }
        .btn-linkish { border: 0; background: transparent; color: #dc2626; font-weight: 700; cursor: pointer; padding: 0; }
        .form-section-box { display: grid; gap: 14px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .form-section-box h3 { font-size: 15px; }
        [data-theme="dark"] .panel-card,
        [data-theme="dark"] .need-card,
        [data-theme="dark"] .campaign-stat,
        [data-theme="dark"] .campaign-preview-card,
        [data-theme="dark"] .campaign-manage-card > .panel-title,
        [data-theme="dark"] .campaign-manage-card > .selector-form,
        [data-theme="dark"] .campaign-manage-card .campaign-edit-stack,
        [data-theme="dark"] .campaign-manage-card .cards-side-panel {
            background: var(--bg-surface);
            border-color: var(--border-strong);
        }
        [data-theme="dark"] .campaign-filter-tabs {
            background: var(--bg-surface);
            border-color: var(--border-strong);
        }
        [data-theme="dark"] .campaign-filter-tabs button {
            color: var(--texto-suave);
        }
        [data-theme="dark"] .panel-card h2,
        [data-theme="dark"] .panel-card h3,
        [data-theme="dark"] .need-card strong,
        [data-theme="dark"] .need-row-title,
        [data-theme="dark"] .campaign-stat strong,
        [data-theme="dark"] .campaign-gallery-header h2,
        [data-theme="dark"] .campaign-preview-body strong {
            color: var(--texto);
        }
        [data-theme="dark"] .panel-card p,
        [data-theme="dark"] .need-card div,
        [data-theme="dark"] .muted,
        [data-theme="dark"] .admin-page-heading p,
        [data-theme="dark"] .campaign-stat span,
        [data-theme="dark"] .campaign-gallery-header p,
        [data-theme="dark"] .campaign-preview-body span {
            color: var(--texto-suave);
        }
        [data-theme="dark"] .selector-form select,
        [data-theme="dark"] .action-form select,
        [data-theme="dark"] .action-form input,
        [data-theme="dark"] .action-form textarea,
        [data-theme="dark"] .need-card-edit input,
        [data-theme="dark"] .need-card-edit textarea {
            background: var(--bg-surface-soft);
            border-color: var(--border-strong);
            color: var(--texto);
        }
        [data-theme="dark"] .need-row,
        [data-theme="dark"] .cards-toggle,
        [data-theme="dark"] .cards-side-panel,
        [data-theme="dark"] .empty-state,
        [data-theme="dark"] .form-section-box {
            background: var(--bg-surface-soft);
            border-color: var(--border-strong);
        }
        [data-theme="dark"] .campaign-edit-stack > .action-form,
        [data-theme="dark"] .campaign-edit-stack > .campaign-action-row > .action-form {
            background: var(--bg-surface-soft);
            border-color: var(--border-strong);
        }
        [data-theme="dark"] .cards-toggle summary {
            color: var(--texto);
        }
        [data-theme="dark"] .btn-secondary {
            background: #2563eb;
            color: #ffffff;
        }
        [data-theme="dark"] .btn-secondary:hover {
            background: #1d4ed8;
        }
        @media (max-width: 1280px) {
            .admin-campaign-main { padding-left: 16px; padding-right: 16px; }
            .stacked-panels,
            .campaign-manage-card { grid-template-columns: 1fr; grid-template-areas: "header" "selector" "editor" "items"; }
            .create-campaign-card,
            .cards-side-panel { position: static; max-height: none; }
        }
        @media (max-width: 760px) {
            .admin-page-heading { grid-template-columns: 1fr; }
            .campaign-gallery-header { align-items: stretch; flex-direction: column; }
            .campaign-filter-tabs { width: 100%; }
            .campaign-filter-tabs button { flex: 1; }
            .campaign-stats,
            .campaign-action-row { grid-template-columns: 1fr; }
            .panel-card { padding: 16px; }
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main class="admin-campaign-main">
    <div class="admin-container">
        <div class="admin-page-heading">
            <div>
                <h1>Campanhas</h1>
                <p>Crie cidades atendidas, mantenha as necessidades atualizadas e controle o ciclo de cada campanha em um so painel.</p>
            </div>
            <div class="campaign-stats" aria-label="Resumo das campanhas">
                <div class="campaign-stat">
                    <span>Total</span>
                    <strong><?php echo (int)$totalCampanhas; ?></strong>
                </div>
                <div class="campaign-stat">
                    <span>Ativas</span>
                    <strong><?php echo (int)$campanhasAtivas; ?></strong>
                </div>
                <div class="campaign-stat">
                    <span>Encerradas</span>
                    <strong><?php echo (int)$campanhasEncerradas; ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($campanhas)): ?>
            <section class="campaign-gallery" aria-label="Galeria de campanhas">
                <div class="campaign-gallery-header">
                    <div>
                        <h2>Visao das campanhas</h2>
                        <p>Confira rapidamente quais campanhas estao ativas ou encerradas antes de editar.</p>
                    </div>
                    <div class="campaign-filter-tabs" aria-label="Filtrar campanhas por status">
                        <button type="button" class="is-active" data-campaign-filter="all">Todas</button>
                        <button type="button" data-campaign-filter="active">Ativas</button>
                        <button type="button" data-campaign-filter="closed">Encerradas</button>
                    </div>
                </div>
                <div class="campaign-gallery-grid">
                    <?php foreach ($campanhas as $campanhaPreview): ?>
                        <?php
                            $previewClosed = strtoupper((string)$campanhaPreview->status) === 'ENCERRADA';
                            $previewSelected = $selectedCampaignId === (int)$campanhaPreview->id;
                            $previewDescription = trim((string)$campanhaPreview->descricao);
                            if ($previewDescription === '') {
                                $previewDescription = $previewClosed ? 'Campanha encerrada, historico preservado.' : 'Campanha ativa recebendo atualizacoes.';
                            }
                        ?>
                        <a
                            class="campaign-preview-card <?php echo $previewClosed ? 'is-closed' : 'is-active'; ?> <?php echo $previewSelected ? 'is-selected' : ''; ?>"
                            data-status="<?php echo $previewClosed ? 'closed' : 'active'; ?>"
                            href="index.php?page=admin_campaign_cards&campanha_id=<?php echo (int)$campanhaPreview->id; ?>"
                            aria-label="Editar campanha <?php echo htmlspecialchars($campanhaPreview->titulo); ?>"
                        >
                            <div class="campaign-preview-media">
                                <img src="<?php echo htmlspecialchars($campanhaPreview->imagem ?: 'assets/images/logo.PNG'); ?>" alt="<?php echo htmlspecialchars($campanhaPreview->titulo); ?>">
                                <span class="campaign-preview-status <?php echo $previewClosed ? 'is-closed' : 'is-active'; ?>">
                                    <?php echo $previewClosed ? 'Encerrada' : 'Ativa'; ?>
                                </span>
                            </div>
                            <div class="campaign-preview-body">
                                <strong><?php echo htmlspecialchars($campanhaPreview->titulo); ?></strong>
                                <span><?php echo htmlspecialchars(mb_strimwidth($previewDescription, 0, 92, '...', 'UTF-8')); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="stacked-panels">
                <section class="panel-card create-campaign-card">
                    <div class="panel-title">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <div>
                            <h2>Criar campanha</h2>
                    <p>Crie a campanha já com imagem e várias necessidades, tudo em um único envio.</p>
                        </div>
                    </div>

                    <form method="POST" action="index.php?page=admin_create_campaign" class="action-form" enctype="multipart/form-data" id="createCampaignForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                        <input type="hidden" name="allow_duplicate_city" value="1">

                        <div>
                            <label for="titulo_completo">Cidade / nome da campanha</label>
                            <input type="text" name="titulo" id="titulo_completo" maxlength="150" required placeholder="Ex.: Goiânia">
                        </div>

                        <div>
                            <label for="descricao_campanha_completa">Descrição da campanha</label>
                            <textarea name="descricao_campanha" id="descricao_campanha_completa" placeholder="Se deixar em branco, o sistema cria uma descrição automática."></textarea>
                        </div>

                        <div>
                            <label for="imagem_campanha_create">Imagem da campanha</label>
                            <input type="file" name="imagem_campanha_create" id="imagem_campanha_create" accept=".jpg,.jpeg,.png,.webp">
                            <div class="muted">Opcional, mas já pode enviar a imagem agora.</div>
                        </div>

                        <div>
                            <label>Necessidades iniciais</label>
                            <div class="need-builder" id="needBuilder"></div>
                            <button type="button" class="btn-secondary" id="addNeedRow">Adicionar outro item</button>
                        </div>

                        <button type="submit">Criar campanha completa</button>
                    </form>
                </section>

                <section class="panel-card campaign-manage-card">
                    <div class="panel-title">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                        <div>
                            <h2>Gerenciar campanhas</h2>
                    <p>Escolha uma campanha para editar. Quando encerrar, ela continua visível como campanha encerrada.</p>
                        </div>
                    </div>

                    <form method="GET" class="selector-form">
                        <input type="hidden" name="page" value="admin_campaign_cards">
                        <div>
                            <label for="campanha_id">Campanha</label>
                            <select name="campanha_id" id="campanha_id" onchange="this.form.submit()">
                                <?php foreach ($campanhas as $campanha): ?>
                                    <option value="<?php echo (int)$campanha->id; ?>" <?php echo $selectedCampaignId === (int)$campanha->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campanha->titulo . ' - ' . $campanha->status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <?php if ($selectedCampaign): ?>
                        <?php $isClosed = strtoupper((string)$selectedCampaign->status) === 'ENCERRADA'; ?>
                        <div class="manage-layout">
                            <div class="campaign-edit-stack">
                                <div class="selected-campaign-visual">
                                    <img src="<?php echo htmlspecialchars($selectedCampaign->imagem ?: 'assets/images/logo.PNG'); ?>" alt="<?php echo htmlspecialchars($selectedCampaign->titulo); ?>">
                                    <div class="selected-campaign-caption">
                                        <div>
                                            <strong><?php echo htmlspecialchars($selectedCampaign->titulo); ?></strong>
                                            <span><?php echo $isClosed ? 'Campanha encerrada' : 'Campanha ativa'; ?></span>
                                        </div>
                                        <div class="status-badge <?php echo $isClosed ? 'is-closed' : 'is-active'; ?>">
                                            <?php echo $isClosed ? 'Encerrada' : 'Ativa'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="campaign-info <?php echo $isClosed ? 'is-closed' : ''; ?>">
                                    <strong><?php echo htmlspecialchars($selectedCampaign->titulo); ?></strong>
                                    <div class="status-badge <?php echo $isClosed ? 'is-closed' : 'is-active'; ?>">
                                        <?php echo $isClosed ? 'Campanha encerrada' : 'Campanha ativa'; ?>
                                    </div>
                                </div>

                                <form method="POST" action="index.php?page=admin_rename_campaign" class="action-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                    <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                    <div>
                                        <label for="titulo_edicao">Nome da campanha</label>
                                        <input type="text" name="titulo" id="titulo_edicao" maxlength="150" value="<?php echo htmlspecialchars($selectedCampaign->titulo); ?>" required>
                                    </div>
                                    <button type="submit">Salvar nome da campanha</button>
                                </form>

                                <div class="campaign-action-row">
                                <?php if (!$isClosed): ?>
                                    <form method="POST" action="index.php?page=admin_close_campaign" class="action-form" onsubmit="return confirm('Tem certeza que deseja encerrar esta campanha?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <button type="submit" class="btn-danger">Encerrar campanha</button>
                                    </form>

                                <?php else: ?>
                                    <form method="POST" action="index.php?page=admin_reopen_campaign" class="action-form" onsubmit="return confirm('Tem certeza que deseja reabrir esta campanha?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <button type="submit" class="btn-secondary">Reabrir campanha</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="index.php?page=admin_delete_campaign" class="action-form" onsubmit="return confirm('Excluir esta campanha da listagem? O historico de doacoes e distribuicoes sera preservado.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                    <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                    <button type="submit" class="btn-danger">Excluir campanha</button>
                                </form>
                                </div>

                                <?php if (!$isClosed): ?>
                                    <form method="POST" action="index.php?page=admin_upload_campaign_image" class="action-form" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <div>
                                            <label for="imagem_campanha">Trocar imagem da campanha</label>
                                            <input type="file" name="imagem_campanha" id="imagem_campanha" accept=".jpg,.jpeg,.png,.webp" required>
                                        </div>
                                        <button type="submit">Salvar imagem da campanha</button>
                                    </form>
                                <?php endif; ?>


                                <form method="POST" action="index.php?page=admin_add_campaign_card" class="action-form form-section-box">
                                        <h3>Adicionar necessidade</h3>
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <div>
                                            <label for="categoria_id">Categoria do item</label>
                                            <select name="categoria_id" id="categoria_id" required>
                                                <option value="">-- Escolha uma categoria --</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?php echo (int)$categoria['id']; ?>">
                                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="descricao">Descrição</label>
                                            <textarea name="descricao" id="descricao" required placeholder="Ex.: Itens prioritários para atendimento imediato das famílias afetadas."></textarea>
                                        </div>
                                        <div>
                                            <label for="quantidade_necessaria">Quantidade necessária</label>
                                            <input type="number" name="quantidade_necessaria" id="quantidade_necessaria" min="1" required>
                                        </div>
                                        <button type="submit">Adicionar item</button>
                                </form>

                                <details class="cards-toggle" id="cardsToggle_<?php echo (int)$selectedCampaign->id; ?>">
                                    <summary>Itens já cadastrados (<?php echo count($necessidades); ?>)</summary>
                                </details>
                            </div>

                            <div class="cards-side-panel">
                                <div class="cards-side-panel-header">
                                    <h3>Itens cadastrados</h3>
                                    <span class="items-count"><?php echo count($necessidades); ?></span>
                                </div>
                                <h3>Itens já cadastrados</h3>
                                <?php if (empty($necessidades)): ?>
                                    <div class="cards-scroll"><div class="empty-state">Essa campanha ainda não possui itens cadastrados.</div></div>
                                <?php else: ?>
                                    <div class="cards-scroll">
                                        <?php foreach ($necessidades as $necessidade): ?>
                                            <article class="need-card">
                                                <strong><?php echo htmlspecialchars($necessidade['categoria_nome']); ?></strong>
                                                <form method="POST" action="index.php?page=admin_update_campaign_card" class="need-card-edit">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                                    <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                                    <input type="hidden" name="necessidade_id" value="<?php echo (int)$necessidade['id']; ?>">
                                                    <div>
                                                        <label for="descricao_necessidade_<?php echo (int)$necessidade['id']; ?>">Descrição</label>
                                                        <textarea name="descricao" id="descricao_necessidade_<?php echo (int)$necessidade['id']; ?>" required><?php echo htmlspecialchars($necessidade['descricao']); ?></textarea>
                                                    </div>
                                                    <div>
                                                        <label for="quantidade_necessidade_<?php echo (int)$necessidade['id']; ?>">Quantidade necessária</label>
                                                        <input type="number" name="quantidade_necessaria" id="quantidade_necessidade_<?php echo (int)$necessidade['id']; ?>" min="1" value="<?php echo (int)$necessidade['quantidade_necessaria']; ?>" required>
                                                    </div>
                                                    <button type="submit">Salvar item</button>
                                                </form>
                                                <div class="card-actions">
                                                    <form method="POST" action="index.php?page=admin_delete_campaign_card" onsubmit="return confirm('Tem certeza que deseja excluir este item?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                                        <input type="hidden" name="necessidade_id" value="<?php echo (int)$necessidade['id']; ?>">
                                                        <button type="submit" class="btn-danger">Excluir item</button>
                                                    </form>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Nenhuma campanha disponível para editar.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
    <a class="footer-social-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer" aria-label="Instagram do ConectaSolidária">
        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
    </a>
</footer>

<script>
    const categoriasDisponiveis = <?php echo json_encode(array_values($categorias), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const needBuilder = document.getElementById('needBuilder');
    const addNeedRowButton = document.getElementById('addNeedRow');
    const campaignFilterButtons = document.querySelectorAll('[data-campaign-filter]');
    const campaignPreviewCards = document.querySelectorAll('.campaign-preview-card');
    const campaignGalleryGrid = document.querySelector('.campaign-gallery-grid');

    function buildCategoriaOptions() {
        let options = '<option value="">-- Escolha uma categoria --</option>';
        categoriasDisponiveis.forEach((categoria) => {
            options += `<option value="${categoria.id}">${categoria.nome}</option>`;
        });
        return options;
    }

    function createNeedRow(index) {
        const row = document.createElement('div');
        row.className = 'need-row';
        row.innerHTML = `
            <div class="need-row-top">
                <span class="need-row-title">Item ${index + 1}</span>
                <button type="button" class="btn-linkish">Remover</button>
            </div>
            <div>
                <label>Categoria</label>
                <select name="categoria_id_create[]" required>
                    ${buildCategoriaOptions()}
                </select>
            </div>
            <div>
                <label>Descrição</label>
                <textarea name="descricao_item_create[]" required placeholder="Ex.: Água potável para atendimento imediato."></textarea>
            </div>
            <div>
                <label>Quantidade necessária</label>
                <input type="number" name="quantidade_item_create[]" min="1" required>
            </div>
        `;

        row.querySelector('.btn-linkish').addEventListener('click', () => {
            if (needBuilder.children.length > 1) {
                row.remove();
                refreshNeedTitles();
            }
        });

        needBuilder.appendChild(row);
    }

    function refreshNeedTitles() {
        [...needBuilder.children].forEach((child, index) => {
            const title = child.querySelector('.need-row-title');
            if (title) {
                title.textContent = `Item ${index + 1}`;
            }
        });
    }

    const cardsToggle = document.querySelector('.cards-toggle');
    const manageLayout = document.querySelector('.manage-layout');

    if (cardsToggle && manageLayout) {
        const syncManageLayout = () => {
            manageLayout.classList.toggle('has-open-panel', cardsToggle.open);
        };

        cardsToggle.addEventListener('toggle', syncManageLayout);
        syncManageLayout();
    }
    if (campaignFilterButtons.length && campaignPreviewCards.length) {
        campaignFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.campaignFilter || 'all';

                campaignFilterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                if (campaignGalleryGrid) {
                    campaignGalleryGrid.classList.toggle('is-filtering', filter !== 'all');
                }

                campaignPreviewCards.forEach((card) => {
                    const shouldShow = filter === 'all' || card.dataset.status === filter;
                    card.classList.toggle('is-hidden', !shouldShow);
                });
            });
        });
    }
    if (needBuilder && addNeedRowButton) {
        createNeedRow(0);
        addNeedRowButton.addEventListener('click', () => {
            createNeedRow(needBuilder.children.length);
        });
    }
</script>

</body>
</html>


