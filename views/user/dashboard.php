<?php
// View: User Dashboard
// Renderizada por DonationController::dashboard()
SessionManager::requireLogin();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - ConectaSolidária</title>
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

        @media (max-width: 900px) {
            .doacoes-grid {
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
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
