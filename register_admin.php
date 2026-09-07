<?php
require_once 'config.php';

$username = 'admin';
$password = 'admin123';  // Change this to something secure!
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role)
                        VALUES (?, ?, 'System Administrator', 'admin')");
$stmt->execute([$username, $hash]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Setup — Smart Slope</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 620px;">
            <div class="alert alert-success shadow-sm" role="alert">
                <h1 class="h4">Admin account created</h1>
                <p class="mb-1"><strong>Username:</strong> admin</p>
                <p class="mb-3"><strong>Password:</strong> admin123</p>
                <a class="btn btn-success" href="login.php">Go to Login</a>
            </div>
            <p class="text-muted small">Delete register_admin.php after the first setup.</p>
        </div>
    </main>
</body>
</html>
<?php
?>