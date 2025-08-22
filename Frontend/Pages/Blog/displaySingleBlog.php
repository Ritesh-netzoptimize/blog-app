<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die("Blog ID is missing.");
}

$blogId = intval($_GET['id']); 

// Fetch blog details
$URL = "http://localhost/blog-app/backend/api/v1/blog/fetch-single/$blogId";
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$cleanResult = preg_replace('/^[^{]+/', '', $result);
$json_response = json_decode($cleanResult, true);

if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
    $blog = $json_response['blog']; 
} else {
    die("Failed to fetch blog. Raw response: " . htmlspecialchars($result));
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$author_id = $is_loggedIn ? $_SESSION['user']['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Blog</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/singleBlog.css">
   
</head>
<body class="body-container">
    <?php include_once '../../Templates/header.php'; ?>
    <a class="back-link" href="javascript:history.back()"><div class="back-button">Back</div></a>
    <div class="blog-content" style="position: relative;">
    <!-- Like Button -->
    <div class="like-container">
        <?php if ($is_loggedIn): ?>
            <button 
                id="likeBtn" 
                class="like-btn unliked" 
                data-blogid="<?php echo $blogId; ?>" 
                data-authorid="<?php echo $author_id; ?>">
                ♥
        </button>
        <?php else: ?>
            <span style="color:#aaa; font-size:22px;">♥</span>
        <?php endif; ?>
        <span id="likeCount" class="like-count">0</span>
    </div>

    <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
    <p class="blog-text"><?php echo htmlspecialchars($blog['content']); ?></p>
    <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
    <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
    <a class="back-link" href="/blog-app/frontend/index.php">Back to all blogs</a>

    <?php include_once '../Comment/create.php'; ?>
    <?php include_once '../Comment/display.php'; ?>
</div>


    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const likeBtn = document.getElementById("likeBtn");
        const likeCount = document.getElementById("likeCount");
        const blogId = likeBtn ? likeBtn.dataset.blogid : null;
        const authorId = likeBtn ? likeBtn.dataset.authorid : null;

        // Fetch total likes count initially
        fetch(`http://localhost/blog-app/backend/api/v1/blog/likes-count/${blogId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    likeCount.textContent = data.Likes_count;
                }
            });

        if (likeBtn) {
            likeBtn.addEventListener("click", () => {
                fetch(`http://localhost/blog-app/backend/api/v1/blog/like/${blogId}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ author_id: authorId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const likedStatus = data.liked_result.status;
                        likeCount.textContent = data.liked_result.totalLikes;

                        if (likedStatus === "liked") {
                            likeBtn.classList.remove("unliked");
                            likeBtn.classList.add("liked");
                        } else {
                            likeBtn.classList.remove("liked");
                            likeBtn.classList.add("unliked");
                        }
                    }
                });
            });
        }
    });
    </script>
</body>
</html>
