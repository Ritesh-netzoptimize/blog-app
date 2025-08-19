<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../models/blogs.php');

class BlogController {
    private $db;
    private $blog;

    public function __construct($db) {
        $this->db = $db;
        $this->blog = new Blog($db); 
    }

    public function create_blog($data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $title = trim($data['title']);
        $content = trim($data['content']);
        $author_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $data['author_id'];
        var_dump($author_id);
        if (!$author_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'User not authenticated as author_id is missing',
                'status_code' => 403
            ]);
        }

        $user = new User($this->db);
        $user_data = $user->getById($author_id);
        if (!$user_data || $user_data['role'] !== 'admin') {
            return $this->sendJson([
                'success' => false,
                'message' => 'User is not authorized to create a blog',
                'status_code' => 403
            ]);
        }

        if (!$title || !$content || !$author_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'All fields are required',
                'status_code' => 401
            ]);
        }
        $new_blog_id = $this->blog->create($title, $content, $author_id);
        if ($new_blog_id) {
            return $this->sendJson([
                'success' => true,
                'message' => 'Blog created successfully',
                'status_code' => 200,
                'blog_id' => $new_blog_id
            ]);
        }
        return $this->sendJson([
            'success' => false,
            'message' => 'Blog creation failed due to database issue',
            'status_code' => 502
        ]);
    }

    public function fetch_blogs() {
        $blogs = $this->blog->fetchAllBlogs();
        if ($blogs) {
            return $this->sendJson([
                'success' => true,
                'message' => 'Blogs fetched successfully',
                'status_code' => 200,
                'blogs' => $blogs
            ]);
        }
        return $this->sendJson([
            'success' => false,
            'message' => 'No blogs found',
            'status_code' => 404
        ]);
    }

    public function delete_blog($blog_id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $author_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        if (!$author_id) {
            $author_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;
        }


        if (!$author_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'User not authenticated',
                'status_code' => 403
            ]);
        }
        $user = new User($this->db);
        $user_data = $user->getById($author_id);
        if (!$user_data || $user_data['role'] !== 'admin') {
            return $this->sendJson([
                'success' => false,
                'message' => 'User is not authorized to delete a blog',
                'status_code' => 403
            ]);
        }
        if (!$blog_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'Blog ID is required',
                'status_code' => 400
            ]);
        }

        $result = $this->blog->delete($blog_id);

        if ($result) {
            return $this->sendJson([
                'success' => true,
                'message' => 'Blog deleted successfully',
                'status_code' => 200
            ]);
        }
        return $this->sendJson([
            'success' => false,
            'message' => 'Blog deletion failed due to database issue or blog not found',
            'status_code' => 502
        ]);
    }

    public function update_blog($blog_id, $data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $title = trim($data['title']);
        $content = trim($data['content']);
        $author_id = trim($data['author_id']) ?? $_SESSION['user_id'] ?? null;
        if (!$author_id) {
            $author_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;
        }
        if (!$author_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'User not authenticated',
                'status_code' => 403,
                'author_id' => $author_id,
            ]);
        }
        $user = new User($this->db);
        $user_data = $user->getById($author_id);
        if (!$user_data || $user_data['role'] !== 'admin') {
            return $this->sendJson([
                'success' => false,
                'message' => 'User is not authorized to update a blog',
                'status_code' => 403
            ]);
        }
        if (!$blog_id || !$title || !$content) {
            return $this->sendJson([
                'success' => false,
                'message' => 'Blog ID, title, and content are required',
                'status_code' => 400
            ]);
        }
        $result = $this->blog->updateBlog($blog_id, $title, $content, $author_id);
        if ($result) {
            return $this->sendJson([
                'success' => true,
                'message' => 'Blog updated successfully',
                'status_code' => 200
            ]);
        }
        return $this->sendJson([
            'success' => false,
            'message' => 'Blog update failed due to database issue or blog not found',
            'status_code' => 502
        ]);
    }

    public function fetch_blog_by_id($blog_id) {
        if (!$blog_id) {
            return $this->sendJson([
                'success' => false,
                'message' => 'Blog ID is required',
                'status_code' => 400
            ]);
        }
        $result = $this->blog->fetchSingleBlog($blog_id);
        if ($result) {
            return $this->sendJson([
                'success' => true,
                'message' => 'Blog fetched successfully',
                'status_code' => 200,
                'blog' => $result
            ]);
        }
        return $this->sendJson([
            'success' => false,
            'message' => 'Blog not found',
            'status_code' => 404
        ]);
    }

    
    private function sendJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data) . "\n";
        exit;
    }
}
