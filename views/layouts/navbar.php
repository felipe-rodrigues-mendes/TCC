<?php
// Navbar compartilhada - refatorada de menu.php
// Esta navbar e incluida em todas as paginas via layout base
SessionManager::start();
$isAuthenticated = SessionManager::isAuthenticated();
$isAdmin = SessionManager::isAdmin();
$userName = SessionManager::getUserName();
?>

<script>
    (function () {
        const storageKey = 'conecta-theme';
        try {
            const savedTheme = window.localStorage.getItem(storageKey);
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const resolvedTheme = (savedTheme === 'light' || savedTheme === 'dark')
                ? savedTheme
                : (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', resolvedTheme);
        } catch (error) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

<header class="site-header" id="siteHeader">
    <div class="header-top">
        <div class="logo-container">
            <a href="index.php">
         <img src="assets/uploads/logo.PNG" class="logo" alt="Logo ConectaSolidária">
</a>
    </div>

        <div class="header-actions">
            <button type="button" id="themeToggle" class="theme-toggle" aria-pressed="false" aria-label="Alternar tema">
                <i class="fas fa-cog" aria-hidden="true"></i>
                <span class="theme-toggle-label">Modo escuro</span>
            </button>
            <button type="button" id="menuToggle" class="menu-toggle" aria-expanded="false" aria-controls="mainNav" aria-label="Abrir menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <nav class="main-nav" id="mainNav">
        <?php if ($isAuthenticated && $isAdmin): ?>
            <a href="index.php?page=admin_donations"><i class="fas fa-user-shield"></i> Admin</a>
            <a href="index.php?page=admin_campaign_cards"><i class="fas fa-layer-group"></i> Campanhas</a>
            <a href="index.php?page=admin_inventory"><i class="fas fa-boxes-stacked"></i> Estoque</a>
            <a href="index.php?page=admin_distributions"><i class="fas fa-truck"></i> Distribui&ccedil;&otilde;es</a>
            <a href="index.php?page=dashboard"><i class="fas fa-user-circle"></i> Painel</a>
            <a href="index.php"><i class="fas fa-home"></i> In&iacute;cio</a>
            <a href="index.php?page=collection_points"><i class="fas fa-location-dot"></i> Pontos de Coleta</a>
            <a href="index.php?page=donation_create"><i class="fas fa-hand-holding-heart"></i> Fazer Doa&ccedil;&atilde;o</a>
            <a href="index.php?page=contact"><i class="fas fa-envelope"></i> Contato</a>
            <a href="index.php?page=about"><i class="fas fa-info-circle"></i> Sobre</a>
        <?php else: ?>
            <a href="index.php"><i class="fas fa-home"></i> In&iacute;cio</a>
            <?php if (!$isAuthenticated): ?>
                <a href="index.php?page=register"><i class="fa fa-user"></i> Cadastro</a>
                <a href="index.php?page=login"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
            <a href="index.php?page=collection_points"><i class="fas fa-location-dot"></i> Pontos de Coleta</a>
            <?php if ($isAuthenticated): ?>
                <a href="index.php?page=donation_create"><i class="fas fa-hand-holding-heart"></i> Fazer Doa&ccedil;&atilde;o</a>
            <?php endif; ?>
            <a href="index.php?page=contact"><i class="fas fa-envelope"></i> Contato</a>
            <a href="index.php?page=about"><i class="fas fa-info-circle"></i> Sobre</a>
            <?php if ($isAuthenticated): ?>
                <a href="index.php?page=dashboard"><i class="fas fa-user-circle"></i> Painel</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isAuthenticated): ?>
            <a href="index.php?page=logout" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        <?php endif; ?>
    </nav>
</header>

<script>
    (function () {
        const storageKey = 'conecta-theme';
        const root = document.documentElement;
        const header = document.getElementById('siteHeader');
        const nav = document.getElementById('mainNav');
        const themeToggle = document.getElementById('themeToggle');
        const menuToggle = document.getElementById('menuToggle');

        const syncThemeButton = () => {
            if (!themeToggle) {
                return;
            }
            const label = themeToggle.querySelector('.theme-toggle-label');
            const isDark = root.getAttribute('data-theme') === 'dark';
            themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            if (label) {
                label.textContent = isDark ? 'Modo claro' : 'Modo escuro';
            }
        };

        syncThemeButton();

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try {
                    window.localStorage.setItem(storageKey, next);
                } catch (error) {
                    // noop
                }
                syncThemeButton();
            });
        }

        if (menuToggle && header && nav) {
            const closeMenu = () => {
                header.classList.remove('nav-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            };

            menuToggle.addEventListener('click', () => {
                const isOpen = header.classList.toggle('nav-open');
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            nav.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1180) {
                        closeMenu();
                    }
                });
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 1180) {
                    closeMenu();
                }
            });
        }
    })();
</script>
