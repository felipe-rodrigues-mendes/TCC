<?php
SessionManager::requireRole('admin');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribuições - Admin</title>
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            min-height: 520px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1 1 520px;
            min-width: 420px;
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

        .mensagem {
            margin-bottom: 18px;
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

        .stock-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .stock-box ul {
            margin: 10px 0 0;
            padding-left: 18px;
        }

        .stock-box li {
            margin-bottom: 8px;
        }

        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            margin-top: 14px;
        }

        .item-card {
            background: #f8fafc;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            padding: 14px;
        }

        .item-card label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .muted {
            color: var(--texto-suave);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .destination-select-note {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed #cbd5e1;
        }

        .institution-list {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .institution-item {
            border: 1px solid var(--cinza-borda);
            border-radius: 12px;
            padding: 12px;
            background: var(--bg-surface-muted);
        }

        .institution-item strong {
            color: var(--texto);
        }

        .institution-item-top {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
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

        .history-list {
            display: grid;
            gap: 18px;
        }

        .history-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
        }

        .history-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-badge.enviado {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-badge.entregue {
            background: #dcfce7;
            color: #166534;
        }

        .history-items {
            margin: 10px 0 0;
            padding-left: 18px;
        }

        .history-items li {
            margin-bottom: 6px;
        }

        .inline-form {
            max-width: none;
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin-top: 14px;
        }

        .inline-form button {
            padding: 10px 14px;
        }

        @media (max-width: 960px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
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
            <h2>Nova Distribuição</h2>
            <p class="helper">Selecione um ponto de estoque, defina o destino e informe os itens que sairão para atendimento.</p>

            <form method="POST" action="index.php?page=admin_distributions">
                <input type="hidden" name="registrar_distribuicao" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">

                <label for="ponto_id">Ponto de estoque</label>
                <select name="ponto_id" id="ponto_id" required onchange="renderStockItems()">
                    <option value="">-- Escolha um ponto --</option>
                    <?php foreach ($pontos as $ponto): ?>
                        <option value="<?php echo (int)$ponto['id']; ?>">
                            <?php echo htmlspecialchars($ponto['nome'] . ' - ' . $ponto['cidade'] . '/' . $ponto['estado']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="stock-box" id="stockPreview">
                    Selecione um ponto para visualizar os itens disponíveis em estoque.
                </div>

                <label for="campanha_id">Campanha / cidade atendida</label>
                <select name="campanha_id" id="campanha_id" required>
                    <option value="">-- Escolha a campanha --</option>
                    <?php foreach ($campanhas as $campanha): ?>
                        <option value="<?php echo (int)$campanha->id; ?>">
                            <?php echo htmlspecialchars($campanha->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="destino_id">Instituição cadastrada</label>
                <select name="destino_id" id="destino_id" required>
                    <option value="">-- Selecionar instituição cadastrada --</option>
                    <?php foreach ($destinos as $destino): ?>
                        <option value="<?php echo (int)$destino['id']; ?>">
                            <?php echo htmlspecialchars($destino['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="destination-select-note">
                    <p class="helper">Use somente instituições cadastradas. Se precisar de uma nova, cadastre no painel abaixo.</p>
                </div>

                <label for="data_envio">Data de envio</label>
                <input type="date" name="data_envio" id="data_envio" value="<?php echo date('Y-m-d'); ?>" required>

                <h3>Itens da distribuição</h3>
                <div class="item-grid" id="itemGrid">
                    <p class="helper">Escolha um ponto para liberar os itens disponíveis para seleção.</p>
                </div>

                <button type="submit">Registrar Distribuição</button>
            </form>
        </section>

        <section class="panel-card">
            <h2>Instituições de caridade</h2>
            <p class="helper">Cadastre novas instituições para entrega e use ativar/desativar para controlar a disponibilidade sem excluir histórico.</p>

            <form method="POST" action="index.php?page=admin_distributions">
                <input type="hidden" name="cadastrar_destino" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">

                <label for="novo_destino_nome">Nome da instituição</label>
                <input type="text" name="novo_destino_nome" id="novo_destino_nome" placeholder="Nome da instituição" required>

                <label for="novo_destino_logradouro">Logradouro</label>
                <input type="text" name="novo_destino_logradouro" id="novo_destino_logradouro" placeholder="Logradouro" required>

                <label for="novo_destino_cidade">Cidade</label>
                <input type="text" name="novo_destino_cidade" id="novo_destino_cidade" placeholder="Cidade" required>

                <label for="novo_destino_estado">Estado</label>
                <input type="text" name="novo_destino_estado" id="novo_destino_estado" placeholder="UF" maxlength="2" required>

                <label for="novo_destino_cep">CEP</label>
                <input type="text" name="novo_destino_cep" id="novo_destino_cep" placeholder="CEP" required>

                <button type="submit">Cadastrar instituição</button>
            </form>

            <h3>Instituições cadastradas</h3>
            <?php if (empty($instituicoes)): ?>
                <p class="helper">Nenhuma instituição cadastrada no momento.</p>
            <?php else: ?>
                <div class="institution-list">
                    <?php foreach ($instituicoes as $instituicao): ?>
                        <?php $instituicaoAtiva = ((int)($instituicao['ativo'] ?? 1)) === 1; ?>
                        <article class="institution-item">
                            <div class="institution-item-top">
                                <div>
                                    <strong><?php echo htmlspecialchars($instituicao['nome']); ?></strong>
                                    <div class="muted">
                                        Status:
                                        <span class="status-badge <?php echo $instituicaoAtiva ? 'ativo' : 'inativo'; ?>">
                                            <?php echo $instituicaoAtiva ? 'ATIVA' : 'INATIVA'; ?>
                                        </span>
                                    </div>
                                    <div class="muted">
                                        <?php echo htmlspecialchars($instituicao['logradouro'] . ' - ' . $instituicao['cidade'] . '/' . $instituicao['estado']); ?>
                                    </div>
                                    <div class="muted">CEP: <?php echo htmlspecialchars($instituicao['cep']); ?></div>
                                </div>
                                <form method="POST" action="index.php?page=admin_distributions" class="inline-form" onsubmit="return confirm('<?php echo $instituicaoAtiva ? 'Desativar esta instituição para impedir novas distribuições?' : 'Ativar esta instituição para permitir novas distribuições?'; ?>');">
                                    <input type="hidden" name="alterar_status_destino" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                    <input type="hidden" name="destino_id" value="<?php echo (int)$instituicao['id']; ?>">
                                    <input type="hidden" name="novo_status" value="<?php echo $instituicaoAtiva ? 'desativar' : 'ativar'; ?>">
                                    <button type="submit" class="<?php echo $instituicaoAtiva ? 'danger-button' : 'activate-button'; ?>">
                                        <?php echo $instituicaoAtiva ? 'Desativar instituição' : 'Ativar instituição'; ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel-card">
            <h2>Histórico de Distribuições</h2>
            <p class="helper">Acompanhe as saídas do estoque e marque a entrega quando o destino confirmar o recebimento.</p>

            <?php if (empty($distribuicoes)): ?>
                <p class="helper">Nenhuma distribuição registrada até o momento.</p>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($distribuicoes as $distribuicao): ?>
                        <article class="history-card">
                            <div class="history-top">
                                <div>
                                    <strong>#<?php echo (int)$distribuicao['id']; ?> - <?php echo htmlspecialchars($distribuicao['destino_nome']); ?></strong>
                                    <div class="muted">
                                        Campanha: <?php echo htmlspecialchars($distribuicao['campanha_nome']); ?>
                                    </div>
                                    <div class="muted">
                                        <?php echo htmlspecialchars($distribuicao['logradouro'] . ' - ' . $distribuicao['cidade'] . '/' . $distribuicao['estado']); ?>
                                    </div>
                                    <div class="muted">
                                        Envio em <?php echo date('d/m/Y', strtotime($distribuicao['data_envio'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge <?php echo htmlspecialchars($distribuicao['status']); ?>">
                                    <?php echo ['enviado' => 'Enviado', 'entregue' => 'Entregue'][strtolower((string)$distribuicao['status'])] ?? ucfirst(strtolower((string)$distribuicao['status'])); ?>
                                </span>
                            </div>

                            <ul class="history-items">
                                <?php foreach ($distribuicao['itens'] as $item): ?>
                                    <li><?php echo htmlspecialchars($item['nome']); ?> - Qtd: <?php echo (int)$item['quantidade']; ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($distribuicao['status'] === 'enviado'): ?>
                                <form method="POST" action="index.php?page=admin_distributions" class="inline-form">
                                    <input type="hidden" name="marcar_entregue" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
                                    <input type="hidden" name="distribuicao_id" value="<?php echo (int)$distribuicao['id']; ?>">
                                    <button type="submit">Marcar como Entregue</button>
                                </form>
                            <?php endif; ?>
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

<script>
const stockByPoint = <?php echo json_encode($estoquePorPonto, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function renderStockItems() {
    const pointId = document.getElementById('ponto_id').value;
    const preview = document.getElementById('stockPreview');
    const grid = document.getElementById('itemGrid');
    const items = stockByPoint[pointId] || [];

    preview.innerHTML = '';
    grid.innerHTML = '';

    if (!pointId) {
        preview.textContent = 'Selecione um ponto para visualizar os itens disponíveis em estoque.';
        grid.innerHTML = '<p class="helper">Escolha um ponto para liberar os itens disponíveis para seleção.</p>';
        return;
    }

    if (items.length === 0) {
        preview.textContent = 'Este ponto ainda não possui itens disponíveis em estoque.';
        grid.innerHTML = '<p class="helper">Não há itens disponíveis neste ponto.</p>';
        return;
    }

    const list = document.createElement('ul');
    items.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = `${item.nome}: ${item.quantidade} unidade(s)`;
        list.appendChild(li);
    });
    preview.appendChild(list);

    items.forEach((item) => {
        const card = document.createElement('div');
        card.className = 'item-card';

        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'itens[]';
        checkbox.value = String(item.id);

        label.appendChild(checkbox);
        label.appendChild(document.createTextNode(' ' + item.nome));

        const muted = document.createElement('div');
        muted.className = 'muted';
        muted.textContent = `Disponível: ${item.quantidade}`;

        const qty = document.createElement('input');
        qty.type = 'number';
        qty.name = `quantidades[${item.id}]`;
        qty.min = '1';
        qty.max = String(item.quantidade);
        qty.disabled = true;
        qty.placeholder = 'Quantidade';

        checkbox.addEventListener('change', function () {
            qty.disabled = !checkbox.checked;
            qty.required = checkbox.checked;
            if (checkbox.checked && !qty.value) {
                qty.value = '1';
            }
        });

        card.appendChild(label);
        card.appendChild(muted);
        card.appendChild(qty);
        grid.appendChild(card);
    });
}
</script>

</body>
</html>
