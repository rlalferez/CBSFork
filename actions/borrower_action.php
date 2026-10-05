<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'search_borrower') {
    header('Content-Type: application/json');
    $query = trim($_POST['query'] ?? '');
    $stmt = $db->prepare("SELECT * FROM borrower WHERE (brwFName LIKE ? OR brwLName LIKE ? OR CONCAT(brwFName, ' ', brwLName) LIKE ? OR brwStudentID LIKE ?) AND is_archived = 0 LIMIT 10");
    $like = "%$query%";
    $stmt->execute([$like, $like, $like, $like]);
    echo json_encode($stmt->fetchAll());
    exit;
} elseif ($action === 'save_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    $studentId = trim($_POST['brwStudentID'] ?? '');
    $fName = trim($_POST['brwFName'] ?? '');
    $lName = trim($_POST['brwLName'] ?? '');
    $college = trim($_POST['brwCollege'] ?? '');
    $org = trim($_POST['brwOrg'] ?? '');
    $contact = trim($_POST['brwContactNo'] ?? '');

    // Revisions 15 & 16: Strict Formatting Validation
    if (!empty($studentId) && !preg_match('/^\d{2}-\d-\d{5}$/', $studentId)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid Student ID format. Use ##-#-#####.'];
        $_SESSION['active_tab'] = 'tab-borrowers';
        header('Location: ../index.php');
        exit;
    }
    
    if (!empty($contact) && !preg_match('/^\d{11}$/', $contact)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Contact number must be exactly 11 digits.'];
        $_SESSION['active_tab'] = 'tab-borrowers';
        header('Location: ../index.php');
        exit;
    }

    if (!empty($studentId)) {
        $checkStmt = $db->prepare("SELECT brwID FROM borrower WHERE brwStudentID = ?");
        $checkStmt->execute([$studentId]);
        $existing = $checkStmt->fetch();
        if ($existing && $existing['brwID'] !== $id) {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Student ID is already registered to another borrower.'];
            $_SESSION['active_tab'] = 'tab-borrowers';
            header('Location: ../index.php');
            exit;
        }
    }

    if (!empty($id) && $id !== 'NEW') {
        $db->prepare("UPDATE borrower SET brwStudentID=?, brwFName=?, brwLName=?, brwCollege=?, brwOrg=?, brwContactNo=? WHERE brwID=?")
           ->execute([$studentId, $fName, $lName, $college, $org, $contact, $id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower updated.'];
    } else {
        $newId = generate_id($db, 'borrower', 'brwID', 'BRW-');
        $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $studentId, $fName, $lName, $college, $org, $contact]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower added.'];
    }
    $_SESSION['active_tab'] = 'tab-borrowers';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'archive_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    $db->prepare("UPDATE borrower SET is_archived = 1 WHERE brwID = ?")->execute([$id]);
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower archived.'];
    
    $_SESSION['active_tab'] = 'tab-borrowers';
    header('Location: ../index.php');
    exit;
}
