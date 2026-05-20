<?php

require_once __DIR__ . '/../models/dao/DonationDAO.php';
require_once __DIR__ . '/../models/dao/InventoryDAO.php';
require_once __DIR__ . '/../models/dao/CampaignDAO.php';
require_once __DIR__ . '/../models/dao/ItemDAO.php';
require_once __DIR__ . '/../models/dao/PointOfCollectionDAO.php';
require_once __DIR__ . '/../models/dao/UserDAO.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/SessionManager.php';

/**
 * Controller para gerenciar operações administrativas.
 * Compatível com o schema atual: doação, estoque, item_estoque.
 */
class AdminController {
    private const PROTECTED_ADMIN_EMAIL = 'admin@conectasolidaria.local';

    private $donationDAO;
    private $inventoryDAO;
    private $campaignDAO;
    private $itemDAO;
    private $pointDAO;
    private $userDAO;

    public function __construct() {
        $this->donationDAO = new DonationDAO();
        $this->inventoryDAO = new InventoryDAO();
        $this->campaignDAO = new CampaignDAO();
        $this->itemDAO = new ItemDAO();
        $this->pointDAO = new PointOfCollectionDAO();
        $this->userDAO = new UserDAO();
    }

    /**
     * Verifica permissão de admin
     */
    private function requireAdmin(): void {
        SessionManager::requireRole('admin');
    }

    private function redirectToCollectionPointsWithMessage(string $mensagem, string $tipo = 'erro'): void {
        SessionManager::setMessage($mensagem, $tipo);
        header('Location: index.php?page=admin_collection_points');
        exit;
    }

    /**
     * Define se o usuário é o administrador principal protegido do sistema.
     */
    private function isProtectedSystemAdmin($usuario): bool {
        if (!$usuario || !isset($usuario->email)) {
            return false;
        }

        return strcasecmp(trim((string)$usuario->email), self::PROTECTED_ADMIN_EMAIL) === 0;
    }

    /**
     * Retorna diretório absoluto das imagens de campanha.
     */
    private function getCampaignUploadDir(): string {
        return __DIR__ . '/../assets/uploads';
    }

    /**
     * Remove imagens já existentes da campanha.
     */
    private function clearCampaignImages(int $campanhaId): void {
        $files = glob($this->getCampaignUploadDir() . '/campaign_' . $campanhaId . '.*');

        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Valida o arquivo enviado para imagem de campanha.
     * @param array|null $arquivo
     * @return array
     */
    private function validateCampaignImageUpload(?array $arquivo): array {
        if (!$arquivo || !is_array($arquivo) || (int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'has_file' => false];
        }

        if ((int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Erro no envio da imagem. Tente novamente.'];
        }

        $tmpName = (string)($arquivo['tmp_name'] ?? '');
        $mime = $tmpName !== '' ? mime_content_type($tmpName) : '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'message' => 'Envie uma imagem JPG, PNG ou WEBP.'];
        }

        return [
            'ok' => true,
            'has_file' => true,
            'extension' => $allowed[$mime],
            'tmp_name' => $tmpName,
        ];
    }

    /**
     * Salva a imagem da campanha no padrão do sistema.
     * @param int $campanhaId
     * @param array $imageData
     * @return bool
     */
    private function saveCampaignImageUpload(int $campanhaId, array $imageData): bool {
        if (empty($imageData['has_file'])) {
            return true;
        }

        $uploadDir = $this->getCampaignUploadDir();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            return false;
        }

        $this->clearCampaignImages($campanhaId);
        $destination = $uploadDir . '/campaign_' . $campanhaId . '.' . $imageData['extension'];

        return move_uploaded_file((string)$imageData['tmp_name'], $destination);
    }

    /**
     * Renderiza painel de gerenciamento de doações
     */
    public function manageDonations(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receber_doacao'])) {
            $this->receiveDonation();
            return;
        }

        $filtro = isset($_GET['status']) ? $_GET['status'] : 'todos';
        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

        $doacoes = $this->donationDAO->findAll($filtro, $busca);
        $usuariosDisponiveis = $this->userDAO->findByType('doador');
        $admins = $this->userDAO->findByType('admin');
        $usuariosCadastrados = $this->userDAO->findAll();
        $protectedAdminEmail = self::PROTECTED_ADMIN_EMAIL;
        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';

