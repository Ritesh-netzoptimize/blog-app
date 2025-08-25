<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $responseMessage = "";

    $is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
    $is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';

    if (!$is_admin) {
        die("Access Denied: Admins only.");
    }

    $URL = 'http://localhost/blog-app/backend/api/v1/blog/fetch-all';
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    $result = curl_exec($ch);

    if ($result === false) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);

    $cleanResult = preg_replace('/^[^{]+/', '', $result);
    $json_response = json_decode($cleanResult, true);

    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        $blogs = $json_response['blogs'];
        $pendingBlogs = array_filter($blogs, function($b) {
            return $b['approved']===0;
        });
    } else {
        $responseMessage = "Failed to fetch blogs. Raw response: " . htmlspecialchars($result);
        $blogs = [];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Blogs</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/admin-dashboard.css">
    <style>
        .approved-label {
            color: green;
            font-weight: bold;
            margin-left: 10px;
        }
        .approve-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .approve-btn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        .disapproved-label {
            color: red;
            font-weight: bold;
            margin-left: 10px;
        }
        .disapprove-btn {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .disapprove-btn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include_once "../Templates/header.php"?>
    <div class="blogs-container">
        <h1>Pending Blog Approvals</h1>

        <?php if ($responseMessage): ?>
            <div class="response-message"><?php echo $responseMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($pendingBlogs) && count($pendingBlogs) > 0): ?>
            <ul class="blogs-list">
                <?php foreach ($blogs as $blog): ?>
                    <?php if ($blog['approved']===0): ?> 
                        <li class="blog-item" id="blog-<?php echo $blog['blog_id']; ?>">
                            <div class="blog-actions">
                                <a href="/blog-app/frontend/Pages/Blog/displaySingleBlog.php?id=<?php echo $blog['blog_id']; ?>">View</a>
                                <button class="approve-btn" data-blog-id="<?php echo $blog['blog_id']; ?>">Approve</button>
                                <button class="disapprove-btn" data-blog-id="<?php echo $blog['blog_id']; ?>">Disapprove</button>
                                <span class="approved-label" id="approved-label-<?php echo $blog['blog_id']; ?>"></span>
                                <span class="disapproved-label" id="disapproved-label-<?php echo $blog['blog_id']; ?>"></span>
                            </div>
                            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                            <p><?php echo htmlspecialchars(substr($blog['content'], 0, 150) . '...'); ?></p>
                            <p class="blog-author">Author ID: <?php echo htmlspecialchars($blog['author_id']); ?></p>
                            <p class="blog-date">Submitted on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p  style="text-align:center; font-size:18px; color:#555; margin-top:20px;">No blogs waiting for approval.</p>
        <?php endif; ?>
    </div>
    <?php include_once "../Templates/footer.php"?>

<script>
    
    document.querySelectorAll(".approve-btn, .disapprove-btn").forEach(btn => {
        btn.addEventListener("click", async function() {
            const blogId = this.dataset.blogId;
            const isApprove = this.classList.contains("approve-btn");
            const label = document.getElementById(isApprove ? "approved-label-" + blogId : "disapproved-label-" + blogId);

            // Get both buttons
            const approveBtn = document.querySelector(`.approve-btn[data-blog-id='${blogId}']`);
            const disapproveBtn = document.querySelector(`.disapprove-btn[data-blog-id='${blogId}']`);

            try {
                const response = await fetch(`http://localhost/blog-app/backend/api/v1/blog/${isApprove ? 'approve-blog' : 'disapprove-blog'}/${blogId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        author_id: <?php echo $_SESSION['user']['user_id']; ?>
                    })
                });

                const responseText = await response.text();
                let data;
                try {
                    const cleanText = responseText.replace(/^[^{]+/, '');
                    data = JSON.parse(cleanText);
                } catch (e) {
                    label.innerText = "Invalid response format";
                    label.style.color = "red";
                    return;
                }

                if (data.success === true) {
                    // Disable both buttons
                    approveBtn.disabled = true;
                    disapproveBtn.disabled = true;

                    // Show label
                    label.innerText = isApprove ? "Approved" : "Disapproved";
                    label.style.color = isApprove ? "green" : "red";
                } else {
                    label.innerText = "Failed: " + (data.message || "Unknown error");
                    label.style.color = "red";
                }
            } catch (err) {
                console.error("Fetch error:", err);
                label.innerText = "Error processing request";
                label.style.color = "red";
            }
        });
    });

</script>

</body>
</html>
