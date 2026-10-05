<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'save_user' && $isAdmin) {
    $id = trim($_POST['userID'] ?? '');
    $fName = trim($_POST['userFName'] ?? '');
    $lName = trim($_POST['userLName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $contact = trim($_POST['userContactNo'] ?? '');
    $role = trim($_POST['userRole'] ?? 'Committee');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($contact) && !preg_match('/^\d{11}$/', $contact)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Contact number must be exactly 11 digits.'];
        $_SESSION['active_tab'] = 'tab-users';
        header('Location: ../index.php');
        exit;
    }

    if (!empty($email)) {
        // 44. Backend Email Format Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Invalid email format.'];
            $_SESSION['active_tab'] = 'tab-users';
            header('Location: ../index.php');
            exit;
        }
        
        $checkStmt = $db->prepare("SELECT userID FROM user WHERE userEmail = ? AND is_archived = 0");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        if ($existing && $existing['userID'] !== $id) {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Email address is already in use by another user.'];
            $_SESSION['active_tab'] = 'tab-users';
            header('Location: ../index.php');
            exit;
        }
    }

    if (!empty($id) && $id !== 'NEW') {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userRole=?, userPassword=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $role, $hash, $id]);
        } else {
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userRole=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $role, $id]);
        }
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User updated.'];
    } else {
        $newId = generate_id($db, 'user', 'userID', 'USR-');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO user (userID, userFName, userLName, userEmail, userContactNo, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $fName, $lName, $email, $contact, $role, $hash]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User created.'];
    }
    $_SESSION['active_tab'] = 'tab-users';
    header('Location: ../index.php');
    exit;

} elseif ($action === 'archive_user' && $isAdmin) {
    $id = trim($_POST['userID'] ?? '');
    if ($id === $currentUser['userID']) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Cannot archive yourself.'];
    } else {
        // Soft delete
        $db->prepare("UPDATE user SET is_archived = 1 WHERE userID = ?")->execute([$id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User archived.'];
    }
    $_SESSION['active_tab'] = 'tab-users';
    header('Location: ../index.php');
    exit;
}
