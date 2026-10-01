<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $uid = trim($_POST['userID'] ?? '');
    $fName = trim($_POST['userFName'] ?? '');
    $lName = trim($_POST['userLName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $contact = trim($_POST['userContactNo'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    // Ensure user can only update their own profile, unless Admin
    if ($uid === $currentUser['userID'] || $isAdmin) {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userPassword=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $hash, $uid]);
        } else {
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $uid]);
        }
        if ($uid === $currentUser['userID']) {
            $_SESSION['full_name'] = $fName . ' ' . $lName;
        }
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
    }
    $_SESSION['active_tab'] = 'tab-profile';
    header('Location: ../index.php');
    exit;
}