        include __DIR__ . '/../views/admin/donations.php';
    }

    /**
     * Recebe doação e adiciona ao inventário
     */
    public function receiveDonation(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receber_doacao'])) {
            if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
                SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
                header('Location: index.php?page=admin_donations');
                exit;
            }

            $doacao_id = (int)($_POST['doacao_id'] ?? 0);
            $codigoDoacao = trim((string)($_POST['codigo_doacao'] ?? ''));

            if ($doacao_id <= 0 && $codigoDoacao !== '') {
                $doacao = $this->donationDAO->findByPublicCode($codigoDoacao);
                $doacao_id = $doacao ? (int)$doacao->id : 0;
            }

            if ($doacao_id <= 0) {
                SessionManager::setMessage('Doação inválida. Confira o código informado.', 'erro');
            } else {
                try {
                    Database::getInstance()->beginTransaction();

                    $doacao = $this->donationDAO->findById($doacao_id);
                    if (!$doacao) {
                        throw new Exception('Doação não encontrada.');
                    }

                    if ($doacao->status === 'recebida') {
                        throw new Exception('Esta doação já foi recebida.');
                    }

                    if ((int)$doacao->ponto_id <= 0) {
                        throw new Exception('Doação sem ponto de coleta definido.');
                    }

                    $estoque_id = $this->inventoryDAO->getOrCreateEstoque((int)$doacao->ponto_id);

                    if (!$this->donationDAO->updateStatus($doacao_id, 'RECEBIDA')) {
                        throw new Exception('Erro ao atualizar status da doação.');
                    }

                    $items = $this->donationDAO->getItems($doacao_id);
                    foreach ($items as $item) {
                        $this->inventoryDAO->addOrUpdateItem(
                            $estoque_id,
                            (int)$item['categoria_id'],
                            (int)$item['quantidade']
                        );
                    }

                    Database::getInstance()->commit();
                    SessionManager::setMessage('Doação recebida e adicionada ao estoque com sucesso!', 'sucesso');
                } catch (Exception $e) {
                    Database::getInstance()->rollback();
                    SessionManager::setMessage('Erro: ' . $e->getMessage(), 'erro');
                }
            }
        }

        header('Location: index.php?page=admin_donations');
        exit;
    }

    /**
     * Renderiza visualização de estoque
     */
    public function viewInventory(): void {
        $this->requireAdmin();

        $estoquesAgrupados = $this->inventoryDAO->getInventoryByLocation();
        include __DIR__ . '/../views/admin/inventory.php';
    }

    /**
     * Renderiza o gerenciamento de pontos de coleta.
     */
    public function manageCollectionPoints(): void {
        $this->requireAdmin();

        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';
        $pontosColeta = $this->pointDAO->findAll(false);

        include __DIR__ . '/../views/admin/collection_points.php';
    }

    /**
     * Cadastra um novo ponto de coleta.
     */
    public function createCollectionPoint(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_collection_points');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            $this->redirectToCollectionPointsWithMessage('Sua sessão expirou. Atualize a página e tente novamente.');
        }

        $nome = trim((string)($_POST['novo_ponto_nome'] ?? ''));
        $logradouro = trim((string)($_POST['novo_ponto_logradouro'] ?? ''));
        $cidade = trim((string)($_POST['novo_ponto_cidade'] ?? ''));
        $estado = strtoupper(trim((string)($_POST['novo_ponto_estado'] ?? '')));
        $cep = trim((string)($_POST['novo_ponto_cep'] ?? ''));

        if ($nome === '' || $logradouro === '' || $cidade === '' || $estado === '' || $cep === '') {
            $this->redirectToCollectionPointsWithMessage('Preencha todos os campos para cadastrar o ponto de coleta.');
        }

        $pontoExistente = $this->pointDAO->findByIdentity($nome, $logradouro, $cidade, $estado, $cep);
        if ($pontoExistente !== null) {
            $pontoExistenteId = (int)($pontoExistente['id'] ?? 0);
            $pontoExistenteAtivo = ((int)($pontoExistente['ativo'] ?? 1)) === 1;

            if ($pontoExistenteAtivo) {
                $this->redirectToCollectionPointsWithMessage('Esse ponto já está cadastrado como ativo.');
            }

            if ($pontoExistenteId <= 0 || !$this->pointDAO->activate($pontoExistenteId)) {
                $this->redirectToCollectionPointsWithMessage('Não foi possível reativar o ponto informado.');
            }

            $this->redirectToCollectionPointsWithMessage('Ponto reativado com sucesso.', 'sucesso');
        }

        try {
            Database::getInstance()->beginTransaction();

            $pointId = $this->pointDAO->create($nome, $logradouro, $cidade, $estado, $cep);
            if ($pointId === null) {
                throw new Exception('Não foi possível cadastrar o ponto informado.');
            }

            Database::getInstance()->commit();
            $this->redirectToCollectionPointsWithMessage('Ponto de coleta cadastrado com sucesso.', 'sucesso');
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            $this->redirectToCollectionPointsWithMessage('Erro ao cadastrar ponto: ' . $e->getMessage());
        }
    }

    /**
     * Ativa ou desativa um ponto de coleta cadastrado.
     */
    public function toggleCollectionPointStatus(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_collection_points');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            $this->redirectToCollectionPointsWithMessage('Sua sessão expirou. Atualize a página e tente novamente.');
        }

        $pointId = (int)($_POST['ponto_id'] ?? 0);
        if ($pointId <= 0) {
            $this->redirectToCollectionPointsWithMessage('Ponto de coleta inválido.');
        }

        $novoStatus = strtolower(trim((string)($_POST['novo_status'] ?? '')));
        if (!in_array($novoStatus, ['ativar', 'desativar'], true)) {
            $this->redirectToCollectionPointsWithMessage('Ação de status inválida para o ponto de coleta.');
        }

        $ponto = $this->pointDAO->findById($pointId);
        if ($ponto === null) {
            $this->redirectToCollectionPointsWithMessage('Ponto de coleta não encontrado.');
        }

        $estaAtivo = ((int)($ponto['ativo'] ?? 1)) === 1;

        if ($novoStatus === 'ativar') {
            if ($estaAtivo) {
                $this->redirectToCollectionPointsWithMessage('O ponto de coleta já está ativo.', 'sucesso');
            }

            if (!$this->pointDAO->activate($pointId)) {
                $this->redirectToCollectionPointsWithMessage('Não foi possível ativar o ponto selecionado.');
            }

            $this->redirectToCollectionPointsWithMessage('Ponto de coleta ativado com sucesso.', 'sucesso');
        }

        if (!$estaAtivo) {
            $this->redirectToCollectionPointsWithMessage('O ponto de coleta já está inativo.', 'sucesso');
        }

        if (!$this->pointDAO->deactivate($pointId)) {
            $this->redirectToCollectionPointsWithMessage('Não foi possível desativar o ponto selecionado.');
        }

        $this->redirectToCollectionPointsWithMessage('Ponto de coleta desativado com sucesso.', 'sucesso');
    }

    /**
     * Renderiza gestão de cards de campanha e permissões de admin.
     */
    public function manageCampaignCards(): void {
        $this->requireAdmin();

        $campanhas = $this->campaignDAO->findAll();
        $categorias = $this->itemDAO->findAll();

        $selectedCampaignId = isset($_GET['campanha_id']) ? (int)$_GET['campanha_id'] : 0;
        if ($selectedCampaignId <= 0 && !empty($campanhas)) {
            $selectedCampaignId = (int)$campanhas[0]->id;
        }

        $selectedCampaign = null;
        $necessidades = [];
        if ($selectedCampaignId > 0) {
            $selectedCampaign = $this->campaignDAO->findById($selectedCampaignId);
            if ($selectedCampaign) {
                $necessidades = $this->campaignDAO->getNecessidades($selectedCampaignId);
            }
        }

        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';

        include __DIR__ . '/../views/admin/campaign_cards.php';
    }

    /**
     * Cadastra uma nova cidade como campanha ativa.
     */
    public function createCampaign(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $categoriasCreate = $_POST['categoria_id_create'] ?? null;
        if (is_array($categoriasCreate)) {
            $titulo = trim((string)($_POST['titulo'] ?? ''));
            $descricao = trim((string)($_POST['descricao_campanha'] ?? ''));
            $usuarioId = (int)(SessionManager::getUserId() ?? 0);
            $descricoesItens = $_POST['descricao_item_create'] ?? [];
            $quantidades = $_POST['quantidade_item_create'] ?? [];
            $imageData = $this->validateCampaignImageUpload($_FILES['imagem_campanha_create'] ?? null);

            if ($titulo === '' || $usuarioId <= 0) {
                SessionManager::setMessage('Informe o nome da cidade para criar a campanha.', 'erro');
                header('Location: index.php?page=admin_campaign_cards');
                exit;
            }

            if (!$imageData['ok']) {
                SessionManager::setMessage((string)$imageData['message'], 'erro');
                header('Location: index.php?page=admin_campaign_cards');
                exit;
            }

            if ($descricao === '') {
                $descricao = 'Campanha emergencial para apoio às famílias afetadas em ' . $titulo . '.';
            }

            if (!is_array($descricoesItens) || !is_array($quantidades)) {
                SessionManager::setMessage('Informe pelo menos uma necessidade para a nova campanha.', 'erro');
                header('Location: index.php?page=admin_campaign_cards');
                exit;
            }

            $necessidades = [];
            $categoriasUsadas = [];

            foreach ($categoriasCreate as $index => $categoriaId) {
                $categoriaId = (int)$categoriaId;
                $descricaoItem = trim((string)($descricoesItens[$index] ?? ''));
                $quantidade = (int)($quantidades[$index] ?? 0);

                if ($categoriaId <= 0 && $descricaoItem === '' && $quantidade <= 0) {
                    continue;
                }

                if ($categoriaId <= 0 || $descricaoItem === '' || $quantidade <= 0) {
                    SessionManager::setMessage('Preencha corretamente todas as necessidades da nova campanha.', 'erro');
                    header('Location: index.php?page=admin_campaign_cards');
                    exit;
                }

                if (isset($categoriasUsadas[$categoriaId])) {
                    SessionManager::setMessage('Não repita a mesma categoria ao criar a campanha.', 'erro');
                    header('Location: index.php?page=admin_campaign_cards');
                    exit;
                }

                $categoriasUsadas[$categoriaId] = true;
                $necessidades[] = [
                    'categoria_id' => $categoriaId,
                    'descricao' => $descricaoItem,
                    'quantidade' => $quantidade,
                ];
            }

            if (empty($necessidades)) {
                SessionManager::setMessage('Cadastre pelo menos uma necessidade na nova campanha.', 'erro');
                header('Location: index.php?page=admin_campaign_cards');
                exit;
            }

            try {
                Database::getInstance()->beginTransaction();
                $novaCampanhaId = $this->campaignDAO->createActiveCampaign($titulo, $descricao, $usuarioId);

                if ($novaCampanhaId === null) {
                    throw new Exception('Não foi possível criar a nova campanha.');
                }

                foreach ($necessidades as $necessidade) {
                    $created = $this->campaignDAO->createNecessidade(
                        (int)$novaCampanhaId,
                        (int)$necessidade['categoria_id'],
                        (string)$necessidade['descricao'],
                        (int)$necessidade['quantidade']
                    );

                    if (!$created) {
                        throw new Exception('Não foi possível cadastrar todas as necessidades da campanha.');
                    }
                }

                Database::getInstance()->commit();
            } catch (Exception $e) {
                Database::getInstance()->rollback();
                SessionManager::setMessage($e->getMessage(), 'erro');
                header('Location: index.php?page=admin_campaign_cards');
                exit;
            }

            if (!$this->saveCampaignImageUpload((int)$novaCampanhaId, $imageData)) {
                SessionManager::setMessage('Campanha criada, mas não foi possível salvar a imagem.', 'erro');
                header('Location: index.php?page=admin_campaign_cards&campanha_id=' . (int)$novaCampanhaId);
                exit;
            }

            SessionManager::setMessage('Nova cidade/campanha ativa cadastrada com sucesso.', 'sucesso');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . (int)$novaCampanhaId);
            exit;
        }

        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $descricao = trim((string)($_POST['descricao_campanha'] ?? ''));
        $usuarioId = (int)(SessionManager::getUserId() ?? 0);

        if ($titulo === '' || $usuarioId <= 0) {
            SessionManager::setMessage('Informe o nome da cidade para criar a campanha.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if ($descricao === '') {
            $descricao = 'Campanha emergencial para apoio às famílias afetadas em ' . $titulo . '.';
        }

        $allowDuplicate = isset($_POST['allow_duplicate_city']) && $_POST['allow_duplicate_city'] === '1';
        $campanhaExistente = $this->campaignDAO->findByTitle($titulo);
        if (!$allowDuplicate && $campanhaExistente) {
            SessionManager::setMessage('Já existe uma campanha ativa cadastrada para essa cidade.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . (int)$campanhaExistente->id);
            exit;
        }

        $novaCampanhaId = $this->campaignDAO->createActiveCampaign($titulo, $descricao, $usuarioId);
        if ($novaCampanhaId !== null) {
            SessionManager::setMessage('Nova cidade/campanha ativa cadastrada com sucesso.', 'sucesso');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $novaCampanhaId);
            exit;
        }

        SessionManager::setMessage('Não foi possível criar a nova campanha.', 'erro');
        header('Location: index.php?page=admin_campaign_cards');
        exit;
    }

    /**
     * Exclui um card de necessidade da campanha.
     */
    public function deleteCampaignCard(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $necessidadeId = (int)($_POST['necessidade_id'] ?? 0);

        if ($campanhaId <= 0 || $necessidadeId <= 0) {
            SessionManager::setMessage('Card inválido para exclusão.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        try {
            if ($this->campaignDAO->deleteNecessidade($necessidadeId, $campanhaId)) {
            SessionManager::setMessage('Card removido com sucesso.', 'sucesso');
            } else {
            SessionManager::setMessage('Não foi possível remover o card da campanha.', 'erro');
            }
        } catch (Throwable $e) {
            SessionManager::setMessage('Não foi possível remover o item da campanha.', 'erro');
        }

        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Faz upload da imagem padrão da campanha.
     */
    public function uploadCampaignImage(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        if ($campanhaId <= 0) {
            SessionManager::setMessage('Selecione uma campanha válida para enviar a imagem.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!isset($_FILES['imagem_campanha']) || !is_array($_FILES['imagem_campanha'])) {
            SessionManager::setMessage('Nenhuma imagem foi enviada.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $arquivo = $_FILES['imagem_campanha'];
        if ((int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            SessionManager::setMessage('Erro no envio da imagem. Tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $tmpName = (string)($arquivo['tmp_name'] ?? '');
        $mime = $tmpName !== '' ? mime_content_type($tmpName) : '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            SessionManager::setMessage('Envie uma imagem JPG, PNG ou WEBP.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $uploadDir = $this->getCampaignUploadDir();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            SessionManager::setMessage('Não foi possível preparar a pasta da imagem.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $this->clearCampaignImages($campanhaId);
        $destination = $uploadDir . '/campaign_' . $campanhaId . '.' . $allowed[$mime];

        if (!move_uploaded_file($tmpName, $destination)) {
            SessionManager::setMessage('Não foi possível salvar a imagem da campanha.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        SessionManager::setMessage('Imagem da campanha atualizada com sucesso.', 'sucesso');
        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }
    /**
     * Encerra a campanha sem apagar o histórico.
     */
    public function closeCampaign(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        if ($campanhaId <= 0) {
            SessionManager::setMessage('Campanha inválida para encerramento.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if ($this->campaignDAO->closeCampaign($campanhaId)) {
            SessionManager::setMessage('Campanha encerrada com sucesso.', 'sucesso');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        SessionManager::setMessage('Não foi possível encerrar a campanha.', 'erro');
        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Reabre uma campanha encerrada.
     */
    public function reopenCampaign(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        if ($campanhaId <= 0) {
            SessionManager::setMessage('Campanha inválida para reabertura.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if ($this->campaignDAO->reopenCampaign($campanhaId)) {
            SessionManager::setMessage('Campanha reaberta com sucesso.', 'sucesso');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        SessionManager::setMessage('Não foi possível reabrir a campanha.', 'erro');
        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Atualiza o nome de uma campanha.
     */
    public function renameCampaign(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $novoTitulo = trim((string)($_POST['titulo'] ?? ''));

        if ($campanhaId <= 0 || $novoTitulo === '') {
            SessionManager::setMessage('Informe corretamente a campanha e o novo nome.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $campanha = $this->campaignDAO->findById($campanhaId);
        if (!$campanha) {
            SessionManager::setMessage('Campanha não encontrada.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (strcasecmp(trim((string)$campanha->titulo), $novoTitulo) !== 0) {
            $campanhaExistente = $this->campaignDAO->findByTitle($novoTitulo);
            if ($campanhaExistente && (int)$campanhaExistente->id !== $campanhaId) {
                SessionManager::setMessage('Já existe uma campanha ativa com esse nome.', 'erro');
                header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
                exit;
            }
        }

        if ($this->campaignDAO->renameCampaign($campanhaId, $novoTitulo)) {
            SessionManager::setMessage('Nome da campanha atualizado com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível atualizar o nome da campanha.', 'erro');
        }

        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Cadastra um novo card/necessidade para uma campanha.
     */
    public function addCampaignCard(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        $quantidade = (int)($_POST['quantidade_necessaria'] ?? 0);

        if ($campanhaId <= 0 || $categoriaId <= 0 || $descricao === '' || $quantidade <= 0) {
            SessionManager::setMessage('Preencha campanha, categoria, descrição e quantidade corretamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        $campanha = $this->campaignDAO->findById($campanhaId);
        if (!$campanha) {
            SessionManager::setMessage('Selecione uma campanha válida.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if ($this->campaignDAO->necessidadeExists($campanhaId, $categoriaId)) {
            SessionManager::setMessage('Essa categoria já possui um card cadastrado para a campanha selecionada.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        if ($this->campaignDAO->createNecessidade($campanhaId, $categoriaId, $descricao, $quantidade)) {
            SessionManager::setMessage('Card da campanha cadastrado com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível cadastrar o card da campanha.', 'erro');
        }

        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Atualiza um card/necessidade existente da campanha.
     */
    public function updateCampaignCard(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        $campanhaId = (int)($_POST['campanha_id'] ?? 0);
        $necessidadeId = (int)($_POST['necessidade_id'] ?? 0);
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        $quantidade = (int)($_POST['quantidade_necessaria'] ?? 0);

        if ($campanhaId <= 0 || $necessidadeId <= 0 || $descricao === '' || $quantidade <= 0) {
            SessionManager::setMessage('Preencha descrição e quantidade corretamente.', 'erro');
            header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
            exit;
        }

        if (!$this->campaignDAO->findById($campanhaId)) {
            SessionManager::setMessage('Campanha não encontrada.', 'erro');
            header('Location: index.php?page=admin_campaign_cards');
            exit;
        }

        if ($this->campaignDAO->updateNecessidade($necessidadeId, $campanhaId, $descricao, $quantidade)) {
            SessionManager::setMessage('Item da campanha atualizado com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível atualizar o item da campanha.', 'erro');
        }

        header('Location: index.php?page=admin_campaign_cards&campanha_id=' . $campanhaId);
        exit;
    }

    /**
     * Promove um usuário cadastrado para administrador.
     */
    public function promoteUser(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            SessionManager::setMessage('Selecione um usuário válido para promover.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuario = $this->userDAO->findById($usuarioId);
        if (!$usuario) {
            SessionManager::setMessage('Usuário não encontrado.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($usuario->perfil_nome === 'admin') {
            SessionManager::setMessage('Esse usuário já é administrador.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($this->userDAO->promoteToAdmin($usuarioId)) {
            SessionManager::setMessage('Usuário promovido para administrador com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível promover o usuário para administrador.', 'erro');
        }

        header('Location: index.php?page=admin_donations');
        exit;
    }

    /**
     * Rebaixa um administrador para perfil de usuário (doador).
     */
    public function demoteUser(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            SessionManager::setMessage('Selecione um administrador válido para remover o acesso.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($usuarioId === (int)(SessionManager::getUserId() ?? 0)) {
            SessionManager::setMessage('Você não pode remover o seu próprio acesso de administrador.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuario = $this->userDAO->findById($usuarioId);
        if (!$usuario) {
            SessionManager::setMessage('Usuário não encontrado.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($usuario->perfil_nome !== 'admin') {
            SessionManager::setMessage('Esse usuário já não possui perfil de administrador.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($this->isProtectedSystemAdmin($usuario)) {
            SessionManager::setMessage('O administrador principal não pode perder o acesso de admin.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($this->userDAO->demoteToDoador($usuarioId)) {
            SessionManager::setMessage('Administrador rebaixado para usuário com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível remover o acesso de administrador deste usuário.', 'erro');
        }

        header('Location: index.php?page=admin_donations');
        exit;
    }

    /**
     * Ativa ou desativa um usuário cadastrado.
     */
    public function toggleUserStatus(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            SessionManager::setMessage('Selecione um usuário válido para alterar o status.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($usuarioId === (int)(SessionManager::getUserId() ?? 0)) {
            SessionManager::setMessage('Você não pode alterar o status do usuário que está logado.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $usuario = $this->userDAO->findById($usuarioId);
        if (!$usuario) {
            SessionManager::setMessage('Usuário não encontrado.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        $novoStatus = !$usuario->ativo;

        if ($this->isProtectedSystemAdmin($usuario) && !$novoStatus) {
            SessionManager::setMessage('O administrador principal não pode ser desativado.', 'erro');
            header('Location: index.php?page=admin_donations');
            exit;
        }

        if ($this->userDAO->updateActiveStatus($usuarioId, $novoStatus)) {
            $acao = $novoStatus ? 'ativado' : 'desativado';
            SessionManager::setMessage('Usuário ' . $acao . ' com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível atualizar o status do usuário.', 'erro');
        }

        header('Location: index.php?page=admin_donations');
        exit;
    }
}



