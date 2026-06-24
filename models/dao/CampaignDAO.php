<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../dto/CampaignDTO.php';

/**
 * Data Access Object para gerenciar operações de campanhas no banco de dados.
 * Compatível com o schema atual: campanha + necessidade.
 */
class CampaignDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    private function shouldHideCampaignTitle(string $titulo): bool {
        $tituloNormalizado = trim(mb_strtolower($titulo, 'UTF-8'));
        return in_array($tituloNormalizado, ['brasilia', 'brasília', 'ceilandia', 'ceilândia', 'taguatinga'], true);
    }

    /**
     * Busca todas as campanhas ativas.
     * @return array Array de CampaignDTO
     */
    public function findAllActive(): array {
        $sql = '
            SELECT id_campanha AS id, titulo, descricao, data_inicio, data_fim, status, id_usuario
            FROM campanha
            WHERE status = \'ATIVA\' AND excluida = 0
            ORDER BY titulo ASC
        ';
        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            error_log('Erro ao buscar campanhas ativas: ' . $this->conn->error);
            return [];
        }

        $campanhas = [];
        while ($dados = $resultado->fetch_assoc()) {
            $dados['status'] = strtoupper((string)$dados['status']);
            if ($this->shouldHideCampaignTitle((string)($dados['titulo'] ?? ''))) {
                continue;
            }
            $campanhas[] = CampaignDTO::fromArray($dados);
        }

        return $campanhas;
    }

    /**
     * Busca campanha por ID com suas necessidades.
     * @param int $id
     * @return CampaignDTO|null
     */
    public function findById(int $id, bool $includeDeleted = true): ?CampaignDTO {
        $sql = '
            SELECT id_campanha AS id, titulo, descricao, data_inicio, data_fim, status, id_usuario
            FROM campanha
            WHERE id_campanha = ?
        ';
        if (!$includeDeleted) {
            $sql .= ' AND excluida = 0';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de campanha: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $dados = $resultado->fetch_assoc();
            $stmt->close();

            $campanha = CampaignDTO::fromArray($dados);
            $campanha->necessidades = $this->getNecessidades($id);
            return $campanha;
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca campanha por título.
     * @param string $titulo
     * @return CampaignDTO|null
     */
    public function findByTitle(string $titulo): ?CampaignDTO {
        $sql = '
            SELECT id_campanha AS id, titulo, descricao, data_inicio, data_fim, status, id_usuario
            FROM campanha
            WHERE titulo = ? AND status = \'ATIVA\' AND excluida = 0
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca por título: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('s', $titulo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $dados = $resultado->fetch_assoc();
            $stmt->close();

            $campanha = CampaignDTO::fromArray($dados);
            $campanha->necessidades = $this->getNecessidades((int)$dados['id']);
            return $campanha;
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca necessidades de uma campanha.
     * @param int $campanha_id
     * @return array
     */
    public function getNecessidades(int $campanha_id): array {
        $sql = '
            SELECT n.id_necessidade AS id, n.id_categoria AS categoria_id, ci.nome AS categoria_nome,
                   n.quantidade_necessaria, n.descricao
            FROM necessidade n
            INNER JOIN categoria_item ci ON n.id_categoria = ci.id_categoria
            WHERE n.id_campanha = ?
            ORDER BY ci.nome ASC
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de necessidades: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $campanha_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $necessidades = [];
        while ($item = $resultado->fetch_assoc()) {
            $necessidades[] = $item;
        }

        $stmt->close();
        return $necessidades;
    }

    /**
     * Busca todas as campanhas.
     * @return array
     */
    public function findAll(): array {
        $sql = '
            SELECT id_campanha AS id, titulo, descricao, data_inicio, data_fim, status, id_usuario
            FROM campanha
            WHERE excluida = 0
            ORDER BY titulo ASC
        ';
        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            error_log('Erro ao buscar todas as campanhas: ' . $this->conn->error);
            return [];
        }

        $campanhas = [];
        while ($dados = $resultado->fetch_assoc()) {
            $dados['status'] = strtoupper((string)$dados['status']);
            if ($this->shouldHideCampaignTitle((string)($dados['titulo'] ?? ''))) {
                continue;
            }
            $campanhas[] = CampaignDTO::fromArray($dados);
        }

        return $campanhas;
    }

    /**
     * Verifica se a campanha já possui uma necessidade para a categoria informada.
     * @param int $campanhaId
     * @param int $categoriaId
     * @return bool
     */
    public function necessidadeExists(int $campanhaId, int $categoriaId): bool {
        $sql = '
            SELECT id_necessidade
            FROM necessidade
            WHERE id_campanha = ? AND id_categoria = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar verificação de necessidade: ' . $this->conn->error);
            return true;
        }

        $stmt->bind_param('ii', $campanhaId, $categoriaId);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $exists = $resultado->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Cadastra uma necessidade/card em uma campanha.
     * @param int $campanhaId
     * @param int $categoriaId
     * @param string $descricao
     * @param int $quantidade
     * @return bool
     */
    public function createNecessidade(int $campanhaId, int $categoriaId, string $descricao, int $quantidade): bool {
        $sql = '
            INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
            VALUES (?, ?, ?, ?)
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar cadastro de necessidade: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('iisi', $campanhaId, $categoriaId, $descricao, $quantidade);
        $sucesso = $stmt->execute();

        if (!$sucesso) {
            error_log('Erro ao cadastrar necessidade: ' . $stmt->error);
        }

        $stmt->close();
        return $sucesso;
    }

    /**
     * Atualiza descricao e quantidade de uma necessidade/card da campanha.
     * @param int $necessidadeId
     * @param int $campanhaId
     * @param string $descricao
     * @param int $quantidade
     * @return bool
     */
    public function updateNecessidade(int $necessidadeId, int $campanhaId, string $descricao, int $quantidade): bool {
        $sql = '
            UPDATE necessidade
            SET descricao = ?, quantidade_necessaria = ?
            WHERE id_necessidade = ? AND id_campanha = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualizacao de necessidade: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('siii', $descricao, $quantidade, $necessidadeId, $campanhaId);
        $sucesso = $stmt->execute();

        if (!$sucesso) {
            error_log('Erro ao atualizar necessidade: ' . $stmt->error);
        }

        $stmt->close();
        return $sucesso;
    }

    /**
     * Cria uma nova campanha ativa.
     * @param string $titulo
     * @param string $descricao
     * @param int $usuarioId
     * @return int|null
     */
    public function createActiveCampaign(string $titulo, string $descricao, int $usuarioId): ?int {
        $sql = '
            INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), \'ATIVA\', ?)
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar cadastro de campanha: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('ssi', $titulo, $descricao, $usuarioId);
        $sucesso = $stmt->execute();

        if (!$sucesso) {
            error_log('Erro ao cadastrar campanha: ' . $stmt->error);
            $stmt->close();
            return null;
        }

        $campanhaId = (int)$this->conn->insert_id;
        $stmt->close();
        return $campanhaId > 0 ? $campanhaId : null;
    }

    /**
     * Remove uma necessidade/card de uma campanha.
     * @param int $necessidadeId
     * @param int $campanhaId
     * @return bool
     */
    public function deleteNecessidade(int $necessidadeId, int $campanhaId): bool {
        $sql = 'DELETE FROM necessidade WHERE id_necessidade = ? AND id_campanha = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar exclusão de necessidade: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('ii', $necessidadeId, $campanhaId);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados > 0;
    }

    /**
     * Encerra a campanha sem apagar o histórico.
     * @param int $campanhaId
     * @return bool
     */
    public function closeCampaign(int $campanhaId): bool {
        $sql = "UPDATE campanha SET status = 'ENCERRADA' WHERE id_campanha = ? AND status <> 'ENCERRADA' AND excluida = 0 LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar encerramento de campanha: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $campanhaId);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados > 0;
    }

    /**
     * Reabre a campanha para voltar a receber doações.
     * @param int $campanhaId
     * @return bool
     */
    public function reopenCampaign(int $campanhaId): bool {
        $sql = "UPDATE campanha SET status = 'ATIVA' WHERE id_campanha = ? AND status <> 'ATIVA' AND excluida = 0 LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar reabertura de campanha: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $campanhaId);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados > 0;
    }

    /**
     * Atualiza o titulo de uma campanha.
     * @param int $campanhaId
     * @param string $titulo
     * @return bool
     */
    public function renameCampaign(int $campanhaId, string $titulo): bool {
        $sql = 'UPDATE campanha SET titulo = ? WHERE id_campanha = ? AND excluida = 0 LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar renomeacao de campanha: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $titulo, $campanhaId);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados >= 0;
    }

    /**
     * Oculta uma campanha da gestao e dos cards publicos sem apagar historico.
     * @param int $campanhaId
     * @return bool
     */
    public function softDeleteCampaign(int $campanhaId): bool {
        $sql = 'UPDATE campanha SET excluida = 1 WHERE id_campanha = ? AND excluida = 0 LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar exclusao logica de campanha: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $campanhaId);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados > 0;
    }
}



