<?php
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
    <script>
        function toggleSubcategories(id) {
            const subList = document.getElementById("sub-" + id);
            if (subList) {
                subList.classList.toggle("show");
            }
        }
    </script>
</head>
<body>
    <?php include "../../templates/header.php" ?>
    <div class="category-container">
        <h1>All Categories</h1>
        <?php if (!empty($categoryTree)): ?>
            <ul class="category-list">
                <?php
                function renderCategories($categories) {
                    foreach ($categories as $cat) {
                        echo '<li class="category-item">';
                        echo '<span class="category-name" onclick="toggleSubcategories('.$cat['category_id'].')">'.$cat['name'].'</span>';
                        if (isset($cat['children'])) {
                            echo '<ul class="subcategory-list" id="sub-'.$cat['category_id'].'">';
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
</body>
</html>
