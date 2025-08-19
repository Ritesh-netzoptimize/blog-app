<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$responseMessage = "";

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);

$URL = 'http://localhost/blog-app/backend/api/v1/blog/fetch-all';
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
$result = curl_exec($ch);

if ($result === false) {
    die("cURL Error: " . curl_error($ch));
}
curl_close($ch);
$cleanResult = preg_replace('/^[^{]+/', '', $result);
$json_response = json_decode($cleanResult, true);
if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
    $blogs = $json_response['blogs'];
} else {
    $responseMessage = "Failed to fetch blogs. Raw response: " . htmlspecialchars($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Blogs</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/blogs.css">
</head>
<body>
    <div class="blogs-container">
        <h1>All Blogs</h1>
        <?php if ($is_loggedIn): ?>
            <a href="/blog-app/frontend/Pages/Blog/createBlog.php"><div class="create-blog-button">Create New Blog</div></a>
        <?php endif; ?>
        <?php if ($responseMessage): ?>
            <div class="response-message"><?php echo $responseMessage; ?></div>
            <?php endif; ?>

        <?php if (isset($blogs) && count($blogs) > 0): ?>
            <ul class="blogs-list">
                <?php foreach ($blogs as $blog): ?>
                    <li class="blog-item">
                        <div class="blog-actions">
                            <?php if ($is_loggedIn): ?>
                                <a href="/blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=<?php echo $blog['blog_id'] ?>">View</a>
                            <?php endif; ?>
                        </div>
                        <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                        <p><?php echo htmlspecialchars(substr($blog['content'], 0, 100) . '...'); ?></p>
                        <p class="blog-author">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
                        <p class="blog-date">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No blogs available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
