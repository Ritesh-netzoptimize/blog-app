<?php
require_once(__DIR__ . '/../models/users.php');

class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db); 

    }
    
    public function register($data) {
        try {
            $username = trim($data['username']);
            $email = trim($data['email']);
            $password = trim($data['password']);
            
            if (!$username || !$email || !$password) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'All fields are required',
                    'status_code' => 401
                ]);
            }
            
            $user = new User($this->db);
            if ($user->existsByEmail($email)) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Email already exists',
                    'status_code' => 402
                ]);
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $new_user_id = $user->create($username, $email, $hashed_password);
            if ($new_user_id) {
                $_SESSION['user_id']  = $new_user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email']    = $email;

                setcookie('user_id', $new_user_id, time() + 3600, '/', '', false, true);
                setcookie('username', $username, time() + 3600, '/', '', false, true);

                $response = [
                    'success' => true,
                    'message' => 'Registration successful',
                    'status_code' => 200,
                    'session_id' => session_id(),
                    'user' => [
                        'user_id' => $new_user_id,
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user'
                    ]
                ];
                return $this->sendJson($response);
            }

            return $this->sendJson([
                'success' => false,
                'message' => 'User registration failed due to database issue',
                'status_code' => 502
            ]);
        } catch (\Throwable $th) {
            return $this->sendJson([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }

    public function login($data) {
        try {
            $email = trim($data['email']);
            $password = trim($data['password']);

            if (!$email || !$password) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Email and password are required',
                    'status_code' => 501
                ]);
            }

            $user = new User($this->db);

            $stmt = $user->conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dbUser) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Email not registered',
                    'status_code' => 404
                ]);
            }
            echo $password . "\n";
            echo $dbUser['password'] . "\n";
            if (!password_verify($password, $dbUser['password'])) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Incorrect password',
                    'status_code' => 401
                ]);
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id']  = $dbUser['user_id'];
            $_SESSION['username'] = $dbUser['username'];
            $_SESSION['email']    = $dbUser['email'];
            $_SESSION['role']    = $dbUser['role'];

            setcookie('user_id', $dbUser['user_id'], time() + 3600, '/', '', false, true);
            setcookie('username', $dbUser['username'], time() + 3600, '/', '', false, true);

            return $this->sendJson([
                'success' => true,
                'message' => 'Login successful',
                'status_code' => 200,
                'session_id' => session_id(),
                'user' => [
                    'user_id' => $dbUser['user_id'],
                    'username' => $dbUser['username'],
                    'email' => $dbUser['email'],
                    'role' => $dbUser['role']
                ]
            ]);
        } catch (\Throwable $th) {
            return $this->sendJson([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }

    public function logout() {
        try {
            if(session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            session_destroy();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]);
            }
            return $this->sendJson([
                'success' => true,
                'message' => 'User logged out successfully',
                'status_code' => 200
            ]);
        } catch (\Throwable $th) {
            return $this->sendJson([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }

    public function fetch_all_users($data) {
        try {
            $user_role = $data['role'] 
                ?? ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null));
            if ($user_role !== 'admin') {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Only admin can fetch all users',
                    'status_code' => 403
                ]);
            }
            $users = $this->user->fetchAllUsers();
            if ($users) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Users fetched successfully',
                    'status_code' => 200,
                    'usres' => $users
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'No users found',
                'status_code' => 404
            ]);
        } catch (\Throwable $th) {
            return $this->sendJson([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }

    private function sendJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data) . "\n";
        exit;
    }
}
