<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    die("You must be logged in to update a blog.");
}

if (!isset($_GET['id'])) {
    die("Blog ID is missing.");
}

$blogId = (int) $_GET['id'];
$responseMessage = "";
$blog = ["title" => "", "content" => ""];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $URL = "http://localhost/blog-app/backend/api/v1/blog/fetch-single/$blogId";
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);

    $clean = preg_replace('/^[^{]+/', '', $result);
    $json = json_decode($clean, true);

    if ($json && !empty($json['success'])) {
        $blog = $json['blog'];
    } else {
        $responseMessage = "Failed to load blog. Raw response: " . htmlspecialchars($result);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!isset($_SESSION['user_id'])) {
                $responseMessage = "Session error: User ID not found. Please log in again.";
                return;
            }
    if ($title === '' || $content === '') {
        $responseMessage = "Title and content are required.";
    } else {
        $URL = "http://localhost/blog-app/backend/api/v1/blog/update/$blogId";
        $payload = json_encode([
            "title"   => $title,
            "content" => $content,
            // "author_id" => $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null,
            "author_id" => $_SESSION['user_id'] ?? null
        ]);
        
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        $clean = preg_replace('/^[^{]+/', '', $result);
        $json  = json_decode($clean, true);

        if ($json && !empty($json['success'])) {
            header("Location: /blog-app/frontend/Pages/adminDashboard.php");
            exit;
        } else {
            $responseMessage = "Update failed. Response: " . htmlspecialchars($result);
        }
    }

    $blog['title'] = $title ?? $blog['title'];
    $blog['content'] = $content ?? $blog['content'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Update Blog</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/update-blog.css" />
</head>
<body class="page">
    <?php include_once '../../Templates/header.php'; ?>

    <div class="card">
        <h1 class="card-title">Update Blog</h1>

        <?php if (!empty($responseMessage)): ?>
            <div class="message"><?php echo $responseMessage; ?></div>
            <script>
                console.error(<?php echo json_encode($responseMessage); ?>);
            </script>
        <?php endif; ?>

    <form class="form" method="POST" action="">
            <div class="field">
                <label class="label" for="title">Title</label>
                <input
                    class="input"
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo htmlspecialchars($blog['title'] ?? ''); ?>"
                    required
                />
            </div>

            <div class="field">
                <label class="label" for="content">Content</label>
                <textarea
                    class="textarea"
                    id="content"
                    name="content"
                    required
                ><?php echo htmlspecialchars($blog['content'] ?? ''); ?></textarea>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn btn-secondary" href="/blog-app/Frontend/Pages/adminDashboard.php">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
