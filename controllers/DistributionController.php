<?php

require_once __DIR__ . '/../models/dao/DistributionDAO.php';
require_once __DIR__ . '/../models/dao/DestinationDAO.php';
require_once __DIR__ . '/../models/dao/InventoryDAO.php';
require_once __DIR__ . '/../models/dao/PointOfCollectionDAO.php';
require_once __DIR__ . '/../models/dao/CampaignDAO.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/SessionManager.php';

/**
 * Controller para gerenciar distribuições administrativas.
 */
class DistributionController {
    private $distributionDAO;
    private $destinationDAO;
    private $inventoryDAO;
    private $pointDAO;
    private $campaignDAO;

    public function __construct() {
        $this->distributionDAO = new DistributionDAO();
        $this->destinationDAO = new DestinationDAO();
        $this->inventoryDAO = new InventoryDAO();
        $this->pointDAO = new PointOfCollectionDAO();
        $this->campaignDAO = new CampaignDAO();
    }

    private function requireAdmin(): void {
        SessionManager::requireRole('admin');
    }

    private function normalizeText(string $value): string {
        $normalized = trim($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        if ($ascii !== false) {
            $normalized = $ascii;
        }

        $normalized = strtolower($normalized);

        // Remove separadores para comparações estáveis.
        return preg_replace('/[^a-z0-9]+/', '', $normalized) ?? '';
    }

    private function getAllowedStockPoints(): array {
        $allowedTerms = ['taguatinga', 'ceilandia'];
        $points = $this->pointDAO->findAll();

        return array_values(array_filter($points, function (array $point) use ($allowedTerms) {
            $haystacks = [
                $this->normalizeText((string)($point['nome'] ?? '')),
                $this->normalizeText((string)($point['cidade'] ?? '')),
                $this->normalizeText((string)($point['logradouro'] ?? '')),
            ];

            foreach ($haystacks as $haystack) {
                foreach ($allowedTerms as $term) {
                    if ($haystack !== '' && str_contains($haystack, $term)) {
                        return true;
                    }
                }
            }

            return false;
        }));
    }

    private function redirectWithMessage(string $mensagem, string $tipo = 'erro'): void {
        SessionManager::setMessage($mensagem, $tipo);
        header('Location: index.php?page=admin_distributions');
        exit;
    }

    private function validateCsrfOrRedirect(): void {
        if (SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            return;
        }

        $this->redirectWithMessage('Sua sessão expirou. Atualize a página e tente novamente.');
    }

    public function manage(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['cadastrar_destino'])) {
                $this->createDestination();
                return;
            }

            if (isset($_POST['alterar_status_destino']) || isset($_POST['excluir_destino'])) {
                $this->updateDestinationStatus();
                return;
            }

            if (isset($_POST['registrar_distribuicao'])) {
                $this->store();
                return;
            }

