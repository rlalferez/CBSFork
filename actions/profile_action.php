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
    if ($uid !== $currentUser['userID'] && !$isAdmin) {
        redirect_with_alert('tab-profile', 'danger', 'Permission Denied: You can only update your own profile.');
    }
    
    if (!empty($contact)) {
        $cleanContact = validate_ph_contact($contact);
        if (!$cleanContact) {
            redirect_with_alert('tab-profile', 'danger', 'Invalid Contact Number. Must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).');
        }
        $contact = $cleanContact;
    }
    
    if (!empty($email)) {
        if (!validate_email_format($email)) {
            redirect_with_alert('tab-profile', 'danger', 'Error: Invalid email format.');
        }
        
        $checkStmt = $db->prepare("SELECT userID FROM user WHERE userEmail = ? AND is_archived = 0");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        if ($existing && $existing['userID'] !== $uid) {
            redirect_with_alert('tab-profile', 'danger', 'Error: Email address is already in use by another user.');
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
        $_SESSION['email'] = $email;
        $_SESSION['contact_number'] = $contact;
    }
    redirect_with_alert('tab-profile', 'success', 'Profile updated successfully.');
}

redirect_with_alert('tab-profile', 'warning', 'Invalid profile action.');
