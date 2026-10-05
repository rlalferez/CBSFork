<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'search_transaction') {
    header('Content-Type: application/json');
    $query = trim($_POST['query'] ?? '');
    $stmt = $db->prepare("SELECT b.*, i.itemDesc, br.brwFName, br.brwLName FROM borrow_transaction b JOIN item i ON b.itemID = i.itemID JOIN borrower br ON b.brwID = br.brwID WHERE b.brwTransID LIKE ? OR CONCAT(br.brwFName, ' ', br.brwLName) LIKE ? OR br.brwStudentID LIKE ? LIMIT 10");
    $like = "%$query%";
    $stmt->execute([$like, $like, $like]);
    echo json_encode($stmt->fetchAll());
    exit;
} elseif ($action === 'search_active_borrows') {
    header('Content-Type: application/json');
    $brwID = trim($_POST['brwID'] ?? '');
    // Get all borrow transactions for this borrower that haven't been fully returned
    $stmt = $db->prepare("
        SELECT b.brwTransID, b.itemID, i.itemDesc, b.brwTransItemQty, b.brwTransBorrowOnDate,
               COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0) as returned_qty
        FROM borrow_transaction b
        JOIN item i ON b.itemID = i.itemID
        WHERE b.brwID = ?
        HAVING (b.brwTransItemQty - returned_qty) > 0
    ");
    $stmt->execute([$brwID]);
    echo json_encode($stmt->fetchAll());
    exit;
} elseif ($action === 'purchase_item') {
    if (!$isAdmin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Permission Denied.'];
    } else {
        $itemDescs = $_POST['purItemDesc'] ?? [];
        $categories = $_POST['purCategory'] ?? [];
        $qtys = $_POST['purQty'] ?? [];
        $orNo = trim($_POST['purORNo'] ?? '');
        $purDate = trim($_POST['purDate'] ?? date('Y-m-d'));
        
        try {
            if (empty($itemDescs)) { // 43. Empty Transaction Payload Guard
                throw new Exception("No items provided for purchase.");
            }
            
            $db->beginTransaction();
            $count = 0;
            
            for ($i = 0; $i < count($itemDescs); $i++) {
                $desc = trim($itemDescs[$i]);
                $cat = trim($categories[$i]);
                $qty = max(1, (int)$qtys[$i]);
                if (empty($desc)) continue;
                
                // 40. Dynamic Category Auto-Registration
                $db->prepare("INSERT IGNORE INTO category (categoryName) VALUES (?)")->execute([$cat]);
                
                // Find if item already exists
                $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ? AND is_archived = 0 LIMIT 1");
                $stmt->execute([$desc, $cat]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $itemID = $existing['itemID'];
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
            $_SESSION['alert'] = ['type' => 'success', 'message' => "Logged $count purchase(s) and restocked inventory."];
        } catch(Exception $e) {
            $db->rollBack();
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error processing purchase.'];
        }
    }
    $_SESSION['active_tab'] = 'tab-purchases';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'create_borrow') {
    $brwID = trim($_POST['brwID'] ?? '');
    
    // Create new borrower if brwID is empty but details are provided
    if (empty($brwID) && !empty($_POST['brwStudentID'])) {
        $studentId = trim($_POST['brwStudentID']);
        $contactNo = trim($_POST['brwContact'] ?? '');
        
        // Revisions 15 & 16: Strict Formatting Validation
        if (!preg_match('/^\d{2}-\d-\d{5}$/', $studentId)) {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid Student ID format. Use ##-#-#####.'];
            $_SESSION['active_tab'] = 'tab-transactions';
            header('Location: ../index.php');
            exit;
        }
        if (!empty($contactNo) && !preg_match('/^\d{11}$/', $contactNo)) {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Contact number must be exactly 11 digits.'];
            $_SESSION['active_tab'] = 'tab-transactions';
            header('Location: ../index.php');
            exit;
        }
        
        // Check if student ID already exists
        $checkStmt = $db->prepare("SELECT brwID FROM borrower WHERE brwStudentID = ? LIMIT 1");
        $checkStmt->execute([$studentId]);
        $existingBorrower = $checkStmt->fetch();
        
        if ($existingBorrower) {
            $brwID = $existingBorrower['brwID'];
        } else {
            $brwID = generate_id($db, 'borrower', 'brwID', 'BRW-');
            $stmt = $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwContactNo, brwCollege, brwOrg) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $brwID, 
                $studentId, 
                $_POST['brwFName'], 
                $_POST['brwLName'], 
                $_POST['brwContact'], 
                $_POST['brwCollege'], 
                $_POST['brwDept']
            ]);
        }
    }
    
    $itemIDs = $_POST['itemID'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $borrowOnDate = trim($_POST['brwTransBorrowOnDate'] ?? date('Y-m-d'));
    $returnByDate = trim($_POST['brwTransReturnByDate'] ?? date('Y-m-d'));
    $payStat = trim($_POST['brwTransPayStat'] ?? 'Free');

    // 7. Empty Checkout Guard
    if (empty($itemIDs)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: No items selected for checkout.'];
        header('Location: ../index.php');
        exit;
    }

    // 8. Date Logic Validation Guard
    if (strtotime($returnByDate) < strtotime($borrowOnDate)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: Return date cannot be earlier than borrow date.'];
        header('Location: ../index.php');
        exit;
    }

    $db->beginTransaction();
    try {
        $transIDs = [];
        for($i = 0; $i < count($itemIDs); $i++) {
            $itemID = $itemIDs[$i];
            $qty = max(1, (int)$qtys[$i]);
            
            // 10 & 12. SQL Injection Guard and Archived Item Guard
            $itemStmt = $db->prepare("SELECT itemRate, itemAvailableQty FROM item WHERE itemID = ? AND is_archived = 0");
            $itemStmt->execute([$itemID]);
            $item = $itemStmt->fetch();
            
            if (!$item || $item['itemAvailableQty'] < $qty) {
                throw new Exception("Invalid, archived, or out-of-stock item ID: " . htmlspecialchars($itemID));
            }
            
            $transID = generate_id($db, 'borrow_transaction', 'brwTransID', 'TXN-');
            $transIDs[] = $transID;
            $total = $item['itemRate'] * $qty;
            
            $stmt = $db->prepare("INSERT INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal, brwTransStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$transID, $itemID, $currentUser['userID'], $brwID, $qty, $item['itemRate'], date('Y-m-d'), $borrowOnDate, $returnByDate, $payStat, $total, 'Released']);
            
            $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?")->execute([$qty, $itemID]);
        }
        $db->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => "Checkout successful. Created " . count($transIDs) . " transaction(s)."];
        $_SESSION['print_receipt'] = $transIDs;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    $_SESSION['active_tab'] = 'tab-transactions';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'create_return') {
    $brwTransIDs = $_POST['brwTransID'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $brwID = trim($_POST['brwID'] ?? '');
    $retDate = trim($_POST['retReturnedOnDate'] ?? date('Y-m-d'));
    
    $db->beginTransaction();
    try {
        if (empty($brwTransIDs)) { // 43. Empty Transaction Payload Guard
            throw new Exception("No items selected for return.");
        }
        
        $count = 0;
        for($i = 0; $i < count($brwTransIDs); $i++) {
            $brwTransID = $brwTransIDs[$i];
            $qty = max(1, (int)$qtys[$i]);
            
            if(empty($brwTransID)) continue;
            
            // Get original transaction details to find itemID and checkout date
            $origTxn = $db->prepare("SELECT itemID, brwTransItemQty, brwTransBorrowOnDate FROM borrow_transaction WHERE brwTransID = ?");
            $origTxn->execute([$brwTransID]);
            $txnData = $origTxn->fetch();
            
            if($txnData) {
                $itemID = $txnData['itemID'];
                $originalQty = $txnData['brwTransItemQty'];
                
                // 41. Temporal Date Logic Guard
                if (strtotime($retDate) < strtotime($txnData['brwTransBorrowOnDate'])) {
                    throw new Exception("Return date cannot be earlier than the original checkout date.");
                }
                
                // 11. Oversized Return Guard
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
                
                // 9. Partial Return Status Bug Fix
                // Only mark as Returned if the sum of returned quantities meets or exceeds the originally borrowed quantity
                if (($alreadyReturned + $qty) >= $originalQty) {
                    $db->prepare("UPDATE borrow_transaction SET brwTransStatus = 'Returned' WHERE brwTransID = ?")->execute([$brwTransID]);
                }
                
                $count++;
            }
        }
        $db->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => "Returned $count item(s) successfully."];
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }
    
    $_SESSION['active_tab'] = 'tab-transactions';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'archive_purchase') {
    if (!$isAdmin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Permission Denied.'];
    } else {
        $purID = trim($_POST['purTransID'] ?? '');
        try {
            $db->beginTransaction();
            
            // Get purchase details to subtract qty
            $stmt = $db->prepare("SELECT itemID, purQty FROM purchase_transaction WHERE purTransID = ?");
            $stmt->execute([$purID]);
            $pur = $stmt->fetch();
            
            if ($pur) {
                // Deduct from item table
                $qty = $pur['purQty'];
                $itemID = $pur['itemID'];
                
                // Get current available stock to ensure we don't drop below zero
                $stockCheck = $db->prepare("SELECT itemAvailableQty FROM item WHERE itemID = ?");
                $stockCheck->execute([$itemID]);
                $itemStock = $stockCheck->fetch();
                
                if ($itemStock && ($itemStock['itemAvailableQty'] - $qty) < 0) {
                    throw new Exception("Cannot reverse this purchase. The items have already been checked out. You must return them before archiving this purchase.");
                }
                
                $db->prepare("UPDATE item SET itemTotalQty = itemTotalQty - ?, itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?")
                   ->execute([$qty, $qty, $itemID]);
                   
                // Delete purchase transaction
                $db->prepare("DELETE FROM purchase_transaction WHERE purTransID = ?")->execute([$purID]);
                
                $db->commit();
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Purchase record deleted and stock deducted.'];
            } else {
                throw new Exception("Purchase record not found.");
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
        }
    }
    
    $_SESSION['active_tab'] = 'tab-purchases';
    header('Location: ../index.php');
    exit;
}

