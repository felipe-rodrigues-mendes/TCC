<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar pontos de coleta.
 */
class PointOfCollectionDAO {
    private $conn;
    private $supportsActiveColumn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->supportsActiveColumn = $this->hasActiveColumn();
        if (!$this->supportsActiveColumn) {
            $this->supportsActiveColumn = $this->addActiveColumnIfMissing();
        }
    }

    private function hasActiveColumn(): bool {
        $result = $this->conn->query("SHOW COLUMNS FROM ponto_coleta LIKE 'ativo'");
        return $result !== false && $result->num_rows > 0;
    }

    private function addActiveColumnIfMissing(): bool {
        if ($this->hasActiveColumn()) {
            return true;
        }

        $sql = 'ALTER TABLE ponto_coleta ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER id_endereco';
        if (!$this->conn->query($sql)) {
            // 1060 = duplicate column name (concorrencia/execucao paralela)
            if ((int)$this->conn->errno !== 1060) {
                error_log('Erro ao adicionar coluna ativo em ponto_coleta: ' . $this->conn->error);
            }
        }

        return $this->hasActiveColumn();
    }

    public function findAll(bool $onlyActive = true): array {
        $activeSelect = $this->supportsActiveColumn ? 'pc.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                pc.id_ponto AS id,
                pc.nome,
                e.logradouro,
                e.cidade,
                e.estado,
                e.cep,
                "" AS telefone,
                "" AS complemento,
                "" AS numero,
                ' . $activeSelect . ',
                e.id_endereco AS endereco_id
            FROM ponto_coleta pc
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
        ';

        if ($onlyActive && $this->supportsActiveColumn) {
            $sql .= ' WHERE pc.ativo = 1';
        }

        if ($this->supportsActiveColumn) {
            $sql .= ' ORDER BY pc.ativo DESC, pc.nome ASC';
        } else {
            $sql .= ' ORDER BY pc.nome ASC';
        }

        $resultado = $this->conn->query($sql);
        if (!$resultado) {
            error_log('Erro ao buscar pontos de coleta: ' . $this->conn->error);
            return [];
        }

        $pontos = [];
        while ($ponto = $resultado->fetch_assoc()) {
            $pontos[] = $ponto;
        }

        return $pontos;
    }

    public function findById(int $id): ?array {
        $activeSelect = $this->supportsActiveColumn ? 'pc.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                pc.id_ponto AS id,
                pc.nome,
                e.logradouro,
                e.cidade,
                e.estado,
                e.cep,
                "" AS telefone,
                "" AS complemento,
                "" AS numero,
                ' . $activeSelect . ',
                e.id_endereco AS endereco_id
            FROM ponto_coleta pc
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
            WHERE pc.id_ponto = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de ponto por id: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $ponto = ($resultado && $resultado->num_rows === 1) ? $resultado->fetch_assoc() : null;
        $stmt->close();

        return $ponto ?: null;
    }

    public function findAllNames(bool $onlyActive = true): array {
        $activeSelect = $this->supportsActiveColumn ? 'ativo' : '1 AS ativo';
        $sql = '
            SELECT
                id_ponto AS id,
                nome,
                ' . $activeSelect . '
            FROM ponto_coleta
        ';

        if ($onlyActive && $this->supportsActiveColumn) {
            $sql .= ' WHERE ativo = 1';
        }

        if ($this->supportsActiveColumn) {
            $sql .= ' ORDER BY ativo DESC, nome ASC';
        } else {
            $sql .= ' ORDER BY nome ASC';
        }

        $resultado = $this->conn->query($sql);
        if (!$resultado) {
            error_log('Erro ao buscar nomes de pontos: ' . $this->conn->error);
            return [];
        }

        $pontos = [];
        while ($ponto = $resultado->fetch_assoc()) {
            $pontos[] = $ponto;
        }

        return $pontos;
    }

    public function findByIdentity(string $nome, string $logradouro, string $cidade, string $estado, string $cep): ?array {
        $activeSelect = $this->supportsActiveColumn ? 'pc.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                pc.id_ponto AS id,
                ' . $activeSelect . '
            FROM ponto_coleta pc
            INNER JOIN endereco e ON e.id_endereco = pc.id_endereco
            WHERE LOWER(TRIM(pc.nome)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.logradouro)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.cidade)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.estado)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.cep)) = LOWER(TRIM(?))
        ';

        if ($this->supportsActiveColumn) {
            $sql .= ' ORDER BY pc.ativo DESC, pc.id_ponto DESC';
        } else {
            $sql .= ' ORDER BY pc.id_ponto DESC';
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao buscar ponto por identidade: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('sssss', $nome, $logradouro, $cidade, $estado, $cep);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    private function setActiveStatus(int $pointId, int $activeValue): bool {
        if (!$this->supportsActiveColumn) {
            error_log('A coluna ativo nao esta disponivel em ponto_coleta.');
            return false;
        }

        $sql = 'UPDATE ponto_coleta SET ativo = ? WHERE id_ponto = ?';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualizacao de status de ponto: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('ii', $activeValue, $pointId);
        $ok = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if (!$ok) {
            return false;
        }

        if ($affectedRows > 0) {
            return true;
        }

        $sqlCheck = 'SELECT 1 FROM ponto_coleta WHERE id_ponto = ? AND ativo = ? LIMIT 1';
        $stmtCheck = $this->conn->prepare($sqlCheck);
        if (!$stmtCheck) {
            error_log('Erro ao validar status de ponto apos atualizacao: ' . $this->conn->error);
            return false;
        }

        $stmtCheck->bind_param('ii', $pointId, $activeValue);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $hasExpectedStatus = $result !== false && $result->num_rows > 0;
        $stmtCheck->close();

        return $hasExpectedStatus;
    }

    public function activate(int $pointId): bool {
        return $this->setActiveStatus($pointId, 1);
    }

    public function deactivate(int $pointId): bool {
        return $this->setActiveStatus($pointId, 0);
    }

    public function create(string $nome, string $logradouro, string $cidade, string $estado, string $cep): ?int {
        $sqlAddress = 'INSERT INTO endereco (logradouro, cidade, estado, cep) VALUES (?, ?, ?, ?)';
        $stmtAddress = $this->conn->prepare($sqlAddress);
        if (!$stmtAddress) {
            error_log('Erro ao preparar endereco de ponto: ' . $this->conn->error);
            return null;
        }

        $stmtAddress->bind_param('ssss', $logradouro, $cidade, $estado, $cep);
        if (!$stmtAddress->execute()) {
            error_log('Erro ao inserir endereco de ponto: ' . $stmtAddress->error);
            $stmtAddress->close();
            return null;
        }

        $addressId = $this->conn->insert_id;
        $stmtAddress->close();

        $sqlPoint = 'INSERT INTO ponto_coleta (nome, id_endereco) VALUES (?, ?)';
        $stmtPoint = $this->conn->prepare($sqlPoint);
        if (!$stmtPoint) {
            error_log('Erro ao preparar insercao de ponto: ' . $this->conn->error);
            return null;
        }

        $stmtPoint->bind_param('si', $nome, $addressId);
        if (!$stmtPoint->execute()) {
            error_log('Erro ao inserir ponto de coleta: ' . $stmtPoint->error);
            $stmtPoint->close();
            return null;
        }

        $pointId = $this->conn->insert_id;
        $stmtPoint->close();

        return $pointId;
    }
}
