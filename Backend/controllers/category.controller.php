<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../models/blogs.php');
require_once(__DIR__ . '/../models/categories.php');
require_once(__DIR__ . '/../models/blogCategory.php');

class CategoryController {
    private $db;
    private $category;
    private $blogCategory;

    public function __construct($db) {
        $this->db = $db;
        $this->category = new Category($db); 
        $this->blogCategory = new BlogCategory($db);
    }

    public function create_category($data) {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $name = trim($data['name'] ?? '');
        $parent_id = $data['parent_id'] ?? null;

        $user_role = $data['user_role'] 
            ?? ($_SESSION['user_role'] ?? ($_SESSION['user']['user_role'] ?? null));

        if (!$user_role || $user_role !== 'admin') {
            return $this->sendJson([
                'success' => false,
                'message' => 'Unauthorized: Only admins can create categories',
                'status_code' => 403
            ]);
        }

        if (!$name) {
            return $this->sendJson([
                'success' => false,
                'message' => 'Category name is required',
                'status_code' => 400
            ]);
        }

        if ($parent_id !== null) {
            $parentCategory = $this->category->findCategoryById($parent_id);
            if (!$parentCategory) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid parent_id: Parent category does not exist',
                    'status_code' => 400
                ]);
            }
        }

        $category_id = $this->category->create($name, $parent_id);

        return $this->sendJson([
            'success' => true,
            'message' => 'Category created successfully',
            'category_id' => $category_id,
            'status_code' => 201
        ]);
    } catch (\Throwable $th) {
        return $this->sendJson([
            'success' => false,
            'message' => 'An error occurred: ' . $th->getMessage(),
            'status_code' => 500
        ]);
    }
}


    public function assign_category_to_blog($data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $blog_id = $data['blog_id'] ?? null;
            $category_id = $data['category_id'] ?? null;

            $user_role = $data['user_role'] 
                ?? ($_SESSION['user_role'] ?? ($_SESSION['user']['user_role'] ?? null));

            if (!$user_role || $user_role !== 'admin') {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Unauthorized: Only admins can assign categories to blogs',
                    'status_code' => 403
                ]);
            }

            if (!$blog_id || !$category_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Both blog_id and category_id are required',
                    'status_code' => 400
                ]);
            }

            $blog = $this->blog->getById($blog_id);
            if (!$blog) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid blog_id: Blog not found',
                    'status_code' => 404
                ]);
            }

            $category = $this->category->findCategoryById($category_id);
            if (!$category) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid category_id: Category not found',
                    'status_code' => 404
                ]);
            }

            $this->blogCategory->assign($blog_id, $category_id);

            return $this->sendJson([
                'success' => true,
                'message' => 'Category assigned to blog successfully',
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


    public function fetch_categories() {
        try {
            $categories = $this->category->fetchAllCategories();
            return $this->sendJson([
                'success' => true,
                'categories' => $categories,
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

    public function fetch_catogory_by_id($category_id) {
        try {
            $category = $this->category->findCategoryById($category_id);
            if ($category) {
                return $this->sendJson([
                    'success' => true,
                    'category' => $category,
                    'status_code' => 200
                ]);
            } else {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Category not found',
                    'status_code' => 404
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

    public function delete_category($category_id, $data) {
        try {
           
        } catch (\Throwable $th) {
            
        }
    }

    // public function update_category($blog_id, $data) {
    //     try {
           
    //     } catch (\Throwable $th) {
            
    //     }
    // }
    
    private function sendJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data) . "\n";
        exit();
    }
}
