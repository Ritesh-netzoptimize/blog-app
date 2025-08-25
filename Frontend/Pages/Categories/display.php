<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';

$URL = "http://localhost/blog-app/backend/api/v1/category/fetch-all";
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$categories = [];
if ($result) {
    $data = json_decode($result, true);
    if ($data && $data['success'] === true) {
        $categories = $data['categories'];
    }
}

function buildTree(array $categories, $parentId = null) {
    $branch = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $children = buildTree($categories, $category['category_id']);
            if ($children) {
                $category['children'] = $children;
            }
            $branch[] = $category;
        }
    }
    return $branch;
}

$categoryTree = buildTree($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/display-categories.css">
    <style>
        .form-container {
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        .form-container h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .form-container input {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .form-container button {
            padding: 8px 12px;
            background: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background: #1557b0;
        }
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
    <script>
        function toggleSubcategories(id) {
            const subList = document.getElementById("sub-" + id);
            if (subList) {
                subList.style.display = (subList.style.display === "none") ? "block" : "none";
            }
        }
    </script>
</head>
<body>
    <?php include "../../templates/header.php" ?>
    <div class="category-container">
        <h1>All Categories</h1>
        <?php if ($is_admin): ?>
                <div class="form-container">
                    <h2>Create category</h2>
                    <div id="form-message"></div>
                    <form id="topCategoryForm">
                        <input type="text" name="name" id="name" placeholder="Enter Category Name" required>
                        <input type="hidden" name="parent_id" id="parent_id" value="">
                        <button type="submit">Create</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php if (!empty($categoryTree)): ?>
            <ul class="category-list">
                <?php
                function renderCategories($categories) {
                    foreach ($categories as $cat) {
                        echo '<li class="category-item">';

                        if (isset($cat['children'])) {
                            echo '<span class="caret" onclick="toggleSubcategories('.$cat['category_id'].')">▶</span>';
                        }
                        echo '<a class="category-name" href="/blog-app/frontend/Pages/categories/subcategories/displayAssociatedBlogs.php?id='.$cat['category_id'].'">'
                            .htmlspecialchars($cat['name']).'</a>';
                        if (isset($cat['children'])) {
                            echo '<ul class="subcategory-list" id="sub-'.$cat['category_id'].'" style="display:none;">';
                            renderCategories($cat['children']);
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }
                renderCategories($categoryTree);
                ?>
            </ul>
        <?php else: ?>
            <p>No categories available.</p>
        <?php endif; ?>

        
    </div>
    <?php include "../../templates/footer.php" ?>

    <script>
        const form = document.getElementById("topCategoryForm");
        const formMessage = document.getElementById("form-message");

        if (form) {
            form.addEventListener("submit", async function (e) {
                e.preventDefault();

                const name = document.getElementById("name").value.trim();

                if (!name) {
                    formMessage.innerHTML = "<p class='error-message'>Category name is required.</p>";
                    return;
                }

                const payload = {
                    name: name,
                    user_role: "admin",
                    parent_id: null
                };

                try {
                    const response = await fetch("http://localhost/blog-app/backend/api/v1/category/create", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (data.success) {
                        formMessage.innerHTML = "<p class='success-message'>" + data.message + "</p>";
                        form.reset();
                    } else {
                        formMessage.innerHTML = "<p class='error-message'>" + data.message + "</p>";
                    }
                } catch (error) {
                    formMessage.innerHTML = "<p class='error-message'>Something went wrong. Try again.</p>";
                }
            });
        }
    </script>
</body>
</html>
