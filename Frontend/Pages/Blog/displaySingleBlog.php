<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die("Blog ID is missing.");
}

$blogId = intval($_GET['id']); 

$URL = "http://localhost/blog-app/backend/api/v1/blog/fetch-single/$blogId";
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$cleanResult = preg_replace('/^[^{]+/', '', $result);
$json_response = json_decode($cleanResult, true);

if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
    $blog = $json_response['blog']; 
} else {
    die("Failed to fetch blog. Raw response: " . htmlspecialchars($result));
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Blog</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/singleBlog.css">
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/assign-categegory.css">
    <style>
        .assign-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            max-width: 400px;
        }
        .assign-container h3 {
            margin-bottom: 10px;
        }
        select {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 100%;
            margin-bottom: 10px;
        }
        button.assign-btn {
            padding: 8px 12px;
            background: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button.assign-btn:hover {
            background: #1557b0;
        }
        .selected-info {
            margin-top: 10px;
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body class="body-container">
    <?php include_once '../../Templates/header.php'; ?>
    <a class="back-link" href="javascript:history.back()"><div class="back-button">Back</div></a>
    <div class="blog-content">
        <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
        <p class="blog-text"><?php echo htmlspecialchars($blog['content']); ?></p>
        <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
        <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
        <a class="back-link" href="/blog-app/frontend/index.php">Back to all blogs</a>

        <?php include_once '../Comment/create.php'; ?>
        <?php include_once '../Comment/display.php'; ?>
    </div>

    
</body>
</html>
