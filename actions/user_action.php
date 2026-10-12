<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if (!$isAdmin) {
    redirect_with_alert('tab-transactions', 'danger', 'Access denied. Administrator privileges required.');
}

if ($action === 'save_user') {
    $id = trim($_POST['userID'] ?? '');
    $fName = trim($_POST['userFName'] ?? '');
    $lName = trim($_POST['userLName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $contact = trim($_POST['userContactNo'] ?? '');
    $role = trim($_POST['userRole'] ?? 'Committee');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($contact)) {
        $cleanContact = validate_ph_contact($contact);
        if (!$cleanContact) {
            redirect_with_alert('tab-users', 'danger', 'Invalid Contact Number. Must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).');
        }
        $contact = $cleanContact;
    }

    if (!empty($email)) {
        if (!validate_email_format($email)) {
            redirect_with_alert('tab-users', 'danger', 'Error: Invalid email format.');
        }
        
        $checkStmt = $db->prepare("SELECT userID FROM user WHERE userEmail = ? AND is_archived = 0");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        if ($existing && $existing['userID'] !== $id) {
            redirect_with_alert('tab-users', 'danger', 'Error: Email address is already in use by another user.');
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
        redirect_with_alert('tab-users', 'success', 'User updated.');
    } else {
        // 50. Backend Password Enforcement for New User Accounts
        if (empty($pass)) {
            redirect_with_alert('tab-users', 'danger', 'Error: Password is required when creating a new user account.');
        }
        $newId = generate_id($db, 'user', 'userID', 'USR-');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO user (userID, userFName, userLName, userEmail, userContactNo, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $fName, $lName, $email, $contact, $role, $hash]);
        redirect_with_alert('tab-users', 'success', 'User created.');
    }

} elseif ($action === 'archive_user') {
    $id = trim($_POST['userID'] ?? '');
    if ($id === $currentUser['userID']) {
        redirect_with_alert('tab-users', 'danger', 'Cannot archive yourself.');
    } else {
        $db->prepare("UPDATE user SET is_archived = 1 WHERE userID = ?")->execute([$id]);
        redirect_with_alert('tab-users', 'success', 'User archived.');
    }
}

redirect_with_alert('tab-users', 'warning', 'Invalid user action.');
