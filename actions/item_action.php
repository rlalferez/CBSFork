<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if (!$isAdmin) {
    redirect_with_alert('tab-inventory', 'danger', 'Permission Denied. Administrator privileges required.');
}

if ($action === 'save_item') {
    $id = trim($_POST['itemID'] ?? '');
    $desc = trim($_POST['itemDesc'] ?? '');
    $category = trim($_POST['itemCategory'] ?? 'Audio & Visual');
    $qty = max(0, (int)($_POST['itemTotalQty'] ?? 0));
    $rate = max(0.0, (float)($_POST['itemRate'] ?? 0));

    if (!empty($category)) {
        $db->prepare("INSERT IGNORE INTO category (categoryName) VALUES (?)")->execute([$category]);
    }

    if (!empty($id) && $id !== 'NEW') {
        $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemID != ? AND is_archived = 0");
        $stmt->execute([$desc, $id]);
        if ($stmt->fetch()) {
            redirect_with_alert('tab-inventory', 'danger', "An item with this description already exists in the inventory.");
        } else {
            $currStmt = $db->prepare("SELECT itemTotalQty, itemAvailableQty FROM item WHERE itemID = ?");
            $currStmt->execute([$id]);
            $currItem = $currStmt->fetch();
            
            $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.itemID = ? AND b.brwTransStatus = 'Released'");
            $check->execute([$id]);
            $unreturned = (int)($check->fetch()['unreturned'] ?? 0);
            
            $currentlyCheckedOut = max(
                $unreturned,
                $currItem ? max(0, (int)$currItem['itemTotalQty'] - (int)$currItem['itemAvailableQty']) : 0
            );
            
            if ($qty < $currentlyCheckedOut) {
                redirect_with_alert('tab-inventory', 'danger', "Quantity cannot be reduced below $currentlyCheckedOut unit(s) currently checked out.");
            }
            
            $newAvailableQty = $qty - $currentlyCheckedOut;
            $stmt = $db->prepare("UPDATE item SET itemDesc = ?, itemCategory = ?, itemTotalQty = ?, itemAvailableQty = ?, itemRate = ? WHERE itemID = ?");
            $stmt->execute([$desc, $category, $qty, $newAvailableQty, $rate, $id]);
            redirect_with_alert('tab-inventory', 'success', "Item updated successfully.");
        }
    } else {
        $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND is_archived = 0");
        $stmt->execute([$desc]);
        if ($stmt->fetch()) {
            redirect_with_alert('tab-inventory', 'danger', "Cannot create: An item with this description already exists in the inventory.");
        } else {
            $newId = generate_id($db, 'item', 'itemID', 'ITM-');
            $stmt = $db->prepare("INSERT INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$newId, $desc, $category, $qty, $qty, $rate]);
            redirect_with_alert('tab-inventory', 'success', "New item added.");
        }
    }

} elseif ($action === 'add_category') {
    $catName = trim($_POST['categoryName'] ?? '');
    if (!empty($catName)) {
        try {
            $db->prepare("INSERT INTO category (categoryName) VALUES (?)")->execute([$catName]);
            redirect_with_alert('tab-inventory', 'success', 'Category added successfully.');
        } catch (Exception $e) {
            redirect_with_alert('tab-inventory', 'danger', 'Category already exists or invalid.');
        }
    }
    redirect_with_alert('tab-inventory', 'warning', 'Category name cannot be empty.');

} elseif ($action === 'delete_category') {
    $catID = $_POST['categoryID'] ?? '';
    if (!empty($catID)) {
        $db->prepare("DELETE FROM category WHERE categoryID = ?")->execute([$catID]);
        redirect_with_alert('tab-inventory', 'success', 'Category deleted.');
    }
    redirect_with_alert('tab-inventory', 'warning', 'No category specified.');

} elseif ($action === 'archive_item') {
    $id = trim($_POST['itemID'] ?? '');
    
    $check = $db->prepare("SELECT SUM(CASE WHEN b.brwTransStatus = 'Released' THEN (brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) ELSE brwTransItemQty END) as active_count FROM borrow_transaction b WHERE b.itemID = ? AND b.brwTransStatus IN ('Released', 'Reserved')");
    $check->execute([$id]);
    $activeCount = (int)($check->fetch()['active_count'] ?? 0);
    
    if ($activeCount > 0) {
        redirect_with_alert('tab-inventory', 'danger', "Cannot archive item: $activeCount unit(s) are currently checked out or reserved. Complete or cancel them first.");
    } else {
        $db->prepare("UPDATE item SET is_archived = 1 WHERE itemID = ?")->execute([$id]);
        redirect_with_alert('tab-inventory', 'success', 'Item archived successfully.');
    }
}

redirect_with_alert('tab-inventory', 'warning', 'Invalid inventory action.');
