<?php

/**
 * Classe de gerenciamento centralizado de conexão com banco de dados
 * Refatorado de conexao.php original para padrão seguro e reusável
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
            error_log("Erro de conexão com banco de dados: " . $this->conn->connect_error);
            die("Erro ao conectar ao banco de dados. Contate o administrador.");
        }

        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Obtém instância única de conexão (Singleton)
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retorna objeto MySQLi para usar em DAOs
     * @return mysqli
     */
    public function getConnection(): mysqli {
        return $this->conn;
    }

    /**
     * Fecha conexão (chamado ao final da aplicação)
     */
    public function closeConnection(): void {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Inicia transação
     */
    public function beginTransaction(): void {
        $this->conn->begin_transaction();
    }

    /**
     * Confirma transação
     */
    public function commit(): void {
        $this->conn->commit();
    }

    /**
     * Desfaz transação
     */
    public function rollback(): void {
        $this->conn->rollback();
    }
}

