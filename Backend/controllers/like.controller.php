<?php

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../models/likes.php');

class LikeController {
    private $db;
    private $like;

    public function __construct($db) {
        $this->db = $db;
        $this->like = new Like($db);
    }

    public function toggle_like($blog_id, $data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $author_id = null;

        if (!empty($data['author_id'])) {
            $author_id = trim($data['author_id']);
        }

        if (!$author_id) {
            if (isset($_SESSION['user']['user_id'])) {
                $author_id = $_SESSION['user']['user_id'];
            } elseif (isset($_SESSION['user_id'])) {
                $author_id = $_SESSION['user_id'];
            }
        }
            if (!$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'User not authenticated as author_id is missing',
                    'status_code' => 403
                ]);
            }
            if (!$blog_id || !$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'All fields are required',
                    'status_code' => 401
                ]);
            }
            $result = $this->like->toggleLike($blog_id, $author_id);

            // return [
            //     "success" => true,
            //     "status" => $status,
            //     "totalLikes" => $totalLikes
            // ];

            if ($result['status']==="unliked") {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Unliked successfully',
                    'status_code' => 200,
                    'liked_result' => $result
                ]);
            } else {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Liked successfully',
                    'status_code' => 200,
                    'liked_result' => $result
                ]);
            }
        } catch (\Throwable $th) {
            return $this->sendJson([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
                'status_code' => 500
            ]);
        }
    }

    public function fetch_likes_count_by_blog_id($blog_id) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!$blog_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Blog ID is required',
                    'status_code' => 400
                ]);
            }
            $num_of_likes = $this->like->fetchCountByBlogId($blog_id);
            if ($num_of_likes) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Number of likes fetched successfully',
                    'status_code' => 200,
                    'Likes_count' => $num_of_likes
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'No Likes found for this blog',
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

    public function fetch_blogs_by_user_likes($user_id) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!$user_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'User not authenticated as user_id is missing',
                    'status_code' => 403
                ]);
            }
            
            $user_liked_blogs = $this->like->fetchBlogsByUserLikes($user_id);
            if ($user_liked_blogs == []) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'User has not liked any blog yet',
                    'status_code' => 200,
                    'user_liked_blogs' => $user_liked_blogs
                ]);
            }
            if ($user_liked_blogs) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Users liked blog fetched successfully',
                    'status_code' => 200,
                    'user_liked_blogs' => $user_liked_blogs
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'User has not liked any blog yet',
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

    public function sendJson($data) {
        header('Content-Type: application/json');
        http_response_code($data['status_code'] ?? 200);
        echo json_encode($data) . "\n";
        exit();
    }
}