            if (isset($_POST['marcar_entregue'])) {
                $this->markDelivered();
                return;
            }
        }

        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';
        $pontos = $this->getAllowedStockPoints();
        $campanhas = $this->campaignDAO->findAllActive();
        $destinos = $this->destinationDAO->findAll(true);
        $instituicoes = $this->destinationDAO->findAll(false);
        $distribuicoes = $this->distributionDAO->findAll();
        $estoquePorPonto = [];

        foreach ($pontos as $ponto) {
            $estoquePorPonto[(int)$ponto['id']] = $this->inventoryDAO->getInventoryByPoint((int)$ponto['id']);
        }

        include __DIR__ . '/../views/admin/distributions.php';
    }

    public function createDestination(): void {
        $this->requireAdmin();
        $this->validateCsrfOrRedirect();

        $nome = trim((string)($_POST['novo_destino_nome'] ?? ''));
        $logradouro = trim((string)($_POST['novo_destino_logradouro'] ?? ''));
        $cidade = trim((string)($_POST['novo_destino_cidade'] ?? ''));
        $estado = strtoupper(trim((string)($_POST['novo_destino_estado'] ?? '')));
        $cep = trim((string)($_POST['novo_destino_cep'] ?? ''));

        if ($nome === '' || $logradouro === '' || $cidade === '' || $estado === '' || $cep === '') {
            $this->redirectWithMessage('Preencha todos os campos para cadastrar a instituição de caridade.');
        }

        $destinoExistente = $this->destinationDAO->findByIdentity($nome, $logradouro, $cidade, $estado, $cep);
        if ($destinoExistente !== null) {
            $destinoExistenteId = (int)($destinoExistente['id'] ?? 0);
            $destinoExistenteAtivo = ((int)($destinoExistente['ativo'] ?? 1)) === 1;

            if ($destinoExistenteAtivo) {
                $this->redirectWithMessage('Essa instituição já está cadastrada como destino ativo.');
            }

            if ($destinoExistenteId <= 0) {
                $this->redirectWithMessage('Não foi possível reativar a instituição informada.');
            }

            if (!$this->destinationDAO->activate($destinoExistenteId)) {
                $this->redirectWithMessage('Não foi possível reativar a instituição informada.');
            }

            $this->redirectWithMessage('Instituição reativada e liberada para novas distribuições.', 'sucesso');
        }

        try {
            Database::getInstance()->beginTransaction();

            $destinoId = $this->destinationDAO->create($nome, $logradouro, $cidade, $estado, $cep);
            if ($destinoId === null) {
                throw new Exception('Não foi possível cadastrar a instituição informada.');
            }

            Database::getInstance()->commit();
            $this->redirectWithMessage('Instituição de caridade cadastrada com sucesso.', 'sucesso');
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            $this->redirectWithMessage('Erro ao cadastrar instituição: ' . $e->getMessage());
        }
    }

    public function updateDestinationStatus(): void {
        $this->requireAdmin();
        $this->validateCsrfOrRedirect();

        $destinoId = (int)($_POST['destino_id'] ?? 0);
        if ($destinoId <= 0) {
            $this->redirectWithMessage('Instituição inválida.');
        }

        $novoStatus = strtolower(trim((string)($_POST['novo_status'] ?? '')));
        if ($novoStatus === '' && isset($_POST['excluir_destino'])) {
            // Compatibilidade com o nome antigo do formulário.
            $novoStatus = 'desativar';
        }

        if (!in_array($novoStatus, ['ativar', 'desativar'], true)) {
            $this->redirectWithMessage('Ação de status inválida para a instituição.');
        }

        $destino = $this->destinationDAO->findById($destinoId);
        if ($destino === null) {
            $this->redirectWithMessage('Instituição não encontrada.');
        }

        $estaAtivo = ((int)($destino['ativo'] ?? 1)) === 1;

        if ($novoStatus === 'ativar') {
            if ($estaAtivo) {
                $this->redirectWithMessage('A instituição já está ativa.', 'sucesso');
            }

            if (!$this->destinationDAO->activate($destinoId)) {
                $this->redirectWithMessage('Não foi possível ativar a instituição selecionada.');
            }

            $this->redirectWithMessage('Instituição ativada com sucesso.', 'sucesso');
        }

        if (!$estaAtivo) {
            $this->redirectWithMessage('A instituição já está inativa.', 'sucesso');
        }

        if (!$this->destinationDAO->deactivate($destinoId)) {
            $this->redirectWithMessage('Não foi possível desativar a instituição selecionada.');
        }

        $this->redirectWithMessage('Instituição desativada com sucesso.', 'sucesso');
    }

    public function store(): void {
        $this->requireAdmin();
        $this->validateCsrfOrRedirect();

        $pontoId = (int)($_POST['ponto_id'] ?? 0);
        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $destinoId = (int)($_POST['destino_id'] ?? 0);
        $dataEnvio = trim((string)($_POST['data_envio'] ?? date('Y-m-d')));
        $itensSelecionados = isset($_POST['itens']) ? (array)$_POST['itens'] : [];
        $quantidades = isset($_POST['quantidades']) ? (array)$_POST['quantidades'] : [];

        if ($pontoId <= 0) {
            $this->redirectWithMessage('Selecione um ponto de estoque para distribuir os itens.');
        }

        $allowedPontos = $this->getAllowedStockPoints();
        $allowedPointIds = array_map('intval', array_column($allowedPontos, 'id'));
        if (!in_array($pontoId, $allowedPointIds, true)) {
            $this->redirectWithMessage('O ponto de estoque selecionado não está disponível para distribuição.');
        }

        if ($campanhaId <= 0) {
            $this->redirectWithMessage('Selecione a campanha/cidade dessa distribuição.');
        }

        if ($destinoId <= 0) {
            $this->redirectWithMessage('Selecione a instituição de caridade cadastrada para a entrega.');
        }

        if (!$this->destinationDAO->existsActive($destinoId)) {
            $this->redirectWithMessage('A instituição selecionada não está disponível para novas entregas.');
        }

        if (empty($itensSelecionados)) {
            $this->redirectWithMessage('Selecione ao menos um item para distribuição.');
        }

        $estoqueDisponivel = [];
        foreach ($this->inventoryDAO->getInventoryByPoint($pontoId) as $item) {
            $estoqueDisponivel[(int)$item['id']] = [
                'nome' => (string)$item['nome'],
                'quantidade' => (int)$item['quantidade'],
            ];
        }

        $itens = [];
        foreach ($itensSelecionados as $categoriaId) {
            $categoriaId = (int)$categoriaId;
            $quantidade = isset($quantidades[$categoriaId]) ? (int)$quantidades[$categoriaId] : 0;

            if ($categoriaId <= 0 || $quantidade <= 0) {
                $this->redirectWithMessage('Informe quantidades válidas para cada item selecionado.');
            }

            if (!isset($estoqueDisponivel[$categoriaId])) {
                $this->redirectWithMessage('Um dos itens selecionados não existe no estoque do ponto informado.');
            }

            if ($quantidade > $estoqueDisponivel[$categoriaId]['quantidade']) {
                $this->redirectWithMessage('A quantidade solicitada excede o estoque disponível para um dos itens.');
            }

            $itens[$categoriaId] = $quantidade;
        }

        try {
            Database::getInstance()->beginTransaction();

            $this->distributionDAO->create($destinoId, $campanhaId, $dataEnvio, $itens);

            foreach ($itens as $categoriaId => $quantidade) {
                $this->inventoryDAO->removeItemFromPoint($pontoId, (int)$categoriaId, (int)$quantidade);
            }

            Database::getInstance()->commit();
            $this->redirectWithMessage('Distribuição registrada com sucesso e estoque atualizado.', 'sucesso');
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            $this->redirectWithMessage('Erro ao registrar distribuição: ' . $e->getMessage());
        }
    }

    public function markDelivered(): void {
        $this->requireAdmin();
        $this->validateCsrfOrRedirect();

        $distribuicaoId = (int)($_POST['distribuicao_id'] ?? 0);
        if ($distribuicaoId <= 0) {
            $this->redirectWithMessage('Distribuição inválida.');
        }

        if ($this->distributionDAO->updateStatus($distribuicaoId, 'ENTREGUE')) {
            $this->redirectWithMessage('Distribuição marcada como entregue.', 'sucesso');
        }

        $this->redirectWithMessage('Não foi possível atualizar o status da distribuição.');
    }
}
