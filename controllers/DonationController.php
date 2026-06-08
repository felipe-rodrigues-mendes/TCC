<?php

require_once __DIR__ . '/../models/dao/DonationDAO.php';
require_once __DIR__ . '/../models/dao/CampaignDAO.php';
require_once __DIR__ . '/../models/dao/ItemDAO.php';
require_once __DIR__ . '/../models/dao/PointOfCollectionDAO.php';
require_once __DIR__ . '/../models/dao/UserDAO.php';
require_once __DIR__ . '/../models/dao/DistributionDAO.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../utils/SimplePdf.php';
require_once __DIR__ . '/../utils/qrcode.php';
require_once __DIR__ . '/SessionManager.php';

/**
 * Controller para gerenciar operações de doação.
 * Compatível com o schema atual: doação + item_doacao.
 */
class DonationController {
    private $donationDAO;
    private $campaignDAO;
    private $itemDAO;
    private $pointDAO;
    private $userDAO;
    private $distributionDAO;

    public function __construct() {
        $this->donationDAO = new DonationDAO();
        $this->campaignDAO = new CampaignDAO();
        $this->itemDAO = new ItemDAO();
        $this->pointDAO = new PointOfCollectionDAO();
        $this->userDAO = new UserDAO();
        $this->distributionDAO = new DistributionDAO();
    }

    private function getDonationPoints(): array {
        return $this->pointDAO->findAllNames(true);
    }

    private function getUserUploadDir(): string {
        return __DIR__ . '/../assets/uploads/users';
    }

