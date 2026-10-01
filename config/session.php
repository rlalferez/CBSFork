<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current Logged-in User
$currentUser = isset($_SESSION['userID']) ? $_SESSION : null;
$isAdmin = ($currentUser && $currentUser['role'] === 'Council'); // 'Council' is the new admin

// Protect pages that require login (except login page)
$current_file = basename($_SERVER['PHP_SELF']);
if (!$currentUser && $current_file !== 'login.php' && $current_file !== 'auth_action.php') {
    header('Location: login.php');
    exit;
}
