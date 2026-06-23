<?php
SessionManager::requireRole('admin');
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
        .admin-container { max-width: 1700px; margin: 0 auto; }
        .admin-grid { display: grid; gap: 24px; align-items: start; }
        .stacked-panels { display: grid; grid-template-columns: minmax(620px, 1.12fr) minmax(860px, 1.38fr); gap: 28px; align-items: stretch; }
        .panel-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); padding: 24px; min-height: 100%; display: grid; gap: 18px; align-content: start; }
        .panel-card h2, .panel-card h3 { margin: 0; color: #0f172a; }
        .admin-container > h1 { margin-bottom: 18px; }
        .panel-card > p:first-of-type { margin: -6px 0 0; min-height: 44px; }
        .panel-card p { color: #475569; }
        .mensagem { margin-bottom: 20px; padding: 14px 16px; border-radius: 10px; font-weight: bold; }
        .mensagem.sucesso { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .mensagem.erro { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .selector-form, .action-form { display: grid; gap: 14px; }
        .selector-form { margin-bottom: 18px; }
        .selector-form select, .action-form select, .action-form input, .action-form textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font: inherit;
        }
        .action-form textarea { min-height: 110px; resize: vertical; }
        .action-form button, .selector-form button, .btn-secondary, .btn-danger {
            border: 0; border-radius: 10px; padding: 12px 16px; color: #fff; font-weight: 700; cursor: pointer;
        }
        .action-form button, .selector-form button { background: #2563eb; }
        .action-form button:hover, .selector-form button:hover { background: #1d4ed8; }
        .btn-secondary { background: #0f766e; }
        .btn-secondary:hover { background: #115e59; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .campaign-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; }
        .campaign-info.is-closed { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }
        .status-badge { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; margin-top: 10px; }
        .status-badge.is-active { background: #dcfce7; color: #166534; }
        .status-badge.is-closed { background: #e2e8f0; color: #475569; }


        .manage-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; align-items: start; }
        .cards-toggle { margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; overflow: hidden; }
        .cards-side-panel { display: none; padding: 22px; border: 1px solid #e2e8f0; border-radius: 16px; background: #f8fafc; }
        .manage-layout.has-open-panel .cards-side-panel { display: block; }
        .cards-side-panel h3 { margin-bottom: 12px; }
        .manage-layout.has-open-panel { grid-template-columns: minmax(0, 1fr) minmax(560px, 1.15fr); }
        .cards-toggle summary { padding: 14px 16px; cursor: pointer; font-weight: 700; color: #0f172a; list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .cards-toggle summary::-webkit-details-marker { display: none; }
        .cards-toggle summary::after { content: '+'; font-size: 18px; color: #2563eb; }
        .cards-toggle[open] summary::after { content: '-'; }
        .cards-scroll {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding: 4px 4px 18px;
            scroll-behavior: smooth;
            scrollbar-width: thin;
        }
        .need-card { flex: 0 0 320px; min-height: 220px; border: 1px solid #dbe4f0; border-radius: 12px; padding: 16px; background: #fff; display: grid; gap: 10px; align-content: start; }
        .need-card strong { display: block; margin-bottom: 6px; color: #0f172a; }
        .need-card div { color: #475569; line-height: 1.5; }
        .need-card span { display: inline-block; font-size: 14px; font-weight: 700; color: #1d4ed8; }
        .need-card-edit { display: grid; gap: 10px; margin-top: 8px; }
        .need-card-edit textarea,
        .need-card-edit input {
            width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font: inherit;
        }
        .need-card-edit textarea { min-height: 90px; resize: vertical; }
        .need-card-edit button { border: 0; border-radius: 10px; padding: 10px 14px; color: #fff; font-weight: 700; cursor: pointer; background: #2563eb; }
        .need-card-edit button:hover { background: #1d4ed8; }
        .card-actions { margin-top: 14px; display: flex; justify-content: flex-end; }
        .muted { color: #64748b; font-size: 14px; }
        .empty-state { padding: 18px; border-radius: 12px; background: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1; }
        .need-builder { display: grid; gap: 12px; }
        .need-row { border: 1px solid #dbeafe; background: #f8fbff; border-radius: 14px; padding: 14px; display: grid; gap: 10px; }
        .need-row-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .need-row-title { font-size: 14px; font-weight: 700; color: #0f172a; }
        .btn-linkish { border: 0; background: transparent; color: #dc2626; font-weight: 700; cursor: pointer; padding: 0; }
        [data-theme="dark"] .panel-card,
        [data-theme="dark"] .need-card {
            background: var(--bg-surface);
            border-color: var(--border-strong);
        }
        [data-theme="dark"] .panel-card h2,
        [data-theme="dark"] .panel-card h3,
        [data-theme="dark"] .need-card strong,
        [data-theme="dark"] .need-row-title {
            color: var(--texto);
        }
        [data-theme="dark"] .panel-card p,
        [data-theme="dark"] .need-card div,
        [data-theme="dark"] .muted {
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
        [data-theme="dark"] .empty-state {
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
        @media (max-width: 1280px) { .stacked-panels { grid-template-columns: 1fr; } .manage-layout.has-open-panel { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="admin-container">
        <h1>Campanhas</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="stacked-panels">
                <section class="panel-card">
                    <h2>Criar campanha completa</h2>
                    <p>Crie a campanha já com imagem e várias necessidades, tudo em um único envio.</p>

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

                <section class="panel-card">
                    <h2>Gerenciar campanhas</h2>
                    <p>Escolha uma campanha para editar. Quando encerrar, ela continua visível como campanha encerrada.</p>

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
                            <div>
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

                                <?php if (!$isClosed): ?>
                                    <form method="POST" action="index.php?page=admin_close_campaign" class="action-form" onsubmit="return confirm('Tem certeza que deseja encerrar esta campanha?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <button type="submit" class="btn-danger">Encerrar campanha</button>
                                    </form>

                                    <form method="POST" action="index.php?page=admin_upload_campaign_image" class="action-form" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <div>
                                            <label for="imagem_campanha">Trocar imagem da campanha</label>
                                            <input type="file" name="imagem_campanha" id="imagem_campanha" accept=".jpg,.jpeg,.png,.webp" required>
                                        </div>
                                        <button type="submit">Salvar imagem da campanha</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="index.php?page=admin_reopen_campaign" class="action-form" onsubmit="return confirm('Tem certeza que deseja reabrir esta campanha?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="campanha_id" value="<?php echo (int)$selectedCampaign->id; ?>">
                                        <button type="submit" class="btn-secondary">Reabrir campanha</button>
                                    </form>
                                <?php endif; ?>


                                <form method="POST" action="index.php?page=admin_add_campaign_card" class="action-form">
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
    if (needBuilder && addNeedRowButton) {
        createNeedRow(0);
        addNeedRowButton.addEventListener('click', () => {
            createNeedRow(needBuilder.children.length);
        });
    }
</script>

</body>
</html>


