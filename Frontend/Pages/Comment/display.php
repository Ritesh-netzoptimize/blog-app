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
$comments = ($json_response && isset($json_response['success']) && $json_response['success'] === true)
    ? $json_response['comments']
    : [];


function fetchReplies($commentId) {
    $URL = "http://localhost/blog-app/backend/api/v1/comment/fetch-by-comment/$commentId";
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);
    $cleanResult = preg_replace('/^[^{]+/', '', $result);
    $json_response = json_decode($cleanResult, true);
    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        return $json_response['replies'];
    }
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_text'])) {
    $parentId = intval($_POST['parent_id']);
    $blogId = intval($_POST['blog_id']);
    $replyText = trim($_POST['reply_text']);
    $author_id = intval($_SESSION['user_id']);

    $payload = json_encode([
        "comment_id" => $parentId,
        "blog_id" => $blogId,
        "comment" => $replyText,
        "author_id" =>$author_id
    ]);

    $URL = "http://localhost/blog-app/backend/api/v1/comment/create";
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    curl_close($ch);
    $clean = preg_replace('/^[^{]+/', '', $result);
    $response = json_decode($clean, true);
    $responseMessage = ($response && $response['success'])
        ? "Reply added successfully!"
        : "Failed to add reply.";
}

function renderComment($comment, $blogId, $is_loggedIn) {
    $replies = fetchReplies($comment['comment_id']);
    ?>
    <li class="comment">
        <p><?php echo htmlspecialchars($comment['comment']); ?></p>
        <p style="font-size: 15px;" class="comment-author">By: <?php echo htmlspecialchars($comment['username']); ?></p>
        <p style="font-size: 13px;" class="comment-date">On: <?php echo htmlspecialchars($comment['created_at']); ?></p>

        <div class="comment-actions">
    <?php if ($is_loggedIn): ?>
        <a href="javascript:void(0);" class="reply-toggle" data-id="<?php echo $comment['comment_id']; ?>">Reply</a>
        <?php if(($_SESSION['user']['username'] === $comment['username']) || ($_SESSION['user']['role'] === 'admin')):?>
            <a href="/blog-app/frontend/Pages/comment/delete.php?id=<?php echo $comment['comment_id']; ?>" onclick="return confirm('Are you sure you want to delete this comment?');">Delete</a>
        <?php endif?>
        <?php if(($_SESSION['user']['username'] === $comment['username'])): ?>
            <a href="/blog-app/frontend/Pages/comment/update.php?id=<?php echo $comment['comment_id']; ?>">Edit</a>
        <?php endif ?>
    <?php else: ?>
        <span style="color:gray; font-size:1.2em;">Login to reply</span>
    <?php endif; ?>
</div>


        <div class="reply-section" id="reply-section-<?php echo $comment['comment_id']; ?>">
            <?php if (!empty($replies)): ?>
                <ul style="list-style-type:none; padding-left:0;">
                    <?php foreach ($replies as $reply): ?>
                        <?php renderComment($reply, $blogId, $is_loggedIn); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <!-- Reply form only if logged in -->
            <?php if ($is_loggedIn): ?>
                <form method="post" class="reply-input">
                    <input type="hidden" name="parent_id" value="<?php echo $comment['comment_id']; ?>">
                    <input type="hidden" name="blog_id" value="<?php echo $blogId; ?>">
                    <input type="text" name="reply_text" placeholder="Write a reply..." required>
                    <button type="submit">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </li>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comments Section</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
    margin: 0;
    padding: 20px;
}

.comments-section {
    max-width: 800px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.comment, .reply {
    border-bottom: 1px solid #ddd;
    padding: 10px 0;
}
.reply-section {
    margin-top: 10px;
    margin-left: 40px;
    border-left: 2px solid #eee;
    padding-left: 10px;
    display: none;
}
.reply-input {
    margin-top: 5px;
}
.reply-input input {
    width: 70%;
    padding: 6px;
}
.reply-input button {
    padding: 6px 10px;
}
.comment-actions a {
    margin-right: 10px;
    font-size: 0.85em;
    text-decoration: none;
    color: #007BFF;
}
.comment-actions a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div  class="comments-section">
    <h2>Comments</h2>

    <?php if (!empty($responseMessage)) : ?>
        <p style="color: green; font-size: 20px;"><?php echo $responseMessage; ?></p>
    <?php endif; ?>

    <!-- Always show comments -->
    <?php if (!empty($comments)): ?>
        <ul style="font-size: 10px; list-style-type:none; padding-left:0;">
            <?php foreach ($comments as $comment): ?>
                <?php renderComment($comment, $blogId, $is_loggedIn); ?>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="margin-top: 15px;">No comments yet. Be the first to comment!</p>
    <?php endif; ?>

    <!-- Show add-comment form only if logged in -->
    
        <!-- <p style="color: red;">You must be logged in to comment.</p> -->
</div>


<script>
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('reply-toggle')) {
        const id = e.target.dataset.id;
        const replySection = document.getElementById(`reply-section-${id}`);
        if (replySection) {
            replySection.style.display = replySection.style.display === 'block' ? 'none' : 'block';
        }
    }
});
</script>

</body>
</html>
