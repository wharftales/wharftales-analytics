<?php
// Header component - reusable across all pages
// Requires: $user variable to be set before including this file
?>
<div class="header">
    <a href="/index.php" class="logo">📊 Analytics</a>
    <div class="user-menu">
        <span class="user-info">
            <?= htmlspecialchars($user['name']) ?>
            <?php if ($user['is_admin']): ?>
                <span class="admin-badge">ADMIN</span>
            <?php endif; ?>
        </span>
        <?php if ($user['is_admin']): ?>
            <a href="/app/users.php" class="btn btn-secondary">Users</a>
        <?php endif; ?>
        <a href="/app/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>
