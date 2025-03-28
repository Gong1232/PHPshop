<?php
require_once 'includes/User.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    $username = htmlspecialchars($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $profile_photo = $_FILES['profile_photo'];
    
    $result = $user->register($username, $email, $password, $profile_photo);
    
    if($result === true) {
        header("Location: login.php?verify_email=1");
        exit();
    } else {
        $error = $result;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <main>
        <div class="auth-container">
            <h2>Create Account</h2>
            <?php if($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Profile Photo</label>
                    <input type="file" name="profile_photo" accept="image/*">
                </div>
                
                <button type="submit" class="btn-primary">Sign Up</button>
            </form>
            
            <div class="auth-links">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </main>

    <?php include 'include/footer.php'; ?>
</body>
</html>