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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Blog</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/singleBlog.css">
</head>
<body class="body-container">
    <?php include_once '../../Templates/header.php'; ?>
    <div class="blog-content">
        <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
        <p class="blog-text"><?php echo htmlspecialchars($blog['content']); ?></p>
        <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
        <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
        <a class="back-link" href="">Back to all blogs</a>
    </div>
</body>

</html>
