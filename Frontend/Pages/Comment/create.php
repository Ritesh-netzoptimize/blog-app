<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$responseMessage = "";

$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($blogId <= 0) {
    die("Invalid Blog ID.");
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$author_id = $is_loggedIn ? $_SESSION['user']['user_id'] : null;

// Handle POST only if logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_loggedIn) {
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    if ($comment && $author_id) {
        $commentData = [
            'blog_id' => $blogId,
            'comment' => $comment,
            'author_id' => $author_id
        ];
        $URL = 'http://localhost/blog-app/backend/api/v1/comment/create';
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($commentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $cleanResult = preg_replace('/^[^{]+/', '', $result);
        $json_response = json_decode($cleanResult, true);

        if ($json_response && $json_response['success']) {
            $_SESSION['flash_message'] = "Comment added successfully!";
        } else {
            $_SESSION['flash_message'] = "Failed to add comment.";
        }
    } else {
        $_SESSION['flash_message'] = "Comment cannot be empty.";
    }

    // ✅ Redirect to same page to prevent resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/create-comment.css">
</head>
<body>
    <?php if ($is_loggedIn): ?>
        <div class="container">

        <p>Add a comment</p>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="message"><?php echo $_SESSION['flash_message']; ?></div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <form id="commentForm" method="POST" action="">
            <input type="text" name="comment" id="comment" required>
            <input type="hidden" name="author_id" value="<?php echo htmlspecialchars($author_id); ?>">
            <button type="submit" style="margin-bottom: 20px;">comment</button>
        </form>
    </div>
    <?php endif ?>
</body>

<script>
    if (data.success) {
    document.getElementById("formMessage").innerHTML = "<p style='color:green;'>Comment added successfully!</p>";
    document.getElementById("commentForm").reset();

} else {
    document.getElementById("formMessage").innerHTML = "<p style='color:red;'>" + data.message + "</p>";
}
</script>
</html>