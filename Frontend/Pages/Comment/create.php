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
if (!$is_loggedIn) {
    die("You must be logged in to comment.");
}

$author_id = isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : '';
// var_dump($author_id);
if (!$author_id) {
    die("User ID is required.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if ($result === false) {
            $responseMessage = "cURL Error: " . curl_error($ch);
        }
        curl_close($ch);
        $cleanResult = preg_replace('/^[^{]+/', '', $result);
        $json_response = json_decode($cleanResult, true);
        if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
            header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=$blogId");
            exit();
        }
        else {
            $responseMessage = "Failed to add comment. Raw response: " . htmlspecialchars($result);
        }
    } else {
        $responseMessage = "comment and Author ID are required.";
    }
} else {
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        $responseMessage = "Comment added successfully!";
    } else {
        $responseMessage = "";
    }
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
    <div class="container">

        <p>Add a comment</p>

        <?php if (!empty($responseMessage)) : ?>
            <div class="message"><?php echo $responseMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="comment" id="comment" required>

            <input type="hidden" name="author_id" value="<?php echo htmlspecialchars($author_id); ?>">

            <button type="submit">comment</button>
        </form>
    </div>
</body>
</html>