<?php
require_once(__DIR__ . '/../config/db.php');

class BlogCategory {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function assign($blog_id, $category_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO blog_categories (blog_id, category_id) VALUES (:blog_id, :category_id)"
        );

        $result = $stmt->execute([
            ':blog_id' => $blog_id,
            ':category_id' => $category_id,
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>
