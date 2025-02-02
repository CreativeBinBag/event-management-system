<?php

    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'event_management';


    class Database {
        private $connection;
        private static $instance = null;
        private $db_host;
        private $db_user;
        private $db_pass;
        private $db_name;


    private function __construct() {
        
        $this->db_host = $GLOBALS['db_host'];
        $this->db_user = $GLOBALS['db_user'];
        $this->db_pass = $GLOBALS['db_pass'];
        $this->db_name = $GLOBALS['db_name'];

        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->db_host . ";dbname=" . $this->db_name,
                $this->db_user,
                $this->db_pass,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
             error_log("Database connection failed: " . $e->getMessage() . " Time: ". date('Y-m-d H:i:s'), 0);
            throw $e; 
        }
    }

        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function getConnection() {
            return $this->connection;
        }

        // to prevent cloning of the instance
        private function __clone() {}

        // to prevent unserializing of the instance
        public function __wakeup() {} 
    }
    ?>