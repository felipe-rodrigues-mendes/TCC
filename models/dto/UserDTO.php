<?php

/**
 * Data Transfer Object para dados de usuário.
 * Compatível com o schema atual: usuário + login + perfil.
 */
class UserDTO {
    public int $id;
    public int $id_usuario;
    public string $nome;
    public string $email;
    public string $telefone;
    public bool $ativo;
    public int $id_perfil;
    public string $tipo;
    public string $username;
    public string $senha;
    public string $senha_hash;
    public string $ultimo_acesso;
    public string $perfil_nome;

    public function __construct(
        int $id = 0,
        string $nome = "",
        string $email = "",
        string $telefone = "",
        bool $ativo = true,
        int $id_perfil = 0,
        string $tipo = "doador",
        string $username = "",
        string $senha_hash = "",
        string $ultimo_acesso = "",
        string $perfil_nome = ""
    ) {
        $this->id = $id;
        $this->id_usuario = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->telefone = $telefone;
        $this->ativo = $ativo;
        $this->id_perfil = $id_perfil;
        $this->tipo = $tipo;
        $this->username = $username;
        $this->senha = $senha_hash;
        $this->senha_hash = $senha_hash;
        $this->ultimo_acesso = $ultimo_acesso;
        $this->perfil_nome = $perfil_nome;
    }

    /**
     * Converte array associativo em DTO.
     */
    public static function fromArray(array $data): UserDTO {
        $id = (int)($data['id'] ?? $data['id_usuario'] ?? 0);
        $perfil = (string)($data['perfil_nome'] ?? $data['tipo'] ?? 'doador');
        $ativo = isset($data['ativo']) ? (bool)$data['ativo'] : true;
        $senhaHash = (string)($data['senha_hash'] ?? $data['senha'] ?? '');

        return new UserDTO(
            $id,
            (string)($data['nome'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['telefone'] ?? ''),
            $ativo,
            (int)($data['id_perfil'] ?? 0),
            $perfil,
            (string)($data['username'] ?? ''),
            $senhaHash,
            (string)($data['ultimo_acesso'] ?? ''),
            $perfil
        );
    }

}


