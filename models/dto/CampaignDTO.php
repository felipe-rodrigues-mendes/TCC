<?php

/**
 * Data Transfer Object para dados de campanha.
 * Compatível com o schema atual: campanha + necessidade.
 */
class CampaignDTO {
    public int $id;
    public int $id_campanha;
    public string $titulo;
    public string $descricao;
    public string $data_inicio;
    public string $data_fim;
    public string $status;
    public int $id_usuario;
    public string $imagem;
    public array $necessidades = [];

    public function __construct(
        int $id = 0,
        string $titulo = "",
        string $descricao = "",
        string $status = "ATIVA",
        string $data_inicio = "",
        string $data_fim = "",
        int $id_usuario = 0,
        array $necessidades = [],
        string $imagem = ""
    ) {
        $this->id = $id;
        $this->id_campanha = $id;
        $this->titulo = $titulo;
        $this->descricao = $descricao;
        $this->status = strtoupper($status);
        $this->data_inicio = $data_inicio;
        $this->data_fim = $data_fim;
        $this->id_usuario = $id_usuario;
        $this->imagem = $imagem;
        $this->necessidades = $necessidades;
    }

    public static function fromArray(array $data): CampaignDTO {
        $id = (int)($data['id'] ?? $data['id_campanha'] ?? 0);

        return new CampaignDTO(
            $id,
            (string)($data['titulo'] ?? ''),
            (string)($data['descricao'] ?? ''),
            (string)($data['status'] ?? 'ATIVA'),
            (string)($data['data_inicio'] ?? ''),
            (string)($data['data_fim'] ?? ''),
            (int)($data['id_usuario'] ?? 0),
            (array)($data['necessidades'] ?? []),
            (string)($data['imagem'] ?? '')
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'id_campanha' => $this->id_campanha,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
            'status' => $this->status,
            'id_usuario' => $this->id_usuario,
            'imagem' => $this->imagem,
            'necessidades' => $this->necessidades
        ];
    }
}

