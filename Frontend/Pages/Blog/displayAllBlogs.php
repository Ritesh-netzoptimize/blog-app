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
     $approvedBlogs = array_filter($blogs, function($b) {
        return $b['approved'];
    });
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
<style>
    a {
        text-decoration: none;
    }
</style>
<body>
    <div class="blogs-container">
        <h1>All Blogs</h1>
        <?php if ($responseMessage): ?>
            <?php endif; ?>

        <?php if (isset($approvedBlogs) && count($approvedBlogs) > 0): ?>
            <ul class="blogs-list">
                <?php foreach ($approvedBlogs as $blog): ?>
                    <?php if($blog['approved']): ?> 
                        <li class="blog-item">
                            <div class="blog-actions">
                                <?php if ($is_loggedIn): ?>
                                    <?php if ($is_admin): ?>
                                        <a href="/blog-app/frontend/Pages/blog/delete.php?id=<?php echo $blog['blog_id'] ?>">Delete</a>
                                        <a href="/blog-app/frontend/Pages/blog/updateBlog.php?id=<?php echo $blog['blog_id'] ?>">Edit</a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="/blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=<?php echo $blog['blog_id'] ?>">View</a>
                            </div>
                            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                            <p><?php echo htmlspecialchars(substr($blog['content'], 0, 100) . '...'); ?></p>
                            <p class="blog-author">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
                            <p class="blog-date">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
          <div style="display: flex; justify-content: center; align-items: center; height: 60vh; font-size: 28px; font-weight: bold; color: #e74c3c; text-align: center;">
            No blogs available.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
