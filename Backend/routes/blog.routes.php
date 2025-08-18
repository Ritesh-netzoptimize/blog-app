<?php
require_once(__DIR__ . '/../controllers/blog.controller.php');

$blog_controller = new BlogController();

$input = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action'])) {
    switch ($input['action']) {
        case 'create':
            $auth_controller->create_blog($input);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']) . "\n";
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Send a POST request with JSON { "action": "..." }']) . "\n";
