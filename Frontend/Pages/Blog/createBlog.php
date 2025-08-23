<?php
session_start();

// Fetch categories (optional, not used in this snippet but kept)
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

if (!isset($_SESSION['user'])) {
    die("You must be logged in to create a blog.");
}

$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$author_id = $_SESSION['user']['user_id'];

$responseMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $image   = $_FILES['blog_image'] ?? null;

    if (empty($title) || empty($content)) {
        $responseMessage = "Title and content cannot be empty.";
    } else {
        $postData = [
            "title"     => $title,
            "content"   => $content,
            "author_id" => $author_id,
        ];

        // Send the actual uploaded file under key 'image'
        if ($image && $image['error'] === 0) {
            $postData['image'] = new CURLFile($image['tmp_name'], $image['type'], $image['name']);
        }

        $url = "http://localhost/blog-app/backend/api/v1/blog/create";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // send as form-data, NOT json
        $result = curl_exec($ch);
        curl_close($ch);

        $cleanResult = preg_replace('/^[^{]+/', '', $result);
        $json_response = json_decode($cleanResult, true);

        if ($json_response && !empty($json_response['success'])) {
            header("Location: /blog-app/frontend/pages/categories/assignCategories.php?id=" . $json_response['blog_id']);
            exit;
        } else {
            $responseMessage = "Failed to create blog. Response: " . $result;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Blog</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/assign-categegory.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        h1 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        textarea {
            height: 200px;
            resize: vertical;
        }
        button {
            margin-top: 20px;
            padding: 12px 25px;
            background-color: #2980b9;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease-in-out;
        }
        button:hover {
            background-color: #1c5980;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #eaf2f8;
            border-left: 4px solid #2980b9;
            color: #333;
        }
    </style>
</head>
<body>
<?php include_once '../../Templates/header.php'; ?>

<div class="container">
    <h1>Create a New Blog</h1>

    <?php if (!empty($responseMessage)) : ?>
        <div class="message"><?php echo $responseMessage; ?></div>
    <?php endif; ?>

   <form method="POST" action="" enctype="multipart/form-data">
        <label for="title">Blog Title</label>
        <input type="text" name="title" id="title" required>

        <label for="content">Content</label>
        <textarea name="content" id="content" required></textarea>

        <label for="blog_image">Upload Featured Image</label>
        <input type="file" name="blog_image" id="blog_image" accept="image/*">

        <!-- Image Preview Section -->
        <div id="imagePreviewContainer" style="margin-top:15px; display:none;">
            <p><strong>Selected Image Preview:</strong></p>
            <img id="imagePreview" src="" alt="Image Preview" style="max-width:100%; border:1px solid #ccc; border-radius:6px; padding:5px;">
        </div>

        <input type="hidden" name="author_id" value="<?php echo htmlspecialchars($author_id); ?>">

        <button type="submit">Create Blog</button>
    </form>
</div>
</body>
<script>
document.getElementById("blog_image").addEventListener("change", function(event) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById("imagePreviewContainer");
    const previewImage = document.getElementById("imagePreview");

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = "block";
        };
        reader.readAsDataURL(file);
    } else {
        previewImage.src = "";
        previewContainer.style.display = "none";
    }
});
</script>
</html>
