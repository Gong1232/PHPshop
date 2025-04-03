<?php
require 'base.php';

// Get sanitized redirect URL
$redirect = Base::sanitize($_GET['redirect'] ?? 'products.php');

if (Base::isLoggedIn()) {
    // User is logged in - proceed to requested page
    Base::redirect($redirect);
} else {
    // Not logged in - store requested URL and redirect to login
    $_SESSION['return_url'] = $redirect;
    Base::redirect('../login.php');
}