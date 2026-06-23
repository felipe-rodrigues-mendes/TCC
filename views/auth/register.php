<?php
// View: Cadastro/Register
// Renderizada por AuthController::register()
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - ConectaSolid&aacute;ria</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .mensagem {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .form-section {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .form-section h2 {
            margin-bottom: 30px;
            text-align: center;
            color: var(--preto);
        }

        .form-section p {
            text-align: center;
            margin-top: 20px;
            color: var(--texto-suave);
        }

        .form-section a {
            color: var(--azul-principal);
            text-decoration: none;
            font-weight: bold;
        }

        .form-section a:hover {
            text-decoration: underline;
        }

        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 4px 0 16px;
        }

        .terms-group input[type="checkbox"] {
            width: auto;
            margin: 3px 0 0;
        }

        .terms-group label {
            margin: 0;
            font-weight: 600;
            line-height: 1.4;
        }

        .required-mark {
            color: #dc2626;
            font-weight: 700;
        }

        .required-note {
            margin: 0 0 14px;
            font-size: 13px;
            color: var(--texto-suave);
            text-align: left;
        }

        .field-hint {
            margin: 6px 0 14px;
            font-size: 12px;
            color: var(--texto-suave);
            text-align: left;
        }

        .password-input-wrapper {
            position: relative;
            margin-bottom: 8px;
        }

        .password-input-wrapper input {
            margin-bottom: 0;
            padding-right: 44px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #2563eb;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .password-toggle i {
            font-size: 16px;
        }

        .password-toggle:hover {
            color: #1d4ed8;
        }

        .password-checklist {
            margin: 8px 0 16px;
        }

        .password-checklist-title {
            margin: 0 0 8px;
            font-size: 14px;
            text-align: left;
            color: #1e3a8a;
            font-weight: 700;
        }

        .password-checklist-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .password-checklist-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
        }

        .password-checklist-item i {
            width: 16px;
        }

        .password-checklist-item.invalid {
            color: #b91c1c;
        }

        .password-checklist-item.valid {
            color: #15803d;
        }

        .terms-box {
            margin: 8px 0 12px;
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #f8fafc;
        }

        .terms-title {
            margin: 0 0 8px;
            font-size: 15px;
            color: var(--preto);
        }

        .terms-text {
            margin: 0 0 8px;
            font-size: 13px;
            color: var(--texto-suave);
            text-align: left;
            line-height: 1.45;
        }

        .terms-list {
            margin: 0 0 10px 18px;
            padding: 0;
            color: var(--texto-suave);
            font-size: 13px;
            line-height: 1.45;
        }

        .terms-version {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            text-align: left;
        }

        [data-theme="dark"] .form-section label {
            color: var(--texto);
        }

        [data-theme="dark"] .form-section .required-note {
            color: var(--texto-suave);
        }

        [data-theme="dark"] .field-hint {
            color: var(--texto-suave);
        }

        [data-theme="dark"] .password-toggle {
            color: #93c5fd;
        }

        [data-theme="dark"] .password-toggle:hover {
            color: #bfdbfe;
        }

        [data-theme="dark"] .password-checklist-title {
            color: #93c5fd;
        }

        [data-theme="dark"] .password-checklist-item.invalid {
            color: #fca5a5;
        }

        [data-theme="dark"] .password-checklist-item.valid {
            color: #86efac;
        }

        [data-theme="dark"] .terms-box {
            background: var(--bg-surface-soft);
            border-color: var(--border-strong);
        }

        [data-theme="dark"] .terms-title {
            color: var(--texto);
        }

        [data-theme="dark"] .terms-text,
        [data-theme="dark"] .terms-list,
        [data-theme="dark"] .terms-version {
            color: var(--texto-suave);
        }

        [data-theme="dark"] .mensagem {
            background-color: rgba(239, 68, 68, 0.2);
            color: #fecaca;
            border-color: rgba(252, 165, 165, 0.4);
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="form-section">
        <h2>Cadastro de Doador</h2>

        <?php if (!empty($mensagem)) : ?>
            <p class="mensagem">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">

            <p class="required-note"><span class="required-mark">*</span> Campos obrigat&oacute;rios</p>

            <label for="nome">Nome completo <span class="required-mark">*</span></label>
            <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>

            <label for="email">E-mail <span class="required-mark">*</span></label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

            <label for="senha">Senha <span class="required-mark">*</span></label>
            <div class="password-input-wrapper">
                <input type="password" name="senha" id="senha" required minlength="8" maxlength="70" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,70}" title="Use de 8 a 70 caracteres com letra mai&uacute;scula, min&uacute;scula, n&uacute;mero e caractere especial (ex.: ! @ # $ % &amp; *)." aria-describedby="password-checklist">
                <button type="button" class="password-toggle" data-target="senha" aria-label="Mostrar senha">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>

            <div class="password-checklist" id="password-checklist" aria-live="polite">
                <p class="password-checklist-title">Sua senha deve conter:</p>
                <ul class="password-checklist-list">
                    <li class="password-checklist-item invalid" id="password-rule-length">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                        <span>de 8 a 70 caracteres</span>
                    </li>
                    <li class="password-checklist-item invalid" id="password-rule-lower">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                        <span>letra min&uacute;scula</span>
                    </li>
                    <li class="password-checklist-item invalid" id="password-rule-upper">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                        <span>letra mai&uacute;scula</span>
                    </li>
                    <li class="password-checklist-item invalid" id="password-rule-number">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                        <span>n&uacute;mero</span>
                    </li>
                    <li class="password-checklist-item invalid" id="password-rule-special">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                        <span>s&iacute;mbolo (ex.: ! @ # $ % &amp; *)</span>
                    </li>
                </ul>
            </div>

            <label for="confirmar_senha">Confirmar senha <span class="required-mark">*</span></label>
            <div class="password-input-wrapper">
                <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="8" maxlength="70" autocomplete="new-password">
                <button type="button" class="password-toggle" data-target="confirmar_senha" aria-label="Mostrar senha">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>

            <div class="terms-box" id="termos-lgpd" role="note" aria-label="Termos de uso e politica de privacidade">
                <h3 class="terms-title">Termos de Uso e Pol&iacute;tica de Privacidade (LGPD)</h3>
                <p class="terms-text">
                    Para criar sua conta, precisamos tratar seus dados pessoais para autentica&ccedil;&atilde;o,
                    seguran&ccedil;a do acesso e opera&ccedil;&atilde;o das doa&ccedil;&otilde;es na plataforma.
                </p>
                <ul class="terms-list">
                    <li>Dados coletados no cadastro: nome, e-mail e credenciais de acesso.</li>
                    <li>Uso dos dados: identifica&ccedil;&atilde;o do doador, gest&atilde;o de conta e preven&ccedil;&atilde;o de fraude.</li>
                    <li>Seus direitos (LGPD): confirma&ccedil;&atilde;o, acesso, corre&ccedil;&atilde;o e exclus&atilde;o, conforme legisla&ccedil;&atilde;o aplic&aacute;vel.</li>
                </ul>
                <p class="terms-version">Vers&atilde;o dos termos: v1.0</p>
            </div>

            <div class="terms-group">
                <input type="checkbox" name="aceita_termos" id="aceita_termos" value="1" <?php echo !empty($_POST['aceita_termos']) ? 'checked' : ''; ?> required>
                <label for="aceita_termos">
                    Declaro que li e concordo com os Termos de Uso e com a Pol&iacute;tica de Privacidade, nos termos da LGPD
                    (Lei n&ordm; 13.709/2018) <span class="required-mark">*</span>
                </label>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <p>J&aacute; tem conta? <a href="index.php?page=login">Fa&ccedil;a login aqui</a></p>
    </section>
</main>

<footer>
    <p>&copy; 2026 ConectaSolid&aacute;ria</p>
    <a class="footer-social-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer" aria-label="Instagram do ConectaSolidária">
        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
    </a>
</footer>

<script>
    (function () {
        const senhaInput = document.getElementById('senha');
        const rules = [
            {
                element: document.getElementById('password-rule-length'),
                test: function (value) {
                    return value.length >= 8 && value.length <= 70;
                }
            },
            {
                element: document.getElementById('password-rule-lower'),
                test: function (value) {
                    return /[a-z]/.test(value);
                }
            },
            {
                element: document.getElementById('password-rule-upper'),
                test: function (value) {
                    return /[A-Z]/.test(value);
                }
            },
            {
                element: document.getElementById('password-rule-number'),
                test: function (value) {
                    return /\d/.test(value);
                }
            },
            {
                element: document.getElementById('password-rule-special'),
                test: function (value) {
                    return /[^A-Za-z0-9]/.test(value);
                }
            }
        ];

        function updateRuleState(element, isValid) {
            if (!element) {
                return;
            }

            element.classList.toggle('valid', isValid);
            element.classList.toggle('invalid', !isValid);

            const icon = element.querySelector('i');
            if (!icon) {
                return;
            }

            icon.classList.toggle('fa-circle-check', isValid);
            icon.classList.toggle('fa-circle-xmark', !isValid);
        }

        function refreshPasswordRules() {
            if (!senhaInput) {
                return;
            }

            const value = senhaInput.value;
            rules.forEach(function (rule) {
                updateRuleState(rule.element, rule.test(value));
            });
        }

        if (senhaInput) {
            senhaInput.addEventListener('input', refreshPasswordRules);
            refreshPasswordRules();
        }

        document.querySelectorAll('.password-toggle').forEach(function (button) {
            const targetId = button.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);

            if (!targetInput) {
                return;
            }

            button.addEventListener('click', function () {
                const showPassword = targetInput.type === 'password';
                targetInput.type = showPassword ? 'text' : 'password';

                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !showPassword);
                    icon.classList.toggle('fa-eye-slash', showPassword);
                }

                button.setAttribute('aria-label', showPassword ? 'Ocultar senha' : 'Mostrar senha');
            });
        });
    })();
</script>

</body>
</html>
