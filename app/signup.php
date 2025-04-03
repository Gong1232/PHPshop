<?php
require 'include/base.php';

// Redirect if already logged in
if (Base::isLoggedIn()) {
    Base::redirect($_SESSION['return_url'] ?? '/');
}

$error = '';
$email = '';
$username = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Base::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission';
    } else {
        $email = trim(Base::post('email'));
        $password = Base::post('password');
        $confirm_password = Base::post('confirm_password');

        if (empty($email)) {
            $error = 'Please enter your email!';
        } elseif (empty($password)) {
            $error = 'Please enter your password!';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match!';
        } else {
            // Fixed: Use class name instead of self::
            $username = 'phpShop' . UserRegistration::getRandomString(10);
            $user = UserRegistration::insert_user($username, $email, $password);

            if ($user) {
                // Set success message before redirecting
                Base::temp('info', 'Signup successful! Please log in to continue.');
                Base::redirect('/login.php'); // Redirect to login instead of logging in directly
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

class UserRegistration
{
    // Made static
    public static function getRandomString($length)
    {
        return bin2hex(random_bytes($length / 2));
    }

    // Made static
    public static function insert_user($username, $email, $password)
    {
        try {
            $db = Base::getDB();

            // Check if email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                return false;
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $db->prepare("INSERT INTO users 
                            (username, email, password) 
                            VALUES (?, ?, ?)");
            $stmt->execute([
                $username,
                $email,
                $hashed_password
            ]);

            // Return user object
            return (object)[
                'id' => $db->lastInsertId(),
                'username' => $username,
                'email' => $email,
                'role' => 'member'
            ];
        } catch (PDOException $e) {
            Base::logError($e->getMessage());
            return false;
        }
    }
}

include 'include/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - PHPShop</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>

    <main class="auth-page">
        <div class="auth-container">
            <h2>Sign Up</h2>

            <?php if ($error): ?>
                <div class="alert error"><?= Base::sanitize($error) ?></div>
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

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>

            <div class="auth-links">
                Already have an account? <a href="/login.php">Log in</a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>