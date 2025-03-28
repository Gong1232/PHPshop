<?php
require_once 'includes/User.php';

$user = new User();
$token = $_GET['token'] ?? '';

if($user->verifyEmail($token)) {
    header("Location: login.php?verified=1");
} else {
    header("Location: login.php?invalid_token=1");
}
exit();