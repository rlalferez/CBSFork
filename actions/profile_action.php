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
    
    if (!empty($contact) && !preg_match('/^\d{11}$/', $contact)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Contact number must be exactly 11 digits.'];
        $_SESSION['active_tab'] = 'tab-profile';
        header('Location: ../index.php');
        exit;
    }
    
    // Ensure user can only update their own profile, unless Admin
    if ($uid === $currentUser['userID'] || $isAdmin) {
        
        if (!empty($email)) {
            // 44. Backend Email Format Validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Invalid email format.'];
                $_SESSION['active_tab'] = 'tab-profile';
                header('Location: ../index.php');
                exit;
            }
            
            $checkStmt = $db->prepare("SELECT userID FROM user WHERE userEmail = ? AND is_archived = 0");
            $checkStmt->execute([$email]);
            $existing = $checkStmt->fetch();
            if ($existing && $existing['userID'] !== $uid) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Email address is already in use by another user.'];
                $_SESSION['active_tab'] = 'tab-profile';
                header('Location: ../index.php');
                exit;
            }
        }

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
