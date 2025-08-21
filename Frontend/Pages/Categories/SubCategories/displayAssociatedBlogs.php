<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$responseMessage = "";

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';

if (!isset($_GET['id'])) {
    die("Category ID is missing.");
}
$category_id = intval($_GET['id']);

$URL = "http://localhost/blog-app/backend/api/v1/category/fetch-all-blogs/" . $category_id;
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$clean_result = preg_replace('/^[^{]+/', '', $result);

$blogs = [];
if ($result) {
    $data = json_decode($clean_result, true);
    if ($data && $data['success'] === true) {
        $blogs = $data['all_blogs'];
    } else {
        $responseMessage = $data['message'] ?? "Failed to fetch blogs.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogs by Category</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/blogs.css">
</head>
<body>
    <?php include_once '../../../Templates/header.php'; ?>
    <div class="blogs-container">
        <a href="/blog-app/frontend/Pages/categories/display.php">Back to Categories</a>
        <h1>Blogs in this Category</h1>
        
        <?php if ($responseMessage): ?>
            <div class="response-message"><?php echo htmlspecialchars($responseMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($blogs)): ?>
            <ul class="blogs-list">
                <?php foreach ($blogs as $blog): ?>
                    <li class="blog-item">
                        <div class="blog-actions">
                            <?php if ($is_loggedIn): ?>
                                <?php if ($is_admin): ?>
                                    <a href="/blog-app/frontend/Pages/blog/delete.php?id=<?php echo $blog['blog_id'] ?>">Delete</a>
                                    <a href="/blog-app/frontend/Pages/blog/updateBlog.php?id=<?php echo $blog['blog_id'] ?>">Edit</a>
                                <?php endif; ?>
                                <a href="/blog-app/frontend/Pages/blog/displaySingleBlog.php?id=<?php echo $blog['blog_id'] ?>">View</a>
                            <?php else: ?>
                                <a href="/blog-app/frontend/Pages/blog/displaySingleBlog.php?id=<?php echo $blog['blog_id'] ?>">View</a>
                            <?php endif; ?>
                        </div>

                        <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                        <p><?php echo htmlspecialchars(substr($blog['content'], 0, 120) . '...'); ?></p>
                        <p class="blog-author">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
                        <p class="blog-date">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No blogs available for this category.</p>
        <?php endif; ?>
    </div>
</body>
</html>
