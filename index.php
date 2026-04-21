<?php
/**
 * Front Controller - Ponto de entrada único da aplicação
 * Roteia requisições para os controllers apropriados com base no parâmetro 'page'
 * 
 * Refatorado de: estrutura flat PHP anterior
 * MVC Pattern implementado
 */

// Inicia sessão e carrega dependências
session_start();
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/SessionManager.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DonationController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/DistributionController.php';
require_once __DIR__ . '/controllers/PublicController.php';

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Inicia o gerenciador de sessão
SessionManager::start();

// Obtém a página solicitada via GET['page'], padrão é 'home'
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'home';

// Mapeia rotas para controllers e métodos
$routes = [
    // Rotas públicas
    'home' => ['PublicController', 'home'],
    'about' => ['PublicController', 'about'],
    'contact' => ['PublicController', 'contact'],
    'collection_points' => ['PublicController', 'collectionPoints'],
    
    // Rotas de autenticação
    'login' => ['AuthController', 'login'],
    'register' => ['AuthController', 'register'],
    'forgot_password' => ['AuthController', 'forgotPassword'],
    'logout' => ['AuthController', 'logout'],
    
    // Rotas de doações
    'donation_create' => ['DonationController', 'createForm'],
    'donation_store' => ['DonationController', 'store'],
    'donation_edit' => ['DonationController', 'editForm'],
    'donation_update' => ['DonationController', 'update'],
    'donation_cancel' => ['DonationController', 'cancel'],
    'donation_receipt' => ['DonationController', 'receipt'],
    'dashboard' => ['DonationController', 'dashboard'],
    
    // Rotas administrativas
    'admin_donations' => ['AdminController', 'manageDonations'],
    'admin_receive_donation' => ['AdminController', 'receiveDonation'],
    'admin_inventory' => ['AdminController', 'viewInventory'],
    'admin_collection_points' => ['AdminController', 'manageCollectionPoints'],
    'admin_create_collection_point' => ['AdminController', 'createCollectionPoint'],
    'admin_toggle_collection_point_status' => ['AdminController', 'toggleCollectionPointStatus'],
    'admin_campaign_cards' => ['AdminController', 'manageCampaignCards'],
    'admin_create_campaign' => ['AdminController', 'createCampaign'],
    'admin_delete_campaign_card' => ['AdminController', 'deleteCampaignCard'],
    'admin_delete_campaign' => ['AdminController', 'closeCampaign'],
    'admin_close_campaign' => ['AdminController', 'closeCampaign'],
    'admin_reopen_campaign' => ['AdminController', 'reopenCampaign'],
    'admin_rename_campaign' => ['AdminController', 'renameCampaign'],
    'admin_upload_campaign_image' => ['AdminController', 'uploadCampaignImage'],
    'admin_add_campaign_card' => ['AdminController', 'addCampaignCard'],
    'admin_promote_user' => ['AdminController', 'promoteUser'],
    'admin_demote_user' => ['AdminController', 'demoteUser'],
    'admin_toggle_user_status' => ['AdminController', 'toggleUserStatus'],
    'admin_delete_user' => ['AdminController', 'toggleUserStatus'],
    'admin_distributions' => ['DistributionController', 'manage'],
];

// Alternative route aliases for backward compatibility
$aliases = [
    'painel' => 'dashboard',
    'doacao' => 'donation_create',
    'admin' => 'admin_donations',
    'estoque' => 'admin_inventory',
];

// Resolve alias se existir
if (isset($aliases[$page])) {
    $page = $aliases[$page];
}

// Obtém controller e método para a rota
if (isset($routes[$page])) {
    [$controllerName, $methodName] = $routes[$page];
    
    try {
        // Instancia controller e chama método
        $controller = new $controllerName();
        
        if (method_exists($controller, $methodName)) {
            // Executa o método do controller
            $controller->$methodName();
        } else {
            // Método não encontrado
            notFound("Ação não encontrada: {$controllerName}::{$methodName}");
        }
    } catch (Exception $e) {
        // Erro ao executar controller
        error_log("Erro interno em rota '{$page}': " . $e->getMessage());
        error();
    }
} else {
    // Rota não encontrada
    notFound("Página não encontrada: {$page}");
}

