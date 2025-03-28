<?php
require_once 'includes/User.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $result = $user->login($email, $password);
    
    if($result === true) {
        header("Location: dashboard.php");
        exit();
    } elseif($result === "email_not_verified") {
        $error = "Please verify your email first!";
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <main>
        <div class="auth-container">
            <h2>Welcome Back</h2>
            
            <?php if(isset($_GET['verify_email'])): ?>
                <div class="alert success">
                    Verification email sent! Please check your inbox.
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Log In</button>
            </form>
            
            <div class="auth-links">
                <a href="forgot_password.php">Forgot Password?</a>
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </main>

    <?php include 'include/footer.php'; ?>
</body>
</html>