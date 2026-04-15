<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar operações de categorias de itens no banco de dados.
 * Compatível com o schema atual: categoria_item + necessidade.
 */
class ItemDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Busca todas as categorias de itens.
     * @return array
     */
    public function findAll(): array {
        $sql = 'SELECT id_categoria AS id, nome FROM categoria_item ORDER BY nome ASC';
        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            error_log('Erro ao buscar categorias de itens: ' . $this->conn->error);
            return [];
        }

        $categorias = [];
        while ($item = $resultado->fetch_assoc()) {
            $categorias[] = $item;
        }

        return $categorias;
    }

    /**
     * Busca categoria por ID.
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $sql = 'SELECT id_categoria AS id, nome FROM categoria_item WHERE id_categoria = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de categoria: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $categoria = $resultado->fetch_assoc();
            $stmt->close();
            return $categoria;
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca categorias que estão em uma necessidade de campanha específica.
     * @param int $campanha_id
     * @return array
     */
    public function findByCampaign(int $campanha_id): array {
        $sql = '
            SELECT
                ci.id_categoria AS id,
                ci.nome,
                MAX(n.descricao) AS observacao
            FROM categoria_item ci
            INNER JOIN necessidade n ON ci.id_categoria = n.id_categoria
            WHERE n.id_campanha = ?
            GROUP BY ci.id_categoria, ci.nome
            ORDER BY ci.nome ASC
        ';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de categorias por campanha: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $campanha_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $categorias = [];
        while ($item = $resultado->fetch_assoc()) {
            $categorias[] = $item;
        }

        $stmt->close();
        return $categorias;
    }
}

