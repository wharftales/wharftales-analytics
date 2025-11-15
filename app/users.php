<?php
require_once 'config.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();

if (!isAdmin()) {
    header('Location: /index.php');
    exit;
}

$db = getDb();
$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $isAdmin]);
            $success = "User created successfully";
        } catch (Exception $e) {
            $error = "Failed to create user: " . $e->getMessage();
        }
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $userId = $_POST['user_id'] ?? 0;
    
    if ($userId == $user['id']) {
        $error = "You cannot delete your own account";
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $success = "User deleted successfully";
        } catch (Exception $e) {
            $error = "Failed to delete user: " . $e->getMessage();
        }
    }
}

// Get all users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Get all sites for permissions management
$sites = $db->query("SELECT * FROM sites ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Analytics Platform</title>
    <script src="/app/theme.js"></script>
    <link rel="stylesheet" href="/app/common.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 30px;
        }
        .card {
            padding: 32px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .data-table {
            margin-top: 20px;
        }
        .data-table th {
            padding: 12px;
        }
        .data-table td {
            padding: 16px 12px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-admin {
            background: var(--accent-primary);
            color: var(--bg-primary);
        }
        .badge-user {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <h1>User Management</h1>
        <p class="subtitle">Manage users and their permissions</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Create New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_admin" id="is_admin">
                        <label for="is_admin" style="margin: 0;">Administrator (full access to all sites)</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
        
        <div class="card">
            <h2>All Users</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['is_admin']): ?>
                                    <span class="badge badge-admin">ADMIN</span>
                                <?php else: ?>
                                    <span class="badge badge-user">USER</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $user['id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
