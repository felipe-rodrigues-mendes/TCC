<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar destinos de distribuição.
 */
class DestinationDAO {
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
        $result = $this->conn->query("SHOW COLUMNS FROM destino LIKE 'ativo'");
        return $result !== false && $result->num_rows > 0;
    }

    private function addActiveColumnIfMissing(): bool {
        if ($this->hasActiveColumn()) {
            return true;
        }

        $sql = 'ALTER TABLE destino ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER id_endereco';
        if (!$this->conn->query($sql)) {
            // 1060 = Duplicate column name (concorrencia/execucao paralela)
            if ((int)$this->conn->errno !== 1060) {
                error_log('Erro ao adicionar coluna ativo em destino: ' . $this->conn->error);
            }
        }

        return $this->hasActiveColumn();
    }

    public function findAll(bool $onlyActive = true): array {
        $activeSelect = $this->supportsActiveColumn ? 'd.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                d.id_destino AS id,
                d.nome,
                ' . $activeSelect . ',
                e.logradouro,
                e.cidade,
                e.estado,
                e.cep,
                CONCAT_WS(", ", d.nome, e.logradouro, e.cidade, e.estado) AS label
            FROM destino d
            INNER JOIN endereco e ON e.id_endereco = d.id_endereco
        ';

        if ($onlyActive && $this->supportsActiveColumn) {
            $sql .= ' WHERE d.ativo = 1';
        }

        if ($this->supportsActiveColumn) {
            $sql .= ' ORDER BY d.ativo DESC, d.nome ASC';
        } else {
            $sql .= ' ORDER BY d.nome ASC';
        }

        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('Erro ao buscar destinos: ' . $this->conn->error);
            return [];
        }

        $destinos = [];
        while ($row = $result->fetch_assoc()) {
            $destinos[] = $row;
        }

        return $destinos;
    }

    public function findById(int $destinoId): ?array {
        $activeSelect = $this->supportsActiveColumn ? 'd.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                d.id_destino AS id,
                d.nome,
                ' . $activeSelect . ',
                e.logradouro,
                e.cidade,
                e.estado,
                e.cep
            FROM destino d
            INNER JOIN endereco e ON e.id_endereco = d.id_endereco
            WHERE d.id_destino = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao buscar destino por ID: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $destinoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public function existsActive(int $destinoId): bool {
        $sql = 'SELECT 1 FROM destino WHERE id_destino = ?';
        if ($this->supportsActiveColumn) {
            $sql .= ' AND ativo = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao validar destino ativo: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $destinoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result !== false && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function findByIdentity(string $nome, string $logradouro, string $cidade, string $estado, string $cep): ?array {
        $activeSelect = $this->supportsActiveColumn ? 'd.ativo' : '1 AS ativo';
        $sql = '
            SELECT
                d.id_destino AS id,
                ' . $activeSelect . '
            FROM destino d
            INNER JOIN endereco e ON e.id_endereco = d.id_endereco
            WHERE LOWER(TRIM(d.nome)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.logradouro)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.cidade)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.estado)) = LOWER(TRIM(?))
              AND LOWER(TRIM(e.cep)) = LOWER(TRIM(?))
        ';

        if ($this->supportsActiveColumn) {
            $sql .= ' ORDER BY d.ativo DESC, d.id_destino DESC';
        } else {
            $sql .= ' ORDER BY d.id_destino DESC';
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao buscar destino por identidade: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('sssss', $nome, $logradouro, $cidade, $estado, $cep);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public function existsActiveByIdentity(string $nome, string $logradouro, string $cidade, string $estado, string $cep): bool {
        $destino = $this->findByIdentity($nome, $logradouro, $cidade, $estado, $cep);
        if ($destino === null) {
            return false;
        }

        return ((int)($destino['ativo'] ?? 1)) === 1;
    }

    private function setActiveStatus(int $destinoId, int $activeValue): bool {
        if (!$this->supportsActiveColumn) {
            error_log('A coluna ativo nao esta disponivel em destino.');
            return false;
        }

        $sql = 'UPDATE destino SET ativo = ? WHERE id_destino = ?';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualizacao de status do destino: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('ii', $activeValue, $destinoId);
        $ok = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if (!$ok) {
            return false;
        }

        if ($affectedRows > 0) {
            return true;
        }

        $sqlCheck = 'SELECT 1 FROM destino WHERE id_destino = ? AND ativo = ? LIMIT 1';
        $stmtCheck = $this->conn->prepare($sqlCheck);

        if (!$stmtCheck) {
            error_log('Erro ao validar status do destino apos atualizacao: ' . $this->conn->error);
            return false;
        }

        $stmtCheck->bind_param('ii', $destinoId, $activeValue);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $hasExpectedStatus = $result !== false && $result->num_rows > 0;
        $stmtCheck->close();

        return $hasExpectedStatus;
    }

    public function activate(int $destinoId): bool {
        return $this->setActiveStatus($destinoId, 1);
    }

    public function deactivate(int $destinoId): bool {
        return $this->setActiveStatus($destinoId, 0);
    }

    public function create(string $nome, string $logradouro, string $cidade, string $estado, string $cep): ?int {
        $sqlEndereco = 'INSERT INTO endereco (logradouro, cidade, estado, cep) VALUES (?, ?, ?, ?)';
        $stmtEndereco = $this->conn->prepare($sqlEndereco);

        if (!$stmtEndereco) {
            error_log('Erro ao preparar endereço do destino: ' . $this->conn->error);
            return null;
        }

        $stmtEndereco->bind_param('ssss', $logradouro, $cidade, $estado, $cep);
        if (!$stmtEndereco->execute()) {
            error_log('Erro ao inserir endereço do destino: ' . $stmtEndereco->error);
            $stmtEndereco->close();
            return null;
        }

        $enderecoId = $this->conn->insert_id;
        $stmtEndereco->close();

        $sqlDestino = 'INSERT INTO destino (nome, id_endereco) VALUES (?, ?)';
        $stmtDestino = $this->conn->prepare($sqlDestino);

        if (!$stmtDestino) {
            error_log('Erro ao preparar destino: ' . $this->conn->error);
            return null;
        }

        $stmtDestino->bind_param('si', $nome, $enderecoId);
        if (!$stmtDestino->execute()) {
            error_log('Erro ao inserir destino: ' . $stmtDestino->error);
            $stmtDestino->close();
            return null;
        }

        $destinoId = $this->conn->insert_id;
        $stmtDestino->close();

        return $destinoId;
    }
}

