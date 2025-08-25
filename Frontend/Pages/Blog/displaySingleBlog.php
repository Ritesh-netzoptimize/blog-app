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
    $content = $blog['content'];
       $halfLength = intval(strlen($content) / 2);
   
       // take first half
       $firstHalf = substr($content, 0, $halfLength);
   
       // find the last space so we don’t cut a word
       $lastSpace = strrpos($firstHalf, ' ');
       if ($lastSpace !== false) {
           $firstHalf = substr($content, 0, $lastSpace);
           $secondHalf = substr($content, $lastSpace + 1); 
       } else {
           // fallback if no space found
           $secondHalf = substr($content, $halfLength);
       }
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
    <a style="margin-top: 5px; width: fit-content;" class="back-link" href="javascript:history.back()"><div class="back-button">Back</div></a>
    <div class="blog-content" style="">
    <!-- Like Button -->
    <div class="like-container" style="display: flex;">
    <?php if ($is_loggedIn): ?>
        <div 
            id="likeBtn" 
            class="like-btn unliked" 
            data-blogid="<?php echo $blogId; ?>" 
            data-authorid="<?php echo $_SESSION['user']['user_id']; ?>">
            ♥
    </div>
    <?php else: ?>
        <!-- Show heart but not clickable -->
        <span id="likeBtn" class="like-btn disabled-heart">♥</span>
    <?php endif; ?>

        <!-- Always show like count -->
        <span id="likeCount" class="like-count">0</span>
    </div>


   <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>

    <p class="blog-text">
        <?php echo nl2br(htmlspecialchars($firstHalf)); ?>
    </p>


    <?php if (!empty($blog['image_path'])): ?>
    <div style="margin-top: 30px; margin-bottom: 30px;" class="blog-image">
        <img  src="/blog-app/backend/<?php echo htmlspecialchars($blog['image_path']); ?>" 
        alt="Blog Image" 
        style="width: 100%;">
        
    </div>
    <?php endif; ?>

    <p class="blog-text">
        <?php echo nl2br(htmlspecialchars($secondHalf)); ?>
    </p>

    <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
    <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
    <a class="back-link" href="/blog-app/frontend/index.php">Back to all blogs</a>


    <?php include_once '../Comment/create.php'; ?>
    <?php include_once '../Comment/display.php'; ?>
</div>

    <?php include_once '../../Templates/footer.php'; ?>

    <script>
    document.addEventListener("DOMContentLoaded", async function () {
    const blogId = "<?php echo $blogId; ?>";
    const likeCountEl = document.getElementById("likeCount");
    const likeBtn = document.getElementById("likeBtn");

    // Fetch like count
    const res = await fetch(`http://localhost/blog-app/backend/api/v1/blog/likes-count/${blogId}`);
    const data = await res.json();
    if (data.success) {
        likeCountEl.innerText = data.Likes_count;
    }

    // If user is logged in, enable toggle
    <?php if ($is_loggedIn): ?>
    likeBtn.addEventListener("click", async function () {
        const authorId = this.dataset.authorid;

        const response = await fetch(`http://localhost/blog-app/backend/api/v1/blog/like/${blogId}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ author_id: authorId })
        });

        const result = await response.json();
        if (result.success) {
            const likedResult = result.liked_result;
            likeCountEl.innerText = likedResult.totalLikes;

            if (likedResult.status === "liked") {
                likeBtn.classList.remove("unliked");
                likeBtn.classList.add("liked");
            } else {
                likeBtn.classList.remove("liked");
                likeBtn.classList.add("unliked");
            }
        }
    });
    <?php endif; ?>
});
</script>

    </script>
</body>
</html>
