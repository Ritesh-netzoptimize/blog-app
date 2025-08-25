<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($blogId <= 0) {
    die("Invalid Blog ID.");
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$author_id = $is_loggedIn ? $_SESSION['user']['user_id'] : null;

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_loggedIn) {
//     $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
//     if ($comment && $author_id) {
//         $commentData = [
//             'blog_id' => $blogId,
//             'comment' => $comment,
//             'author_id' => $author_id
//         ];

//         $URL = 'http://localhost/blog-app/backend/api/v1/comment/create';
//         $ch = curl_init($URL);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($commentData));
//         curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//         $result = curl_exec($ch);
//         curl_close($ch);

//         $json_response = json_decode($result, true);
//         if ($json_response && $json_response['success']) {
//             $_SESSION['flash_message'] = "Comment added successfully!";
//         } else {
//             $_SESSION['flash_message'] = "Failed to add comment.";
//         }
//     }
//     // Redirect back to blog page
//     header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=$blogId");
//     exit;
// }
?>

<?php if ($is_loggedIn): ?>
<div class="container">
    <p>Add a comment</p>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="message"><?php echo $_SESSION['flash_message']; ?></div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="text" name="comment" required>
        <button type="submit">Comment</button>
    </form>
</div>
<?php endif; ?>
