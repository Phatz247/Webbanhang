<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'myweb';
    private $username = 'root';
    private $password = '';
    protected $conn;

    public function getConnection() {
        if (!$this->conn) {
            try {
                // $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
                $this->conn = new PDO("mysql:host=127.0.0.1;dbname={$this->dbname}", $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Kết nối thất bại: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}
?>