    private function clearUserProfilePhotos(int $usuarioId, ?string $keepPath = null): void {
        $legacyFiles = glob($this->getUserUploadDir() . '/user_' . $usuarioId . '.*') ?: [];
        $versionedFiles = glob($this->getUserUploadDir() . '/user_' . $usuarioId . '_*.*') ?: [];
        $files = array_unique(array_merge($legacyFiles, $versionedFiles));

        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if ($keepPath !== null && realpath($file) === realpath($keepPath)) {
                continue;
            }

            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function formatDonationStatusLabel(string $status): string {
        return match (strtolower(trim($status))) {
            'recebida' => 'Recebida',
            'pendente' => 'Pendente',
            'excluida' => 'Excluída',
            default => ucfirst(strtolower(trim($status))),
        };
    }

    private function loadDonationFormData(): array {
        $campaigns = $this->campaignDAO->findAllActive();
        $pontosColeta = $this->getDonationPoints();
        $campaignItemsMap = [];

        foreach ($campaigns as $campaign) {
            $campaignItemsMap[(int)$campaign->id] = $this->itemDAO->findByCampaign((int)$campaign->id);
        }

        return [
            'campaigns' => $campaigns,
            'pontosColeta' => $pontosColeta,
            'campaignItemsMap' => $campaignItemsMap,
        ];
    }

    private function canUserManageDonation(?DonationDTO $doacao): bool {
        return $doacao !== null
            && $doacao->usuario_id === (int)(SessionManager::getUserId() ?? 0)
            && strtolower($doacao->status) === 'pendente';
    }

    /**
     * Renderiza formulário de criação de doação
     */
    public function createForm(): void {
        SessionManager::requireLogin('index.php?page=donation_create');

        $mensagem = "";
        $tipoMensagem = "";
        $dadosFormulario = $this->loadDonationFormData();
        $campaigns = $dadosFormulario['campaigns'];
        $selectedCampaignId = isset($_GET['campanha_id']) ? (int)$_GET['campanha_id'] : 0;
        $selectedPointId = 0;
        $campaignTitle = isset($_GET['campanha']) ? trim((string)$_GET['campanha']) : '';
        $itensSelecionadosOld = [];
        $quantidadesOld = [];
        $campaignItemsMap = $dadosFormulario['campaignItemsMap'];
        $pontosColeta = $dadosFormulario['pontosColeta'];
        $descricaoOld = '';
        $formAction = 'donation_store';
        $submitLabel = 'Registrar Doação';
        $pageTitle = 'Registrar Doação';
        $editingDonationId = 0;

        if ($selectedCampaignId <= 0 && $campaignTitle !== '') {
            $campaignByTitle = $this->campaignDAO->findByTitle($campaignTitle);
            if ($campaignByTitle !== null) {
                $selectedCampaignId = (int)$campaignByTitle->id;
            }
        }

        include __DIR__ . '/../views/donations/create.php';
    }

    /**
     * Processa submissão de doação
     */
    public function store(): void {
        SessionManager::requireLogin('index.php?page=donation_create');

        $usuario_id = SessionManager::getUserId();
        $mensagem = "";
        $tipoMensagem = "";
        $selectedCampaignId = 0;
        $selectedPointId = 0;
        $itensSelecionadosOld = [];
        $quantidadesOld = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $mensagem = 'Sua sessão expirou. Atualize a página e tente novamente.';
                $tipoMensagem = 'erro';
            }

            $campanha_id = isset($_POST['campanha_id']) ? (int)$_POST['campanha_id'] : 0;
            $ponto_id = isset($_POST['ponto_id']) ? (int)$_POST['ponto_id'] : 0;
            $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
            $itensSelecionados = isset($_POST['itens']) ? (array)$_POST['itens'] : [];
            $quantidades = isset($_POST['quantidades']) ? (array)$_POST['quantidades'] : [];
            $selectedCampaignId = $campanha_id;
            $selectedPointId = $ponto_id;
            $itensSelecionadosOld = $itensSelecionados;
            $quantidadesOld = $quantidades;

            if (!empty($mensagem)) {
                $tipoMensagem = 'erro';
            } elseif ($campanha_id <= 0) {
                $mensagem = 'Selecione uma campanha válida.';
                $tipoMensagem = 'erro';
            } elseif ($ponto_id <= 0) {
                $mensagem = 'Selecione um ponto de coleta válido.';
                $tipoMensagem = 'erro';
            } elseif (count($itensSelecionados) === 0) {
                $mensagem = 'Selecione pelo menos 1 item para doação.';
                $tipoMensagem = 'erro';
            } else {
                $allowedPointIds = array_map(static function (array $point): int {
                    return (int)$point['id'];
                }, $this->getDonationPoints());
                if (!in_array($ponto_id, $allowedPointIds, true)) {
                    $mensagem = 'O ponto de coleta selecionado não está disponível para doação.';
                    $tipoMensagem = 'erro';
                }

                $categoriasPermitidas = $this->itemDAO->findByCampaign($campanha_id);
                $categoriasPermitidasIds = array_map(static function (array $item): int {
                    return (int)$item['id'];
                }, $categoriasPermitidas);
                $categoriasPermitidasLookup = array_flip($categoriasPermitidasIds);

                if (empty($categoriasPermitidasLookup)) {
                    $mensagem = 'Esta campanha não possui necessidades cadastradas no momento.';
                    $tipoMensagem = 'erro';
                }

                $itens = [];
                if (empty($mensagem)) {
                    foreach ($itensSelecionados as $categoria_id) {
                        $categoria_id = (int)$categoria_id;
                        $quantidade = isset($quantidades[$categoria_id]) ? (int)$quantidades[$categoria_id] : 0;

                        if ($categoria_id <= 0 || $quantidade <= 0) {
                            $mensagem = 'Informe uma quantidade válida para cada item.';
                            $tipoMensagem = 'erro';
                            break;
                        }

                        if (!isset($categoriasPermitidasLookup[$categoria_id])) {
                            $mensagem = 'Um dos itens selecionados não pertence às necessidades da campanha.';
                            $tipoMensagem = 'erro';
                            break;
                        }

                        $itens[$categoria_id] = $quantidade;
                    }
                }

                if (empty($mensagem)) {
                    try {
                        $doacaoId = $this->donationDAO->create($usuario_id, $campanha_id, $ponto_id, $descricao, $itens);
                        SessionManager::setMessage('Doação realizada com sucesso! Acompanhe o status no painel.', 'sucesso');
                        header('Location: index.php?page=dashboard');
                        exit;
                    } catch (Exception $e) {
                        $mensagem = 'Erro ao registrar doação: ' . $e->getMessage();
                        $tipoMensagem = 'erro';
                    }
                }
            }
        }

