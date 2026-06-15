<?php
SessionManager::requireRole('admin');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pontos de Coleta - Admin</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-grid {
            display: flex;
            gap: 24px;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .panel-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            flex: 1 1 440px;
            min-width: 320px;
        }

        .panel-card h2,
        .panel-card h3 {
            margin-top: 0;
            margin-bottom: 14px;
        }

        .helper {
            color: var(--texto-suave);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .point-list {
            display: grid;
            gap: 12px;
        }

        .point-item {
            border: 1px solid var(--cinza-borda);
            border-radius: 12px;
            padding: 14px;
            background: var(--bg-surface-muted);
        }

        .point-item-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .muted {
            color: var(--texto-suave);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-badge.ativo {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-badge.inativo {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .inline-form {
            max-width: none;
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }

        .inline-form button {
            padding: 10px 14px;
        }

        .point-item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
        }

        .danger-button {
            background: #dc2626;
            color: #fff;
        }

        .danger-button:hover {
            background: #b91c1c;
        }

        .activate-button {
            background: #15803d;
            color: #fff;
        }

        .activate-button:hover {
            background: #166534;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <section class="panel-card">
            <h2>Novo ponto de coleta</h2>
            <p class="helper">Cadastre novos pontos para liberar recebimento de doações em outros locais.</p>

            <form method="POST" action="index.php?page=admin_create_collection_point">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">

                <label for="novo_ponto_nome">Nome do ponto</label>
                <input type="text" name="novo_ponto_nome" id="novo_ponto_nome" placeholder="Nome do ponto de coleta" required>

                <label for="novo_ponto_logradouro">Logradouro</label>
                <input type="text" name="novo_ponto_logradouro" id="novo_ponto_logradouro" placeholder="Logradouro" required>

                <label for="novo_ponto_cidade">Cidade</label>
                <input type="text" name="novo_ponto_cidade" id="novo_ponto_cidade" placeholder="Cidade" required>

                <label for="novo_ponto_estado">Estado (UF)</label>
                <input type="text" name="novo_ponto_estado" id="novo_ponto_estado" placeholder="UF" maxlength="2" required>

                <label for="novo_ponto_cep">CEP</label>
                <input type="text" name="novo_ponto_cep" id="novo_ponto_cep" placeholder="CEP" required>

                <button type="submit">Cadastrar ponto</button>
            </form>
        </section>

        <section class="panel-card">
            <h2>Pontos cadastrados</h2>
            <p class="helper">Use ativar/desativar para controlar novos recebimentos sem perder o histórico.</p>

            <?php if (empty($pontosColeta)): ?>
                <p class="helper">Nenhum ponto de coleta cadastrado no momento.</p>
            <?php else: ?>
                <div class="point-list">
                    <?php foreach ($pontosColeta as $ponto): ?>
                        <?php
                        $pontoAtivo = ((int)($ponto['ativo'] ?? 1)) === 1;
                        $logradouro = trim((string)($ponto['logradouro'] ?? ''));
                        $cidade = trim((string)($ponto['cidade'] ?? ''));
                        $estado = trim((string)($ponto['estado'] ?? ''));
                        $cep = trim((string)($ponto['cep'] ?? ''));
                        ?>
                        <article class="point-item">
                            <div class="point-item-top">
                                <div>
                                    <strong><?php echo htmlspecialchars((string)($ponto['nome'] ?? '')); ?></strong>
                                    <div class="muted">
                                        Status:
                                        <span class="status-badge <?php echo $pontoAtivo ? 'ativo' : 'inativo'; ?>">
                                            <?php echo $pontoAtivo ? 'ATIVO' : 'INATIVO'; ?>
                                        </span>
                                    </div>
                                    <div class="muted">
                                        <?php echo htmlspecialchars($logradouro . ' - ' . $cidade . '/' . $estado); ?>
                                    </div>
                                    <div class="muted">CEP: <?php echo htmlspecialchars($cep); ?></div>
                                </div>

                                <div class="point-item-actions">
                                    <form method="POST"
                                          action="index.php?page=admin_toggle_collection_point_status"
                                          class="inline-form"
                                          onsubmit="return confirm('<?php echo $pontoAtivo ? 'Desativar este ponto para impedir novas doações nele?' : 'Ativar este ponto para liberar novas doações nele?'; ?>');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                        <input type="hidden" name="ponto_id" value="<?php echo (int)($ponto['id'] ?? 0); ?>">
                                        <input type="hidden" name="novo_status" value="<?php echo $pontoAtivo ? 'desativar' : 'ativar'; ?>">
                                        <button type="submit" class="<?php echo $pontoAtivo ? 'danger-button' : 'activate-button'; ?>">
                                            <?php echo $pontoAtivo ? 'Desativar ponto' : 'Ativar ponto'; ?>
                                        </button>
                                    </form>

                                    <!-- Botão de excluir removido; mantém apenas ativar/desativar para preservar histórico -->
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
