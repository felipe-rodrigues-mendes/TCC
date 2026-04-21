<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../dto/DonationDTO.php';

/**
 * Data Access Object para gerenciar operações de doações no banco de dados.
 * Compatível com o schema atual: doacao + item_doacao.
 */
class DonationDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    private function normalizeStatus(string $status): string {
        return strtoupper(trim($status));
    }

    public function generatePublicCode(int $donationId, ?string $date = null): string {
        $datePart = preg_replace('/[^0-9]/', '', (string)$date);
        if ($datePart === '') {
            $datePart = date('Ymd');
        }

        return sprintf('DCS-%s-%06d', substr($datePart, 0, 8), $donationId);
    }

    public function extractIdFromPublicCode(string $code): int {
        $code = strtoupper(trim($code));
        if (preg_match('/^DCS-\d{8}-(\d{6})$/', $code, $matches) === 1) {
            return (int)$matches[1];
        }

        return 0;
    }

    private function hydrateDonationRow(array $dados): array {
        $dados['status'] = strtolower((string)$dados['status']);
        $dados['codigo_publico'] = $this->generatePublicCode(
            (int)($dados['id'] ?? $dados['id_doacao'] ?? 0),
            (string)($dados['data_criacao'] ?? $dados['data_doacao'] ?? '')
        );

        return $dados;
    }

    /**
     * Cria nova doação com itens.
     * O campo descricao existe na UI, mas não faz parte do schema atual.
     * @param int $usuario_id
     * @param int $campanha_id
     * @param int $ponto_id
     * @param string $descricao
     * @param array $itens Array de items: ['categoria_id' => id, 'quantidade' => qty]
     * @return int|null
     * @throws Exception
     */
    public function create(int $usuario_id, int $campanha_id, int $ponto_id, string $descricao, array $itens): ?int {
        try {
            Database::getInstance()->beginTransaction();

            $sql = 'INSERT INTO doacao (id_usuario, id_campanha, id_ponto, data_doacao, status) VALUES (?, ?, ?, CURDATE(), \'PENDENTE\')';
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Erro ao preparar INSERT de doação: ' . $this->conn->error);
            }

            $stmt->bind_param('iii', $usuario_id, $campanha_id, $ponto_id);

            if (!$stmt->execute()) {
                throw new Exception('Erro ao registrar doação: ' . $stmt->error);
            }

            $doacao_id = $this->conn->insert_id;
            $stmt->close();

            $sqlItem = 'INSERT INTO item_doacao (id_doacao, id_categoria, quantidade) VALUES (?, ?, ?)';
            $stmtItem = $this->conn->prepare($sqlItem);

            if (!$stmtItem) {
                throw new Exception('Erro ao preparar INSERT de itens: ' . $this->conn->error);
            }

            foreach ($itens as $categoria_id => $quantidade) {
                $categoria_id = (int)$categoria_id;
                $quantidade = (int)$quantidade;

                if ($categoria_id <= 0 || $quantidade <= 0) {
                    throw new Exception('Dados inválidos: categoria_id=' . $categoria_id . ', quantidade=' . $quantidade);
                }

                $stmtItem->bind_param('iii', $doacao_id, $categoria_id, $quantidade);

                if (!$stmtItem->execute()) {
                    throw new Exception('Erro ao registrar item da doação: ' . $stmtItem->error);
                }
            }

            $stmtItem->close();
            Database::getInstance()->commit();

            return $doacao_id;
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            error_log('Erro ao criar doação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza uma doação pendente e substitui seus itens.
     */
    public function updateDonation(int $doacaoId, int $campanhaId, int $pontoId, string $descricao, array $itens): bool {
        try {
            Database::getInstance()->beginTransaction();

            $sqlDoacao = 'UPDATE doacao SET id_campanha = ?, id_ponto = ? WHERE id_doacao = ? LIMIT 1';
            $stmtDoacao = $this->conn->prepare($sqlDoacao);

            if (!$stmtDoacao) {
                throw new Exception('Erro ao preparar atualização da doação: ' . $this->conn->error);
            }

            $stmtDoacao->bind_param('iii', $campanhaId, $pontoId, $doacaoId);
            if (!$stmtDoacao->execute()) {
                throw new Exception('Erro ao atualizar doação: ' . $stmtDoacao->error);
            }
            $stmtDoacao->close();

            $sqlDeleteItens = 'DELETE FROM item_doacao WHERE id_doacao = ?';
            $stmtDeleteItens = $this->conn->prepare($sqlDeleteItens);
            if (!$stmtDeleteItens) {
                throw new Exception('Erro ao preparar remoção de itens da doação: ' . $this->conn->error);
            }

            $stmtDeleteItens->bind_param('i', $doacaoId);
            if (!$stmtDeleteItens->execute()) {
                throw new Exception('Erro ao remover itens da doação: ' . $stmtDeleteItens->error);
            }
            $stmtDeleteItens->close();

            $sqlItem = 'INSERT INTO item_doacao (id_doacao, id_categoria, quantidade) VALUES (?, ?, ?)';
            $stmtItem = $this->conn->prepare($sqlItem);

            if (!$stmtItem) {
                throw new Exception('Erro ao preparar atualização de itens: ' . $this->conn->error);
            }

            foreach ($itens as $categoriaId => $quantidade) {
                $categoriaId = (int)$categoriaId;
                $quantidade = (int)$quantidade;

                if ($categoriaId <= 0 || $quantidade <= 0) {
                    throw new Exception('Dados inválidos: categoria_id=' . $categoriaId . ', quantidade=' . $quantidade);
                }

                $stmtItem->bind_param('iii', $doacaoId, $categoriaId, $quantidade);
                if (!$stmtItem->execute()) {
                    throw new Exception('Erro ao registrar item atualizado da doação: ' . $stmtItem->error);
                }
            }

            $stmtItem->close();
            Database::getInstance()->commit();

            return true;
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            error_log('Erro ao atualizar doação: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca doações de um usuário específico.
     * @param int $usuario_id
     * @return array
     */
    public function findByUserId(int $usuario_id): array {
        $sql = '
            SELECT d.id_doacao AS id, d.id_usuario AS usuario_id, d.id_campanha AS campanha_id, d.id_ponto AS ponto_id,
                   d.data_doacao AS data_criacao, d.status, pc.nome AS ponto_nome
            FROM doacao d
            INNER JOIN ponto_coleta pc ON pc.id_ponto = d.id_ponto
            WHERE d.id_usuario = ?
            ORDER BY d.data_doacao DESC, d.id_doacao DESC
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de doações: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $doacoes = [];
        while ($dados = $resultado->fetch_assoc()) {
            $doacoes[] = DonationDTO::fromArray($this->hydrateDonationRow($dados));
        }

        $stmt->close();
        return $doacoes;
    }

    /**
     * Busca doação por ID com seus itens.
     * @param int $id
     * @return DonationDTO|null
     */
    public function findById(int $id): ?DonationDTO {
        $sql = '
            SELECT d.id_doacao AS id, d.id_usuario AS usuario_id, d.id_campanha AS campanha_id, d.id_ponto AS ponto_id,
                   d.data_doacao AS data_criacao, d.status, pc.nome AS ponto_nome
            FROM doacao d
            INNER JOIN ponto_coleta pc ON pc.id_ponto = d.id_ponto
            WHERE d.id_doacao = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de doação por ID: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $dados = $resultado->fetch_assoc();
            $stmt->close();

            $doacao = DonationDTO::fromArray($this->hydrateDonationRow($dados));
            $doacao->itens = $this->getItems($id);
            return $doacao;
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca itens de uma doação específica.
     * @param int $doacao_id
     * @return array
     */
    public function getItems(int $doacao_id): array {
        $sql = '
            SELECT id.id_categoria AS categoria_id, id.quantidade, ci.nome
            FROM item_doacao id
            INNER JOIN categoria_item ci ON id.id_categoria = ci.id_categoria
            WHERE id.id_doacao = ?
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de itens: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $doacao_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $items = [];
        while ($item = $resultado->fetch_assoc()) {
            $items[] = $item;
        }

        $stmt->close();
        return $items;
    }

    /**
     * Busca doações por status.
     * @param string $status
     * @return array
     */
    /**
     * Atualiza status da doação.
     * @param int $doacao_id
     * @return bool
     */
    public function updateStatus(int $doacao_id, string $novoStatus = 'RECEBIDA'): bool {
        $novoStatus = $this->normalizeStatus($novoStatus);

        $sql = 'UPDATE doacao SET status = ? WHERE id_doacao = ?';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar UPDATE de status: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $novoStatus, $doacao_id);
        $resultado = $stmt->execute();
        $stmt->close();

        return $resultado;
    }

    public function markAsExcluded(int $doacaoId): bool {
        return $this->updateStatus($doacaoId, 'EXCLUIDA');
    }

    /**
     * Busca todas as doações com filtros opcionais.
     * @param string $filtroStatus
     * @param string $busca
     * @return array
     */
    public function findAll(string $filtroStatus = 'todos', string $busca = ''): array {
        $sql = '
            SELECT d.id_doacao AS id, d.id_usuario AS usuario_id, d.id_campanha AS campanha_id, d.id_ponto AS ponto_id,
                   d.data_doacao AS data_criacao, d.status, u.nome AS usuario_nome, c.titulo AS campanha_nome, pc.nome AS ponto_nome
            FROM doacao d
            INNER JOIN usuario u ON d.id_usuario = u.id_usuario
            INNER JOIN campanha c ON d.id_campanha = c.id_campanha
            INNER JOIN ponto_coleta pc ON d.id_ponto = pc.id_ponto
            WHERE 1=1
        ';

        $params = [];
        if ($filtroStatus !== 'todos') {
            $sql .= ' AND d.status = ?';
            $params[] = $this->normalizeStatus($filtroStatus);
        }

        $buscaId = $this->extractIdFromPublicCode($busca);
        if (!empty($busca)) {
            $sql .= ' AND (u.nome LIKE ? OR c.titulo LIKE ?';
            $buscaLike = '%' . $busca . '%';
            $params[] = $buscaLike;
            $params[] = $buscaLike;
            if ($buscaId > 0) {
                $sql .= ' OR d.id_doacao = ?';
                $params[] = $buscaId;
            }
            $sql .= ')';
        }

        $sql .= ' ORDER BY d.data_doacao DESC, d.id_doacao DESC';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de todas as doações: ' . $this->conn->error);
            return [];
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                $types .= is_int($param) ? 'i' : 's';
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        $doacoes = [];
        while ($dados = $resultado->fetch_assoc()) {
            $doacoes[] = $this->hydrateDonationRow($dados);
        }

        $stmt->close();
        return $doacoes;
    }

    public function findByPublicCode(string $code): ?DonationDTO {
        $id = $this->extractIdFromPublicCode($code);
        if ($id <= 0) {
            return null;
        }

        $doacao = $this->findById($id);
        if ($doacao === null || $doacao->codigo_publico !== strtoupper(trim($code))) {
            return null;
        }

        return $doacao;
    }
}



