<?php 
// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);
$is_login_page = $current_page === 'login.php';
$is_signup_page = $current_page === 'signup.php';

// Set the title suffix based on the page
$title_suffix = $is_login_page ? 'Login' : ($is_signup_page ? 'Sign Up' : 'Untitled');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="../js/app.js"></script>
</head>

<body>
    <header>
    <div id="info"><?= Base::sanitize(Base::temp('info')) ?></div>

    <h1><a href="/">PHP Shop</a>   <?= $title_suffix ?></h1>


    </header>