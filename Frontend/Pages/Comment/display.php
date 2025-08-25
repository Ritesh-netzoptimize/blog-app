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
        $author_id = intval($_SESSION['user']['user_id']);

        $payload = json_encode([
            "comment_id" => $parentId,
            "blog_id" => $blogId,
            "comment" => $replyText,
            "author_id" => $author_id   // ✅ Added
        ]);

        $URL = "http://localhost/blog-app/backend/api/v1/comment/create";
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);
        $responseMessage = ($response && $response['success'])
            ? "Reply added successfully!"
            : "Failed to add reply.";

        // ✅ Redirect back to same blog after reply
        header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=$blogId");
        exit;
    }


    function renderComment($comment, $blogId, $is_loggedIn, $level = 0) {
        $replies = fetchReplies($comment['comment_id']);
        $replyCount = count($replies);

        // Different background based on nesting level
        $bgColors = ["#fff", "#f9f9f9", "#f1f1f1", "#e9e9e9"];
        $bg = $bgColors[$level % count($bgColors)];
        ?>
        <li class="comment" style="background:<?php echo $bg; ?>; padding:10px; border-radius:6px; margin-bottom:10px;">
            <p><?php echo htmlspecialchars($comment['comment']); ?></p>
            <p style="font-size: 14px; color:#555;" class="comment-author">
                By: <b><?php echo htmlspecialchars($comment['username']); ?></b>
            </p>
            <p style="font-size: 12px; color:#999;" class="comment-date">
                On: <?php echo htmlspecialchars($comment['created_at']); ?>
            </p>

            <div class="comment-actions">
                <!-- Show replies (always visible) -->
                <a href="javascript:void(0);" 
                class="show-replies" 
                data-id="<?php echo $comment['comment_id']; ?>"
                data-count="<?php echo $replyCount; ?>">
                Show Replies (<?php echo $replyCount; ?>)
                </a>

                <?php if ($is_loggedIn): ?>
                    <a href="javascript:void(0);" class="reply-toggle" data-id="<?php echo $comment['comment_id']; ?>">
                        Reply
                    </a>
                    <?php if(($_SESSION['user']['username'] === $comment['username']) || ($_SESSION['user']['role'] === 'admin')):?>
                        <a href="/blog-app/frontend/Pages/comment/delete.php?id=<?php echo $comment['comment_id']; ?>" 
                        onclick="return confirm('Delete this comment?');">Delete</a>
                    <?php endif ?>
                    <?php if($_SESSION['user']['username'] === $comment['username']): ?>
                        <a href="/blog-app/frontend/Pages/comment/update.php?id=<?php echo $comment['comment_id']; ?>">Edit</a>
                    <?php endif ?>
                <?php else: ?>
                    <a style="color:gray; font-size:0.9em; cursor:pointer;" href="../auth/login.php">Login to reply</a>
                <?php endif; ?>
            </div>

            <!-- Replies container -->
            <div class="replies" id="replies-<?php echo $comment['comment_id']; ?>" style="display:none; margin-left:20px; border-left:2px solid #ddd; padding-left:10px;">
                <?php if (!empty($replies)): ?>
                    <ul style="list-style-type:none; padding-left:0;">
                        <?php foreach ($replies as $reply): ?>
                            <?php renderComment($reply, $blogId, $is_loggedIn, $level + 1); ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 0.9em; color: gray; margin-top: 8px;">No replies yet.</p>
                <?php endif; ?>
            </div>

            <!-- Reply form (hidden initially) -->
            <?php if ($is_loggedIn): ?>
                <div class="reply-form" id="reply-form-<?php echo $comment['comment_id']; ?>" style="display:none; margin-top:5px;">
                    <form method="post" class="reply-input">
                        <input type="hidden" name="parent_id" value="<?php echo $comment['comment_id']; ?>">
                        <input type="hidden" name="blog_id" value="<?php echo $blogId; ?>">
                        <input type="text" name="reply_text" placeholder="Write a reply..." required style="width:70%; padding:6px;">
                        <button type="submit" style="padding:6px 12px;">Send</button>
                    </form>
                </div>
            <?php endif; ?>
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

    <div style="margin-top:10px;" class="comments-section">
        <h2>Comments</h2>

        <?php if (!empty($responseMessage)) : ?>
            <p style="color: green; font-size: 20px;"><?php echo $responseMessage; ?></p>
        <?php endif; ?>

        <!-- Always show comments -->
        <?php if (!empty($comments)): ?>
            <ul style="font-size: 15px; list-style-type:none; padding-left:0;">
                <?php foreach ($comments as $comment): ?>
                    <?php renderComment($comment, $blogId, $is_loggedIn); ?>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin-top: 15px;">No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>


<script>

    document.addEventListener('click', function(e) {
        // Toggle replies
        if (e.target.classList.contains('show-replies')) {
            const id = e.target.dataset.id;
            const repliesDiv = document.getElementById(`replies-${id}`);
            const replyCount = e.target.dataset.count;

            if (repliesDiv) {
                const isVisible = repliesDiv.style.display === 'block';
                repliesDiv.style.display = isVisible ? 'none' : 'block';
                e.target.textContent = isVisible 
                    ? `Show Replies (${replyCount})`
                    : `Hide Replies (${replyCount})`;
            }
        }

        // Toggle reply form
        if (e.target.classList.contains('reply-toggle')) {
            const id = e.target.dataset.id;
            const replyForm = document.getElementById(`reply-form-${id}`);
            if (replyForm) {
                replyForm.style.display = (replyForm.style.display === 'block') ? 'none' : 'block';
            }
        }
    });

</script>

</body>
</html>
