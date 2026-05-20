<?php

require_once __DIR__ . '/../models/dao/UserDAO.php';
require_once __DIR__ . '/SessionManager.php';

/**
 * Controller para gerenciar autenticação (login, logout, register).
 * Compatível com o schema atual: usuário + login + perfil.
 */
class AuthController {
    private $userDAO;

    public function __construct() {
        $this->userDAO = new UserDAO();
    }

    /**
     * Renderiza página de login
     */
    public function login(): void {
        $mensagem = "";
        $tipoMensagem = "";
        $redirect = trim((string)($_GET['redirect'] ?? ''));

        if (str_starts_with($redirect, 'index.php')) {
            $_SESSION['redirect_to'] = $redirect;
        }

        if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
            $mensagem = 'Cadastro realizado com sucesso! Faça login.';
            $tipoMensagem = 'sucesso';
        }

        if (isset($_GET['conta']) && $_GET['conta'] === 'excluida') {
            $mensagem = 'Sua conta foi excluída com sucesso.';
            $tipoMensagem = 'sucesso';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $mensagem = 'Sua sessão expirou. Atualize a página e tente novamente.';
                $tipoMensagem = 'erro';
                include __DIR__ . '/../views/auth/login.php';
                return;
            }

            $email = trim($_POST['email'] ?? '');
            $senha = trim($_POST['senha'] ?? '');

            if (empty($email) || empty($senha)) {
                $mensagem = 'Email e senha são obrigatórios.';
                $tipoMensagem = 'erro';
            } else {
                $usuarioEncontrado = $this->userDAO->findByEmail($email);
                $usuario = $this->userDAO->login($email, $senha);

                if ($usuario !== null) {
                    SessionManager::setUser($usuario->id, $usuario->nome, $usuario->tipo);

                    $redirect = SessionManager::getRedirectTo();
                    if ($redirect) {
                        header('Location: ' . $redirect);
                    } elseif ($usuario->tipo === 'admin') {
                        header('Location: index.php?page=admin');
                    } else {
                        header('Location: index.php?page=dashboard');
                    }
                    exit;
                }

                if ($usuarioEncontrado !== null && !$usuarioEncontrado->ativo) {
                    $mensagem = 'Este usuário está desativado. Entre em contato com o administrador.';
                } else {
                    $mensagem = 'Email ou senha incorretos.';
                }
                $tipoMensagem = 'erro';
            }
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Renderiza página de cadastro
     */
    public function register(): void {
        $mensagem = '';
        $redirect = trim((string)($_GET['redirect'] ?? ''));
        $termosVersao = 'v1.0';

        if (str_starts_with($redirect, 'index.php')) {
            $_SESSION['redirect_to'] = $redirect;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $mensagem = 'Sua sessão expirou. Atualize a página e tente novamente.';
                include __DIR__ . '/../views/auth/register.php';
                return;
            }

            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = trim($_POST['senha'] ?? '');
            $confirmarSenha = trim((string)($_POST['confirmar_senha'] ?? ''));
            $aceitaTermos = (($_POST['aceita_termos'] ?? '') === '1');

            if (empty($nome) || empty($email) || empty($senha) || empty($confirmarSenha)) {
                $mensagem = 'Nome, email, senha e confirmação de senha são obrigatórios.';
            } elseif (($erroSenha = $this->getPasswordPolicyError($senha)) !== '') {
                $mensagem = $erroSenha;
            } elseif ($senha !== $confirmarSenha) {
                $mensagem = 'A confirmação da senha não confere.';
            } elseif (!$aceitaTermos) {
                $mensagem = 'Você precisa concordar com os termos de uso para continuar.';
            } else {
                $usuarioId = $this->userDAO->register($nome, $email, $senha, $aceitaTermos, $termosVersao);

                if ($usuarioId !== null) {
                    header('Location: index.php?page=login&cadastro=sucesso');
                    exit;
                }

                $mensagem = 'Erro ao cadastrar. Verifique email e tente novamente.';
            }
        }

