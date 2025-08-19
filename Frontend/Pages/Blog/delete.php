<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    die("You must be logged in to delete a blog.");
}

if (!isset($_GET['id'])) {
    die("Blog ID is missing.");
}

$blogId = intval($_GET['id']); 
var_dump($blogId);

$URL = "http://localhost/blog-app/backend/api/v1/blog/delete/$blogId";
$ch = curl_init($URL);
$payload = json_encode([
            "author_id" => $_SESSION['user_id'] ?? null
        ]);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);


$result = curl_exec($ch);
curl_close($ch);
var_dump($result);
$cleanResult = preg_replace('/^[^{]+/', '', $result);
var_dump($cleanResult);

$json_response = json_decode($cleanResult, true);
var_dump($json_response);
if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
    echo "Blog deleted successfully!";
    header("Location: /blog-app/frontend/Pages/adminDashboard.php");
    exit;
} else {
    die("Failed to delete blog. Response: " . htmlspecialchars($result));
}
?>
