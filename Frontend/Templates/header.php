<?php
if ( session_status() === PHP_SESSION_NONE) {   
    session_start();
}
$is_loggedIn = isset($_SESSION['user']) && isset($_SESSION['session_id']);
$is_admin = $is_loggedIn && $_SESSION['user']['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/blog-app/frontend/Assets/CSS/header.css">
</head>
<style>
        
</style>
<body>

<header class="header">
    <a href="/blog-app/frontend/index.php/" class="logo">LOGO</div>
    <nav class="nav-links">
        <a href="/blog-app/frontend/index.php">Home</a>
        <a href="/blog-app/frontend/pages/categories/display.php">Blogs</a>
        <?php if ($is_loggedIn): ?>
            <a href="/blog-app/frontend/Pages/Blog/createBlog.php">Create New Blog</a>
        <?php endif; ?>
    </nav>
    <div class="auth-links">
        <?php if ($is_loggedIn): ?>
            <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</span>
            <?php if ($is_admin): ?>
                <a href="/blog-app/frontend/Pages/adminDashboard.php">Admin Dashboard</a>
            <?php endif; ?>
            <a href="/blog-app/frontend/Pages/Auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="/blog-app/frontend/Pages/Auth/login.php">Login</a>
            <a href="/blog-app/frontend/Pages/Auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</header>


</body>
</html>
