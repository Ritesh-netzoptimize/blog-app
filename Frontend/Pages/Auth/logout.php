<?php
    session_start();

    if (!isset($_SESSION['user']) || !isset($_SESSION['session_id'])) {
        header("Location: /blog-app/frontend/index.php");
        exit;
    }

    $sessionId = $_SESSION['session_id'] ?? '';

    $URL = "http://localhost/blog-app/backend/api/v1/auth/logout";

    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'session_id' => $sessionId
    ]));
    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    header("Location: /blog-app/frontend/index.php");
    exit;
?>
