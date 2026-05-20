<?php

/**
 * Data Transfer Object para dados de doação.
 * Compatível com o schema atual: doação + item_doacao.
 */
class DonationDTO {
    public int $id;
    public int $id_doacao;
    public int $usuario_id;
    public int $campanha_id;
    public int $ponto_id;
    public string $descricao;
    public string $status;
    public string $data_doacao;
    public string $data_criacao;
    public string $campanha_nome;
    public string $ponto_nome;
    public string $codigo_publico;
    public array $itens = [];
    public array $rastreamento = [];

    public function __construct(
        int $id = 0,
        int $usuario_id = 0,
        int $campanha_id = 0,
        int $ponto_id = 0,
        string $descricao = "",
        string $status = "pendente",
        string $data_doacao = "",
        array $itens = [],
        string $campanha_nome = "",
        string $ponto_nome = "",
        string $codigo_publico = ""
    ) {
        $this->id = $id;
        $this->id_doacao = $id;
        $this->usuario_id = $usuario_id;
        $this->campanha_id = $campanha_id;
        $this->ponto_id = $ponto_id;
        $this->descricao = $descricao;
        $this->status = strtolower($status);
        $this->data_doacao = $data_doacao ?: date('Y-m-d');
        $this->data_criacao = $this->data_doacao;
        $this->campanha_nome = $campanha_nome;
        $this->ponto_nome = $ponto_nome;
        $this->codigo_publico = $codigo_publico;
        $this->itens = $itens;
    }

    public static function fromArray(array $data): DonationDTO {
        $id = (int)($data['id'] ?? $data['id_doacao'] ?? 0);
        $dataDoacao = (string)($data['data_doacao'] ?? $data['data_criacao'] ?? '');
        $status = (string)($data['status'] ?? 'pendente');

        return new DonationDTO(
            $id,
            (int)($data['usuario_id'] ?? $data['id_usuario'] ?? 0),
            (int)($data['campanha_id'] ?? $data['id_campanha'] ?? 0),
            (int)($data['ponto_id'] ?? $data['id_ponto'] ?? 0),
            (string)($data['descricao'] ?? ''),
            $status,
            $dataDoacao,
            (array)($data['itens'] ?? []),
            (string)($data['campanha_nome'] ?? ''),
            (string)($data['ponto_nome'] ?? ''),
            (string)($data['codigo_publico'] ?? '')
        );
    }

}

