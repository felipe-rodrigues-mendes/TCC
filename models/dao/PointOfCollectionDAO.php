<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar pontos de coleta no banco de dados.
 * Compatível com o schema atual: ponto_coleta + endereco.
 */
class PointOfCollectionDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Busca todos os pontos de coleta.
     * @return array
     */
    public function findAll(): array {
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
                1 AS ativo,
                e.id_endereco AS endereco_id
            FROM ponto_coleta pc
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
            ORDER BY pc.nome ASC
        ';

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

    /**
     * Busca ponto de coleta por ID.
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
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
                1 AS ativo,
                e.id_endereco AS endereco_id
            FROM ponto_coleta pc
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
            WHERE pc.id_ponto = ?
            LIMIT 1
        ';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de ponto: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $ponto = $resultado->fetch_assoc();
            $stmt->close();
            return $ponto;
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca pontos de coleta por cidade.
     * @param string $cidade
     * @return array
     */
    public function findByCity(string $cidade): array {
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
                1 AS ativo,
                e.id_endereco AS endereco_id
            FROM ponto_coleta pc
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
            WHERE e.cidade = ?
            ORDER BY pc.nome ASC
        ';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca por cidade: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('s', $cidade);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $pontos = [];
        while ($ponto = $resultado->fetch_assoc()) {
            $pontos[] = $ponto;
        }

        $stmt->close();
        return $pontos;
    }

    /**
     * Busca somente nomes de pontos.
     * @return array
     */
    public function findAllNames(): array {
        $sql = 'SELECT id_ponto AS id, nome FROM ponto_coleta ORDER BY nome ASC';
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
}

