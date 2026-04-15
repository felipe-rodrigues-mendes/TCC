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
        .about-shell {
            display: grid;
            gap: 28px;
        }

        .about-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            min-height: 430px;
            background:
                linear-gradient(120deg, rgba(15, 23, 42, 0.78), rgba(37, 99, 235, 0.55)),
                url('assets/uploas/enchente Rio Grande do Sul.jpg') center/cover;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            display: flex;
            align-items: flex-end;
        }

        .about-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.24), transparent 30%),
                linear-gradient(to top, rgba(15, 23, 42, 0.5), transparent 45%);
        }

        .about-hero-content {
            position: relative;
            z-index: 1;
            color: white;
            padding: 36px;
            max-width: 760px;
        }

        .about-kicker {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.24);
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .about-hero h1 {
            font-size: clamp(34px, 5vw, 54px);
            line-height: 1.05;
            margin-bottom: 16px;
        }

        .about-hero p {
            font-size: 18px;
            line-height: 1.7;
            max-width: 660px;
            color: rgba(255,255,255,0.92);
            margin-bottom: 0;
        }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .impact-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            border: 1px solid #dbeafe;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
        }

        .impact-card i {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 14px;
        }

        .impact-card h2 {
            font-size: 21px;
            margin-bottom: 10px;
            color: #111827;
        }

        .impact-card p {
            color: #4b5563;
            line-height: 1.7;
            margin: 0;
        }

        .about-story {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            align-items: stretch;
        }

        .story-card,
        .gallery-card,
        .steps-card,
        .cta-card {
            background: white;
            border-radius: 22px;
            padding: 28px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .story-card h2,
        .gallery-card h2,
        .steps-card h2,
        .cta-card h2 {
            margin-top: 0;
            margin-bottom: 14px;
            color: #111827;
            font-size: 28px;
        }

        .story-card p,
        .gallery-card p,
        .steps-card p,
        .cta-card p {
            color: #4b5563;
            line-height: 1.8;
        }

        .values-list {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .value-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .value-row i {
            color: #2563eb;
            font-size: 18px;
            margin-top: 3px;
        }

        .value-row strong {
            display: block;
            margin-bottom: 4px;
            color: #111827;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .gallery-grid img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .step-card {
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            border: 1px solid #dbeafe;
            border-radius: 18px;
            padding: 22px 18px;
        }

        .step-number {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1e3a8a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 14px;
        }

        .step-card h3 {
            margin: 0 0 10px;
            font-size: 19px;
            color: #111827;
        }

        .step-card p {
            margin: 0;
            font-size: 14px;
            line-height: 1.65;
        }

        .cta-card {
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.96), rgba(30, 58, 138, 0.94)),
                url('assets/uploas/enchente Bahia.jpg') center/cover;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.2);
        }

        .cta-card > * {
            position: relative;
            z-index: 1;
        }

        .cta-card h2,
        .cta-card p {
            color: white;
        }

        .cta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .cta-btn.primary {
            background: white;
            color: #1d4ed8;
        }

        .cta-btn.secondary {
            background: rgba(255,255,255,0.14);
            color: white;
            border: 1px solid rgba(255,255,255,0.28);
        }

        @media (max-width: 980px) {
            .impact-grid,
            .about-story,
            .steps-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .about-hero {
                min-height: 360px;
            }

            .about-hero-content {
                padding: 24px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../layouts/navbar.php'); ?>

<main>
    <div class="about-shell">
        <section class="about-hero">
            <div class="about-hero-content">
                <span class="about-kicker">Rede de Solidariedade</span>
                <h1>Transformamos ajuda em ação organizada para quem mais precisa.</h1>
                <p>ConectaSolidária é uma plataforma criada para aproximar doadores, pontos de coleta e campanhas humanitárias, tornando a resposta em situações de calamidade mais rápida, transparente e eficiente.</p>
            </div>
        </section>

        <section class="impact-grid">
            <article class="impact-card">
                <i class="fas fa-hand-holding-heart"></i>
                <h2>Propósito Social</h2>
                <p>Unimos pessoas dispostas a ajudar com comunidades afetadas por enchentes, desastres naturais e emergências humanitárias.</p>
            </article>

            <article class="impact-card">
                <i class="fas fa-truck-fast"></i>
                <h2>Resposta Mais Rápida</h2>
                <p>Organizamos campanhas, pontos de coleta, estoque e distribuição para reduzir atrasos na chegada dos itens essenciais.</p>
            </article>

            <article class="impact-card">
                <i class="fas fa-route"></i>
                <h2>Transparência</h2>
                <p>O doador acompanha sua contribuição desde o cadastro até o envio da ajuda para a cidade e o destino atendidos.</p>
            </article>
        </section>

        <section class="about-story">
            <article class="story-card">
                <h2>Nossa Missão</h2>
                <p>Em momentos de crise, a solidariedade precisa de organização. O ConectaSolidária nasceu para digitalizar e centralizar o processo de arrecadação e distribuição de doações, ajudando equipes e voluntários a direcionar recursos com mais segurança, controle e agilidade.</p>
                <p>Mais do que um sistema de cadastro, a proposta é oferecer uma ponte entre quem quer ajudar e quem precisa receber apoio em situações de vulnerabilidade.</p>

                <div class="values-list">
                    <div class="value-row">
                        <i class="fas fa-people-group"></i>
                        <div>
                            <strong>Empatia que conecta</strong>
                            A plataforma foi pensada para fortalecer a rede de apoio entre doadores, voluntários e comunidades atingidas.
                        </div>
                    </div>

                    <div class="value-row">
                        <i class="fas fa-box-open"></i>
                        <div>
                            <strong>Gestão com clareza</strong>
                            Campanhas, itens, estoque e distribuições ficam registrados para reduzir desperdícios e melhorar a logística.
                        </div>
                    </div>

                    <div class="value-row">
                        <i class="fas fa-shield-heart"></i>
                        <div>
                            <strong>Ajuda com confiança</strong>
                            O acompanhamento da doação gera mais transparência e fortalece a confiança no processo solidário.
                        </div>
                    </div>
                </div>
            </article>

            <aside class="gallery-card">
                <h2>Solidariedade em Movimento</h2>
                <p>As campanhas atendidas pelo sistema representam realidades que exigem mobilização rápida, cuidado coletivo e logística humanitária eficiente.</p>

                <div class="gallery-grid">
                    <img src="assets/uploas/enchente Bahia.jpg" alt="Ações de solidariedade e apoio humanitário na Bahia">
                    <img src="assets/uploas/Minas gerais.jpg" alt="Comunidades afetadas recebendo apoio em Minas Gerais">
                    <img src="assets/uploas/enchente Sao paulo.jpg" alt="Mobilização social de ajuda em São Paulo">
                    <img src="assets/uploas/santa catarina.jpg" alt="Apoio emergencial e rede de solidariedade em Santa Catarina">
                </div>
            </aside>
        </section>

        <section class="steps-card">
            <h2>Como o Sistema Funciona</h2>
            <p>O fluxo foi estruturado para facilitar a jornada do doador e, ao mesmo tempo, dar suporte à operação administrativa das campanhas.</p>

            <div class="steps-grid">
                <article class="step-card">
                    <div class="step-number">1</div>
                    <h3>Escolha a campanha</h3>
                    <p>O usuário visualiza campanhas ativas, identifica as necessidades e seleciona a cidade ou região que deseja apoiar.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">2</div>
                    <h3>Registre a doação</h3>
                    <p>A plataforma gera um comprovante em PDF com código da doação e QR Code para apresentação no ponto de coleta.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">3</div>
                    <h3>Recebimento e triagem</h3>
                    <p>A equipe administrativa confirma o recebimento, atualiza o estoque e organiza a saída dos itens para distribuição.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">4</div>
                    <h3>Acompanhe a entrega</h3>
                    <p>O doador pode acompanhar o andamento da contribuição até o envio e a chegada ao destino final da campanha.</p>
                </article>
            </div>
        </section>

        <section class="cta-card">
            <h2>Ajuda organizada salva tempo. Tempo salva vidas.</h2>
            <p>Se você quer contribuir com campanhas humanitárias ou entender melhor como o sistema apoia a logística solidária, continue navegando e participe dessa rede de apoio.</p>

            <div class="cta-actions">
                <a href="index.php?page=collection_points" class="cta-btn primary">
                    <i class="fas fa-location-dot"></i> Ver pontos de coleta
                </a>
                <a href="index.php?page=contact" class="cta-btn secondary">
                    <i class="fas fa-envelope"></i> Entrar em contato
                </a>
            </div>
        </section>
    </div>
</main>

<footer>
    <p>© 2026 ConectaSolidária</p>
</footer>

</body>
</html>
