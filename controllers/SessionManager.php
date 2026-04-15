<?php

/**
 * Gerenciador centralizado de sessões e autenticação
 * Refatorado de: proteger.php (guard logic)
 * Elimina duplicação de verificações de autenticação em múltiplos arquivos
 */
class SessionManager {
    
    /**
     * Inicia/retoma sessão
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se usuário está autenticado
     * @return bool
     */
    public static function isAuthenticated(): bool {
        return isset($_SESSION["usuario_id"]) && $_SESSION["usuario_id"] !== null;
    }

    /**
     * Retorna ID do usuário autenticado
     * @return int|null
     */
    public static function getUserId(): ?int {
        return $_SESSION["usuario_id"] ?? null;
    }

    /**
     * Retorna nome do usuário autenticado
     * @return string|null
     */
    public static function getUserName(): ?string {
        return $_SESSION["usuario_nome"] ?? null;
    }

    /**
     * Retorna tipo do usuário (admin ou doador)
     * @return string|null
     */
    public static function getUserRole(): ?string {
        return $_SESSION["usuario_tipo"] ?? null;
    }

    /**
     * Verifica se usuário tem role específico
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool {
        return self::isAuthenticated() && self::getUserRole() === $role;
    }

    /**
     * Verifica se usuário autenticado é administrador
     * @return bool
     */
    public static function isAdmin(): bool {
        return self::hasRole("admin");
    }

    /**
     * Define dados de sessão após login bem-sucedido
     * @param int $id
     * @param string $nome
     * @param string $tipo
     */
    public static function setUser(int $id, string $nome, string $tipo): void {
        $_SESSION["usuario_id"] = $id;
        $_SESSION["usuario_nome"] = $nome;
        $_SESSION["usuario_tipo"] = $tipo;
    }

    /**
     * Destrói sessão (logout)
     */
    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Redireciona para login se não autenticado
     * @param string $redirectTo URL para redirecionar após login (opcional)
     */
    public static function requireLogin(?string $redirectTo = null): void {
        if (!self::isAuthenticated()) {
            if ($redirectTo) {
                $_SESSION["redirect_to"] = $redirectTo;
            }
            header("Location: index.php?page=login");
            exit;
        }
    }

    /**
     * Redireciona para dashboard se não tiver role específico
     * @param string $role
     */
    public static function requireRole(string $role): void {
        self::requireLogin();

        if (!self::hasRole($role)) {
            header("Location: index.php?page=dashboard");
            exit;
        }
    }

    /**
     * Salva mensagem flash em sessão
     * @param string $mensagem
     * @param string $tipo
     */
    public static function setMessage(string $mensagem, string $tipo = "sucesso"): void {
        $_SESSION["flash_message"] = [
            "mensagem" => $mensagem,
            "tipo" => $tipo
        ];
    }

    /**
     * Recupera e remove mensagem flash da sessão
     * @return array
     */
    public static function getMessage(): array {
        $flash = $_SESSION["flash_message"] ?? [
            "mensagem" => "",
            "tipo" => ""
        ];
        unset($_SESSION["flash_message"]);
        return $flash;
    }

    /**
     * Verifica redirect_to na sessão (para redirecionar após login)
     * @return string|null
     */
    public static function getRedirectTo(): ?string {
        $redirect = $_SESSION["redirect_to"] ?? null;
        unset($_SESSION["redirect_to"]);
        return $redirect;
    }

    /**
     * Gera ou recupera token CSRF da sessão.
     * @return string
     */
    public static function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valida token CSRF enviado via formulário.
     * @param string|null $token
     * @return bool
     */
    public static function validateCsrfToken(?string $token): bool {
        if ($token === null || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
