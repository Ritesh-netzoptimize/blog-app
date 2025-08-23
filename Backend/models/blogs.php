<?php
require_once(__DIR__ . '/../config/db.php');

class Blog {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($title, $content, $author_id) {
        if ($author_id == 1) {
            $stmt = $this->conn->prepare(
            "INSERT INTO blogs (title, content, author_id, approved) VALUES (:title, :content, :author_id, 1)"
            );
        }
        else {
            $stmt = $this->conn->prepare(
            "INSERT INTO blogs (title, content, author_id) VALUES (:title, :content, :author_id)"
            );
        }

        $result = $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':author_id' => $author_id
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function delete($blog_id) {

        $stmt = $this->conn->prepare(
            "SELECT blog_id FROM blogs WHERE blog_id = :blog_id"
        );
        $stmt->execute([':blog_id' => $blog_id]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "DELETE FROM blogs WHERE blog_id = :blog_id"
        );
        $result = $stmt->execute([':blog_id' => $blog_id]);
        return $result;
    }

    public function fetchAllBlogs() {
        $stmt = $this->conn->prepare("SELECT * FROM blogs ORDER BY created_at DESC");
        $stmt->execute();
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($blogs) {
            return $blogs;
        }
        return [];
    }

    // fetch blogs which are not approved of a particular user
    public function fetchPendingApprovalBlogs($author_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blogs WHERE author_id = :author_id AND approved = 0 ORDER BY created_at DESC");

        $stmt->bindParam(":author_id", $author_id, PDO::PARAM_INT);
        $stmt->execute();

        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($blogs) {
            return $blogs;
        }
        return [];

    }


    public function updateBlog($blog_id, $title, $content, $author_id) {
        $stmt = $this->conn->prepare(
            "UPDATE blogs SET title = :title, content = :content, author_id = :author_id WHERE blog_id = :blog_id"
        );
        $result = $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':author_id' => $author_id,
            ':blog_id' => $blog_id
        ]);
        return $result;
    }

    public function fetchSingleBlog($blog_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blogs WHERE blog_id = :blog_id");
        $stmt->bindParam(":blog_id", $blog_id, PDO::PARAM_INT);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($blog) {
            return $blog;
        }
        return false;
    }

    public function approveBlog($blog_id) {
        $stmt = $this->conn->prepare("UPDATE blogs SET approved = 1 WHERE blog_id = :blog_id");
        $stmt->bindParam(":blog_id", $blog_id, PDO::PARAM_INT);
        $success = $stmt->execute();
        echo $success;
        if ($success) {
            return true;
        }
        return false;
    }

    public function findBlogById($blog_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blogs WHERE blog_id = :blog_id");
        $stmt->bindParam(":blog_id", $blog_id, PDO::PARAM_INT);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($blog) {
            return $blog;
        }
        return false;
    }
    public function fetchBlogsByUserId($author_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blogs WHERE author_id = :author_id");
        $stmt->bindParam(":author_id", $author_id, PDO::PARAM_INT);
        $stmt->execute();
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($blogs) {
            return $blogs;
        }
        return false;
    }
}
?>
