<?php
// View: Redefinir senha
// Renderizada por AuthController::reset()
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - ConectaSolidária</title>
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

        .form-section a {
            color: var(--azul-principal);
            text-decoration: none;
            font-weight: bold;
        }

        .form-section a:hover {
            text-decoration: underline;
        }

        .descricao {
            text-align: center;
            margin-bottom: 25px;
            color: var(--texto-suave);
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="form-section">
        <h2>Redefinir Senha</h2>
        <p class="descricao">Digite sua nova senha abaixo.</p>

        <?php if (!empty($mensagem)) : ?>
            <p class="mensagem <?php echo htmlspecialchars($tipoMensagem ?? 'erro'); ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SessionManager::getCsrfToken()); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <label for="senha">Nova senha</label>
            <input type="password" name="senha" id="senha" required>

            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" name="confirmar_senha" id="confirmar_senha" required>

            <button type="submit">Alterar senha</button>
        </form>

        <p><a href="index.php?page=login">Voltar para o login</a></p>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>