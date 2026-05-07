<?php
// View: User Dashboard
// Renderizada por DonationController::dashboard()
SessionManager::requireLogin();
$usuarioPerfil = $usuario ?? null;
$nomePerfil = $usuarioPerfil ? $usuarioPerfil->nome : (SessionManager::getUserName() ?? 'Usuário');
$tipoPerfilRaw = strtolower((string)($usuarioPerfil ? $usuarioPerfil->tipo : (SessionManager::getUserRole() ?? 'doador')));
$tipoPerfilLabel = $tipoPerfilRaw === 'admin' ? 'Admin' : 'Doador';
$fotoPerfil = trim((string)($usuarioPerfil ? $usuarioPerfil->foto_perfil : ''));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - ConectaSolidária</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .painel-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .painel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .painel-header h2 {
            margin: 0;
        }

        .profile-card {
            display: grid;
            grid-template-columns: auto minmax(220px, 1fr);
            gap: 18px;
            align-items: center;
            background: var(--bg-surface);
            border: 1px solid var(--cinza-borda);
            border-radius: 14px;
            box-shadow: var(--sombra);
            padding: 20px;
            margin-bottom: 22px;
        }

        .profile-photo {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            overflow: hidden;
            background: #dbeafe;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            border: 3px solid #bfdbfe;
            flex-shrink: 0;
            position: relative;
        }

        .profile-photo-trigger {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            display: block;
            cursor: pointer;
            position: relative;
        }

        .profile-photo-trigger:hover .profile-photo {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info h3 {
            margin: 0 0 8px;
            color: var(--preto);
            font-size: 22px;
        }

        .profile-info {
            min-width: 0;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e3a8a;
            border: 1px solid #bfdbfe;
            font-weight: bold;
            font-size: 13px;
        }

        .profile-upload-form {
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 10px;
        }

        .profile-upload-form input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .profile-upload-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .profile-upload-form button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 34px;
            padding: 7px 12px;
            border-radius: 999px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            font-size: 13px;
        }

        .profile-file-name {
            color: var(--texto-suave);
            font-size: 12px;
            min-height: 16px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 360px;
            display: none;
        }

        .profile-upload-form.has-file .profile-file-name {
            display: block;
        }

        .profile-upload-form button {
            display: none;
            width: auto;
            border-radius: 999px;
        }

        .profile-upload-form.has-file button {
            display: inline-flex;
        }

        .btn-doacao {
            padding: 12px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-doacao:hover {
            background: #1d4ed8;
        }

        .stats-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .mensagem {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 8px;
            font-weight: bold;
        }

        .mensagem.sucesso {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .mensagem.erro {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .doacoes-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            padding: 10px 4px 20px;
            align-items: stretch;
        }

        .card-doacao {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            border-left: 6px solid #2563eb;
            transition: 0.3s;
            min-width: 0;
            max-width: none;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-doacao:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .card-doacao h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #111827;
            font-size: 20px;
        }

        .info-doacao {
            margin-bottom: 12px;
            color: #374151;
            font-size: 14px;
        }

        .info-doacao strong {
            color: #1f2937;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
        }

        .status-badge.pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.recebida {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.excluida {
            background: #fee2e2;
            color: #991b1b;
        }

        .vazio {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            grid-column: 1 / -1;
        }

        .vazio p {
            margin: 0 0 16px;
        }

        .vazio .btn-doacao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 8px;
        }

        .itens-list {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
        }

        .itens-list li {
            margin-bottom: 4px;
            color: #374151;
        }

        .rastreamento-box {
            margin-top: 14px;
            padding: 14px;
            border-radius: 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .rastreamento-box h4 {
            margin: 0 0 8px;
            color: #1e3a8a;
            font-size: 15px;
        }

        .rastreamento-meta {
            font-size: 12px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 6px;
        }

        .rastreamento-desc {
            color: #374151;
            font-size: 13px;
            line-height: 1.45;
        }

        .timeline {
            position: relative;
            margin-top: 14px;
            padding-left: 18px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            left: 9px;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: linear-gradient(to bottom, #60a5fa, #bfdbfe);
            border-radius: 999px;
        }

        .timeline-item {
            position: relative;
            padding-left: 26px;
            margin-bottom: 14px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -1px;
            top: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid #93c5fd;
            background: white;
            box-shadow: 0 0 0 4px #eff6ff;
        }

        .timeline-item.done .timeline-dot {
            background: #2563eb;
            border-color: #2563eb;
        }

        .timeline-item.current .timeline-dot {
            background: #f59e0b;
            border-color: #f59e0b;
        }

        .timeline-title {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .timeline-item.current .timeline-title {
            color: #92400e;
        }

        .timeline-text {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.4;
        }

        .acoes-doacao {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .btn-secundario {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            background: #e0ecff;
            color: #1d4ed8;
        }

        .btn-secundario:hover {
            background: #dbeafe;
        }

        .btn-alerta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            background: #fef3c7;
            color: #92400e;
            border: none;
            cursor: pointer;
        }

        .btn-alerta:hover {
            background: #fde68a;
        }

        .btn-perigo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            cursor: pointer;
        }

        .btn-perigo:hover {
            background: #fecaca;
        }

        .account-danger-zone {
            margin-top: 32px;
            padding: 22px;
            border: 1px solid var(--cinza-borda);
            border-radius: 12px;
            background: var(--bg-surface);
            box-shadow: var(--sombra);
        }

        .account-danger-zone h3 {
            margin: 0 0 8px;
            color: var(--preto);
        }

        .account-danger-zone p {
            margin: 0 0 16px;
            color: var(--texto-suave);
            line-height: 1.5;
        }

        .account-delete-form {
            max-width: none;
            background: transparent;
            border: 0;
            box-shadow: none;
            padding: 0;
        }

        .account-delete-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .account-delete-confirm {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 10px 0 14px;
            color: var(--texto);
            font-weight: bold;
        }

        .account-delete-confirm input {
            width: auto;
            margin: 3px 0 0;
        }

        [data-theme="dark"] .account-danger-zone {
            background: var(--bg-surface);
            border-color: var(--cinza-borda);
        }

        [data-theme="dark"] .account-danger-zone h3 {
            color: var(--texto);
        }

        [data-theme="dark"] .account-danger-zone p,
        [data-theme="dark"] .account-delete-confirm {
            color: var(--texto-suave);
        }

        [data-theme="dark"] .profile-photo {
            background: rgba(59, 130, 246, 0.18);
            color: #bfdbfe;
            border-color: rgba(147, 197, 253, 0.45);
        }

        [data-theme="dark"] .profile-role {
            background: rgba(59, 130, 246, 0.18);
            color: #bfdbfe;
            border-color: rgba(147, 197, 253, 0.45);
        }

        @media (max-width: 900px) {
            .profile-card {
                grid-template-columns: 1fr;
            }

            .profile-photo,
            .profile-photo-trigger {
                width: 112px;
                height: 112px;
            }

            .doacoes-grid {
                grid-template-columns: 1fr;
            }

            .account-delete-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="painel-container">
        <div class="painel-header">
            <h2>Bem-vindo, <?php echo htmlspecialchars(SessionManager::getUserName()); ?>!</h2>
            <a href="index.php?page=donation_create" class="btn-doacao">
                Nova Doação
            </a>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <section class="profile-card" aria-label="Perfil do usuário">
            <label for="foto_perfil" class="profile-photo-trigger" title="Alterar foto de perfil">
                <span class="profile-photo">
                    <?php if ($fotoPerfil !== ''): ?>
                        <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de perfil de <?php echo htmlspecialchars($nomePerfil); ?>">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </span>
            </label>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($nomePerfil); ?></h3>
                <span class="profile-role">
                    <i class="fas <?php echo $tipoPerfilRaw === 'admin' ? 'fa-user-shield' : 'fa-hand-holding-heart'; ?>"></i>
                    Tipo: <?php echo htmlspecialchars($tipoPerfilLabel); ?>
                </span>
                <form method="POST" action="index.php?page=profile_photo_update" enctype="multipart/form-data" class="profile-upload-form" id="profile-upload-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                    <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" required>
                    <div class="profile-upload-actions">
                        <button type="submit" class="btn-secundario">
                            <i class="fas fa-check"></i> Salvar foto
                        </button>
                    </div>
                    <div class="profile-file-name" id="profile-file-name"></div>
                </form>
            </div>
        </section>

        <div class="stats-box">
            <i class="fas fa-gift"></i> Total de Doações: <?php echo count($doacoes); ?>
        </div>

        <?php if (empty($doacoes)): ?>
            <div class="vazio">
                <p><i class="fas fa-inbox"></i></p>
                <p>Você ainda não fez nenhuma doação.</p>
                <a href="index.php?page=donation_create" class="btn-doacao">Fazer sua primeira doação</a>
            </div>
        <?php else: ?>
            <div class="doacoes-grid">
                <?php foreach ($doacoes as $doacao): ?>
                    <div class="card-doacao">
                        <h3><?php echo htmlspecialchars($doacao->campanha_nome ?? "?"); ?></h3>
                        
                        <div class="info-doacao">
                            <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($doacao->data_criacao)); ?>
                        </div>

                        <div class="info-doacao">
                            <strong>Status:</strong>
                            <span class="status-badge <?php echo $doacao->status; ?>">
                                <?php echo ucfirst($doacao->status); ?>
                            </span>
                        </div>

                        <div class="info-doacao">
                            <strong>Código da doação:</strong> <?php echo htmlspecialchars($doacao->codigo_publico); ?>
                        </div>

                        <?php if (!empty($doacao->ponto_nome)): ?>
                            <div class="info-doacao">
                                <strong>Ponto de coleta:</strong> <?php echo htmlspecialchars($doacao->ponto_nome); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doacao->descricao)): ?>
                            <div class="info-doacao">
                                <strong>Observações:</strong><br>
                                <?php echo htmlspecialchars(substr($doacao->descricao, 0, 100)); ?>...
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doacao->itens)): ?>
                            <div class="itens-list">
                                <strong>Itens:</strong>
                                <ul>
                                    <?php foreach ($doacao->itens as $item): ?>
                                        <li><?php echo htmlspecialchars($item['nome']); ?> - Qtd: <?php echo $item['quantidade']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doacao->rastreamento)): ?>
                            <div class="rastreamento-box">
                                <div class="rastreamento-meta">
                                    Acompanhamento: etapa <?php echo (int)$doacao->rastreamento['etapa']; ?> de <?php echo (int)$doacao->rastreamento['total_etapas']; ?>
                                </div>
                                <h4><?php echo htmlspecialchars($doacao->rastreamento['titulo']); ?></h4>
                                <div class="rastreamento-desc">
                                    <?php echo htmlspecialchars($doacao->rastreamento['descricao']); ?>
                                </div>
                                <?php if (!empty($doacao->rastreamento['etapas'])): ?>
                                    <div class="timeline">
                                        <?php foreach ($doacao->rastreamento['etapas'] as $index => $etapa): ?>
                                            <?php
                                                $stepNumber = $index + 1;
                                                $stateClass = '';
                                                if ($stepNumber < (int)$doacao->rastreamento['etapa']) {
                                                    $stateClass = 'done';
                                                } elseif ($stepNumber === (int)$doacao->rastreamento['etapa']) {
                                                    $stateClass = ((int)$doacao->rastreamento['etapa'] >= (int)$doacao->rastreamento['total_etapas'])
                                                        ? 'done'
                                                        : 'current';
                                                }
                                            ?>
                                            <div class="timeline-item <?php echo $stateClass; ?>">
                                                <div class="timeline-dot"></div>
                                                <div class="timeline-title"><?php echo htmlspecialchars($etapa['titulo']); ?></div>
                                                <div class="timeline-text"><?php echo htmlspecialchars($etapa['descricao']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="acoes-doacao">
                            <a href="index.php?page=donation_receipt&id=<?php echo (int)$doacao->id; ?>" class="btn-secundario">
                                <i class="fas fa-file-pdf"></i> Baixar PDF
                            </a>
                            <?php if ($doacao->status === 'pendente'): ?>
                                <a href="index.php?page=donation_edit&id=<?php echo (int)$doacao->id; ?>" class="btn-alerta">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <form method="POST" action="index.php?page=donation_cancel" onsubmit="return confirm('Tem certeza que deseja excluir esta doação? O registro continuará no sistema como excluído.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                    <input type="hidden" name="doacao_id" value="<?php echo (int)$doacao->id; ?>">
                                    <button type="submit" class="btn-perigo">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (SessionManager::getUserRole() !== 'admin'): ?>
            <section class="account-danger-zone" aria-labelledby="account-delete-title">
                <h3 id="account-delete-title"><i class="fas fa-user-slash"></i> Excluir minha conta</h3>
                <p>Ao excluir sua conta, seu acesso será removido e seus dados pessoais serão anonimizados. O histórico de doações permanece no sistema para controle das campanhas.</p>
                <form method="POST" action="index.php?page=account_delete" class="account-delete-form" onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                    <div class="account-delete-grid">
                        <div>
                            <label for="senha_confirmacao">Senha atual</label>
                            <input type="password" id="senha_confirmacao" name="senha_confirmacao" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn-perigo">
                            <i class="fas fa-trash"></i> Excluir conta
                        </button>
                    </div>
                    <label class="account-delete-confirm">
                        <input type="checkbox" name="confirmar_exclusao" value="1" required>
                        Entendo que perderei o acesso a esta conta.
                    </label>
                </form>
            </section>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

<script>
    const profilePhotoInput = document.getElementById('foto_perfil');
    const profileFileName = document.getElementById('profile-file-name');
    const profileUploadForm = document.getElementById('profile-upload-form');

    if (profilePhotoInput && profileFileName) {
        profilePhotoInput.addEventListener('change', function () {
            const file = this.files && this.files.length > 0 ? this.files[0] : null;
            profileFileName.textContent = file ? file.name : '';
            if (profileUploadForm) {
                profileUploadForm.classList.toggle('has-file', Boolean(file));
            }
        });
    }
</script>

</body>
</html>
