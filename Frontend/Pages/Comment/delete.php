<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$responseMessage = "";

if (!isset($_GET['id'])) {
    die("Comment ID is missing.");
}

$commentId = $_GET['id'] ? intval($_GET['id']) : 0;

if ($commentId <= 0) {
    die("Invalid Comment ID.");
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    die("You must be logged in to delete a comment.");
}

$authorId = $_SESSION['user']['user_id'];

$URL = "http://localhost/blog-app/backend/api/v1/comment/delete/$commentId";
$payload = json_encode([
    'comment_id' => $commentId,
    'author_id' => $authorId,
    'role' => $_SESSION['user']['role']
]);

$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$result = curl_exec($ch);
if ($result === false) {
    $responseMessage = "cURL Error: " . curl_error($ch);
    curl_close($ch);
} else {
    curl_close($ch);
    $cleanResult = preg_replace('/^[^{]+/', '', $result);
    $json_response = json_decode($cleanResult, true);
    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        $responseMessage = "Comment deleted successfully.";
        header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=" . $json_response['blog_id']);
        exit();
    } else {
        $responseMessage = "Failed to delete comment. Raw response: " . htmlspecialchars($result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Comment</title>
</head>
<body>
    <div>
        <?php echo $responseMessage; ?>
        <br>
        <a href="javascript:history.back()">Go Back</a>
    </div>
</body>
</html>