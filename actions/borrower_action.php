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
} elseif ($action === 'check_student_id') {
    header('Content-Type: application/json');
    $studentId = trim($_POST['studentId'] ?? '');
    $stmt = $db->prepare("SELECT brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo, is_archived FROM borrower WHERE brwStudentID = ? LIMIT 1");
    $stmt->execute([$studentId]);
    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($borrower) {
        echo json_encode([
            'exists' => true,
            'is_archived' => (int)$borrower['is_archived'],
            'borrower' => $borrower
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
} elseif ($action === 'save_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    $studentId = trim($_POST['brwStudentID'] ?? '');
    $fName = trim($_POST['brwFName'] ?? '');
    $lName = trim($_POST['brwLName'] ?? '');
    $college = trim($_POST['brwCollege'] ?? '');
    $org = trim($_POST['brwOrg'] ?? '');
    $contact = trim($_POST['brwContactNo'] ?? '');

    if (empty($studentId) || !validate_student_id($studentId)) {
        redirect_with_alert('tab-borrowers', 'danger', 'Invalid Student ID format. Use ##-#-##### (e.g. 23-1-12345).');
    }
    
    $cleanContact = validate_ph_contact($contact);
    if (!$cleanContact) {
        redirect_with_alert('tab-borrowers', 'danger', 'Invalid Contact Number. Must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).');
    }
    $contact = $cleanContact;

    if (empty($fName) || empty($lName) || empty($college)) {
        redirect_with_alert('tab-borrowers', 'danger', 'Please fill in all required borrower information.');
    }

    if (!empty($studentId)) {
        $checkStmt = $db->prepare("SELECT brwID FROM borrower WHERE brwStudentID = ?");
        $checkStmt->execute([$studentId]);
        $existing = $checkStmt->fetch();
        if ($existing && $existing['brwID'] !== $id) {
            redirect_with_alert('tab-borrowers', 'danger', 'Error: Student ID is already registered to another borrower.');
        }
    }

    if (!empty($id) && $id !== 'NEW') {
        $db->prepare("UPDATE borrower SET brwStudentID=?, brwFName=?, brwLName=?, brwCollege=?, brwOrg=?, brwContactNo=? WHERE brwID=?")
           ->execute([$studentId, $fName, $lName, $college, $org, $contact, $id]);
        redirect_with_alert('tab-borrowers', 'success', 'Borrower updated.');
    } else {
        $newId = generate_id($db, 'borrower', 'brwID', 'BRW-');
        $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $studentId, $fName, $lName, $college, $org, $contact]);
        redirect_with_alert('tab-borrowers', 'success', 'Borrower added.');
    }

} elseif ($action === 'archive_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    
    $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.brwID = ? AND b.brwTransStatus = 'Released'");
    $check->execute([$id]);
    $activeItems = $check->fetch()['unreturned'] ?? 0;
    
    if ($activeItems > 0) {
        redirect_with_alert('tab-borrowers', 'danger', "Cannot archive borrower: they have $activeItems unreturned item(s).");
    } else {
        $db->prepare("UPDATE borrower SET is_archived = 1 WHERE brwID = ?")->execute([$id]);
        redirect_with_alert('tab-borrowers', 'success', 'Borrower archived.');
    }
}

redirect_with_alert('tab-borrowers', 'warning', 'Invalid borrower action.');
