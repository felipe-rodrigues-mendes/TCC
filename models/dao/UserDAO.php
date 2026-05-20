<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../dto/UserDTO.php';

/**
 * Data Access Object para gerenciar operações de usuários no banco de dados.
 * Compatível com o schema atual: usuário + login + perfil.
 */
class UserDAO {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->ensureProfilePhotoColumn();
    }

    private function hasUsuarioColumn(string $columnName): bool {
        $columnNameEscaped = $this->conn->real_escape_string($columnName);
        $sql = "SHOW COLUMNS FROM usuario LIKE '{$columnNameEscaped}'";
        $result = $this->conn->query($sql);

        if ($result === false) {
            error_log('Erro ao validar coluna em usuario: ' . $this->conn->error);
            return false;
        }

        return $result->num_rows > 0;
    }

    private function ensureLgpdColumns(): bool {
        $hasAceiteLgpd = $this->hasUsuarioColumn('aceite_termos_lgpd');
        $hasAceiteData = $this->hasUsuarioColumn('aceite_termos_data');
        $hasAceiteVersao = $this->hasUsuarioColumn('aceite_termos_versao');

        if ($hasAceiteLgpd && $hasAceiteData && $hasAceiteVersao) {
            return true;
        }

        if (!$hasAceiteLgpd) {
            $sql = 'ALTER TABLE usuario ADD COLUMN aceite_termos_lgpd TINYINT(1) NOT NULL DEFAULT 0 AFTER id_perfil';
            if (!$this->conn->query($sql) && (int)$this->conn->errno !== 1060) {
                error_log('Erro ao adicionar coluna aceite_termos_lgpd em usuario: ' . $this->conn->error);
                return false;
            }
        }

        if (!$hasAceiteData) {
            $sql = 'ALTER TABLE usuario ADD COLUMN aceite_termos_data DATETIME NULL AFTER aceite_termos_lgpd';
            if (!$this->conn->query($sql) && (int)$this->conn->errno !== 1060) {
                error_log('Erro ao adicionar coluna aceite_termos_data em usuario: ' . $this->conn->error);
                return false;
            }
        }

        if (!$hasAceiteVersao) {
            $sql = 'ALTER TABLE usuario ADD COLUMN aceite_termos_versao VARCHAR(20) DEFAULT NULL AFTER aceite_termos_data';
            if (!$this->conn->query($sql) && (int)$this->conn->errno !== 1060) {
                error_log('Erro ao adicionar coluna aceite_termos_versao em usuario: ' . $this->conn->error);
                return false;
            }
        }

        return $this->hasUsuarioColumn('aceite_termos_lgpd')
            && $this->hasUsuarioColumn('aceite_termos_data')
            && $this->hasUsuarioColumn('aceite_termos_versao');
    }

    private function ensureProfilePhotoColumn(): bool {
        if ($this->hasUsuarioColumn('foto_perfil')) {
            return true;
        }

        $sql = 'ALTER TABLE usuario ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL AFTER telefone';
        if (!$this->conn->query($sql) && (int)$this->conn->errno !== 1060) {
            error_log('Erro ao adicionar coluna foto_perfil em usuario: ' . $this->conn->error);
            return false;
        }

        return $this->hasUsuarioColumn('foto_perfil');
    }

    private function getPerfilIdByNome(string $nome, bool $criarSeNaoExistir = true): ?int {
        $sql = 'SELECT id_perfil FROM perfil WHERE nome = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca de perfil: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('s', $nome);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $perfil = $resultado->fetch_assoc();
            $stmt->close();
            return (int)$perfil['id_perfil'];
        }

        $stmt->close();

        if (!$criarSeNaoExistir) {
            return null;
        }

        $sqlInsert = 'INSERT INTO perfil (nome) VALUES (?)';
        $stmtInsert = $this->conn->prepare($sqlInsert);

        if (!$stmtInsert) {
            error_log('Erro ao preparar criação de perfil: ' . $this->conn->error);
            return null;
        }

        $stmtInsert->bind_param('s', $nome);

        if (!$stmtInsert->execute()) {
            error_log('Erro ao criar perfil: ' . $stmtInsert->error);
            $stmtInsert->close();
            return null;
        }

        $perfilId = $this->conn->insert_id;
        $stmtInsert->close();
        return $perfilId;
    }

    /**
     * Busca usuário por email ou username.
     * @return UserDTO|null
     */
    public function findByEmail(string $email): ?UserDTO {
        $sql = '
            SELECT
                u.id_usuario AS id,
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.foto_perfil,
                u.ativo,
                u.id_perfil,
                p.nome AS perfil_nome,
                l.username,
                l.senha_hash AS senha,
                l.ultimo_acesso
            FROM usuario u
            LEFT JOIN login l ON l.id_usuario = u.id_usuario
            LEFT JOIN perfil p ON p.id_perfil = u.id_perfil
            WHERE u.email = ? OR l.username = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca por email: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('ss', $email, $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $dados = $resultado->fetch_assoc();
            $stmt->close();
            return UserDTO::fromArray($dados);
        }

        $stmt->close();
        return null;
    }

    /**
     * Busca usuário por ID.
     * @param int $id
     * @return UserDTO|null
     */
    public function findById(int $id): ?UserDTO {
        $sql = '
            SELECT
                u.id_usuario AS id,
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.foto_perfil,
                u.ativo,
                u.id_perfil,
                p.nome AS perfil_nome,
                l.username,
                l.senha_hash AS senha,
                l.ultimo_acesso
            FROM usuario u
            LEFT JOIN login l ON l.id_usuario = u.id_usuario
            LEFT JOIN perfil p ON p.id_perfil = u.id_perfil
            WHERE u.id_usuario = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca por ID: ' . $this->conn->error);
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $dados = $resultado->fetch_assoc();
            $stmt->close();
            return UserDTO::fromArray($dados);
        }

        $stmt->close();
        return null;
    }

    /**
     * Verifica se email já existem.
     * @param string $email
     * @return bool
     */
    public function exists(string $email): bool {
        $sql = '
            SELECT u.id_usuario
            FROM usuario u
            LEFT JOIN login l ON l.id_usuario = u.id_usuario
            WHERE u.email = ? OR l.username = ?
            LIMIT 1
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao verificar existência de usuário: ' . $this->conn->error);
            return true;
        }

        $stmt->bind_param('ss', $email, $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $exists = $resultado->num_rows > 0;

        $stmt->close();
        return $exists;
    }

    /**
     * Registra novo usuário e seu login.
     * @param string $nome
     * @param string $email
     * @param string $senha
     * @param bool $aceitaTermos
     * @param string $termosVersao
     * @return int|null ID do usuário criado ou null se falhar
     */
    public function register(
        string $nome,
        string $email,
        string $senha,
        bool $aceitaTermos = false,
        string $termosVersao = 'v1.0'
    ): ?int {
        $nome = trim($nome);
        $email = trim($email);
        $termosVersao = trim($termosVersao);

        if (empty($nome) || empty($email) || empty($senha)) {
            error_log('Dados incompletos para registro de usuário');
            return null;
        }

        if (!$aceitaTermos) {
            error_log('Tentativa de registro sem aceite de termos LGPD');
            return null;
        }

        if ($termosVersao === '') {
            $termosVersao = 'v1.0';
        }

        if ($this->exists($email)) {
            error_log('Usuário já existe: email=' . $email);
            return null;
        }

        if (!$this->ensureLgpdColumns()) {
            error_log('Falha ao garantir colunas LGPD em usuario para registro');
            return null;
        }

        $perfilId = $this->getPerfilIdByNome('doador');
        if ($perfilId === null) {
            return null;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        if ($senhaHash === false) {
            error_log('Falha ao gerar hash de senha');
            return null;
        }

        try {
            Database::getInstance()->beginTransaction();

            $sqlUsuario = '
                INSERT INTO usuario (
                    nome,
                    email,
                    telefone,
                    ativo,
                    id_perfil,
                    aceite_termos_lgpd,
                    aceite_termos_data,
                    aceite_termos_versao
                )
                VALUES (?, ?, ?, 1, ?, ?, IF(? = 1, NOW(), NULL), ?)
            ';
            $stmtUsuario = $this->conn->prepare($sqlUsuario);
            if (!$stmtUsuario) {
                throw new Exception('Erro ao preparar registro de usuário: ' . $this->conn->error);
            }

            $telefone = '';
            $aceiteTermosInt = $aceitaTermos ? 1 : 0;
            $stmtUsuario->bind_param(
                'sssiiis',
                $nome,
                $email,
                $telefone,
                $perfilId,
                $aceiteTermosInt,
                $aceiteTermosInt,
                $termosVersao
            );

            if (!$stmtUsuario->execute()) {
                throw new Exception('Erro ao executar registro de usuário: ' . $stmtUsuario->error);
            }

            $usuarioId = $this->conn->insert_id;
            $stmtUsuario->close();

            $sqlLogin = 'INSERT INTO login (id_usuario, username, senha_hash, ultimo_acesso) VALUES (?, ?, ?, NULL)';
            $stmtLogin = $this->conn->prepare($sqlLogin);
            if (!$stmtLogin) {
                throw new Exception('Erro ao preparar login do usuário: ' . $this->conn->error);
            }

            $username = $email;
            $stmtLogin->bind_param('iss', $usuarioId, $username, $senhaHash);

            if (!$stmtLogin->execute()) {
                throw new Exception('Erro ao executar login do usuário: ' . $stmtLogin->error);
            }

            $stmtLogin->close();
            Database::getInstance()->commit();
            return $usuarioId;
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            error_log('Erro ao registrar usuário: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica credenciais de login.
     * @param string $email
     * @param string $senha
     * @return UserDTO|null
     */
    public function login(string $email, string $senha): ?UserDTO {
        $usuario = $this->findByEmail($email);

        if ($usuario === null || empty($usuario->senha_hash)) {
            return null;
        }

        if (!$usuario->ativo) {
            return null;
        }

        if (!password_verify($senha, $usuario->senha_hash)) {
            return null;
        }

        $sqlUpdate = 'UPDATE login SET ultimo_acesso = NOW() WHERE id_usuario = ?';
        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        if ($stmtUpdate) {
            $stmtUpdate->bind_param('i', $usuario->id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        return $usuario;
    }

    /**
     * Atualiza a senha de um usuário pelo e-mail.
     * @param string $email
     * @param string $novaSenha
     * @return bool
     */
    public function updatePasswordByEmail(string $email, string $novaSenha): bool {
        $usuario = $this->findByEmail($email);
        if ($usuario === null) {
            return false;
        }

        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        if ($senhaHash === false) {
            error_log('Falha ao gerar hash da nova senha.');
            return false;
        }

        $sql = 'UPDATE login SET senha_hash = ? WHERE id_usuario = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('Erro ao preparar atualizacao de senha: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $senhaHash, $usuario->id);
        $sucesso = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $sucesso && $afetados > 0;
    }

    /**
     * Atualiza a foto de perfil do usuario.
     * @param int $usuarioId
     * @param string $fotoPerfil
     * @return bool
     */
    public function updateProfilePhoto(int $usuarioId, string $fotoPerfil): bool {
        if ($usuarioId <= 0 || trim($fotoPerfil) === '') {
            return false;
        }

        if (!$this->ensureProfilePhotoColumn()) {
            return false;
        }

        $sql = 'UPDATE usuario SET foto_perfil = ? WHERE id_usuario = ? AND ativo = 1 LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualizacao da foto de perfil: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $fotoPerfil, $usuarioId);
        $executou = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        return $executou && $afetados >= 0;
    }

    /**
     * Desativa e anonimiza a própria conta, preservando histórico relacionado.
     * @param int $usuarioId
     * @return bool
     */
    public function deleteOwnAccount(int $usuarioId): bool {
        if ($usuarioId <= 0) {
            return false;
        }

        $nomeAnonimo = 'Conta excluída';
        $emailAnonimo = 'conta-excluida-' . $usuarioId . '@anon.local';
        $telefoneAnonimo = '';

        try {
            Database::getInstance()->beginTransaction();

            $sqlLogin = 'DELETE FROM login WHERE id_usuario = ?';
            $stmtLogin = $this->conn->prepare($sqlLogin);
            if (!$stmtLogin) {
                throw new Exception('Erro ao preparar remocao de login: ' . $this->conn->error);
            }

            $stmtLogin->bind_param('i', $usuarioId);
            if (!$stmtLogin->execute()) {
                throw new Exception('Erro ao remover login: ' . $stmtLogin->error);
            }
            $stmtLogin->close();

            $sqlUsuario = '
                UPDATE usuario
                SET nome = ?,
                    email = ?,
                    telefone = ?,
                    foto_perfil = NULL,
                    ativo = 0
                WHERE id_usuario = ?
                LIMIT 1
            ';
            $stmtUsuario = $this->conn->prepare($sqlUsuario);
            if (!$stmtUsuario) {
                throw new Exception('Erro ao preparar exclusao da conta: ' . $this->conn->error);
            }

            $stmtUsuario->bind_param('sssi', $nomeAnonimo, $emailAnonimo, $telefoneAnonimo, $usuarioId);
            if (!$stmtUsuario->execute()) {
                throw new Exception('Erro ao excluir conta: ' . $stmtUsuario->error);
            }

            $afetados = $stmtUsuario->affected_rows;
            $stmtUsuario->close();

            Database::getInstance()->commit();
            return $afetados >= 0;
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            error_log('Erro ao excluir própria conta: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna usuários com perfil específico.
     * @param string $tipo 'admin' ou 'doador'
     * @return array
     */
    public function findByType(string $tipo): array {
        $sql = '
            SELECT
                u.id_usuario AS id,
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.foto_perfil,
                u.ativo,
                u.id_perfil,
                p.nome AS perfil_nome,
                l.username,
                l.senha_hash AS senha,
                l.ultimo_acesso
            FROM usuario u
            LEFT JOIN login l ON l.id_usuario = u.id_usuario
            INNER JOIN perfil p ON p.id_perfil = u.id_perfil
            WHERE p.nome = ?
            ORDER BY u.nome ASC
        ';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar busca por tipo: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('s', $tipo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuarios = [];
        while ($dados = $resultado->fetch_assoc()) {
            $usuarios[] = UserDTO::fromArray($dados);
        }

        $stmt->close();
        return $usuarios;
    }

    /**
     * Retorna todos os usuários cadastrados.
     * @return array
     */
    public function findAll(): array {
        $sql = '
            SELECT
                u.id_usuario AS id,
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.foto_perfil,
                u.ativo,
                u.id_perfil,
                p.nome AS perfil_nome,
                l.username,
                l.senha_hash AS senha,
                l.ultimo_acesso
            FROM usuario u
            LEFT JOIN login l ON l.id_usuario = u.id_usuario
            LEFT JOIN perfil p ON p.id_perfil = u.id_perfil
            ORDER BY u.nome ASC
        ';
        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            error_log('Erro ao buscar todos os usuários: ' . $this->conn->error);
            return [];
        }

        $usuarios = [];
        while ($dados = $resultado->fetch_assoc()) {
            $usuarios[] = UserDTO::fromArray($dados);
        }

        return $usuarios;
    }

    /**
     * Promove um usuário existente para administrador.
     * @param int $usuarioId
     * @return bool
     */
    public function promoteToAdmin(int $usuarioId): bool {
        $perfilId = $this->getPerfilIdByNome('admin');
        if ($perfilId === null) {
            return false;
        }

        $sql = 'UPDATE usuario SET id_perfil = ? WHERE id_usuario = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar promoção de usuário para admin: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('ii', $perfilId, $usuarioId);
        $executou = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        if (!$executou) {
            return false;
        }

        return $afetados >= 0;
    }

    /**
     * Atualiza o status ativo/inativo do usuário.
     * @param int $usuarioId
     * @param bool $ativo
     * @return bool
     */
    public function updateActiveStatus(int $usuarioId, bool $ativo): bool {
        $sql = 'UPDATE usuario SET ativo = ? WHERE id_usuario = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar atualização de status do usuário: ' . $this->conn->error);
            return false;
        }

        $ativoInt = $ativo ? 1 : 0;
        $stmt->bind_param('ii', $ativoInt, $usuarioId);
        $executou = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        if (!$executou) {
            return false;
        }

        return $afetados >= 0;
    }

    /**
     * Rebaixa um administrador para perfil de doador.
     * @param int $usuarioId
     * @return bool
     */
    public function demoteToDoador(int $usuarioId): bool {
        $perfilId = $this->getPerfilIdByNome('doador');
        if ($perfilId === null) {
            return false;
        }

        $sql = 'UPDATE usuario SET id_perfil = ? WHERE id_usuario = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log('Erro ao preparar rebaixamento de usuário para doador: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('ii', $perfilId, $usuarioId);
        $executou = $stmt->execute();
        $afetados = $stmt->affected_rows;
        $stmt->close();

        if (!$executou) {
            return false;
        }

        return $afetados >= 0;
    }
}
