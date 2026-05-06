<?php
 
require_once __DIR__ . '/../../config/Config.php';
 
class Database {
    private static ?Database $instance = null;
    private PDO $connection;
 
    private function __construct() {
        $host   = Config::get('DB_HOST');
        $dbname = Config::get('DB_NAME');
        $port   = Config::get('DB_PORT');
        $user   = Config::get('DB_USER');
        $pass   = Config::get('DB_PASS');
 
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
 
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
 
    public function getConnection(): PDO {
        return $this->connection;
    }
}