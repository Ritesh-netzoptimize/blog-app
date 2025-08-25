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

    $URL = "http://localhost/blog-app/backend/api/v1/blog/likes-count/$blogId";
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);

    $cleanResult = preg_replace('/^[^{]+/', '', $result);
    $json_response = json_decode($cleanResult, true);

    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        $likes_count = $json_response['Likes_count']; 
        
    } else {
        $likes_count = 0;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
        $author_id = $_SESSION['user']['user_id'] ?? null;
        if ($author_id) {
            $commentData = [
                'blog_id' => $blogId,
                'comment' => trim($_POST['comment']),
                'author_id' => $author_id
            ];

            $URL = 'http://localhost/blog-app/backend/api/v1/comment/create';
            $ch = curl_init($URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($commentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $result = curl_exec($ch);
            curl_close($ch);

            $json_response = json_decode($result, true);
            // $_SESSION['flash_message'] = $json_response['success'] ? "Comment added successfully!" : "Failed to add comment.";   

            header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=$blogId");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_text'])) {
        $parentId = intval($_POST['parent_id']);
        $replyText = trim($_POST['reply_text']);
        $author_id = $_SESSION['user']['user_id'] ?? null;

        if ($author_id && $parentId && $replyText) {
            $payload = json_encode([
                "comment_id" => $parentId,
                "blog_id" => $blogId,
                "comment" => $replyText,
                "author_id" => $author_id
            ]);

            $URL = "http://localhost/blog-app/backend/api/v1/comment/create";
            $ch = curl_init($URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $result = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($result, true);
            $_SESSION['flash_message'] = $json && $json['success'] ? "Reply added successfully!" : "Failed to add reply.";
        }

        // Always redirect back to avoid resubmission
        header("Location: /blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=$blogId");
        exit;
    }


    // Fetch user liked blogs
    $user_id = $_SESSION['user']['user_id'] ?? null;
    if ($user_id) {
        $URL = "http://localhost/blog-app/backend/api/v1/blog/user-liked-blogs/$user_id";
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);

        $cleanResult = preg_replace('/^[^{]+/', '', $result);
        $json_response = json_decode($cleanResult, true);

        if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
            $user_liked_blogs = $json_response['user_liked_blogs']; 
            
            $blog_exists_in_user = false;

            foreach ($user_liked_blogs as $user_liked_blog) {
                if ($user_liked_blog['blog_id'] == $blogId) {
                    $blog_exists_in_user = true;
                    break;
                }
            }


        } else {
            die("Failed to fetch blog. Raw response: " . htmlspecialchars($result));
        }
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
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/create-comment.css">
    <!-- <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/display-comment.css"> -->

<style>
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
</style>
</head>

<body class="body-container">
    <?php include_once '../../Templates/header.php'; ?>
    <a style="margin-top: 5px; width: fit-content;" class="back-link" href="javascript:history.back()">
        <div class="back-button">
            Back
        </div>
    </a>
    <div class="blog-content" style="">
    <!-- Like Button -->
            <div class="like-container" style="display: flex;">
                <?php if ($is_loggedIn): ?>
                    <div id="likeBtn" class="like-btn <?php echo $blog_exists_in_user ? 'liked' : 'unliked'; ?>" data-blogid="<?php echo $blogId; ?>" data-authorid="<?php echo $_SESSION['user']['user_id']; ?>">
                ♥
            </div>
            <?php else: ?>
            <!-- Show heart but not clickable -->
                <span id="likeBtn" class="like-btn disabled-heart">
                    ♥
                </span>
            <?php endif; ?>
            <?php if(!$is_loggedIn): ?>
                <div id="loginModal" class="modal">
                    <div class="modal-content">
                        <p>You must login to like this blog.</p>
                        <button id="goLogin">Login</button>
                        <button id="closeModal">Cancel</button>
                    </div>
                </div>
            <?php endif;?>

            <!-- Always show like count -->
            <span id="likeCount" style="font-size: 15px" class="like-count"><?php echo $likes_count?></span>
        </div>

        <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>

        <p class="blog-text">
            <?php echo nl2br(htmlspecialchars($firstHalf)); ?>
        </p>


        <?php if (!empty($blog['image_path'])): ?>
            <div style="margin-top: 30px; margin-bottom: 30px;" class="blog-image">
                <img  src="/blog-app/backend/<?php echo htmlspecialchars($blog['image_path']); ?>" alt="Blog Image" style="width: 100%;">
            </div>
        <?php endif; ?>

        <p class="blog-text">
            <?php echo nl2br(htmlspecialchars($secondHalf)); ?>
        </p>

        <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
        <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
        <?php if(!$is_loggedIn): ?>
            <a href="/blog-app/frontend/pages/auth/login.php" style="text-decoration:none; color:blue;">Login to add a comment</a>
        <?php endif;?>

        <?php include_once '../Comment/create.php'; ?>
        <?php include_once '../Comment/display.php'; ?>

    </div>

    <?php include_once '../../Templates/footer.php'; ?>

<script>

        document.addEventListener("DOMContentLoaded", async function () {
            const likeBtn = document.getElementById("likeBtn");
            const modal = document.getElementById("loginModal");
            const goLogin = document.getElementById("goLogin");
            const closeModal = document.getElementById("closeModal");
            const blogId = "<?php echo $blogId; ?>";
            const likeCountEl = document.getElementById("likeCount");

            // If not logged in → clicking heart shows modal
            <?php if (!$is_loggedIn): ?>
                likeBtn?.addEventListener("click", () => {
                    modal.style.display = "flex";
                });
                goLogin?.addEventListener("click", () => {
                    window.location.href = "/blog-app/frontend/Pages/Auth/login.php";
                });
                closeModal?.addEventListener("click", () => {
                    modal.style.display = "none";
                });
            <?php endif; ?>

            // If logged in → toggle like
            <?php if ($is_loggedIn): ?>
                likeBtn?.addEventListener("click", async function () {
                    const authorId = this.dataset.authorid;

                    try {
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

                    } catch (err) {
                        console.error("Error liking blog:", err);
                    }
                });
            <?php endif; ?>
        });


</script>

</body>
</html>
