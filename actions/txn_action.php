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
        $itemID = trim($_POST['itemID'] ?? '');
        $orNo = trim($_POST['purORNo'] ?? '');
        $qty = max(1, (int)($_POST['purQty'] ?? 1));
        $purDate = trim($_POST['purDate'] ?? date('Y-m-d'));
        
        $purTransID = generate_id($db, 'purchase_transaction', 'purTransID', 'PUR-');
        
        // Insert Ledger Entry
        $stmt = $db->prepare("INSERT INTO purchase_transaction (purTransID, itemID, userID, purORNo, purQty, purDate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$purTransID, $itemID, $currentUser['userID'], $orNo, $qty, $purDate]);
        
        // Increment total inventory safely
        $db->prepare("UPDATE item SET itemTotalQty = itemTotalQty + ?, itemAvailableQty = itemAvailableQty + ? WHERE itemID = ?")->execute([$qty, $qty, $itemID]);
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Purchase logged and inventory restocked.'];
    }
    $_SESSION['active_tab'] = 'tab-purchases';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'create_borrow') {
    $brwID = trim($_POST['brwID'] ?? '');
    
    // Create new borrower if brwID is empty but details are provided
    if (empty($brwID) && !empty($_POST['brwStudentID'])) {
        $brwID = generate_id($db, 'borrower', 'brwID', 'BRW-');
        $stmt = $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwContactNo, brwCollege, brwOrg) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $brwID, 
            $_POST['brwStudentID'], 
            $_POST['brwFName'], 
            $_POST['brwLName'], 
            $_POST['brwContact'], 
            $_POST['brwCollege'], 
            $_POST['brwDept']
        ]);
    }
    
    $itemIDs = $_POST['itemID'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $borrowOnDate = trim($_POST['brwTransBorrowOnDate'] ?? date('Y-m-d'));
    $returnByDate = trim($_POST['brwTransReturnByDate'] ?? date('Y-m-d'));
    $payStat = trim($_POST['brwTransPayStat'] ?? 'Free');

    $db->beginTransaction();
    try {
        $transIDs = [];
        for($i = 0; $i < count($itemIDs); $i++) {
            $itemID = $itemIDs[$i];
            $qty = max(1, (int)$qtys[$i]);
            
            $item = $db->query("SELECT itemRate, itemAvailableQty FROM item WHERE itemID = '$itemID'")->fetch();
            if ($item['itemAvailableQty'] < $qty) {
                throw new Exception("Not enough stock for item ID: $itemID");
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
        $count = 0;
        for($i = 0; $i < count($brwTransIDs); $i++) {
            $brwTransID = $brwTransIDs[$i];
            $qty = max(1, (int)$qtys[$i]);
            
            if(empty($brwTransID)) continue;
            
            // Get original transaction details to find itemID
            $origTxn = $db->prepare("SELECT itemID FROM borrow_transaction WHERE brwTransID = ?");
            $origTxn->execute([$brwTransID]);
            $txnData = $origTxn->fetch();
            
            if($txnData) {
                $itemID = $txnData['itemID'];
                $retTransID = generate_id($db, 'return_transaction', 'retTransID', 'RET-');
                
                $stmt = $db->prepare("INSERT INTO return_transaction (retTransID, brwTransID, itemID, userID, brwID, brwTransQty, retReturnedOnDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$retTransID, $brwTransID, $itemID, $currentUser['userID'], $brwID, $qty, $retDate]);
                
                $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty + ? WHERE itemID = ?")->execute([$qty, $itemID]);
                $db->prepare("UPDATE borrow_transaction SET brwTransStatus = 'Returned' WHERE brwTransID = ?")->execute([$brwTransID]);
                $count++;
            }
        }
        $db->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => "Returned $count item(s) successfully."];
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error processing return.'];
    }
    
    $_SESSION['active_tab'] = 'tab-transactions';
    header('Location: ../index.php');
    exit;
}
