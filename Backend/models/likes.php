<?php
require_once(__DIR__ . '/../config/db.php');

class Like {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function toggleLike($blog_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT 1 FROM likes WHERE user_id = :user_id AND blog_id = :blog_id");
        $stmt->execute([':user_id' => $user_id, ':blog_id' => $blog_id]);
        $liked = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($liked) {
            $stmt = $this->conn->prepare("DELETE FROM likes WHERE user_id = :user_id AND blog_id = :blog_id");
            $stmt->execute([':user_id' => $user_id, ':blog_id' => $blog_id]);

            $status = "unliked";
        } else {
            $stmt = $this->conn->prepare("INSERT INTO likes (user_id, blog_id) VALUES (:user_id, :blog_id)");
            $stmt->execute([':user_id' => $user_id, ':blog_id' => $blog_id]);

            $status = "liked";
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM likes WHERE blog_id = :blog_id");
        $stmt->execute([':blog_id' => $blog_id]);
        $totalLikes = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "success" => true,
            "status" => $status,
            "totalLikes" => $totalLikes['COUNT(*)']
        ];
    }

    public function fetchCountByBlogId($blog_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM likes WHERE blog_id = :blog_id");
        $stmt->execute([':blog_id' => $blog_id]);
        $num_of_likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $num_of_likes[0]['COUNT(*)'];
    }


    // public function getById($comment_id) {
    //     $stmt = $this->conn->prepare("SELECT * FROM comments WHERE comment_id = :comment_id");
    //     $stmt->execute([':comment_id' => $comment_id]);
    //     return $stmt->fetch(PDO::FETCH_ASSOC);
    // }
    // public function getBlogTitleById($blog_id) {
    //     $stmt = $this->conn->prepare("SELECT title FROM blogs WHERE blog_id = :blog_id");
    //     $stmt->execute([':blog_id' => $blog_id]);
    //     $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $blog ? $blog['title'] : null;
    // }
    // public function getUsernameById($user_id) {
    //     $stmt = $this->conn->prepare("SELECT username FROM users WHERE user_id = :user_id");
    //     $stmt->execute([':user_id' => $user_id]);
    //     $user = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $user ? $user['username'] : null;
    // }

}