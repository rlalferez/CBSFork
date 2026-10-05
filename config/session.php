<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = isset($_SESSION['userID']) ? $_SESSION : null;
$isAdmin = ($currentUser && $currentUser['role'] === 'Council');

$current_file = basename($_SERVER['PHP_SELF']);
if (!$currentUser && $current_file !== 'login.php' && $current_file !== 'auth_action.php') {
    header('Location: login.php');
    exit;
}
