<?php
// login.php
session_start();

// Hardcoded credentials for student prototype
$VALID_USER = 'admin';
$VALID_PASS = 'password123';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if ($user === $VALID_USER && $pass === $VALID_PASS) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Smart Slope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 400px; margin-top: 100px;">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title text-center">Smart Slope V2</h3>
            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mt-3 mb-0"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
</body>
</html>