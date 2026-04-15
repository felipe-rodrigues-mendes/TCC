<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar distribuições.
 */
class DistributionDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create(int $destinoId, int $campanhaId, string $dataEnvio, array $itens): ?int {
        $sql = 'INSERT INTO distribuicao (id_destino, id_campanha, data_envio, status) VALUES (?, ?, ?, \'ENVIADO\')';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Erro ao preparar distribuição: ' . $this->conn->error);
        }

        $stmt->bind_param('iis', $destinoId, $campanhaId, $dataEnvio);
        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar distribuição: ' . $stmt->error);
        }

        $distribuicaoId = $this->conn->insert_id;
        $stmt->close();

        $sqlItem = 'INSERT INTO item_distribuicao (id_distribuicao, id_categoria, quantidade) VALUES (?, ?, ?)';
        $stmtItem = $this->conn->prepare($sqlItem);

        if (!$stmtItem) {
            throw new Exception('Erro ao preparar itens da distribuição: ' . $this->conn->error);
        }

        foreach ($itens as $categoriaId => $quantidade) {
            $categoriaId = (int)$categoriaId;
            $quantidade = (int)$quantidade;
            $stmtItem->bind_param('iii', $distribuicaoId, $categoriaId, $quantidade);

            if (!$stmtItem->execute()) {
                throw new Exception('Erro ao registrar item da distribuição: ' . $stmtItem->error);
            }
        }

        $stmtItem->close();
        return $distribuicaoId;
    }

    public function findAll(): array {
        $sql = '
            SELECT
                d.id_distribuicao AS id,
                d.data_envio,
                d.status,
                d.id_campanha AS campanha_id,
                c.titulo AS campanha_nome,
                de.nome AS destino_nome,
                e.logradouro,
                e.cidade,
                e.estado
            FROM distribuicao d
            INNER JOIN campanha c ON c.id_campanha = d.id_campanha
            INNER JOIN destino de ON de.id_destino = d.id_destino
            INNER JOIN endereco e ON e.id_endereco = de.id_endereco
            ORDER BY d.data_envio DESC, d.id_distribuicao DESC
        ';

        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('Erro ao buscar distribuições: ' . $this->conn->error);
            return [];
        }

        $distribuicoes = [];
        while ($row = $result->fetch_assoc()) {
            $row['status'] = strtolower((string)$row['status']);
            $row['itens'] = $this->getItems((int)$row['id']);
            $distribuicoes[] = $row;
        }

        return $distribuicoes;
    }

    public function getItems(int $distribuicaoId): array {
        $sql = '
            SELECT id.id_categoria AS categoria_id, id.quantidade, ci.nome
            FROM item_distribuicao id
            INNER JOIN categoria_item ci ON ci.id_categoria = id.id_categoria
            WHERE id.id_distribuicao = ?
            ORDER BY ci.nome ASC
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao buscar itens da distribuição: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $distribuicaoId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
        return $items;
    }

    public function updateStatus(int $distribuicaoId, string $status): bool {
        $status = strtoupper(trim($status));
        $sql = 'UPDATE distribuicao SET status = ? WHERE id_distribuicao = ?';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualização de distribuição: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $status, $distribuicaoId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function findLatestByCampaign(int $campanhaId): ?array {
        $sql = '
            SELECT
                d.id_distribuicao AS id,
                d.data_envio,
                d.status,
                d.id_campanha AS campanha_id,
                c.titulo AS campanha_nome,
                de.nome AS destino_nome,
                e.cidade,
                e.estado
            FROM distribuicao d
            INNER JOIN campanha c ON c.id_campanha = d.id_campanha
            INNER JOIN destino de ON de.id_destino = d.id_destino
            INNER JOIN endereco e ON e.id_endereco = de.id_endereco
            WHERE d.id_campanha = ?
            ORDER BY FIELD(d.status, \'ENVIADO\', \'ENTREGUE\'), d.data_envio DESC, d.id_distribuicao DESC
            LIMIT 1
        ';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('Erro ao buscar distribuição por campanha: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $campanhaId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            return null;
        }

        $row = $result->fetch_assoc();
        $row['status'] = strtolower((string)$row['status']);
        $stmt->close();
        return $row;
    }
}

