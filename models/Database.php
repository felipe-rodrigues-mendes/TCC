<?php

/**
 * Classe de gerenciamento centralizado de conexao com banco de dados.
 */
class Database {
    private static $instance = null;
    private $conn;
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "conecta_solidaria";

    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

        if ($this->conn->connect_error) {
            error_log("Erro de conexao com banco de dados: " . $this->conn->connect_error);
            die("Erro ao conectar ao banco de dados. Contate o administrador.");
        }

        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Obtem instancia unica de conexao (Singleton).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    /**
     * Retorna objeto MySQLi para usar em DAOs.
     */
    public function getConnection(): mysqli {
        return $this->conn;
    }

    /**
     * Inicia transacao.
     */
    public function beginTransaction(): void {
        $this->conn->begin_transaction();
    }

    /**
     * Confirma transacao.
     */
    public function commit(): void {
        $this->conn->commit();
    }

    /**
     * Desfaz transacao.
     */
    public function rollback(): void {
        $this->conn->rollback();
    }
}
