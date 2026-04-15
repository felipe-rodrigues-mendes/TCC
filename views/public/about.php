<?php
// View: About
// Renderizada por PublicController::about()
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre - ConectaSolidária</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .about-container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .about-container h1 {
            color: var(--azul-principal);
            margin-bottom: 20px;
        }

        .about-container p, .about-container li {
            line-height: 1.8;
            color: var(--texto);
            margin-bottom: 15px;
            font-size: 16px;
        }

        .about-container ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        .about-container h2 {
            color: var(--preto);
            margin-top: 30px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <section class="about-container">
        <h1>Sobre ConectaSolidária</h1>

        <p>
            ConectaSolidária é uma plataforma de coordenação de doações para situações de calamidade pública. 
            Nosso objetivo é conectar doadores dispostos a ajudar com comunidades afetadas por desastres naturais 
            ou emergências humanitárias.
        </p>

        <h2>Nossa Missão</h2>
        <p>
            Democratizar e otimizar o processo de arrecadação e distribuição de doações em situações de emergência, 
            garantindo que recursos cheguem rapidamente a quem mais precisa.
        </p>

        <h2>Como Funciona</h2>
        <ul>
            <li><strong>Campanhas Ativas:</strong> Visualize campanhas de doação para diferentes regiões</li>
            <li><strong>Doações Direcionadas:</strong> Doe especificamente para as necessidades da campanha que escolher</li>
            <li><strong>Pontos de Coleta:</strong> Encontre os endereços dos pontos de coleta mais próximos</li>
            <li><strong>Rastreamento:</strong> Acompanhe o status de suas doações em tempo real</li>
        </ul>

        <h2>Tecnologia</h2>
        <p>
            ConectaSolidária foi desenvolvida com tecnologias robustas e escaláveis, utilizando PHP, MySQL e 
            uma arquitetura MVC para garantir manutenibilidade e segurança dos dados.
        </p>

        <h2>Contato</h2>
        <p>
            Tem dúvidas ou sugestões? <a href="index.php?page=contact">Entre em contato conosco</a>.
        </p>
    </section>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
