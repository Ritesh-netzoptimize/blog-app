<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/controllers/auth.controller.php');
require_once(__DIR__ . '/controllers/blog.controller.php');

$db = (new Database())->getConnection();
$auth_controller = new AuthController($db);
$blog_controller = new BlogController($db);

$input = json_decode(file_get_contents("php://input"), true);
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $uri);

// Expecting: /api/v1/auth/login OR /api/v1/blog/create
if (count($segments) < 4 || $segments[2] !== "api" || $segments[3] !== "v1") {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Invalid API route"]);
    exit;
}
$resource = $segments[4];
$action = $segments[5] ?? null;

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
                $blog_controller->delete_blog($blog_id);
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

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Unknown resource"]);
}