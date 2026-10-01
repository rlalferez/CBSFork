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

// 2. DATA QUERIES FOR DISPLAY
// ==========================================================
$items = $db->query("SELECT * FROM item WHERE is_archived = 0 ORDER BY itemCategory ASC, itemDesc ASC")->fetchAll();
$borrowers = $db->query("SELECT * FROM borrower WHERE is_archived = 0 ORDER BY brwFName ASC")->fetchAll();
$users = $isAdmin ? $db->query("SELECT * FROM user WHERE is_archived = 0 ORDER BY userRole ASC, userFName ASC")->fetchAll() : [];
$profileUser = $db->query("SELECT * FROM user WHERE userID = " . $db->quote($currentUser['userID']))->fetch();

// Unified Transactions (JOIN both borrow and return tables)
$unifiedTransactions = $db->query("
    SELECT 
        b.brwTransID, b.brwTransDate, b.brwTransBorrowOnDate, b.brwTransReturnByDate,
        b.itemID, i.itemDesc, b.brwTransItemQty as borrow_qty, b.brwTransPayStat, b.itemRate,
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
$isReportActive = isset($_GET['period']);
if ($isReportActive) {
    $activeTab = 'tab-reports';
}
$repPeriod = $_GET['period'] ?? 'all';
$repDate = $_GET['date_val'] ?? '';
$repMonth = $_GET['month_val'] ?? '';
$repYear = $_GET['year_val'] ?? date('Y');
$repBrw = trim($_GET['brw_val'] ?? '');

$repWhereB = "1=1"; $repWhereR = "1=1"; $repWhereP = "1=1";

if (!empty($repBrw)) {
    $q = $db->quote("%$repBrw%");
    $repWhereB .= " AND (b.brwID LIKE $q OR br.brwStudentID LIKE $q OR br.brwFName LIKE $q OR br.brwLName LIKE $q)";
    $repWhereR .= " AND (r.brwID LIKE $q OR br.brwStudentID LIKE $q OR br.brwFName LIKE $q OR br.brwLName LIKE $q)";
    // purchases has no brwID
}

if ($repPeriod === 'day' && !empty($repDate)) {
    $repWhereB .= " AND DATE(b.brwTransDate) = " . $db->quote($repDate);
    $repWhereR .= " AND DATE(r.retReturnedOnDate) = " . $db->quote($repDate);
    $repWhereP .= " AND DATE(p.purDate) = " . $db->quote($repDate);
} elseif ($repPeriod === 'month' && !empty($repMonth)) {
    $repWhereB .= " AND b.brwTransDate LIKE " . $db->quote($repMonth . '%');
    $repWhereR .= " AND r.retReturnedOnDate LIKE " . $db->quote($repMonth . '%');
    $repWhereP .= " AND p.purDate LIKE " . $db->quote($repMonth . '%');
} elseif ($repPeriod === 'year' && !empty($repYear)) {
    $repWhereB .= " AND YEAR(b.brwTransDate) = " . (int)$repYear;
    $repWhereR .= " AND YEAR(r.retReturnedOnDate) = " . (int)$repYear;
    $repWhereP .= " AND YEAR(p.purDate) = " . (int)$repYear;
}

$repUnified = $db->query("
    SELECT 
        b.brwTransID, b.brwTransDate, b.brwTransBorrowOnDate, b.brwTransReturnByDate,
        b.itemID, i.itemDesc, b.brwTransItemQty as borrow_qty, b.brwTransPayStat, b.itemRate,
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
              <div>
                <h3 class="fw-bold text-dark mb-1">Council Desk Station</h3>
                <div class="text-muted small">
                  Welcome, <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong> &bull; Assigned as <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-uppercase"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
              </div>
              <div class="bg-white px-3 py-2 rounded-pill border shadow-sm small text-muted d-flex align-items-center gap-2">
                <svg width="16" height="16" class="text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>Date: <strong><?= date('F d, Y') ?></strong></span>
              </div>
            </div>
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
if ($isAdmin) { require_once 'views/modals/modal_user.php'; }
require_once 'views/modals/modal_purchase.php';

// Include Footer & JS
require_once 'views/layout/footer.php'; 
?>
