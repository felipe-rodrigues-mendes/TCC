<?php

require_once __DIR__ . '/../models/dao/CampaignDAO.php';
require_once __DIR__ . '/../models/dao/PointOfCollectionDAO.php';
require_once __DIR__ . '/SessionManager.php';

/**
 * Controller para gerenciar páginas públicas.
 * Compatível com o schema atual: campanha, necessidade, ponto_coleta, endereco.
 */
class PublicController {
    private $campaignDAO;
    private $pointDAO;

    public function __construct() {
        $this->campaignDAO = new CampaignDAO();
        $this->pointDAO = new PointOfCollectionDAO();
    }

    private function resolveCampaignImage(int $campaignId, string $titulo, array $imagensCampanhas): string {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $relativePath = 'assets/uploas/campaign_' . $campaignId . '.' . $ext;
            $absolutePath = __DIR__ . '/../' . $relativePath;

            if (is_file($absolutePath)) {
                return $relativePath;
            }
        }

        return $imagensCampanhas[$titulo] ?? 'assets/uploas/logo.PNG';
    }

    private function getOfficialCollectionPoints(): array {
        $allowedNames = ['Escola Técnica de Ceilândia', 'Ginásio de Taguatinga'];
        $points = array_filter(
            $this->pointDAO->findAll(),
            static function (array $point) use ($allowedNames): bool {
                return in_array((string)$point['nome'], $allowedNames, true);
            }
        );

        if (empty($points)) {
            $points = $this->pointDAO->findAll();
        }

        return array_map([$this, 'formatPointForPublicView'], array_values($points));
    }

    private function formatPointForPublicView(array $point): array {
        $logradouro = trim((string)($point['logradouro'] ?? ''));
        $cidade = trim((string)($point['cidade'] ?? ''));
        $estado = trim((string)($point['estado'] ?? ''));
        $cep = trim((string)($point['cep'] ?? ''));
        $numero = trim((string)($point['numero'] ?? ''));
        $complemento = trim((string)($point['complemento'] ?? ''));
        $endereco = implode(', ', array_filter([$logradouro, $numero, $complemento]));

        return [
            'id' => (int)($point['id'] ?? 0),
            'nome' => (string)($point['nome'] ?? ''),
            'logradouro' => $logradouro,
            'numero' => $numero,
            'complemento' => $complemento,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'telefone' => trim((string)($point['telefone'] ?? '')),
            'endereco' => $endereco !== '' ? $endereco : $logradouro,
            'map_query' => implode(', ', array_filter([$logradouro, $cidade, $estado, $cep])),
        ];
    }

    /**
     * Renderiza homepage com carousel de campanhas
     */
    public function home(): void {
        $campanhas = $this->campaignDAO->findAll();

        $imagensCampanhas = [
            'Rio Grande do Sul' => 'assets/uploads/enchente Rio Grande do Sul.jpg',
            'Bahia' => 'assets/uploads/enchente Bahia.jpg',
            'Minas Gerais' => 'assets/uploads/Minas gerais.jpg',
            'São Paulo' => 'assets/uploads/enchente Sao paulo.jpg',
            'Santa Catarina' => 'assets/uploads/santa catarina.jpg',
            'Paraná' => 'assets/uploads/Parána.jpg'
        ];

        foreach ($campanhas as &$campanha) {
            $campanha->imagem = $this->resolveCampaignImage((int)$campanha->id, (string)$campanha->titulo, $imagensCampanhas);
            $campanha->necessidades = $this->campaignDAO->getNecessidades((int)$campanha->id);
        }
        unset($campanha);

        $pontosDestaque = array_slice($this->getOfficialCollectionPoints(), 0, 3);
        $pontosCount = count($this->getOfficialCollectionPoints());

        include __DIR__ . '/../views/public/home.php';
    }

    /**
     * Renderiza página sobre
     */
    public function about(): void {
        include __DIR__ . '/../views/public/about_v2.php';
    }

    /**
     * Renderiza página de pontos de coleta
     */
    public function collectionPoints(): void {
        $cidadeSelecionada = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
        $pontos = $this->getOfficialCollectionPoints();

        if (!empty($cidadeSelecionada)) {
            $pontos = array_values(array_filter($pontos, static function (array $ponto) use ($cidadeSelecionada): bool {
                return mb_strtolower((string)$ponto['cidade']) === mb_strtolower($cidadeSelecionada);
            }));
        }

        include __DIR__ . '/../views/public/collection_points.php';
    }

    /**
     * Renderiza página de pontos com endereços e mapa
     */
    public function contact(): void {
        $pontosContato = $this->getOfficialCollectionPoints();

        include __DIR__ . '/../views/public/contact.php';
    }
}
