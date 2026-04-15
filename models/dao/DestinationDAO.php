<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar destinos de distribuição.
 */
class DestinationDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function findAll(): array {
        $sql = '
            SELECT
                d.id_destino AS id,
                d.nome,
                e.logradouro,
                e.cidade,
                e.estado,
                e.cep,
                CONCAT_WS(", ", d.nome, e.logradouro, e.cidade, e.estado) AS label
            FROM destino d
            INNER JOIN endereco e ON e.id_endereco = d.id_endereco
            ORDER BY d.nome ASC
        ';

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

