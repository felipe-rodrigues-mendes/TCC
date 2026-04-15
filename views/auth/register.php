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

            <label for="senha">Senha (m&iacute;nimo 6 caracteres) <span class="required-mark">*</span></label>
            <input type="password" name="senha" id="senha" required minlength="6">

            <label for="confirmar_senha">Confirmar senha <span class="required-mark">*</span></label>
            <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6">

            <div class="terms-group">
                <input type="checkbox" name="aceita_termos" id="aceita_termos" value="1" <?php echo !empty($_POST['aceita_termos']) ? 'checked' : ''; ?> required>
                <label for="aceita_termos">Concordo com os termos de uso <span class="required-mark">*</span></label>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <p>J&aacute; tem conta? <a href="index.php?page=login">Fa&ccedil;a login aqui</a></p>
    </section>
</main>

<footer>
    <p>&copy; 2026 ConectaSolid&aacute;ria</p>
</footer>

</body>
</html>
