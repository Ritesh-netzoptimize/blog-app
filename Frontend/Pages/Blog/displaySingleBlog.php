<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die("Blog ID is missing.");
}

$blogId = intval($_GET['id']); 

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

$categoriesURL = "http://localhost/blog-app/backend/api/v1/category/fetch-all";
$ch = curl_init($categoriesURL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$catResult = curl_exec($ch);
curl_close($ch);

$categories = [];
if ($catResult) {
    $catData = json_decode($catResult, true);
    if ($catData && $catData['success'] === true) {
        $categories = $catData['categories'];
    }
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Blog</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/singleBlog.css">
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/assign-categegory.css">
    <style>
        .assign-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            max-width: 400px;
        }
        .assign-container h3 {
            margin-bottom: 10px;
        }
        select {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 100%;
            margin-bottom: 10px;
        }
        button.assign-btn {
            padding: 8px 12px;
            background: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button.assign-btn:hover {
            background: #1557b0;
        }
        .selected-info {
            margin-top: 10px;
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body class="body-container">
    <?php include_once '../../Templates/header.php'; ?>
    <a class="back-link" href="javascript:history.back()"><div class="back-button">Back</div></a>
    <div class="blog-content">
        <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
        <p class="blog-text"><?php echo htmlspecialchars($blog['content']); ?></p>
        <p class="blog-meta">Author: <?php echo htmlspecialchars($blog['author_id']); ?></p>
        <p class="blog-meta">Published on: <?php echo htmlspecialchars($blog['created_at']); ?></p>
        <a class="back-link" href="/blog-app/frontend/index.php">Back to all blogs</a>

        <?php if ($is_admin): ?>
            <div class="assign-container">
                <h3>Assign Category</h3>
                <select id="categorySelect">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="selectedCategory" class="selected-info"></div>
                <button class="assign-btn" id="assignBtn">Assign Category</button>
                <div id="assignMessage"></div>
            </div>
        <?php endif; ?>

        <?php include_once '../Comment/create.php'; ?>
        <?php include_once '../Comment/display.php'; ?>
    </div>

    <script>
        const categorySelect = document.getElementById("categorySelect");
        const selectedCategory = document.getElementById("selectedCategory");
        const assignBtn = document.getElementById("assignBtn");
        const assignMessage = document.getElementById("assignMessage");
        const blogId = <?php echo $blogId; ?>;

        let chosenCategoryId = null;

        categorySelect?.addEventListener("change", function() {
            chosenCategoryId = this.value;
            if (chosenCategoryId) {
                const selectedText = this.options[this.selectedIndex].text;
                selectedCategory.innerHTML = "Selected: " + selectedText;
            } else {
                selectedCategory.innerHTML = "";
            }
        });

        assignBtn?.addEventListener("click", async function() {
            if (!chosenCategoryId) {
                assignMessage.innerHTML = "<p style='color:red;'>Please select a category first.</p>";
                return;
            }
            const payload = {
                blog_id: blogId,
                category_id: chosenCategoryId,
                user_role: "admin"
            };
            try {
                const response = await fetch(`http://localhost/blog-app/backend/api/v1/category/assign-category/${chosenCategoryId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    assignMessage.innerHTML = "<p style='color:green;'>" + data.message + "</p>";
                } else {
                    assignMessage.innerHTML = "<p style='color:red;'>" + data.message + "</p>";
                }
            } catch (error) {
                assignMessage.innerHTML = "<p style='color:red;'>Something went wrong. Try again.</p>";
            }
        });
    </script>
</body>
</html>
