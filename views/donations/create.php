<?php
// View: Donation Create
// Renderizada por DonationController::createForm() e store()
SessionManager::requireLogin();

$selectedCampaignId = isset($selectedCampaignId) ? (int)$selectedCampaignId : 0;
$selectedPointId = isset($selectedPointId) ? (int)$selectedPointId : 0;
$campaignItemsMap = isset($campaignItemsMap) && is_array($campaignItemsMap) ? $campaignItemsMap : [];
$itensSelecionadosOld = isset($itensSelecionadosOld) && is_array($itensSelecionadosOld) ? array_map('intval', $itensSelecionadosOld) : [];
$quantidadesOld = isset($quantidadesOld) && is_array($quantidadesOld) ? $quantidadesOld : [];
$pontosColeta = isset($pontosColeta) && is_array($pontosColeta) ? $pontosColeta : [];
$descricaoOld = isset($descricaoOld) ? (string)$descricaoOld : ((string)($_POST['descricao'] ?? ''));
$formAction = isset($formAction) ? (string)$formAction : 'donation_store';
$submitLabel = isset($submitLabel) ? (string)$submitLabel : 'Registrar Doação';
$pageTitle = isset($pageTitle) ? (string)$pageTitle : 'Registrar Doação';
$editingDonationId = isset($editingDonationId) ? (int)$editingDonationId : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Doação - ConectaSolidária</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-main {
            max-width: 900px;
            margin: 30px auto;
        }

        .necessidades-box {
            margin: 20px 0;
            padding: 18px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            margin-bottom: 30px;
        }

        .necessidades-box h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #1e3a8a;
        }

        .necessidades-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .necessidades-box li {
            margin-bottom: 10px;
            color: #1f2937;
        }

        .itens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .item-doacao {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        .item-doacao label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }

        .obs-item {
            margin-top: 6px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.35;
        }

        .item-doacao input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }

        .item-doacao input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .mensagem {
            margin-bottom: 15px;
            font-weight: bold;
            padding: 12px;
            border-radius: 8px;
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

        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .form-actions button, .form-actions a {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .form-actions button {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
        }

        .form-actions button:hover {
            filter: brightness(1.1);
        }

        .form-actions a {
            background: #e5e7eb;
            color: #111827;
        }

        .form-actions a:hover {
            background: #d1d5db;
        }

        .field-gap-top {
            margin-top: 20px;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .itens-placeholder {
            color: #6b7280;
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="form-main">
        <h2>Registrar Doação</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($campaigns)): ?>
            <div class="mensagem erro">
                Não há campanhas ativas no momento. Aguarde a ativação de uma campanha para doar.
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=<?php echo htmlspecialchars($formAction); ?>" id="formDoacao">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
            <?php if ($editingDonationId > 0): ?>
                <input type="hidden" name="doacao_id" value="<?php echo $editingDonationId; ?>">
            <?php endif; ?>

            <label for="campanha_id">Selecione uma Campanha *</label>
            <select name="campanha_id" id="campanha_id" required onchange="renderItensPorCampanha()">
                <option value="">-- Escolha uma campanha --</option>
                <?php foreach ($campaigns as $campaign): ?>
                    <option value="<?php echo (int)$campaign->id; ?>" <?php echo $selectedCampaignId === (int)$campaign->id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($campaign->titulo); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="ponto_id" class="field-gap-top">Escolha o ponto de coleta *</label>
            <select name="ponto_id" id="ponto_id" required>
                <option value="">-- Escolha o ponto de coleta --</option>
                <?php foreach ($pontosColeta as $ponto): ?>
                    <option value="<?php echo (int)$ponto['id']; ?>" <?php echo $selectedPointId === (int)$ponto['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ponto['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="necessidadesContainer"></div>

            <label for="descricao">Descrição (opcional)</label>
            <textarea name="descricao" id="descricao" placeholder="Adicione observações sobre sua doação..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>

            <h3 class="section-title">Itens para Doação</h3>
            <div class="itens-grid" id="itensContainer"></div>

            <div class="form-actions">
                <button type="submit">Registrar Doação</button>
                <a href="index.php?page=dashboard">Cancelar</a>
            </div>
        </form>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

<script>
const campaignItemsMap = <?php echo json_encode($campaignItemsMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const oldSelectedItems = <?php echo json_encode($itensSelecionadosOld); ?>;
const oldQuantities = <?php echo json_encode($quantidadesOld, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const formTitle = <?php echo json_encode($pageTitle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const submitLabel = <?php echo json_encode($submitLabel, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const descricaoOld = <?php echo json_encode($descricaoOld, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function createItemCard(item) {
    const id = parseInt(item.id, 10);
    const checked = oldSelectedItems.includes(id);
    const oldQty = parseInt(oldQuantities[id] ?? oldQuantities[String(id)] ?? 1, 10);
    const quantity = Number.isNaN(oldQty) || oldQty <= 0 ? 1 : oldQty;

    const wrapper = document.createElement('div');
    wrapper.className = 'item-doacao';

    const label = document.createElement('label');
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.name = 'itens[]';
    checkbox.value = String(id);
    checkbox.checked = checked;

    const text = document.createTextNode(item.nome);
    label.appendChild(checkbox);
    label.appendChild(text);

    const qty = document.createElement('input');
    qty.type = 'number';
    qty.min = '1';
    qty.step = '1';
    qty.name = `quantidades[${id}]`;
    qty.value = String(quantity);
    qty.required = checked;
    qty.disabled = !checked;

    checkbox.addEventListener('change', function () {
        qty.disabled = !checkbox.checked;
        qty.required = checkbox.checked;
        if (checkbox.checked && (!qty.value || Number(qty.value) <= 0)) {
            qty.value = '1';
        }
    });

    wrapper.appendChild(label);
    if (item.observacao) {
        const obs = document.createElement('p');
        obs.className = 'obs-item';
        obs.textContent = `Obs: ${item.observacao}`;
        wrapper.appendChild(obs);
    }
    wrapper.appendChild(qty);
    return wrapper;
}

function renderItensPorCampanha() {
    const select = document.getElementById('campanha_id');
    const container = document.getElementById('itensContainer');
    const necessidades = document.getElementById('necessidadesContainer');
    const campanhaId = select.value;

    container.innerHTML = '';
    necessidades.innerHTML = '';

    if (!campanhaId) {
        container.innerHTML = '<p class="itens-placeholder">Selecione uma campanha para ver os itens necessários.</p>';
        return;
    }

    const itens = campaignItemsMap[campanhaId] || [];
    if (itens.length === 0) {
        necessidades.innerHTML = '<div class="necessidades-box"><h3>Necessidades da Campanha</h3><p>Esta campanha não possui necessidades cadastradas.</p></div>';
        container.innerHTML = '<p class="itens-placeholder">Nenhum item disponível para esta campanha.</p>';
        return;
    }

    const ul = document.createElement('ul');
    itens.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = item.observacao ? `${item.nome} - ${item.observacao}` : item.nome;
        ul.appendChild(li);
        container.appendChild(createItemCard(item));
    });

    const box = document.createElement('div');
    box.className = 'necessidades-box';
    box.innerHTML = '<h3>Necessidades da Campanha</h3>';
    box.appendChild(ul);
    necessidades.appendChild(box);
}

window.addEventListener('load', function () {
    const title = document.querySelector('.form-main h2');
    if (title) {
        title.textContent = formTitle;
    }

    const submitButton = document.querySelector('#formDoacao button[type="submit"]');
    if (submitButton) {
        submitButton.textContent = submitLabel;
    }

    const descricao = document.getElementById('descricao');
    if (descricao && descricao.value === '') {
        descricao.value = descricaoOld;
    }

    renderItensPorCampanha();
});
</script>

</body>
</html>
