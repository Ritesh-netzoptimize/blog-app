<?php if ($is_loggedIn): ?>
<div class="container">
    <p>Add a comment</p>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="message"><?php echo $_SESSION['flash_message']; ?></div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="text" name="comment" required>
        <button type="submit">Comment</button>
    </form>
</div>
<?php endif; ?>
