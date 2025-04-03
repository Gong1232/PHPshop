<?php
require 'include/base.php';

// Redirect if already logged in
if (Base::isLoggedIn()) {
    Base::redirect($_SESSION['return_url'] ?? '/');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Base::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission';
    } else {
        $email = trim(Base::post('email'));
        $password = Base::post('password');

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password!';
        } else {
            $db = Base::getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            if ($user && password_verify($password, $user->password)) {
                Base::login($user, '/index.php');
            } else {
                $error = 'Invalid email or password!';
            }
        }
    }
}

include 'include/header.php';
?>

<main class="auth-page">
    <div class="auth-container">
        <h2>Login</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= Base::sanitize($error) ?></div>
        <?php endif; ?>

        <?php 
        // Check for temp message from signup
        $success_message = Base::temp('info');
        if ($success_message): ?>
            <div class="alert success"><?= Base::sanitize($success_message) ?></div>
            <script>
                // Popup alert
                alert("<?= Base::sanitize($success_message) ?>");
            </script>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Base::csrfToken() ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= Base::sanitize($email) ?>" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="auth-links">
            Don't have an account? <a href="/signup.php">Sign Up</a>
        </div>
    </div>
</main>

<?php include 'include/footer.php'; ?>