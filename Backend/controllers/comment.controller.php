<?php

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../models/comments.php');

class CommentController {
    private $db;
    private $comment;
    private $blogInstance;
    private $userInstance;

    public function __construct($db) {
        $this->db = $db;
        $this->comment = new Comment($db);
        $this->blogInstance = new Blog($db);
        $this->userInstance = new User($db);
    }

    public function create_comment($data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $comment = trim($data['comment']);
            $blog_id = isset($data['blog_id']) ? (int)$data['blog_id'] : null;
            $author_id="";
            if (isset($data['author_id'])) $author_id = trim($data['author_id']) ?? $_SESSION['user_id'] ?? null;
            if (!$author_id) {
                $author_id = $_SESSION['user_id'] ?? null;
            }
            if (!$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'User not authenticated as author_id is missing',
                    'status_code' => 403
                ]);
            }
            if (!$comment || !$blog_id || !$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'All fields are required',
                    'status_code' => 401
                ]);
            }
            $new_comment_id = $this->comment->create($comment, $blog_id, $author_id);
            if ($new_comment_id) {
                $blog = $this->blogInstance->findBlogById($blog_id);
                if (!$blog) {
                    return $this->sendJson([
                        'success' => false,
                        'message' => 'Blog not found',
                        'status_code' => 404
                    ]);
                }

                $user = $this->userInstance->getById($author_id);
                if (!$user) {
                    return $this->sendJson([
                        'success' => false,
                        'message' => 'User not found',
                        'status_code' => 404
                    ]);
                }

                return $this->sendJson([
                    'success' => true,
                    'message' => 'Comment created successfully',
                    'status_code' => 200,
                    'comment_id' => $new_comment_id,
                    'blog_title' => $blog['title'] ?? null,
                    'username' => $user['username'] ?? null
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'Comment creation failed due to database issue',
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

    public function fetch_comments_by_blog_id($blog_id) {
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
            $comments = $this->comment->fetchByBlogId($blog_id);
            if ($comments) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Comments fetched successfully',
                    'status_code' => 200,
                    'comments' => $comments
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'No comments found for this blog',
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

    public function delete_comment($comment_id, $data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!$comment_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Comment ID is required',
                    'status_code' => 400
                ]);
            }
            $author_id = "";
            if (isset($data['author_id'])) $author_id = trim($data['author_id']) ?? $_SESSION['user_id'] ?? null;
            if (!$author_id) {
                $author_id = $_SESSION['user_id'] ?? null;
            }
            if (!$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'status_code' => 403
                ]);
            }
            $comment = $this->comment->getById($comment_id);
            if (!$comment) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Comment not found',
                    'status_code' => 404
                ]);
            }
            if ($comment['user_id'] !== $author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'You are not authorized to delete this comment',
                    'status_code' => 403
                ]);
            }
            
            $deleted = $this->comment->delete($comment_id);
            if ($deleted) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Comment deleted successfully',
                    'status_code' => 200
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'Comment deletion failed',
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

    public function update_comment($comment_id, $data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $comment = trim($data['comment'] ?? '');
            $author_id = "";
            if (isset($data['author_id'])) $author_id = trim($data['author_id']) ?? $_SESSION['user_id'] ?? null;
            if (!$author_id) {
                $author_id = $_SESSION['user_id'] ?? null;
            }
            if (!$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'status_code' => 403
                ]);
            }
            if (!$comment || !$comment_id || !$author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'All fields are required',
                    'status_code' => 401
                ]);
            }
            $existing_comment = $this->comment->getById($comment_id);
            if (!$existing_comment) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Comment not found',
                    'status_code' => 404
                ]);
            }
            if ($existing_comment['user_id'] !== $author_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'You are not authorized to update this comment',
                    'status_code' => 403
                ]);
            }
            $updated = $this->comment->update($comment_id, $comment);
            if ($updated) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Comment updated successfully',
                    'status_code' => 200
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'Comment update failed due to database issue',
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

    public function sendJson($data) {
        header('Content-Type: application/json');
        http_response_code($data['status_code'] ?? 200);
        echo json_encode($data) . "\n";
        exit();
    }
}