        $dadosFormulario = $this->loadDonationFormData();
        $campaigns = $dadosFormulario['campaigns'];
        $pontosColeta = $dadosFormulario['pontosColeta'];
        $campaignItemsMap = $dadosFormulario['campaignItemsMap'];
        $descricaoOld = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
        $formAction = 'donation_store';
        $submitLabel = 'Registrar Doação';
        $pageTitle = 'Registrar Doação';
        $editingDonationId = 0;

        include __DIR__ . '/../views/donations/create.php';
    }

    /**
     * Renderiza dashboard com doações do usuário
     */
    public function dashboard(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        $usuario_id = SessionManager::getUserId();
        $usuario = $this->userDAO->findById((int)$usuario_id);
        $doacoes = $this->donationDAO->findByUserId($usuario_id);
        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';

        foreach ($doacoes as &$doacao) {
            $campanha = $this->campaignDAO->findById((int)$doacao->campanha_id);
            $doacao->campanha_nome = $campanha ? $campanha->titulo : 'N/A';
            $doacao->itens = $this->donationDAO->getItems((int)$doacao->id);
            $doacao->rastreamento = $this->buildTrackingData($doacao);
        }
        unset($doacao);

        include __DIR__ . '/../views/user/dashboard.php';
    }

    public function updateProfilePhoto(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!isset($_FILES['foto_perfil']) || !is_array($_FILES['foto_perfil'])) {
            SessionManager::setMessage('Selecione uma imagem para atualizar sua foto.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $arquivo = $_FILES['foto_perfil'];
        if ((int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            SessionManager::setMessage('Erro no envio da imagem. Tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if ((int)($arquivo['size'] ?? 0) > 5 * 1024 * 1024) {
            SessionManager::setMessage('A imagem deve ter no máximo 5 MB.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $tmpName = (string)($arquivo['tmp_name'] ?? '');
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';

        if ($imageInfo === false || !str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            SessionManager::setMessage('Envie um arquivo de imagem válido.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $extension = image_type_to_extension((int)($imageInfo[2] ?? 0), false);
        if ($extension === false || $extension === '') {
            $originalName = strtolower((string)($arquivo['name'] ?? ''));
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        }

        $extension = strtolower((string)$extension);
        $extension = preg_replace('/[^a-z0-9]/', '', $extension);
        if ($extension === '' || $extension === 'svg') {
            SessionManager::setMessage('Não foi possível identificar o formato da imagem.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $usuarioId = (int)(SessionManager::getUserId() ?? 0);
        if ($usuarioId <= 0) {
            SessionManager::setMessage('Usuário inválido para atualizar a foto.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $uploadDir = $this->getUserUploadDir();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            SessionManager::setMessage('Não foi possível preparar a pasta da foto.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        try {
            $fileVersion = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $fileVersion = date('YmdHis') . '_' . mt_rand(1000, 9999);
        }

        $fileName = 'user_' . $usuarioId . '_' . $fileVersion . '.' . $extension;
        $relativePath = 'assets/uploads/users/' . $fileName;
        $destination = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            SessionManager::setMessage('Não foi possível salvar a foto de perfil.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!$this->userDAO->updateProfilePhoto($usuarioId, $relativePath)) {
            @unlink($destination);
            SessionManager::setMessage('Não foi possível atualizar sua foto no cadastro.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->clearUserProfilePhotos($usuarioId, $destination);

        SessionManager::setMessage('Foto de perfil atualizada com sucesso.', 'sucesso');
        header('Location: index.php?page=dashboard');
        exit;
    }

    public function removeProfilePhoto(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $usuarioId = (int)(SessionManager::getUserId() ?? 0);
        if ($usuarioId <= 0) {
            SessionManager::setMessage('Usuário inválido para remover a foto.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!$this->userDAO->removeProfilePhoto($usuarioId)) {
            SessionManager::setMessage('Não foi possível remover sua foto do cadastro.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->clearUserProfilePhotos($usuarioId);

        SessionManager::setMessage('Foto de perfil removida com sucesso.', 'sucesso');
        header('Location: index.php?page=dashboard');
        exit;
    }

    public function editForm(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        $doacaoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $doacao = $this->donationDAO->findById($doacaoId);

        if (!$this->canUserManageDonation($doacao)) {
            SessionManager::setMessage('Essa doação não pode mais ser editada.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $flash = SessionManager::getMessage();
        $mensagem = $flash['mensagem'] ?? '';
        $tipoMensagem = $flash['tipo'] ?? '';
        $dadosFormulario = $this->loadDonationFormData();
        $campaigns = $dadosFormulario['campaigns'];
        $pontosColeta = $dadosFormulario['pontosColeta'];
        $campaignItemsMap = $dadosFormulario['campaignItemsMap'];
        $selectedCampaignId = (int)$doacao->campanha_id;
        $selectedPointId = (int)$doacao->ponto_id;
        $itensSelecionadosOld = [];
        $quantidadesOld = [];

        foreach ($doacao->itens as $item) {
            $categoriaId = (int)($item['categoria_id'] ?? 0);
            if ($categoriaId > 0) {
                $itensSelecionadosOld[] = $categoriaId;
                $quantidadesOld[$categoriaId] = (int)($item['quantidade'] ?? 1);
            }
        }

        $descricaoOld = $doacao->descricao ?? '';
        $formAction = 'donation_update';
        $submitLabel = 'Salvar alterações';
        $pageTitle = 'Editar Doação';
        $editingDonationId = (int)$doacao->id;

        include __DIR__ . '/../views/donations/create.php';
    }

    public function update(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $doacaoId = (int)($_POST['doacao_id'] ?? 0);
        $doacao = $this->donationDAO->findById($doacaoId);

        if (!$this->canUserManageDonation($doacao)) {
            SessionManager::setMessage('Essa doação não pode mais ser editada.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=donation_edit&id=' . $doacaoId);
            exit;
        }

        $campanha_id = isset($_POST['campanha_id']) ? (int)$_POST['campanha_id'] : 0;
        $ponto_id = isset($_POST['ponto_id']) ? (int)$_POST['ponto_id'] : 0;
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $itensSelecionados = isset($_POST['itens']) ? (array)$_POST['itens'] : [];
        $quantidades = isset($_POST['quantidades']) ? (array)$_POST['quantidades'] : [];

        $mensagem = '';
        if ($campanha_id <= 0) {
            $mensagem = 'Selecione uma campanha válida.';
        } elseif ($ponto_id <= 0) {
            $mensagem = 'Selecione um ponto de coleta válido.';
        } elseif (count($itensSelecionados) === 0) {
            $mensagem = 'Selecione pelo menos 1 item para doação.';
        } else {
            $allowedPointIds = array_map(static function (array $point): int {
                return (int)$point['id'];
            }, $this->getDonationPoints());

            if (!in_array($ponto_id, $allowedPointIds, true)) {
                $mensagem = 'O ponto de coleta selecionado não está disponível para doação.';
            }
        }

        $categoriasPermitidas = $this->itemDAO->findByCampaign($campanha_id);
        $categoriasPermitidasIds = array_map(static function (array $item): int {
            return (int)$item['id'];
        }, $categoriasPermitidas);
        $categoriasPermitidasLookup = array_flip($categoriasPermitidasIds);

        if ($mensagem === '' && empty($categoriasPermitidasLookup)) {
            $mensagem = 'Esta campanha não possui necessidades cadastradas no momento.';
        }

        $itens = [];
        if ($mensagem === '') {
            foreach ($itensSelecionados as $categoria_id) {
                $categoria_id = (int)$categoria_id;
                $quantidade = isset($quantidades[$categoria_id]) ? (int)$quantidades[$categoria_id] : 0;

                if ($categoria_id <= 0 || $quantidade <= 0) {
                    $mensagem = 'Informe uma quantidade válida para cada item.';
                    break;
                }

                if (!isset($categoriasPermitidasLookup[$categoria_id])) {
                    $mensagem = 'Um dos itens selecionados não pertence às necessidades da campanha.';
                    break;
                }

                $itens[$categoria_id] = $quantidade;
            }
        }

        if ($mensagem !== '') {
            SessionManager::setMessage($mensagem, 'erro');
            header('Location: index.php?page=donation_edit&id=' . $doacaoId);
            exit;
        }

        if ($this->donationDAO->updateDonation($doacaoId, $campanha_id, $ponto_id, $descricao, $itens)) {
            SessionManager::setMessage('Doação atualizada com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível atualizar a doação.', 'erro');
        }

        header('Location: index.php?page=dashboard');
        exit;
    }

    public function cancel(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $doacaoId = (int)($_POST['doacao_id'] ?? 0);
        $doacao = $this->donationDAO->findById($doacaoId);

        if (!$this->canUserManageDonation($doacao)) {
            SessionManager::setMessage('Essa doação não pode mais ser excluída.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if ($this->donationDAO->markAsExcluded($doacaoId)) {
            SessionManager::setMessage('Doação marcada como excluída com sucesso.', 'sucesso');
        } else {
            SessionManager::setMessage('Não foi possível excluir a doação.', 'erro');
        }

        header('Location: index.php?page=dashboard');
        exit;
    }

    /**
     * Gera comprovante PDF da doação.
     */
    public function receipt(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        $doacaoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($doacaoId <= 0) {
            notFound('Doação não encontrada.');
        }

        $doacao = $this->donationDAO->findById($doacaoId);
        if ($doacao === null) {
            notFound('Doação não encontrada.');
        }

        $isOwner = $doacao->usuario_id === SessionManager::getUserId();
        if (!$isOwner && !SessionManager::isAdmin()) {
            notFound('Você não tem permissão para acessar este comprovante.');
        }

        $campanha = $this->campaignDAO->findById((int)$doacao->campanha_id);
        $ponto = $this->pointDAO->findById((int)$doacao->ponto_id);
        $usuario = $this->userDAO->findById((int)$doacao->usuario_id);
        $codigo = $doacao->codigo_publico !== '' ? $doacao->codigo_publico : $this->donationDAO->generatePublicCode($doacao->id, $doacao->data_criacao);
        $statusLabel = $this->formatDonationStatusLabel($doacao->status);

        $pdf = new SimplePdf();
        $y = 790;
        $logoCandidates = [
            __DIR__ . '/../assets/images/logo_sem_fundo_final.png',
            __DIR__ . '/../assets/images/logo_sem_fundo_final.jpg',
        ];
        $logoSlotX = 380;
        $logoSlotY = 728;
        $logoSlotWidth = 175;
        $logoSlotHeight = 84;

        $pdf->addStrokedRect(20, 20, 555, 802, 2.2, 25, 118, 210);
        $pdf->addStrokedRect(30, 30, 535, 782, 1.0, 66, 133, 244);

        foreach ($logoCandidates as $logoPath) {
            $logoDrawX = $logoSlotX;
            $logoDrawY = $logoSlotY;
            $logoDrawWidth = $logoSlotWidth;
            $logoDrawHeight = $logoSlotHeight;

            $logoInfo = @getimagesize($logoPath);
            if ($logoInfo !== false && (int)($logoInfo[0] ?? 0) > 0 && (int)($logoInfo[1] ?? 0) > 0) {
                $scale = min(
                    $logoSlotWidth / (int)$logoInfo[0],
                    $logoSlotHeight / (int)$logoInfo[1]
                );

                if ($scale > 0) {
                    $logoDrawWidth = max(1, (int) floor((int)$logoInfo[0] * $scale));
                    $logoDrawHeight = max(1, (int) floor((int)$logoInfo[1] * $scale));
                    $logoDrawX = $logoSlotX + (int) floor(($logoSlotWidth - $logoDrawWidth) / 2);
                    $logoDrawY = $logoSlotY + (int) floor(($logoSlotHeight - $logoDrawHeight) / 2);
                }
            }

            if ($pdf->addImage($logoPath, $logoDrawX, $logoDrawY, $logoDrawWidth, $logoDrawHeight)) {
                break;
            }
        }

        $pdf->addLine('Comprovante de Entrega da Doação', 50, $y, 18, true);
        $qr = new QRCode($codigo, ['s' => 'qrh']);
        $matrix = $qr->get_matrix();
        $moduleSize = 3;
        $qrWidth = count($matrix[0]) * $moduleSize;
        $qrCenterX = (int) floor(595 / 2);
        $qrX = (int) floor((595 - $qrWidth) / 2);
        $qrY = 208;
        $qrHeight = count($matrix) * $moduleSize;

        foreach ($matrix as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                if ((int)$value === 1) {
                    $pdf->addFilledRect(
                        $qrX + ($colIndex * $moduleSize),
                        $qrY - ($rowIndex * $moduleSize),
                        $moduleSize,
                        $moduleSize
                    );
                }
            }
        }

        $y -= 32;
        $pdf->addLine('Código da doação: ' . $codigo, 50, $y, 14, true);
        $y -= 24;
        $pdf->addLine('Status atual: ' . $statusLabel, 50, $y, 12, true);
        $y -= 32;

        $y = $pdf->addWrappedText('Doador: ' . ($usuario ? $usuario->nome : SessionManager::getUserName()), 50, $y, 62);
        $y = $pdf->addWrappedText('Campanha: ' . ($campanha ? $campanha->titulo : $doacao->campanha_nome), 50, $y, 62);
        $pdf->addLine('Data da doação: ' . date('d/m/Y', strtotime($doacao->data_criacao)), 50, $y);
        $y -= 30;

        $pdf->addLine('Ponto de entrega', 50, $y, 14, true);
        $y -= 22;
        $y = $pdf->addWrappedText('Local: ' . ($ponto['nome'] ?? $doacao->ponto_nome), 50, $y, 62);

        $enderecoEntrega = '';
        if ($ponto !== null) {
            $enderecoEntrega = trim(
                ($ponto['logradouro'] ?? '') . ', ' .
                ($ponto['cidade'] ?? '') . ' - ' .
                ($ponto['estado'] ?? '') . ', CEP ' .
                ($ponto['cep'] ?? '')
            );
        }
        $y = $pdf->addWrappedText('Endereço: ' . $enderecoEntrega, 50, $y, 62);
        $y -= 30;

        $pdf->addLine('Itens declarados', 50, $y, 14, true);
        $y -= 22;
        foreach ($doacao->itens as $item) {
            $pdf->addLine('- ' . $item['nome'] . ' | Quantidade: ' . (int)$item['quantidade'], 60, $y);
            $y -= 18;
        }

        $y -= 18;
        $y = $pdf->addWrappedText('Apresente este comprovante no ponto de coleta para confirmar o recebimento da doação.', 50, $y, 76, 11, false, 14);
        $y = $pdf->addWrappedText('O coletor poderá localizar a doação por meio do código ou do QR Code abaixo.', 50, $y, 76, 11, false, 14);
        $y = $pdf->addWrappedText('No painel do usuário, acompanhe o andamento na seção "Acompanhamento", da etapa 1 de 4 até a entrega no abrigo da cidade de destino.', 50, $y, 76, 11, false, 14);
        $y = $pdf->addWrappedText('Após o recebimento no ponto, a doação seguirá para a cidade selecionada.', 50, $y, 76, 11, false, 14);

        $pdf->addCenteredLine('QR Code para conferência rápida', $qrCenterX, $qrY + 20, 10, true);
        $pdf->addCenteredLine('© 2026 ConectaSolidária', (int) floor(595 / 2), 44, 10);

        $pdf->output('comprovante-doacao-' . $codigo . '.pdf');
        exit;
    }

    private function buildTrackingData(DonationDTO $doacao): array {
        $tracking = [
            'titulo' => 'Doação registrada',
            'descricao' => 'Sua doação foi cadastrada e aguarda entrega no ponto de coleta escolhido.',
            'etapa' => 1,
            'total_etapas' => 4,
            'etapas' => [
                ['titulo' => 'Cadastro realizado', 'descricao' => 'Comprovante gerado e doação registrada no sistema.'],
                ['titulo' => 'Recebimento no ponto', 'descricao' => 'Aguardando conferência presencial no ponto escolhido.'],
                ['titulo' => 'Em rota para a campanha', 'descricao' => 'Os itens seguirão para o destino da campanha selecionada.'],
                ['titulo' => 'Entrega final', 'descricao' => 'A campanha confirma a chegada no destino atendido.'],
            ],
        ];

        if ($doacao->status === 'excluida') {
            return [
                'titulo' => 'Doação excluída pelo usuário',
                'descricao' => 'Essa doação foi cancelada antes da entrega no ponto de coleta.',
                'etapa' => 1,
                'total_etapas' => 1,
                'etapas' => [
                    ['titulo' => 'Doação excluída', 'descricao' => 'O registro foi mantido no sistema, mas a entrega não será mais realizada.'],
                ],
            ];
        }

        if ($doacao->status !== 'recebida') {
            return $tracking;
        }

        $tracking = [
            'titulo' => 'Doação recebida no ponto de coleta',
            'descricao' => 'Sua doação já foi recebida e está em triagem para seguir para a campanha ' . $doacao->campanha_nome . '.',
            'etapa' => 2,
            'total_etapas' => 4,
            'etapas' => [
                ['titulo' => 'Cadastro realizado', 'descricao' => 'Comprovante gerado e doação registrada no sistema.'],
                ['titulo' => 'Recebimento no ponto', 'descricao' => 'Equipe confirmou a entrega da doação no ponto de coleta.'],
                ['titulo' => 'Em rota para a campanha', 'descricao' => 'Aguardando saída logística para o destino da campanha.'],
                ['titulo' => 'Entrega final', 'descricao' => 'Destino final ainda não confirmou o recebimento.'],
            ],
        ];

        $latestDistribution = $this->distributionDAO->findLatestByCampaign((int)$doacao->campanha_id);
        if ($latestDistribution === null) {
            return $tracking;
        }

        if ($latestDistribution['status'] === 'enviado') {
            return [
                'titulo' => 'Doação enviada',
                'descricao' => 'Itens da campanha ' . $latestDistribution['campanha_nome'] . ' foram enviados para ' . $latestDistribution['destino_nome'] . ' em ' . $latestDistribution['cidade'] . '/' . $latestDistribution['estado'] . '.',
                'etapa' => 3,
                'total_etapas' => 4,
                'etapas' => [
                    ['titulo' => 'Cadastro realizado', 'descricao' => 'Comprovante gerado e doação registrada no sistema.'],
                    ['titulo' => 'Recebimento no ponto', 'descricao' => 'Equipe confirmou a entrega da doação no ponto de coleta.'],
                    ['titulo' => 'Em rota para a campanha', 'descricao' => 'Carga enviada para ' . $latestDistribution['destino_nome'] . ' em ' . $latestDistribution['cidade'] . '/' . $latestDistribution['estado'] . '.'],
                    ['titulo' => 'Entrega final', 'descricao' => 'Aguardando confirmação do destino final.'],
                ],
            ];
        }

        return [
            'titulo' => 'Entrega confirmada no abrigo',
            'descricao' => 'A campanha ' . $latestDistribution['campanha_nome'] . ' já teve entrega confirmada em ' . $latestDistribution['destino_nome'] . ' (' . $latestDistribution['cidade'] . '/' . $latestDistribution['estado'] . ').',
            'etapa' => 4,
            'total_etapas' => 4,
            'etapas' => [
                ['titulo' => 'Cadastro realizado', 'descricao' => 'Comprovante gerado e doação registrada no sistema.'],
                ['titulo' => 'Recebimento no ponto', 'descricao' => 'Equipe confirmou a entrega da doação no ponto de coleta.'],
                ['titulo' => 'Em rota para a campanha', 'descricao' => 'Carga enviada para ' . $latestDistribution['destino_nome'] . ' em ' . $latestDistribution['cidade'] . '/' . $latestDistribution['estado'] . '.'],
                ['titulo' => 'Entrega final', 'descricao' => 'Destino final confirmou o recebimento da campanha.'],
            ],
        ];
    }
}

