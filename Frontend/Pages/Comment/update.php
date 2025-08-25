<?php

    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $responseMessage = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if(!isset($_GET['id'])) {
            die("Comment ID is missing.");
        }

        $commentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if($commentId <= 0) {
            die("Invalid Comment ID.");
        }

        if(!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
            die("You must be logged in to delete a comment.");
        }

        $authorId = $_SESSION['user']['user_id'];

        $URL = "http://localhost/blog-app/backend/api/v1/comment/update/$commentId";
        $payload = json_encode([
            'comment_id' => $commentId,
            'comment' => isset($_POST['comment']) ? trim($_POST['comment']) : '',
            'author_id' => $authorId,
        ]);

        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        if($result === false) {
            $responseMessage = "cURL Error: " . curl_error($ch);
            curl_close($ch);
        }
        else {
            curl_close($ch);
            $cleanResult = preg_replace('/^[^{]+/', '', $result);
            $json_response = json_decode($cleanResult, true);
            if($json_response && isset($json_response['success']) && $json_response['success'] === true) {
                $responseMessage = "Comment updated successfully.";
                header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=" . $json_response['blog_id']);
                exit();
            }
            else {
                $responseMessage = "Failed to update comment. Raw response: " . htmlspecialchars($result);
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
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/update-comment.css">
</head>
<body>
    <form action="" method="POST" class="update-comment-container">
        <p class="update-title">Edit comment</p>
        <label for="comment"></label>
        <div class="comment-input-container">
            <input type="text" name="comment" value="<?php echo htmlspecialchars($json_response['comment'] ?? ''); ?>" />
        <button type="submit" class="btn">Update Comment</button>
        </div>
        <div>
            <?php echo $responseMessage; ?>
            <br>
            <a class="back-link btn-secondary"  href="javascript:history.back()">Back to Blog</a>
        </div>
    </form>
</body>
</html>
</body>
</html>
