<?php
require_once(__DIR__ . '/../config/db.php');

class Category {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $parent_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO categories (name, parent_id) VALUES (:name, :parent_id)"
        );

        $result = $stmt->execute([
            ':name' => $name,
            ':parent_id' => $parent_id,
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function delete($category_id) {

        $stmt = $this->conn->prepare(
            "SELECT category_id FROM categories WHERE category_id = :category_id"
        );
        $stmt->execute([':category_id' => $category_id]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "DELETE FROM categories WHERE category_id = :category_id"
        );
        $result = $stmt->execute([':category_id' => $category_id]);
        return $result;
    }

    public function fetchAllCategories() {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY created_at DESC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($categories) {
            return $categories;
        }
        return [];
    }

    public function findCategoryById($category_id) {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE category_id = :category_id");
        $stmt->bindParam(":category_id", $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($category) {
            return $category;
        }
        return false;
    }

    // public function updateCategory($blog_id, $title, $content, $author_id) {
    //     $stmt = $this->conn->prepare(
    //         "UPDATE blogs SET title = :title, content = :content, author_id = :author_id WHERE blog_id = :blog_id"
    //     );
    //     $result = $stmt->execute([
    //         ':title' => $title,
    //         ':content' => $content,
    //         ':author_id' => $author_id,
    //         ':blog_id' => $blog_id
    //     ]);
    //     return $result;
    // }
}
?>
