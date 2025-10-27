<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $socket;
    public $conn;

    public function __construct() {
        // Load configuration file
        $config_file = __DIR__ . '/../config.php';
        if (file_exists($config_file)) {
            require_once $config_file;
            $this->host = DB_HOST;
            $this->db_name = DB_NAME;
            $this->username = DB_USER;
            $this->password = DB_PASS;
            $this->socket = defined('DB_SOCKET') ? DB_SOCKET : null;
        } else {
            throw new Exception("Configuration file not found. Please copy config.example.php to config.php and update with your database credentials.");
        }
    }
  
    public function connect() {
        $this->conn = null;
        try {
            // Use socket for XAMPP on macOS if available
            if ($this->socket && file_exists($this->socket)) {
                $dsn = "mysql:unix_socket=" . $this->socket . ";dbname=" . $this->db_name;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } else {
                // Fallback to TCP connection
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                                      $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

