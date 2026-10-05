<?php
/**
 * ==========================================================
 * CONFEDERATES STUDENT COUNCIL - RESOURCE MANAGEMENT SYSTEM
 * Main Application Controller & View (index.php) - REFACTORED
 * ==========================================================
 */
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/helpers.php';

$db = get_db();

// Determine Active Tab (Dashboard)
$activeTab = $_SESSION['active_tab'] ?? 'tab-transactions';
// Reset active tab for next load
unset($_SESSION['active_tab']);

// Fetch Alert
$alert = $_SESSION['alert'] ?? ['type' => '', 'message' => ''];
unset($_SESSION['alert']);

// Trigger Receipt Print
$printReceipt = $_SESSION['print_receipt'] ?? null;
unset($_SESSION['print_receipt']);


// 2. DATA QUERIES FOR DISPLAY
// ==========================================================
$items = $db->query("SELECT * FROM item WHERE is_archived = 0 ORDER BY itemCategory ASC, itemDesc ASC")->fetchAll();
$categories = $db->query("SELECT * FROM category ORDER BY categoryName ASC")->fetchAll();
$borrowers = $db->query("SELECT * FROM borrower WHERE is_archived = 0 ORDER BY brwFName ASC")->fetchAll();
$users = $isAdmin ? $db->query("SELECT * FROM user WHERE is_archived = 0 ORDER BY userRole ASC, userFName ASC")->fetchAll() : [];
$profileUser = $db->query("SELECT * FROM user WHERE userID = " . $db->quote($currentUser['userID']))->fetch();

