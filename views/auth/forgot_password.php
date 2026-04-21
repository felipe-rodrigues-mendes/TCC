<?php
// View: Recuperação de senha
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - ConectaSolidária</title>
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
        }

        .mensagem.sucesso {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .mensagem.erro {
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
            margin-bottom: 20px;
            text-align: center;
            color: var(--preto);
        }

        .form-section p {
            text-align: center;
            margin-top: 20px;
            color: var(--texto-suave);
        }

        .helper-text {
            text-align: center;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="form-section">
        <h2>Redefinir Senha</h2>
        <p class="helper-text">Informe seu e-mail e escolha uma nova senha para voltar a acessar sua conta.</p>

        <?php if (!empty($mensagem)) : ?>
            <p class="mensagem <?php echo htmlspecialchars($tipoMensagem); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">

            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" required>

            <label for="nova_senha">Nova senha</label>
            <input type="password" name="nova_senha" id="nova_senha" required minlength="6">

            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6">

            <button type="submit">Salvar nova senha</button>
        </form>

        <p><a href="index.php?page=login">Voltar para o login</a></p>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>

