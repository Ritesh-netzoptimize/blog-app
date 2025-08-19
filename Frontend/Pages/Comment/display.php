<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$responseMessage = "";

$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($blogId <= 0) {
    die("Invalid Blog ID.");
}
$URL = "http://localhost/blog-app/backend/api/v1/comment/fetch-by-blog/$blogId";
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);
$cleanResult = preg_replace('/^[^{]+/', '', $result);
$json_response = json_decode($cleanResult, true);
if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
    $comments = $json_response['comments'];
}
else {
    $comments = [];
    // die("Failed to fetch comments. Raw response: " . htmlspecialchars($result));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/all-comments.css">
</head>
<body>
    <div class="comments-section">
        <h2>Comments</h2>
        <?php if (!empty($comments)): ?>
            <ul>
                <?php foreach ($comments as $comment): ?>
                    <li>
                        <div>
                            <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                            <p class="comment-author">Username: <?php echo htmlspecialchars($comment['username']); ?></p>
                            <p class="comment-date">Posted on: <?php echo htmlspecialchars($comment['created_at']); ?></p>
                        </div>
                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                            <div>
                                <div class="comment-actions">
                                <a href="/blog-app/frontend/Pages/Comment/delete.php?id=<?php echo $comment['comment_id'] ?>"><div>Delete</div></a>
                            </div>
                        <?php endif; ?>
                        <?php if ($_SESSION['user']['username'] === $comment['username']): ?>
                            <div>
                                <div class="comment-actions">
                                <a href="/blog-app/Frontend/Pages/Comment/update.php?id=<?php echo $comment['comment_id'] ?>"><div>Edit</div></a>
                            </div>
                            </div>
                        <?php endif; ?>
                        <?php if (($_SESSION['user']['role'] !== 'admin') && ($_SESSION['user']['username'] === $comment['username'])): ?>
                            <div>
                                <div class="comment-actions">
                                <a href="/blog-app/frontend/Pages/Comment/delete.php?id=<?php echo $comment['comment_id'] ?>"><div>Delete</div></a>
                            </div>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>
</body>
</html>