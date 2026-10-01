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
        $rate = (float)($_POST['itemRate'] ?? 0);

        if (!empty($id) && $id !== 'NEW') {
            $stmt = $db->prepare("UPDATE item SET itemDesc = ?, itemCategory = ?, itemRate = ? WHERE itemID = ?");
            $stmt->execute([$desc, $category, $rate, $id]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => "Item updated."];
        } else {
            $newId = generate_id($db, 'item', 'itemID', 'ITM-');
            $stmt = $db->prepare("INSERT INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate) VALUES (?, ?, ?, 0, 0, ?)");
            $stmt->execute([$newId, $desc, $category, $rate]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => "New item added."];
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
