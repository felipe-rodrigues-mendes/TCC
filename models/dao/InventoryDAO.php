<?php

require_once __DIR__ . '/../Database.php';

/**
 * Data Access Object para gerenciar operações de inventário no banco de dados.
 * Compatível com o schema atual: estoque + item_estoque.
 */
class InventoryDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Busca estoque por ponto de coleta, cria se não existir.
     * @param int $ponto_id
     * @return int ID do estoque
     * @throws Exception
     */
    public function getOrCreateEstoque(int $ponto_id): int {
        $sql = 'SELECT id_estoque FROM estoque WHERE id_ponto = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Erro ao buscar estoque: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $ponto_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $estoque_id = (int)$resultado->fetch_assoc()['id_estoque'];
            $stmt->close();
            return $estoque_id;
        }

        $stmt->close();

        $sqlInsert = 'INSERT INTO estoque (id_ponto) VALUES (?)';
        $stmtInsert = $this->conn->prepare($sqlInsert);

        if (!$stmtInsert) {
            throw new Exception('Erro ao criar estoque: ' . $this->conn->error);
        }

        $stmtInsert->bind_param('i', $ponto_id);

        if (!$stmtInsert->execute()) {
            throw new Exception('Erro ao executar insert de estoque: ' . $stmtInsert->error);
        }

        $estoque_id = $this->conn->insert_id;
        $stmtInsert->close();

        return $estoque_id;
    }

    /**
     * Adiciona item ao inventário ou atualiza quantidade.
     * @param int $estoque_id
     * @param int $categoria_id
     * @param int $quantidade
     * @throws Exception
     */
    public function addOrUpdateItem(int $estoque_id, int $categoria_id, int $quantidade): void {
        if ($quantidade <= 0) {
            throw new Exception('Quantidade deve ser maior que zero');
        }

        $sqlExiste = 'SELECT id_item FROM item_estoque WHERE id_estoque = ? AND id_categoria = ? LIMIT 1';
        $stmtExiste = $this->conn->prepare($sqlExiste);

        if (!$stmtExiste) {
            throw new Exception('Erro ao verificar existência de item: ' . $this->conn->error);
        }

        $stmtExiste->bind_param('ii', $estoque_id, $categoria_id);
        $stmtExiste->execute();
        $resultadoExiste = $stmtExiste->get_result();

        if ($resultadoExiste->num_rows > 0) {
            $stmtExiste->close();
            $sqlUpdate = 'UPDATE item_estoque SET quantidade = quantidade + ? WHERE id_estoque = ? AND id_categoria = ?';
            $stmtUpdate = $this->conn->prepare($sqlUpdate);

            if (!$stmtUpdate) {
                throw new Exception('Erro ao atualizar item: ' . $this->conn->error);
            }

            $stmtUpdate->bind_param('iii', $quantidade, $estoque_id, $categoria_id);

            if (!$stmtUpdate->execute()) {
                throw new Exception('Erro ao executar UPDATE: ' . $stmtUpdate->error);
            }

            $stmtUpdate->close();
        } else {
            $stmtExiste->close();
            $sqlInsert = 'INSERT INTO item_estoque (id_estoque, id_categoria, quantidade) VALUES (?, ?, ?)';
            $stmtInsert = $this->conn->prepare($sqlInsert);

            if (!$stmtInsert) {
                throw new Exception('Erro ao preparar INSERT: ' . $this->conn->error);
            }

            $stmtInsert->bind_param('iii', $estoque_id, $categoria_id, $quantidade);

            if (!$stmtInsert->execute()) {
                throw new Exception('Erro ao executar INSERT: ' . $stmtInsert->error);
            }

            $stmtInsert->close();
        }
    }

    /**
     * Busca inventário agrupado por ponto de coleta.
     * @return array
     */
    public function getInventoryByLocation(): array {
        $sql = '
            SELECT
                pc.id_ponto AS ponto_id,
                pc.nome AS ponto_coleta,
                e.logradouro,
                e.cidade,
                e.estado,
                ci.id_categoria AS categoria_id,
                ci.nome AS item,
                ie.quantidade
            FROM item_estoque ie
            INNER JOIN estoque es ON ie.id_estoque = es.id_estoque
            INNER JOIN ponto_coleta pc ON es.id_ponto = pc.id_ponto
            INNER JOIN endereco e ON pc.id_endereco = e.id_endereco
            INNER JOIN categoria_item ci ON ie.id_categoria = ci.id_categoria
            ORDER BY pc.nome ASC, ci.nome ASC
        ';

        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            error_log('Erro ao buscar inventário por localização: ' . $this->conn->error);
            return [];
        }

        $estoquesAgrupados = [];
        while ($linha = $resultado->fetch_assoc()) {
            $ponto = $linha['ponto_coleta'] . ' - ' . $linha['cidade'] . '/' . $linha['estado'];

            if (!isset($estoquesAgrupados[$ponto])) {
                $estoquesAgrupados[$ponto] = [
                    'ponto_id' => $linha['ponto_id'],
                    'logradouro' => $linha['logradouro'],
                    'itens' => []
                ];
            }

            $estoquesAgrupados[$ponto]['itens'][] = [
                'categoria_id' => $linha['categoria_id'],
                'item' => $linha['item'],
                'quantidade' => $linha['quantidade']
            ];
        }

        return $estoquesAgrupados;
    }

    /**
     * Busca inventário de um ponto específico.
     * @param int $ponto_id
     * @return array
     */
    public function getInventoryByPoint(int $ponto_id): array {
        $sql = '
            SELECT ci.id_categoria AS id, ci.nome, ie.quantidade
            FROM item_estoque ie
            INNER JOIN estoque es ON ie.id_estoque = es.id_estoque
            INNER JOIN categoria_item ci ON ie.id_categoria = ci.id_categoria
            WHERE es.id_ponto = ?
            ORDER BY ci.nome ASC
        ';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de inventário por ponto: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $ponto_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $itens = [];
        while ($item = $resultado->fetch_assoc()) {
            $itens[] = $item;
        }

        $stmt->close();
        return $itens;
    }

    /**
     * Debita quantidade do estoque de um ponto.
     * @param int $ponto_id
     * @param int $categoria_id
     * @param int $quantidade
     * @throws Exception
     */
    public function removeItemFromPoint(int $ponto_id, int $categoria_id, int $quantidade): void {
        if ($quantidade <= 0) {
            throw new Exception('Quantidade inválida para saída de estoque.');
        }

        $sql = '
            SELECT ie.id_item, ie.quantidade
            FROM item_estoque ie
            INNER JOIN estoque es ON es.id_estoque = ie.id_estoque
            WHERE es.id_ponto = ? AND ie.id_categoria = ?
            LIMIT 1
        ';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Erro ao consultar item em estoque: ' . $this->conn->error);
        }

        $stmt->bind_param('ii', $ponto_id, $categoria_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            throw new Exception('Item não encontrado no estoque selecionado.');
        }

        $item = $result->fetch_assoc();
        $stmt->close();

        $quantidadeAtual = (int)$item['quantidade'];
        if ($quantidadeAtual < $quantidade) {
            throw new Exception('Estoque insuficiente para um dos itens selecionados.');
        }

        if ($quantidadeAtual === $quantidade) {
            $sqlDelete = 'DELETE ie FROM item_estoque ie INNER JOIN estoque es ON es.id_estoque = ie.id_estoque WHERE es.id_ponto = ? AND ie.id_categoria = ?';
            $stmtDelete = $this->conn->prepare($sqlDelete);

            if (!$stmtDelete) {
                throw new Exception('Erro ao remover item esgotado: ' . $this->conn->error);
            }

            $stmtDelete->bind_param('ii', $ponto_id, $categoria_id);
            if (!$stmtDelete->execute()) {
                throw new Exception('Erro ao remover item do estoque: ' . $stmtDelete->error);
            }

            $stmtDelete->close();
            return;
        }

        $sqlUpdate = '
            UPDATE item_estoque ie
            INNER JOIN estoque es ON es.id_estoque = ie.id_estoque
            SET ie.quantidade = ie.quantidade - ?
            WHERE es.id_ponto = ? AND ie.id_categoria = ?
        ';

        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            throw new Exception('Erro ao preparar baixa de estoque: ' . $this->conn->error);
        }

        $stmtUpdate->bind_param('iii', $quantidade, $ponto_id, $categoria_id);
        if (!$stmtUpdate->execute()) {
            throw new Exception('Erro ao atualizar estoque: ' . $stmtUpdate->error);
        }

        $stmtUpdate->close();
    }

}

