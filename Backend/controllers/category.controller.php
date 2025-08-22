<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../models/blogs.php');
require_once(__DIR__ . '/../models/categories.php');
require_once(__DIR__ . '/../models/blogCategory.php');

class CategoryController {
    private $db;
    private $category;
    private $blogCategory;
    private $blog;

    public function __construct($db) {
        $this->db = $db;
        $this->category = new Category($db); 
        $this->blogCategory = new BlogCategory($db);
        $this->blog = new Blog($db);
    }

    public function create_category($data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $name = trim($data['name'] ?? '');
            $parent_id = $data['parent_id'] ?? null;

            $user_role = $data['user_role'] 
                ?? ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null));

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


    public function assign_category_to_blog($category_id, $data) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $blog_id = $data['blog_id'] ?? null;
            
            // echo json_encode(["blog_id" => $blog_id, "category_id" => $category_id]);
            if (!$blog_id || !$category_id) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Both blog_id and category_id are required',
                    'status_code' => 400
                ]);
            }

            $blog = $this->blog->fetchSingleBlog($blog_id);
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
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['user']) && empty($data['user_role'])) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'You must be logged in to delete a category',
                    'status_code' => 401
                ]);
            }

            $user_role = $data['user_role'] 
                ?? ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null));

            if (!$user_role || $user_role !== 'admin') {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Unauthorized: Only admins can delete categories',
                    'status_code' => 403
                ]);
            }

            if (!$category_id || !is_numeric($category_id)) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid category_id',
                    'status_code' => 400
                ]);
            }

            $category = $this->category->findCategoryById($category_id);
            if (!$category) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Category not found',
                    'status_code' => 404
                ]);
            }

            $deleted = $this->category->delete($category_id);

            if ($deleted) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'Category deleted successfully',
                    'status_code' => 200
                ]);
            } else {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Category deletion failed',
                    'status_code' => 500
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


    public function update_category($category_id, $data) {
        try {
            // echo json_encode(["category_id" => $category_id]);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $name = trim($data['name']);
           
            $user_role = $data['user_role'] 
                ?? ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null));

            if (!$user_role || $user_role !== 'admin') {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Unauthorized: Only admins can update categories',
                    'status_code' => 403
                ]);
            }

            if (!$category_id || !is_numeric($category_id)) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid category_id',
                    'status_code' => 400
                ]);
            }
            
            $category = $this->category->findCategoryById($category_id);
            // echo json_encode(["category_id" => $category_id]);
            // echo json_encode(["category" => $category]); -> true/false

            if (!$category) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Category not found',
                    'status_code' => 404
                ]);
            }
            $category = $this->category->updateCategory($category_id, $name);
            if ($category) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'category updated successfully',
                    'status_code' => 200,
                ]);
            }
            return $this->sendJson([
                'success' => false,
                'message' => 'category update failed due to database issue or blog not found',
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
    
    public function fetch_associated_blogs_from_category_id($category_id) {
            // $category = $this->category->findCategoryById($category_id); gives true/false
         try {
            // echo json_encode(["inside fetch_associated_blogs_from_category_id" => $category_id]);

            $category = $this->category->findCategoryById($category_id);
            if (!$category) {
                return $this->sendJson([
                    'success' => false,
                    'message' => 'Category not found',
                    'status_code' => 404
                ]);
            }
            // echo json_encode(["afer getting category" => $category]);
            
            $all_blogs = $this->blogCategory->fetchAssociatedBlogsFromCategoryId($category_id);
            // echo json_encode(["all blogs" => $all_blogs]);

            if ($all_blogs) {
                return $this->sendJson([
                    'success' => true,
                    'message' => 'blogs fetched successfully',
                    'status_code' => 200,
                    'all_blogs' => $all_blogs
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

    private function sendJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data) . "\n";
        exit();
    }
}
