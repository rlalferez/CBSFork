<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'search_active_borrows') {
    header('Content-Type: application/json');
    $brwID = trim($_POST['brwID'] ?? '');
    $stmt = $db->prepare("
        SELECT b.brwTransID, b.itemID, i.itemDesc, b.brwTransItemQty, b.brwTransBorrowOnDate,
               COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0) as returned_qty
        FROM borrow_transaction b
        JOIN item i ON b.itemID = i.itemID
        WHERE b.brwID = ? AND b.brwTransStatus = 'Released'
        HAVING (b.brwTransItemQty - returned_qty) > 0
    ");
    $stmt->execute([$brwID]);
    echo json_encode($stmt->fetchAll());
    exit;
} elseif ($action === 'check_availability') {
    header('Content-Type: application/json');
    $itemID = trim($_POST['itemID'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $startDate = trim($_POST['borrowOnDate'] ?? date('Y-m-d'));
    $endDate = trim($_POST['returnByDate'] ?? date('Y-m-d'));
    
    if (empty($itemID)) {
        echo json_encode(['success' => false, 'message' => 'No item selected.']);
        exit;
    }
    if (strtotime($endDate) < strtotime($startDate)) {
        echo json_encode(['success' => false, 'message' => 'Return date cannot be earlier than borrow date.']);
        exit;
    }
    
    $result = check_item_availability_for_dates($db, $itemID, $qty, $startDate, $endDate);
    echo json_encode($result);
    exit;
} elseif ($action === 'release_reservation') {
    $brwTransID = trim($_POST['brwTransID'] ?? '');
    try {
        $stmt = $db->prepare("SELECT * FROM borrow_transaction WHERE brwTransID = ? AND brwTransStatus = 'Reserved'");
        $stmt->execute([$brwTransID]);
        $txn = $stmt->fetch();
        if ($txn) {
            $db->beginTransaction();
            $db->prepare("UPDATE borrow_transaction SET brwTransStatus = 'Released' WHERE brwTransID = ?")->execute([$brwTransID]);
            $db->prepare("UPDATE item SET itemAvailableQty = GREATEST(0, itemAvailableQty - ?) WHERE itemID = ?")->execute([$txn['brwTransItemQty'], $txn['itemID']]);
            $db->commit();
            redirect_with_alert('tab-transactions', 'success', "Reservation $brwTransID marked as Released.");
        } else {
            redirect_with_alert('tab-transactions', 'danger', "Reservation record not found.");
        }
    } catch(Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirect_with_alert('tab-transactions', 'danger', $e->getMessage());
    }

} elseif ($action === 'cancel_reservation') {
    $brwTransID = trim($_POST['brwTransID'] ?? '');
    $db->prepare("UPDATE borrow_transaction SET brwTransStatus = 'Cancelled' WHERE brwTransID = ? AND brwTransStatus = 'Reserved'")->execute([$brwTransID]);
    redirect_with_alert('tab-transactions', 'success', "Reservation $brwTransID has been cancelled.");

} elseif ($action === 'purchase_item') {
    if (!$isAdmin) {
        redirect_with_alert('tab-purchases', 'danger', 'Permission Denied. Administrator privileges required.');
    }

    $itemDescs = $_POST['purItemDesc'] ?? [];
    $categories = $_POST['purCategory'] ?? [];
    $qtys = $_POST['purQty'] ?? [];
    $orNo = trim($_POST['purORNo'] ?? '');
    $purDate = trim($_POST['purDate'] ?? date('Y-m-d'));
    
    try {
        if (empty($itemDescs)) {
            throw new Exception("No items provided for purchase.");
        }
        
        $db->beginTransaction();
        $count = 0;
        
        for ($i = 0; $i < count($itemDescs); $i++) {
            $desc = trim($itemDescs[$i]);
            $cat = trim($categories[$i]);
            $qty = max(1, (int)$qtys[$i]);
            if (empty($desc)) continue;
            
            $db->prepare("INSERT IGNORE INTO category (categoryName) VALUES (?)")->execute([$cat]);
            
            $stmt = $db->prepare("SELECT itemID, itemCategory FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND is_archived = 0 LIMIT 1");
            $stmt->execute([$desc]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $itemID = $existing['itemID'];
                $cat = $existing['itemCategory'];
                $db->prepare("UPDATE item SET itemTotalQty = itemTotalQty + ?, itemAvailableQty = itemAvailableQty + ? WHERE itemID = ?")->execute([$qty, $qty, $itemID]);
            } else {
                $itemID = generate_id($db, 'item', 'itemID', 'ITM-');
                $db->prepare("INSERT INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty) VALUES (?, ?, ?, ?, ?)")->execute([$itemID, $desc, $cat, $qty, $qty]);
            }
            
            $purTransID = generate_id($db, 'purchase_transaction', 'purTransID', 'PUR-');
            $db->prepare("INSERT INTO purchase_transaction (purTransID, itemID, userID, purORNo, purQty, purDate) VALUES (?, ?, ?, ?, ?, ?)")->execute([$purTransID, $itemID, $currentUser['userID'], $orNo, $qty, $purDate]);
            $count++;
        }
        $db->commit();
        redirect_with_alert('tab-purchases', 'success', "Logged $count purchase(s) and restocked inventory.");
    } catch(Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirect_with_alert('tab-purchases', 'danger', 'Error processing purchase.');
    }

} elseif ($action === 'create_borrow') {
    $brwID = trim($_POST['brwID'] ?? '');
    
    if (empty($brwID) && !empty($_POST['brwStudentID'])) {
        $studentId = trim($_POST['brwStudentID']);
        $contactNo = trim($_POST['brwContact'] ?? '');
        $fName = trim($_POST['brwFName'] ?? '');
        $lName = trim($_POST['brwLName'] ?? '');
        $college = trim($_POST['brwCollege'] ?? '');
        $org = trim($_POST['brwDept'] ?? '');
        
        if (!validate_student_id($studentId)) {
            redirect_with_alert('tab-transactions', 'danger', 'Invalid Student ID format. Use ##-#-##### (e.g. 23-1-12345).');
        }
        
        $cleanContact = validate_ph_contact($contactNo);
        if (!$cleanContact) {
            redirect_with_alert('tab-transactions', 'danger', 'Invalid Contact Number. Must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).');
        }
        $contactNo = $cleanContact;

        if (empty($fName) || empty($lName) || empty($college)) {
            redirect_with_alert('tab-transactions', 'danger', 'Please fill in all borrower details (Name, College).');
        }
        
        $checkStmt = $db->prepare("SELECT brwID, brwStudentID, brwFName, brwLName, is_archived FROM borrower WHERE brwStudentID = ? LIMIT 1");
        $checkStmt->execute([$studentId]);
        $existingBorrower = $checkStmt->fetch();
        
        if ($existingBorrower) {
            $status = ((int)$existingBorrower['is_archived'] === 1) ? 'archived' : 'active';
            redirect_with_alert('tab-transactions', 'danger', "Cannot register borrower: Student ID {$studentId} is already registered to an {$status} borrower ({$existingBorrower['brwFName']} {$existingBorrower['brwLName']}). Please select them from the search bar or verify the ID.");
        } else {
            $brwID = generate_id($db, 'borrower', 'brwID', 'BRW-');
            $stmt = $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwContactNo, brwCollege, brwOrg) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $brwID, 
                $studentId, 
                $fName, 
                $lName, 
                $contactNo, 
                $college, 
                $org
            ]);
        }
    }

    if (empty($brwID)) {
        redirect_with_alert('tab-transactions', 'danger', 'Please select or add a borrower.');
    }
    
    $itemIDs = $_POST['itemID'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $borrowOnDate = trim($_POST['brwTransBorrowOnDate'] ?? date('Y-m-d'));
    $returnByDate = trim($_POST['brwTransReturnByDate'] ?? date('Y-m-d'));
    
    $payStat = trim($_POST['brwTransPayStat'] ?? 'Free');

    if (empty($itemIDs)) {
        redirect_with_alert('tab-transactions', 'danger', 'Error: No items selected for checkout.');
    }

    if (strtotime($returnByDate) < strtotime($borrowOnDate)) {
        redirect_with_alert('tab-transactions', 'danger', 'Error: Return date cannot be earlier than borrow date.');
    }

    $today = date('Y-m-d');
    $isReservation = ($borrowOnDate > $today);
    $status = $isReservation ? 'Reserved' : 'Released';

    $db->beginTransaction();
    try {
        $transIDs = [];
        for($i = 0; $i < count($itemIDs); $i++) {
            $itemID = trim($itemIDs[$i] ?? '');
            if (empty($itemID)) continue;
            $qty = max(1, (int)($qtys[$i] ?? 1));
            
            $availCheck = check_item_availability_for_dates($db, $itemID, $qty, $borrowOnDate, $returnByDate);
            if (!$availCheck['success']) {
                $itemDesc = !empty($availCheck['item']['itemDesc']) ? "'" . $availCheck['item']['itemDesc'] . "': " : "";
                throw new Exception($itemDesc . $availCheck['message']);
            }
            
            $item = $availCheck['item'];
            $transID = generate_id($db, 'borrow_transaction', 'brwTransID', 'TXN-');
            $transIDs[] = $transID;
            $total = (float)$item['itemRate'] * $qty;
            
            $stmt = $db->prepare("INSERT INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal, brwTransStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$transID, $itemID, $currentUser['userID'], $brwID, $qty, $item['itemRate'], $today, $borrowOnDate, $returnByDate, $payStat, $total, $status]);
            
            if ($status === 'Released') {
                $db->prepare("UPDATE item SET itemAvailableQty = GREATEST(0, itemAvailableQty - ?) WHERE itemID = ?")->execute([$qty, $itemID]);
            }
        }
        
        if (empty($transIDs)) {
            throw new Exception("No valid items provided for checkout.");
        }
        
        $db->commit();
        $_SESSION['print_receipt'] = $transIDs;
        $successMsg = $isReservation
            ? ("Reservation placed successfully for " . count($transIDs) . " item(s) from " . date('M j, Y', strtotime($borrowOnDate)) . " to " . date('M j, Y', strtotime($returnByDate)) . ".")
            : ("Checkout successful. Created " . count($transIDs) . " transaction(s).");
        redirect_with_alert('tab-transactions', 'success', $successMsg);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirect_with_alert('tab-transactions', 'danger', $e->getMessage());
    }

} elseif ($action === 'create_return') {
    $brwTransIDs = $_POST['brwTransID'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $brwID = trim($_POST['brwID'] ?? '');
    $retDate = trim($_POST['retReturnedOnDate'] ?? date('Y-m-d'));
    
    $db->beginTransaction();
    try {
        if (empty($brwTransIDs)) {
            throw new Exception("No items selected for return.");
        }
        
        $count = 0;
        for($i = 0; $i < count($brwTransIDs); $i++) {
            $brwTransID = $brwTransIDs[$i];
            $qty = max(1, (int)$qtys[$i]);
            
            if(empty($brwTransID)) continue;
            
            $origTxn = $db->prepare("SELECT itemID, brwTransItemQty, brwTransBorrowOnDate FROM borrow_transaction WHERE brwTransID = ?");
            $origTxn->execute([$brwTransID]);
            $txnData = $origTxn->fetch();
            
            if($txnData) {
                $itemID = $txnData['itemID'];
                $originalQty = $txnData['brwTransItemQty'];
                
                if (strtotime($retDate) < strtotime($txnData['brwTransBorrowOnDate'])) {
                    throw new Exception("Return date cannot be earlier than the original checkout date.");
                }
                
                $sumStmt = $db->prepare("SELECT SUM(brwTransQty) as total_returned FROM return_transaction WHERE brwTransID = ?");
                $sumStmt->execute([$brwTransID]);
                $sumData = $sumStmt->fetch();
                $alreadyReturned = $sumData['total_returned'] ?? 0;
                
                if ($qty > ($originalQty - $alreadyReturned)) {
                    throw new Exception("Cannot return more items than originally borrowed.");
                }
                
                $retTransID = generate_id($db, 'return_transaction', 'retTransID', 'RET-');
                
                $stmt = $db->prepare("INSERT INTO return_transaction (retTransID, brwTransID, itemID, userID, brwID, brwTransQty, retReturnedOnDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$retTransID, $brwTransID, $itemID, $currentUser['userID'], $brwID, $qty, $retDate]);
                
                $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty + ? WHERE itemID = ?")->execute([$qty, $itemID]);
                
                if (($alreadyReturned + $qty) >= $originalQty) {
                    $db->prepare("UPDATE borrow_transaction SET brwTransStatus = 'Returned' WHERE brwTransID = ?")->execute([$brwTransID]);
                }
                
                $count++;
            }
        }
        $db->commit();
        redirect_with_alert('tab-transactions', 'success', "Returned $count item(s) successfully.");
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirect_with_alert('tab-transactions', 'danger', $e->getMessage());
    }

} elseif ($action === 'archive_purchase') {
    if (!$isAdmin) {
        redirect_with_alert('tab-purchases', 'danger', 'Permission Denied. Administrator privileges required.');
    }

    $purID = trim($_POST['purTransID'] ?? '');
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT itemID, purQty FROM purchase_transaction WHERE purTransID = ?");
        $stmt->execute([$purID]);
        $pur = $stmt->fetch();
        
        if ($pur) {
            $qty = $pur['purQty'];
            $itemID = $pur['itemID'];
            
            $stockCheck = $db->prepare("SELECT itemAvailableQty FROM item WHERE itemID = ?");
            $stockCheck->execute([$itemID]);
            $itemStock = $stockCheck->fetch();
            
            if ($itemStock && ($itemStock['itemAvailableQty'] - $qty) < 0) {
                throw new Exception("Cannot reverse this purchase. The items have already been checked out. You must return them before archiving this purchase.");
            }
            
            $db->prepare("UPDATE item SET itemTotalQty = itemTotalQty - ?, itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?")
               ->execute([$qty, $qty, $itemID]);
               
            $db->prepare("DELETE FROM purchase_transaction WHERE purTransID = ?")->execute([$purID]);
            
            $db->commit();
            redirect_with_alert('tab-purchases', 'success', 'Purchase record deleted and stock deducted.');
        } else {
            throw new Exception("Purchase record not found.");
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirect_with_alert('tab-purchases', 'danger', $e->getMessage());
    }
}

redirect_with_alert('tab-transactions', 'warning', 'Invalid transaction action.');
