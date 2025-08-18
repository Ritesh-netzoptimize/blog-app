<?php
require_once(__DIR__ . '/../controllers/auth.controller.php');

$auth_controller = new AuthController();

$input = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action'])) {
    switch ($input['action']) {
        case 'register':
            $auth_controller->register($input);
            break;
        case 'login':
            $auth_controller->login($input);
            break;
        case 'logout':
            $auth_controller->logout();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']) . "\n";
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Send a POST request with JSON { "action": "..." }']) . "\n";
