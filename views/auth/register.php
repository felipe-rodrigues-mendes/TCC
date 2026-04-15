<?php
// View: Cadastro/Register
// Renderizada por AuthController::register()
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - ConectaSolidária</title>
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

            <label for="nome">Nome completo</label>
            <input type="text" name="nome" id="nome" required>

            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" required>

            <label for="senha">Senha (mínimo 6 caracteres)</label>
            <input type="password" name="senha" id="senha" required minlength="6">

            <button type="submit">Cadastrar</button>
        </form>

        <p>Já tem conta? <a href="index.php?page=login">Faça login aqui</a></p>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
