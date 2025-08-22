<?php
require_once(__DIR__ . '/../config/db.php');

class Comment {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($comment, $blog_id, $author_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO comments (comment, blog_id, user_id) VALUES (:comment, :blog_id, :author_id)"
        );
        $result = $stmt->execute([
            ':comment' => $comment,
            ':blog_id' => $blog_id,
            ':author_id' => $author_id
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function createReply($parent_id, $comment, $blog_id, $author_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO comments (comment, blog_id, parent_id, user_id) VALUES (:comment, :blog_id, :parent_id, :author_id)"
        );
        $result = $stmt->execute([
            ':comment' => $comment,
            ':blog_id' => $blog_id,
            ':parent_id' => $parent_id,
            ':author_id' => $author_id
        ]);
        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function fetchByBlogId($blog_id) {
        $stmt = $this->conn->prepare("SELECT * FROM comments WHERE blog_id = :blog_id AND parent_id is NULL ORDER BY created_at ASC");
        $stmt->execute([':blog_id' => $blog_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($comments) {
            foreach ($comments as &$comment) {
                $comment['blog_title'] = $this->getBlogTitleById($comment['blog_id']);
                $comment['username'] = $this->getUsernameById($comment['user_id']);
            }
            return $comments;
        }
        return [];
    }

    public function fetchByCommentId($parent_id) {
        $stmt = $this->conn->prepare("SELECT * FROM comments WHERE parent_id = :parent_id ORDER BY created_at ASC");
        $stmt->execute([':parent_id' => $parent_id]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($replies) {
            foreach ($replies as &$reply) {
                $reply['username'] = $this->getUsernameById($reply['user_id']);
            }
            return $replies;
        }
        return [];
    }

    public function delete($comment_id) {
        $stmt = $this->conn->prepare("SELECT comment_id FROM comments WHERE comment_id = :comment_id");
        $stmt->execute([':comment_id' => $comment_id]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $stmt = $this->conn->prepare("DELETE FROM comments WHERE comment_id = :comment_id");
        $result = $stmt->execute([':comment_id' => $comment_id]);
        return $result;
    }

    public function update($comment_id, $comment) {
        $stmt = $this->conn->prepare("UPDATE comments SET comment = :comment WHERE comment_id = :comment_id");
        $result = $stmt->execute([
            ':comment' => $comment,
            ':comment_id' => $comment_id
        ]);
        return $result;
    }

    public function getById($comment_id) {
        $stmt = $this->conn->prepare("SELECT * FROM comments WHERE comment_id = :comment_id");
        $stmt->execute([':comment_id' => $comment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getBlogTitleById($blog_id) {
        $stmt = $this->conn->prepare("SELECT title FROM blogs WHERE blog_id = :blog_id");
        $stmt->execute([':blog_id' => $blog_id]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        return $blog ? $blog['title'] : null;
    }
    public function getUsernameById($user_id) {
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['username'] : null;
    }

}