<?php
session_start();
$responseMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $URL = 'http://localhost/blog-app/backend/api/v1/auth/register';

    $post_data = json_encode([
        "email" => $email,
        "username" => $username,
        "password" => $password
    ]);

    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $result = curl_exec($ch);

    if ($result === false) {
        die("cURL Error: " . curl_error($ch));
    }

    curl_close($ch);

    $cleanResult = preg_replace('/^[^{]+/', '', $result);
    $json_response = json_decode($cleanResult, true);

    // var_dump($json_response);
    // var_dump(isset($json_response['success']));
    // var_dump($json_response['success']);

    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        $_SESSION['user'] = $json_response['user'];
        if (isset($_SESSION['user']['user_id'])) {
            $_SESSION['user_id'] = $_SESSION['user']['user_id'];
        }
        $_SESSION['session_id'] = session_id(); 
        $_SESSION['user_id'] = $json_response['user']['user_id'];  


        header('Location: /blog-app/frontend/Pages/Auth/success_login.php');
        exit();
    } else {
        if ($json_response && isset($json_response['message'])) {
        // show only backend message like "Incorrect password" or "User not found"
        $responseMessage = "Login failed: " . htmlspecialchars($json_response['message']);
    } else {
        // fallback in case backend sends something unexpected
        $responseMessage = "Login failed. Raw response: " . htmlspecialchars($result);
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/login.css">
</head>
<body>
    <div class="auth-container">

        <h1>Register page</h1>

        <?php if ($responseMessage): ?>
            <p style="color: red; font-weight: bold; text-align: center;">
                <?= $responseMessage ?>
            </p>
        <?php endif; ?>

        
        <form method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="/blog-app/frontend/Pages/Auth/login.php">Login here</a></p>
    </div>
</body>
</html>