/**
 * Sanitiza entrada de usuário
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Renderiza página de erro 404
 * @param string $mensagem
 */
function notFound($mensagem) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Página Não Encontrada</title>
        <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
        <script>
            (function () {
                const storageKey = 'conecta-theme';
                try {
                    const savedTheme = window.localStorage.getItem(storageKey);
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const theme = (savedTheme === 'light' || savedTheme === 'dark')
                        ? savedTheme
                        : (prefersDark ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>
        <style>
            .error-header {
                margin-bottom: 30px;
                padding-top: 12px;
                display: flex;
                justify-content: center;
            }

            .erro-404 {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 70vh;
                text-align: center;
                max-width: 600px;
                margin: 0 auto;
            }

            .erro-404 h1 {
                font-size: 80px;
                color: #ef4444;
                margin: 0;
            }

            .erro-404 p {
                font-size: 20px;
                color: var(--texto-suave);
                margin: 15px 0;
            }

            .erro-404 a {
                display: inline-block;
                padding: 12px 24px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                font-weight: bold;
                transition: 0.3s;
            }

            .erro-404 a:hover {
                background: #1d4ed8;
            }
        </style>
    </head>
    <body>
        <header class="error-header">
            <div class="logo-container">
                <a href="index.php">
              <img src="assets/uploads/logo.PNG" class="logo" alt="Logo ConectaSolidária">
                </a>
            </div>
        </header>

        <main>
            <div class="erro-404">
                <h1>404</h1>
                <p><?php echo htmlspecialchars($mensagem); ?></p>
                <a href="index.php">Voltar para Home</a>
            </div>
        </main>

        <footer>
            <p>© 2026 ConectaSolidária</p>
        </footer>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Renderiza página de erro genérica
 * @param string $mensagem
 */
function error($mensagem = "Ocorreu um erro interno ao processar sua requisição.") {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro - ConectaSolidária</title>
        <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
        <script>
            (function () {
                const storageKey = 'conecta-theme';
                try {
                    const savedTheme = window.localStorage.getItem(storageKey);
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const theme = (savedTheme === 'light' || savedTheme === 'dark')
                        ? savedTheme
                        : (prefersDark ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>
        <style>
            .error-header {
                margin-bottom: 30px;
                padding-top: 12px;
                display: flex;
                justify-content: center;
            }

            .erro-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 70vh;
                text-align: center;
                max-width: 600px;
                margin: 0 auto;
            }

            .erro-box h1 {
                font-size: 36px;
                color: #ef4444;
                margin: 0 0 15px;
            }

            .erro-box p {
                font-size: 16px;
                color: var(--texto-suave);
                margin: 10px 0;
            }

            .erro-box .detalhes {
                background: #fee2e2;
                border: 1px solid #fca5a5;
                color: #7f1d1d;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: left;
                font-family: monospace;
                font-size: 13px;
                overflow-x: auto;
            }

            [data-theme="dark"] .erro-box .detalhes {
                background: rgba(239, 68, 68, 0.2);
                border-color: rgba(252, 165, 165, 0.4);
                color: #fecaca;
            }

            .erro-box a {
                display: inline-block;
                padding: 12px 24px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                font-weight: bold;
            }

            .erro-box a:hover {
                background: #1d4ed8;
            }
        </style>
    </head>
    <body>
        <header class="error-header">
            <div class="logo-container">
                <a href="index.php">
                    <img src="assets/uploads/logo.PNG" class="logo" alt="Logo ConectaSolidária">
                </a>
            </div>
        </header>

        <main>
            <div class="erro-box">
                <h1><i class="fas fa-exclamation-circle"></i> Erro</h1>
                <p>Ocorreu um erro ao processar sua requisição.</p>
                <a href="index.php">Voltar para Home</a>
            </div>
        </main>

        <footer>
            <p>© 2026 ConectaSolidária</p>
        </footer>
    </body>
    </html>
    <?php
    exit;
}
