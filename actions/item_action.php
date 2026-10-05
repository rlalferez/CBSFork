<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'save_item') {
    if (!$isAdmin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Permission Denied.'];
    } else {
        $id = trim($_POST['itemID'] ?? '');
        $desc = trim($_POST['itemDesc'] ?? '');
        $category = trim($_POST['itemCategory'] ?? 'Audio & Visual');
        $qty = max(0, (int)($_POST['itemTotalQty'] ?? 0));
        $rate = max(0.0, (float)($_POST['itemRate'] ?? 0)); // 42. Negative Item Rate Guard
        
        // 40. Dynamic Category Auto-Registration
        if (!empty($category)) {
            $db->prepare("INSERT IGNORE INTO category (categoryName) VALUES (?)")->execute([$category]);
        }

        if (!empty($id) && $id !== 'NEW') {
            $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ? AND itemID != ? AND is_archived = 0");
            $stmt->execute([$desc, $category, $id]);
            if ($stmt->fetch()) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => "An item with this description already exists in this category."];
            } else {
                $stmt = $db->prepare("UPDATE item SET itemDesc = ?, itemCategory = ?, itemRate = ? WHERE itemID = ?");
                $stmt->execute([$desc, $category, $rate, $id]);
                $_SESSION['alert'] = ['type' => 'success', 'message' => "Item updated."];
            }
        } else {
            $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ? AND is_archived = 0");
            $stmt->execute([$desc, $category]);
            if ($stmt->fetch()) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => "Cannot create: An item with this description already exists in this category."];
            } else {
                $newId = generate_id($db, 'item', 'itemID', 'ITM-');
                $stmt = $db->prepare("INSERT INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$newId, $desc, $category, $qty, $qty, $rate]);
                $_SESSION['alert'] = ['type' => 'success', 'message' => "New item added."];
            }
        }
    }
    $_SESSION['active_tab'] = 'tab-inventory';
    header('Location: ../index.php');
    exit;

} elseif ($action === 'add_category') {
    if ($isAdmin) {
        $catName = trim($_POST['categoryName'] ?? '');
        if (!empty($catName)) {
            try {
                $db->prepare("INSERT INTO category (categoryName) VALUES (?)")->execute([$catName]);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Category added successfully.'];
            } catch (Exception $e) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Category already exists or invalid.'];
            }
        }
    }
    $_SESSION['active_tab'] = 'tab-inventory';
    header('Location: ../index.php');
    exit;

} elseif ($action === 'delete_category') {
    if ($isAdmin) {
        $catID = $_POST['categoryID'] ?? '';
        if (!empty($catID)) {
            $db->prepare("DELETE FROM category WHERE categoryID = ?")->execute([$catID]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Category deleted.'];
        }
    }
    $_SESSION['active_tab'] = 'tab-inventory';
    header('Location: ../index.php');
    exit;

} elseif ($action === 'archive_item') {
    if (!$isAdmin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Permission Denied.'];
    } else {
        $id = trim($_POST['itemID'] ?? '');
        $db->prepare("UPDATE item SET is_archived = 1 WHERE itemID = ?")->execute([$id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Item archived successfully.'];
    }
    $_SESSION['active_tab'] = 'tab-inventory';
    header('Location: ../index.php');
    exit;
}
