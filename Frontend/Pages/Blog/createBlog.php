<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("You must be logged in to create a blog.");
}

$author_id = $_SESSION['user']['user_id'];

if (!$author_id) {
    die("User ID not found in session.");
}

$responseMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($title) || empty($content)) {
        $responseMessage = "Title and content cannot be empty.";
    } else {
        $postData = json_encode([
            "title" => $title,
            "content" => $content,
            "author_id" => $author_id
        ]);

        $url = "http://localhost/blog-app/backend/api/v1/blog/create";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $result = curl_exec($ch);
        curl_close($ch);

        $cleanResult = preg_replace('/^[^{]+/', '', $result);
        $json_response = json_decode($cleanResult, true);
       

        if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
            $responseMessage = "Blog created successfully!";
        } else {
            $responseMessage = "Failed to create blog. Response: " . ($result);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Blog</title>
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
        textarea {
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

        <form method="POST" action="">
            <label for="title">Blog Title</label>
            <input type="text" name="title" id="title" required>

            <label for="content">Content</label>
            <textarea name="content" id="content" required></textarea>

            <!-- Hidden author ID field (optional if needed for debugging) -->
            <input type="hidden" name="author_id" value="<?php echo htmlspecialchars($author_id); ?>">

            <button type="submit">Create Blog</button>
        </form>
    </div>
</body>
</html>
