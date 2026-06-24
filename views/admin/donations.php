<?php
// View: Admin - Donations
// Renderizada por AdminController::manageDonations() e receiveDonation()
SessionManager::requireRole('admin');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Doações - Admin</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filtros-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filtros input, .filtros select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .filtros button {
            padding: 8px 16px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .filtros button:hover {
            background: #1d4ed8;
        }

        .doacoes-tabela {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
            color: #374151;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f3f4f6;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
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


        .form-receber {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .form-receber-inline {
            display: inline;
        }

        .form-receber select {
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-receber button {
            padding: 6px 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .form-receber button:hover {
            background: #15803d;
        }

        .donation-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #2563eb;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            white-space: nowrap;
        }

        .btn-pdf:hover {
            background: #1d4ed8;
            color: white;
        }

        .mensagem {
            margin-bottom: 15px;
            padding: 12px;
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

        .vazio {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .codigo-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
        }

        .codigo-box h3 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .codigo-box label {
            display: block;
            margin-bottom: 8px;
        }

        .codigo-box form {
            max-width: none;
            box-shadow: none;
            background: transparent;
            padding: 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: end;
        }

        .codigo-box input {
            max-width: 320px;
            margin-bottom: 0;
        }

        .panel-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-top: 24px;
        }

        .panel-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .panel-card > p {
            margin-top: 0;
            margin-bottom: 16px;
        }

        .action-form {
            display: grid;
            gap: 12px;
        }

        .action-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .action-form button {
            padding: 10px 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .action-form button:hover {
            background: #1d4ed8;
        }

        .user-list {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .user-badge {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }

        .user-badge form {
            margin: 0;
        }

        .user-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
        }

        .user-status.is-active {
            background: #dcfce7;
            color: #166534;
        }

        .user-status.is-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-toggle-user {
            padding: 8px 12px;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-toggle-user.deactivate {
            background: #dc2626;
        }

        .btn-toggle-user.activate {
            background: #16a34a;
        }

        .btn-toggle-user.deactivate:hover {
            background: #b91c1c;
        }

        .btn-toggle-user.activate:hover {
            background: #15803d;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .current-user-note {
            color: #fff;
            background: #64748b;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-nao-recebida {
            color: #991b1b;
        }

        .status-recebida {
            color: #16a34a;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="admin-container">
        <div class="admin-header">
            <h2>Gerenciar Doações</h2>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="codigo-box">
            <h3>Receber por código da doação</h3>
            <form method="POST" action="index.php?page=admin_receive_donation">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                <input type="hidden" name="receber_doacao" value="1">
                <div>
                    <label for="codigo_doacao">Código apresentado pelo doador</label>
                    <input type="text" name="codigo_doacao" id="codigo_doacao" placeholder="Ex.: DCS-20260410-000002" required>
                </div>
                <button type="submit">Receber por código</button>
            </form>
        </div>

        <div class="filtros">
            <form method="GET" class="filtros-form">
                <input type="hidden" name="page" value="admin_donations">
                
                <select name="status" onchange="this.form.submit()">
                    <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="pendente" <?php echo $filtro === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                    <option value="recebida" <?php echo $filtro === 'recebida' ? 'selected' : ''; ?>>Recebidas</option>
                    <option value="excluida" <?php echo $filtro === 'excluida' ? 'selected' : ''; ?>>Excluídas</option>
                </select>

                <input type="text" name="busca" placeholder="Buscar por nome, campanha ou código..." value="<?php echo htmlspecialchars($busca); ?>">
                
                <button type="submit">Filtrar</button>
            </form>
        </div>

        <?php if (empty($doacoes)): ?>
            <div class="vazio">
                <p>Nenhuma doação encontrada com esses filtros.</p>
            </div>
        <?php else: ?>
            <div class="doacoes-tabela">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Doador</th>
                            <th>Campanha</th>
                            <th>Ponto</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doacoes as $doacao): ?>
                            <tr>
                                <td>#<?php echo $doacao['id']; ?></td>
                                <td><?php echo htmlspecialchars($doacao['codigo_publico']); ?></td>
                                <td><?php echo htmlspecialchars($doacao['usuario_nome']); ?></td>
                                <td><?php echo htmlspecialchars($doacao['campanha_nome']); ?></td>
                                <td><?php echo htmlspecialchars($doacao['ponto_nome']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($doacao['data_criacao'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $doacao['status']; ?>">
                                        <?php echo ['pendente' => 'Pendente', 'recebida' => 'Recebida', 'excluida' => 'Excluída'][$doacao['status']] ?? ucfirst($doacao['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="donation-actions">
                                        <a
                                            href="index.php?page=donation_receipt&id=<?php echo (int)$doacao['id']; ?>"
                                            class="btn-pdf"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <i class="fas fa-file-pdf"></i> Ver PDF
                                        </a>

                                    <?php if ($doacao['status'] === 'pendente'): ?>
                                        <form method="POST" action="index.php?page=admin_receive_donation" class="form-receber form-receber-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                            <input type="hidden" name="doacao_id" value="<?php echo $doacao['id']; ?>">
                                            <input type="hidden" name="receber_doacao" value="1">

                                            <button type="submit">Receber</button>
                                        </form>
                                    <?php elseif ($doacao['status'] === 'excluida'): ?>
                                        <span class="status-nao-recebida">Não recebida</span>
                                    <?php else: ?>
                                        <span class="status-recebida">Recebida</span>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <section class="panel-card">
            <h3>Dar acesso de admin</h3>
            <p>Escolha um usuário já cadastrado para promover ao perfil de administrador.</p>

            <form method="POST" action="index.php?page=admin_promote_user" class="action-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                <div>
                    <label for="usuario_id">Usuário cadastrado</label>
                    <select name="usuario_id" id="usuario_id" required>
                        <option value="">-- Escolha um usuário --</option>
                        <?php foreach ($usuariosDisponiveis as $usuario): ?>
                            <option value="<?php echo (int)$usuario->id; ?>">
                                <?php echo htmlspecialchars($usuario->nome . ' - ' . $usuario->email); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Tornar administrador</button>
            </form>

            <div class="user-list">
                <h3>Administradores atuais</h3>
                <?php if (empty($admins)): ?>
                    <div class="muted">Nenhum administrador encontrado.</div>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                        <?php
                            $isProtectedAdmin = isset($protectedAdminEmail) && strcasecmp((string)$admin->email, (string)$protectedAdminEmail) === 0;
                            $isCurrentAdmin = (int)$admin->id === (int)(SessionManager::getUserId() ?? 0);
                        ?>
                        <div class="user-badge">
                            <div>
                                <strong><?php echo htmlspecialchars($admin->nome); ?></strong>
                                <div class="muted"><?php echo htmlspecialchars($admin->email); ?></div>
                            </div>
                            <div class="user-actions">
                                <?php if ($isProtectedAdmin): ?>
                                    <span class="current-user-note">Admin principal protegido</span>
                                <?php elseif ($isCurrentAdmin): ?>
                                    <span class="current-user-note">Usuário atual</span>
                                <?php else: ?>
                                    <form method="POST" action="index.php?page=admin_demote_user" onsubmit="return confirm('Tem certeza que deseja remover o acesso de admin deste usuário?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="usuario_id" value="<?php echo (int)$admin->id; ?>">
                                        <button type="submit" class="btn-toggle-user deactivate">Remover admin</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel-card">
            <h3>Gerenciar status de usuários</h3>
            <p>O admin pode ativar ou desativar usuários cadastrados sem excluir o histórico deles.</p>

            <div class="user-list">
                <?php if (empty($usuariosCadastrados)): ?>
                    <div class="muted">Nenhum usuário cadastrado.</div>
                <?php else: ?>
                    <?php foreach ($usuariosCadastrados as $usuario): ?>
                        <div class="user-badge">
                            <div>
                                <strong><?php echo htmlspecialchars($usuario->nome); ?></strong>
                                <div class="muted">
                                    <?php echo htmlspecialchars($usuario->email); ?> - <?php echo htmlspecialchars($usuario->perfil_nome ?: 'sem perfil'); ?>
                                </div>
                                <div class="user-status <?php echo $usuario->ativo ? 'is-active' : 'is-inactive'; ?>">
                                    <?php echo $usuario->ativo ? 'Ativo' : 'Inativo'; ?>
                                </div>
                            </div>

                            <div class="user-actions">
                                <?php
                                    $isProtectedAdmin = isset($protectedAdminEmail) && strcasecmp((string)$usuario->email, (string)$protectedAdminEmail) === 0;
                                    $isCurrentUser = (int)$usuario->id === (int)(SessionManager::getUserId() ?? 0);
                                ?>
                                <?php if ($isProtectedAdmin): ?>
                                    <span class="current-user-note">Admin principal protegido</span>
                                <?php elseif (!$isCurrentUser): ?>
                                    <form method="POST" action="index.php?page=admin_toggle_user_status" onsubmit="return confirm('Tem certeza que deseja <?php echo $usuario->ativo ? 'desativar' : 'ativar'; ?> este usuário?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="usuario_id" value="<?php echo (int)$usuario->id; ?>">
                                        <button type="submit" class="btn-toggle-user <?php echo $usuario->ativo ? 'deactivate' : 'activate'; ?>">
                                            <?php echo $usuario->ativo ? 'Desativar usuário' : 'Ativar usuário'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="current-user-note">Usuário atual</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
    <a class="footer-social-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer" aria-label="Instagram do ConectaSolidária">
        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
    </a>
</footer>

</body>
</html>




