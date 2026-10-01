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
    $itemID = trim($_POST['itemID'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $borrowOnDate = trim($_POST['brwTransBorrowOnDate'] ?? date('Y-m-d'));
    $returnByDate = trim($_POST['brwTransReturnByDate'] ?? date('Y-m-d'));
    $payStat = trim($_POST['brwTransPayStat'] ?? 'Free');

    // Verify stock is sufficient before proceeding
    $item = $db->query("SELECT itemRate, itemAvailableQty FROM item WHERE itemID = '$itemID'")->fetch();
    if ($item['itemAvailableQty'] < $qty) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not enough stock available.'];
    } else {
        $transID = generate_id($db, 'borrow_transaction', 'brwTransID', 'TXN-');
        $total = $item['itemRate'] * $qty;
        
        // Record Borrow Ledger Entry
        $stmt = $db->prepare("INSERT INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$transID, $itemID, $currentUser['userID'], $brwID, $qty, $item['itemRate'], date('Y-m-d'), $borrowOnDate, $returnByDate, $payStat, $total]);
        
        // Deduct available stock
        $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?")->execute([$qty, $itemID]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => "Checkout successful. Transaction ID: $transID"];
    }
    $_SESSION['active_tab'] = 'tab-transactions';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'create_return') {
    $brwTransID = trim($_POST['brwTransID'] ?? '');
    $itemID = trim($_POST['itemID'] ?? '');
    $brwID = trim($_POST['brwID'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $retDate = trim($_POST['retReturnedOnDate'] ?? date('Y-m-d'));
    
    $retTransID = generate_id($db, 'return_transaction', 'retTransID', 'RET-');
    
    // Record Return Ledger Entry
    $stmt = $db->prepare("INSERT INTO return_transaction (retTransID, brwTransID, itemID, userID, brwID, brwTransQty, retReturnedOnDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$retTransID, $brwTransID, $itemID, $currentUser['userID'], $brwID, $qty, $retDate]);
    
    // Restore available stock
    $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty + ? WHERE itemID = ?")->execute([$qty, $itemID]);
    $_SESSION['alert'] = ['type' => 'success', 'message' => "Return processed successfully."];
    
    $_SESSION['active_tab'] = 'tab-transactions';
    header('Location: ../index.php');
    exit;
}