// Unified Transactions (JOIN both borrow and return tables)
$unifiedTransactions = $db->query("
    SELECT 
        b.brwTransID, b.brwTransDate, b.brwTransBorrowOnDate, b.brwTransReturnByDate,
        b.itemID, i.itemDesc, b.brwTransItemQty as borrow_qty, b.brwTransPayStat, b.brwTransStatus, b.itemRate,
        b.brwID, br.brwFName, br.brwLName, u1.userFName as borrow_staff_f, u1.userLName as borrow_staff_l,
        r.retTransID, r.retReturnedOnDate, r.brwTransQty as return_qty,
        u2.userFName as return_staff_f, u2.userLName as return_staff_l
    FROM borrow_transaction b
    JOIN item i ON b.itemID = i.itemID
    JOIN borrower br ON b.brwID = br.brwID
    JOIN user u1 ON b.userID = u1.userID
    LEFT JOIN return_transaction r ON b.brwTransID = r.brwTransID
    LEFT JOIN user u2 ON r.userID = u2.userID
    ORDER BY b.brwTransDate DESC
")->fetchAll();

$borrows = $db->query("SELECT b.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName 
                       FROM borrow_transaction b JOIN item i ON b.itemID = i.itemID JOIN borrower br ON b.brwID = br.brwID JOIN user u ON b.userID = u.userID
                       ORDER BY b.brwTransDate DESC")->fetchAll();

$returns = $db->query("SELECT r.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName 
                       FROM return_transaction r JOIN item i ON r.itemID = i.itemID JOIN borrower br ON r.brwID = br.brwID JOIN user u ON r.userID = u.userID
                       ORDER BY r.retReturnedOnDate DESC")->fetchAll();
                       
$purchases = $db->query("SELECT p.*, i.itemDesc, u.userFName, u.userLName 
                         FROM purchase_transaction p JOIN item i ON p.itemID = i.itemID JOIN user u ON p.userID = u.userID
                         ORDER BY p.purDate DESC")->fetchAll();

// ==========================================================
// 3. REPORT QUERIES
// ==========================================================
$isReportActive = isset($_GET['start_date']) || isset($_GET['end_date']);
if ($isReportActive) {
    $activeTab = 'tab-reports';
}
$repStartDate = $_GET['start_date'] ?? date('Y-m-01');
$repEndDate = $_GET['end_date'] ?? date('Y-m-d');

$repWhereB = "1=1"; $repWhereR = "1=1"; $repWhereP = "1=1";

if (!empty($repStartDate) && !empty($repEndDate)) {
    $start = $db->quote($repStartDate . ' 00:00:00');
    $end = $db->quote($repEndDate . ' 23:59:59');
    $repWhereB .= " AND b.brwTransDate BETWEEN $start AND $end";
    $repWhereR .= " AND r.retReturnedOnDate BETWEEN $start AND $end";
    $repWhereP .= " AND p.purDate BETWEEN $start AND $end";
}

$repUnified = $db->query("
    SELECT 
        b.brwTransID, b.brwTransDate, b.brwTransBorrowOnDate, b.brwTransReturnByDate,
        b.itemID, i.itemDesc, b.brwTransItemQty as borrow_qty, b.brwTransPayStat, b.brwTransStatus, b.itemRate,
        b.brwID, br.brwFName, br.brwLName, u1.userFName as borrow_staff_f, u1.userLName as borrow_staff_l,
        r.retTransID, r.retReturnedOnDate, r.brwTransQty as return_qty,
        u2.userFName as return_staff_f, u2.userLName as return_staff_l
    FROM borrow_transaction b
    JOIN item i ON b.itemID = i.itemID
    JOIN borrower br ON b.brwID = br.brwID
    JOIN user u1 ON b.userID = u1.userID
    LEFT JOIN return_transaction r ON b.brwTransID = r.brwTransID
    LEFT JOIN user u2 ON r.userID = u2.userID
    WHERE $repWhereB
    ORDER BY b.brwTransDate DESC
")->fetchAll();

$repBorrows = $db->query("SELECT b.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName FROM borrow_transaction b JOIN item i ON b.itemID = i.itemID JOIN borrower br ON b.brwID = br.brwID JOIN user u ON b.userID = u.userID WHERE $repWhereB ORDER BY b.brwTransDate DESC")->fetchAll();
$repReturns = $db->query("SELECT r.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName FROM return_transaction r JOIN item i ON r.itemID = i.itemID JOIN borrower br ON r.brwID = br.brwID JOIN user u ON r.userID = u.userID WHERE $repWhereR ORDER BY r.retReturnedOnDate DESC")->fetchAll();
$repPurchases = $db->query("SELECT p.*, i.itemDesc, u.userFName, u.userLName FROM purchase_transaction p JOIN item i ON p.itemID = i.itemID JOIN user u ON p.userID = u.userID WHERE $repWhereP ORDER BY p.purDate DESC")->fetchAll();
$repInventory = $db->query("SELECT * FROM item WHERE is_archived = 0 ORDER BY itemCategory ASC, itemDesc ASC")->fetchAll();

require_once 'views/layout/header.php';
?>

<?php require_once 'views/layout/topbar.php'; ?>
<div class="app-shell pb-0 no-print">
    <div class="app-layout">
        <?php require_once 'views/layout/sidebar.php'; ?>
        
        <main class="app-main">
            <!-- Alert Display -->
            <?php if (!empty($alert['message'])): ?>
            <div class="px-4 pt-4 pb-0 no-print">
                <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <div><?= htmlspecialchars($alert['message']) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php endif; ?>

            <div class="tab-content" id="v-pills-tabContent">
                <?php 
                    require_once 'views/pages/transactions.php';
                    require_once 'views/pages/purchases.php';
                    require_once 'views/pages/inventory.php';
                    require_once 'views/pages/borrowers.php';
                    require_once 'views/pages/reports.php';
                    require_once 'views/pages/profile.php';
                    
                    if ($isAdmin) {
                        require_once 'views/pages/users.php';
                    }
                ?>
            </div>
        </main>
    </div>
</div>

<?php 
// Include Modals
require_once 'views/modals/modal_txn.php';
require_once 'views/modals/modal_item.php';
require_once 'views/modals/modal_borrower.php';
if ($isAdmin) { require_once 'views/modals/modal_user.php'; require_once 'views/modals/modal_category.php'; }
require_once 'views/modals/modal_purchase.php';

// Include Footer & JS
require_once 'views/layout/footer.php'; 
?>

<?php if ($printReceipt): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ids = <?= json_encode($printReceipt) ?>;
        const url = 'print_receipt.php?ids=' + encodeURIComponent(JSON.stringify(ids));
        window.open(url, 'ReceiptWindow', 'width=800,height=600');
    });
</script>
<?php endif; ?>