        include __DIR__ . '/../views/auth/register.php';
    }

    private function getPasswordPolicyError(string $senha): string {
        if (strlen($senha) < 8) {
            return 'A senha deve ter no mínimo 8 caracteres.';
        }

        if (strlen($senha) > 70) {
            return 'A senha deve ter no máximo 70 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $senha)) {
            return 'A senha deve ter pelo menos uma letra maiúscula.';
        }

        if (!preg_match('/[a-z]/', $senha)) {
            return 'A senha deve ter pelo menos uma letra minúscula.';
        }

        if (!preg_match('/\\d/', $senha)) {
            return 'A senha deve ter pelo menos um número.';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
            return 'A senha deve ter pelo menos um caractere especial.';
        }

        return '';
    }
    /**
     * Renderiza e processa recuperação de senha.
     */
    public function forgotPassword(): void {
        $mensagem = '';
        $tipoMensagem = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $mensagem = 'Sua sessão expirou. Atualize a página e tente novamente.';
                $tipoMensagem = 'erro';
                include __DIR__ . '/../views/auth/forgot_password.php';
                return;
            }

            $email = trim((string)($_POST['email'] ?? ''));
            $novaSenha = trim((string)($_POST['nova_senha'] ?? ''));
            $confirmacaoSenha = trim((string)($_POST['confirmar_senha'] ?? ''));

            if ($email === '' || $novaSenha === '' || $confirmacaoSenha === '') {
                $mensagem = 'Preencha e-mail, nova senha e confirmação.';
                $tipoMensagem = 'erro';
            } elseif (($erroSenha = $this->getPasswordPolicyError($novaSenha)) !== '') {
                $mensagem = $erroSenha;
                $tipoMensagem = 'erro';
            } elseif ($novaSenha !== $confirmacaoSenha) {
                $mensagem = 'A confirmação da senha não confere.';
                $tipoMensagem = 'erro';
            } elseif ($this->userDAO->updatePasswordByEmail($email, $novaSenha)) {
                header('Location: index.php?page=login&redefinicao=sucesso');
                exit;
            } else {
                $mensagem = 'Não foi possível redefinir a senha. Verifique o e-mail informado.';
                $tipoMensagem = 'erro';
            }
        }

        include __DIR__ . '/../views/auth/forgot_password.php';
    }

    /**
     * Exclui a própria conta do usuário autenticado.
     */
    public function deleteAccount(): void {
        SessionManager::requireLogin('index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!SessionManager::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            SessionManager::setMessage('Sua sessão expirou. Atualize a página e tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $usuarioId = (int)(SessionManager::getUserId() ?? 0);
        $senha = trim((string)($_POST['senha_confirmacao'] ?? ''));
        $confirmacao = (string)($_POST['confirmar_exclusao'] ?? '');

        if ($usuarioId <= 0 || $senha === '' || $confirmacao !== '1') {
            SessionManager::setMessage('Confirme a exclusão e informe sua senha atual.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        $usuario = $this->userDAO->findById($usuarioId);
        if ($usuario === null || !$usuario->ativo || empty($usuario->senha_hash)) {
            SessionManager::setMessage('Não foi possível localizar sua conta ativa.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if ($usuario->tipo === 'admin') {
            SessionManager::setMessage('Contas administrativas não podem ser excluídas por este fluxo.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!password_verify($senha, $usuario->senha_hash)) {
            SessionManager::setMessage('Senha atual incorreta. A conta não foi excluída.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!$this->userDAO->deleteOwnAccount($usuarioId)) {
            SessionManager::setMessage('Não foi possível excluir sua conta. Tente novamente.', 'erro');
            header('Location: index.php?page=dashboard');
            exit;
        }

        SessionManager::destroy();
        header('Location: index.php?page=login&conta=excluida');
        exit;
    }

    /**
     * Faz logout do usuário
     */
    public function logout(): void {
        SessionManager::destroy();
        header('Location: index.php');
        exit;
    }
}
