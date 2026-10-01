<?php
require_once '../config/db.php';
require_once '../config/session.php';

$db = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $db->prepare("SELECT * FROM user WHERE userEmail = ? AND is_archived = 0 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['userPassword'])) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['full_name'] = $user['userFName'] . ' ' . $user['userLName'];
        $_SESSION['role'] = $user['userRole'];
        $_SESSION['email'] = $user['userEmail'];
        $_SESSION['contact_number'] = $user['userContactNo'];
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email or password.'];
        header('Location: ../login.php');
        exit;
    }
} elseif ($action === 'logout') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}
