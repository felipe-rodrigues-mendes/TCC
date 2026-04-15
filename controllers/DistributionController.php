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

        // Remove separadores e artefatos de transliteracao para comparações estáveis.
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

    public function manage(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $destinos = $this->destinationDAO->findAll();
        $distribuicoes = $this->distributionDAO->findAll();
        $estoquePorPonto = [];

        foreach ($pontos as $ponto) {
            $estoquePorPonto[(int)$ponto['id']] = $this->inventoryDAO->getInventoryByPoint((int)$ponto['id']);
        }

        include __DIR__ . '/../views/admin/distributions.php';
    }

    public function store(): void {
        $this->requireAdmin();

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        $pontoId = (int)($_POST['ponto_id'] ?? 0);
        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $destinoExistenteId = (int)($_POST['destino_id'] ?? 0);
        $novoDestinoNome = trim((string)($_POST['novo_destino_nome'] ?? ''));
        $novoDestinoLogradouro = trim((string)($_POST['novo_destino_logradouro'] ?? ''));
        $novoDestinoCidade = trim((string)($_POST['novo_destino_cidade'] ?? ''));
        $novoDestinoEstado = trim((string)($_POST['novo_destino_estado'] ?? ''));
        $novoDestinoCep = trim((string)($_POST['novo_destino_cep'] ?? ''));
        $dataEnvio = trim((string)($_POST['data_envio'] ?? date('Y-m-d')));
        $itensSelecionados = isset($_POST['itens']) ? (array)$_POST['itens'] : [];
        $quantidades = isset($_POST['quantidades']) ? (array)$_POST['quantidades'] : [];

        if ($pontoId <= 0) {
            SessionManager::setMessage('Selecione um ponto de estoque para distribuir os itens.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        $allowedPontos = $this->getAllowedStockPoints();
        $allowedPointIds = array_map('intval', array_column($allowedPontos, 'id'));
        if (!in_array($pontoId, $allowedPointIds, true)) {
            SessionManager::setMessage('O ponto de estoque selecionado não está disponível para distribuição.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        if ($campanhaId <= 0) {
            SessionManager::setMessage('Selecione a campanha/cidade dessa distribuição.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        if (empty($itensSelecionados)) {
            SessionManager::setMessage('Selecione ao menos um item para distribuição.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        $destinoId = $destinoExistenteId;
        $criandoNovoDestino = $destinoId <= 0 && $novoDestinoNome !== '';

        if ($destinoId <= 0 && !$criandoNovoDestino) {
            SessionManager::setMessage('Selecione um destino existente ou cadastre um novo destino.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        if ($criandoNovoDestino && ($novoDestinoLogradouro === '' || $novoDestinoCidade === '' || $novoDestinoEstado === '' || $novoDestinoCep === '')) {
            SessionManager::setMessage('Preencha todos os campos do novo destino para continuar.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
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
                SessionManager::setMessage('Informe quantidades válidas para cada item selecionado.', 'erro');
                header('Location: index.php?page=admin_distributions');
                exit;
            }

            if (!isset($estoqueDisponivel[$categoriaId])) {
                SessionManager::setMessage('Um dos itens selecionados não existe no estoque do ponto informado.', 'erro');
                header('Location: index.php?page=admin_distributions');
                exit;
            }

            if ($quantidade > $estoqueDisponivel[$categoriaId]['quantidade']) {
                SessionManager::setMessage('A quantidade solicitada excede o estoque disponível para um dos itens.', 'erro');
                header('Location: index.php?page=admin_distributions');
                exit;
            }

            $itens[$categoriaId] = $quantidade;
        }

        try {
            Database::getInstance()->beginTransaction();

            if ($criandoNovoDestino) {
                $destinoId = $this->destinationDAO->create(
                    $novoDestinoNome,
                    $novoDestinoLogradouro,
                    $novoDestinoCidade,
                    $novoDestinoEstado,
                    $novoDestinoCep
                );

                if ($destinoId === null) {
                    throw new Exception('Não foi possível cadastrar o destino informado.');
                }
            }

            $this->distributionDAO->create($destinoId, $campanhaId, $dataEnvio, $itens);

            foreach ($itens as $categoriaId => $quantidade) {
                $this->inventoryDAO->removeItemFromPoint($pontoId, (int)$categoriaId, (int)$quantidade);
            }

            Database::getInstance()->commit();
            SessionManager::setMessage('Distribuição registrada com sucesso e estoque atualizado.', 'sucesso');
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            SessionManager::setMessage('Erro ao registrar distribuição: ' . $e->getMessage(), 'erro');
        }

        header('Location: index.php?page=admin_distributions');
        exit;
    }

    public function markDelivered(): void {
        $this->requireAdmin();

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        $distribuicaoId = (int)($_POST['distribuicao_id'] ?? 0);
        if ($distribuicaoId <= 0) {
            SessionManager::setMessage('Distribuição inválida.', 'erro');
            header('Location: index.php?page=admin_distributions');
            exit;
        }

        if ($this->distributionDAO->updateStatus($distribuicaoId, 'ENTREGUE')) {
            SessionManager::setMessage('Distribuição marcada como entregue.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível atualizar o status da distribuição.', 'erro');
        }

        header('Location: index.php?page=admin_distributions');
        exit;
    }
}

