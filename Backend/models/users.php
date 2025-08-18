<?php
require_once(__DIR__ . '/../config/db.php');

class User {
    public $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($username, $email, $password) {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)"
        );

        $result = $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $password
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT user_id, username, email, role FROM users WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    public function existsByEmail($email) {
        $stmt = $this->conn->prepare(
            "SELECT user_id FROM users WHERE email = :email"
        );

        $stmt->execute([':email' => $email]);

        return $stmt->rowCount() > 0;
    }
}
?>
