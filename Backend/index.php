<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/controllers/auth.controller.php');
require_once(__DIR__ . '/controllers/blog.controller.php');
require_once(__DIR__ . '/controllers/comment.controller.php');
require_once(__DIR__ . '/controllers/category.controller.php');

$db = (new Database())->getConnection();
$auth_controller = new AuthController($db);
$blog_controller = new BlogController($db);
$comment_controller = new CommentController($db);
$category_controller = new CategoryController($db);

$input = json_decode(file_get_contents("php://input"), true);
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $uri);

// Expecting: /api/v1/auth/login OR /api/v1/blog/create OR /api/v1/comment/create, segments[2] = api, segments[3] = v1, segments[4] = resource (auth, blog, comment), segments[5] = action (login, create, etc.), segments[6] = optional resource ID (e.g., blog ID for fetching a single blog).

if (count($segments) < 4 || $segments[2] !== "api" || $segments[3] !== "v1") {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Invalid API route"]);
    exit;
}
$resource = $segments[4];
$action = $segments[5] ?? null;
// echo json_encode(["resource" => $resource]);
switch ($resource) {
    case 'auth':
        if ($action === "register") $auth_controller->register($input);
        elseif ($action === "login") $auth_controller->login($input);
        elseif ($action === "logout") $auth_controller->logout($input);
        else echo json_encode(["success" => false, "message" => "Invalid auth action"]);
        break;

    case 'blog':
        if ($action === "create") $blog_controller->create_blog($input);
        elseif ($action === "fetch-all") $blog_controller->fetch_blogs();
        elseif ($action === "fetch-single") { 
            $blog_id = $segments[6] ?? null;
            if ($blog_id) {
                $blog_controller->fetch_blog_by_id($blog_id);
            } else {
                echo json_encode(["success" => false, "message" => "Blog ID is required"]);
            }
        }
        elseif ($action === "delete") {
            $blog_id = $segments[6] ?? null;
            if ($blog_id) {
                $blog_controller->delete_blog($blog_id, $input);
            } else {
                echo json_encode(["success" => false, "message" => "Blog ID is required for deletion"]);
            }
        }   
        elseif ($action === "update") {
            $blog_id = $segments[6] ?? null;
            if ($blog_id && isset($input['title']) && isset($input['content'])) {
                $blog_controller->update_blog($blog_id, $input);
            }
            else {
                echo json_encode(["success" => false, "message" => "Blog ID, title, and content are required for update"]);
            }
        }                                                   
        else echo json_encode(["success" => false, "message" => "Invalid blog action"]);
        break;
        
    case 'comment':
        if ($action === "create") $comment_controller->create_comment($input);
        else if ($action === "fetch-by-blog") {
            $blog_id = $segments[6] ?? null;
            if ($blog_id) {
                $comment_controller->fetch_comments_by_blog_id($blog_id);
            } else {
                echo json_encode(["success" => false, "message" => "Blog ID is required to fetch comments"]);
            }
        }
        else if ($action === "delete") {
            $comment_id = $segments[6] ?? null;
            if ($comment_id) {
                $comment_controller->delete_comment($comment_id, $input);
            }
            else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for deletion"]);
            }
        }
        else if ($action === "update") {
            $comment_id = $segments[6] ?? null;
            if ($comment_id && isset($input['comment'])) {
                $comment_controller->update_comment($comment_id, $input);
            }
            else {
                echo json_encode(["success" => false, "message" => "Comment ID and content are required for update"]);
            }
        }
        else echo json_encode(["success" => false, "message" => "Invalid comment action"]);
        break;

    case 'category':
        if($action === "create") $category_controller->create_category($input);
        else if($action === "fetch-all") $category_controller->fetch_categories();
        else if($action === "fetch-by-id") {
            $category_id = $segments[6] ?? null;
            if ($category_id) {
                $category_controller->fetch_catogory_by_id($category_id);
            } else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for creation"]);
            }
        }
        else if($action === "delete") {
            $category_id = $segments[6] ?? null;
            if ($category_id) {
                $category_controller->delete_category($category_id, $input);
            } else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for deletion"]);
            }
        }
        else if($action === "assign-category") {
            $category_id = $segments[6] ?? null;
            if ($category_id) {
                $category_controller->assign_category_to_blog($category_id, $input);
            } else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for assignment"]);
            }
        }
        else if($action === "update") {
            $category_id = $segments[6] ?? null;
            // echo json_encode(["category_id" => $category_id]);

            if ($category_id) {
            // echo json_encode(["category_id" => $category_id]);

                $category_controller->update_category($category_id, $input);
            } else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for updation"]);
            }
        }
        else if($action === "fetch-all-blogs") {
            $category_id = $segments[6] ?? null;
            // echo json_encode(["category_id" => $category_id]);
            // echo json_encode(["action" => $action]);
            
            if ($category_id) {
                // echo json_encode(["action" => $action]);
            // echo json_encode(["category_id" => $category_id]);

                $category_controller->fetch_associated_blogs_from_category_id($category_id);
            } else {
                echo json_encode(["success" => false, "message" => "Comment ID is required for updation"]);
            }
        }
        else echo json_encode(["success" => false, "message" => "Invalid comment action"]);
        break;
    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Unknown resource"]);
}