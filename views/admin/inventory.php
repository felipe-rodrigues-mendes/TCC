<?php
// View: Admin - Inventory
// Renderizada por AdminController::viewInventory()
SessionManager::requireRole('admin');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Admin</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-container > h2 {
            margin-bottom: 10px;
        }

        .admin-container > p {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .estoque-grid {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-top: 25px;
            scroll-behavior: smooth;
            align-items: stretch;
        }

        .estoque-grid::-webkit-scrollbar {
            height: 10px;
        }

        .estoque-grid::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 999px;
        }

        .estoque-grid::-webkit-scrollbar-thumb {
            background: #2563eb;
            border-radius: 999px;
        }

        .card-estoque {
            flex: 0 0 380px;
            min-width: 380px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 6px solid #2563eb;
        }

        .card-estoque:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .ponto-titulo {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 8px;
        }

        .endereco-info {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .itens-lista {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            list-style: none;
            padding-left: 0;
        }

        .itens-lista li {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #374151;
        }

        .itens-lista li:last-child {
            border-bottom: none;
        }

        .qtd-badge {
            background: #2563eb;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }

        .vazio {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="admin-container">
        <h2>Visualizar Estoque</h2>
        <p>Confira os itens armazenados em cada ponto de coleta.</p>

        <?php if (empty($estoquesAgrupados)): ?>
            <div class="vazio">
                <p><i class="fas fa-inbox"></i></p>
                <p>Nenhum item em estoque no momento.</p>
            </div>
        <?php else: ?>
            <div class="estoque-grid">
                <?php foreach ($estoquesAgrupados as $ponto => $dados): ?>
                    <div class="card-estoque">
                        <div class="ponto-titulo">
                            <i class="fas fa-warehouse"></i> <?php echo htmlspecialchars($ponto); ?>
                        </div>
                        
                        <div class="endereco-info">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($dados['logradouro']); ?>
                        </div>

                        <?php if (empty($dados['itens'])): ?>
                            <p class="muted">Este ponto ainda não possui itens em estoque.</p>
                        <?php else: ?>
                            <ul class="itens-lista">
                                <?php foreach ($dados['itens'] as $item): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($item['item']); ?></span>
                                        <span class="qtd-badge"><?php echo (int)$item['quantidade']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
    <a class="footer-social-link" href="https://www.instagram.com/conecta_solidaria/" target="_blank" rel="noopener noreferrer" aria-label="Instagram do ConectaSolidária">
        <i class="fab fa-instagram" aria-hidden="true"></i> @conecta_solidaria
    </a>
</footer>

</body>
</html>

