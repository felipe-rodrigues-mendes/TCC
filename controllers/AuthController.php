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

            if (empty($nome) || empty($email) || empty($senha)) {
                $mensagem = 'Nome, email e senha são obrigatórios.';
            } elseif (strlen($senha) < 6) {
                $mensagem = 'Senha deve ter no mínimo 6 caracteres.';
            } else {
                $usuarioId = $this->userDAO->register($nome, $email, $senha);

                if ($usuarioId !== null) {
                    header('Location: index.php?page=login&cadastro=sucesso');
                    exit;
                }

                $mensagem = 'Erro ao cadastrar. Verifique email e tente novamente.';
            }
        }

        include __DIR__ . '/../views/auth/register.php';
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
            } elseif (strlen($novaSenha) < 6) {
                $mensagem = 'A nova senha deve ter no mínimo 6 caracteres.';
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
     * Faz logout do usuário
     */
    public function logout(): void {
        SessionManager::destroy();
        header('Location: index.php');
        exit;
    }
}


