<?php
require_once "Database.php";

class SanPham extends Database {
    private $id, $name, $price, $image, $description;

    // Khởi tạo đối tượng sản phẩm
    public function __construct($id = null, $name = "", $price = 0, $image = "", $description = "") {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->image = $image;
        $this->description = $description;
    }

    // Lấy tất cả sản phẩm
    public function getAll() {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM sanpham");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thêm mới sản phẩm
    public function insert() {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("INSERT INTO sanpham (name, price, image, description) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$this->name, $this->price, $this->image, $this->description]);
    }
}
?>
