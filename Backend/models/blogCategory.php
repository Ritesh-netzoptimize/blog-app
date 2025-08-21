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

    public function fetchAssociatedBlogsFromCategoryId($category_id) {
        $stmt = $this->conn->prepare("SELECT b.* FROM blogs b JOIN blog_categories bc ON b.blog_id = bc.blog_id WHERE bc.category_id = :category_id");
        $stmt->execute([
            ':category_id' => $category_id,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
