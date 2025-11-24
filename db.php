<?php
// db.php - Conexión a Base de Datos

class Database {
    private $host = 'localhost';
    private $db_name = 'balanceo_db';
    private $username = 'root'; // Ajustar según configuración local
    private $password = '';     // Ajustar según configuración local
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // En producción, loguear el error en lugar de mostrarlo
            // error_log("Connection error: " . $exception->getMessage());
            // echo "Error de conexión.";
            throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
