<?php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/helpers.php';

$db = get_db();

$sessionTab = $_SESSION['active_tab'] ?? null;
unset($_SESSION['active_tab']);
$activeTab = $sessionTab ?? 'tab-transactions';

$alert = $_SESSION['alert'] ?? ['type' => '', 'message' => ''];
unset($_SESSION['alert']);


$items = $db->query("SELECT * FROM item WHERE is_archived = 0 ORDER BY itemCategory ASC, itemDesc ASC")->fetchAll();
$categories = $db->query("SELECT * FROM category ORDER BY categoryName ASC")->fetchAll();
$borrowers = $db->query("SELECT * FROM borrower WHERE is_archived = 0 ORDER BY brwFName ASC")->fetchAll();
$users = $isAdmin ? $db->query("SELECT * FROM user WHERE is_archived = 0 ORDER BY userRole ASC, userFName ASC")->fetchAll() : [];
$profileUser = $db->query("SELECT * FROM user WHERE userID = " . $db->quote($currentUser['userID']))->fetch();

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

$isReportActive = isset($_GET['start_date']) || isset($_GET['end_date']) || isset($_GET['period']);
if ($isReportActive) {
    $activeTab = 'tab-reports';
}

$today = date('Y-m-d');
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$currentYearStart = date('Y-01-01');
$currentYearEnd = date('Y-12-31');

$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$currentWeekEnd = date('Y-m-d', strtotime('sunday this week'));

$activePeriod = $_GET['period'] ?? null;

if ($activePeriod === 'day') {
    if (!empty($_GET['day'])) {
        $repStartDate = $_GET['day'];
        $repEndDate = $_GET['day'];
    } else {
        $repStartDate = $today;
        $repEndDate = $today;
    }
} elseif ($activePeriod === 'week') {
    if (!empty($_GET['week'])) {
        $wInput = $_GET['week'];
        if (preg_match('/^(\d{4})-W(\d{2})$/', $wInput, $m)) {
            $dto = new DateTime();
            $dto->setISODate((int)$m[1], (int)$m[2]);
            $repStartDate = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $repEndDate = $dto->format('Y-m-d');
        } else {
            $repStartDate = date('Y-m-d', strtotime('monday this week', strtotime($wInput)));
            $repEndDate = date('Y-m-d', strtotime('sunday this week', strtotime($wInput)));
        }
    } else {
        $repStartDate = $currentWeekStart;
        $repEndDate = $currentWeekEnd;
    }
} elseif ($activePeriod === 'month') {
    if (!empty($_GET['month'])) {
        $repStartDate = date('Y-m-01', strtotime($_GET['month'] . '-01'));
        $repEndDate = date('Y-m-t', strtotime($_GET['month'] . '-01'));
    } else {
        $repStartDate = $currentMonthStart;
        $repEndDate = $currentMonthEnd;
    }
} elseif ($activePeriod === 'year') {
    if (!empty($_GET['year'])) {
        $y = (int)$_GET['year'];
        $repStartDate = "$y-01-01";
        $repEndDate = "$y-12-31";
    } else {
        $repStartDate = $currentYearStart;
        $repEndDate = $currentYearEnd;
    }
} else {
    $repStartDate = $_GET['start_date'] ?? $currentMonthStart;
    $repEndDate = $_GET['end_date'] ?? date('Y-m-d');
    
    if ($repStartDate === $repEndDate && $repStartDate === $today) {
        $activePeriod = 'day';
    } elseif ($repStartDate === $currentWeekStart && $repEndDate === $currentWeekEnd) {
        $activePeriod = 'week';
    } elseif ($repStartDate === $currentYearStart && $repEndDate === $currentYearEnd) {
        $activePeriod = 'year';
    } elseif ($repStartDate === $currentMonthStart && ($repEndDate === $currentMonthEnd || $repEndDate === date('Y-m-d'))) {
        $activePeriod = 'month';
    } else {
        $activePeriod = 'month';
    }
}

if ($repStartDate === $repEndDate) {
    $formattedPeriodLabel = ($repStartDate === $today ? 'Today, ' : '') . date('M d, Y', strtotime($repStartDate));
} elseif (date('Y-m-01', strtotime($repStartDate)) === $repStartDate && date('Y-m-t', strtotime($repStartDate)) === $repEndDate) {
    $formattedPeriodLabel = date('F Y', strtotime($repStartDate));
} elseif (date('Y-01-01', strtotime($repStartDate)) === $repStartDate && date('Y-12-31', strtotime($repStartDate)) === $repEndDate) {
    $formattedPeriodLabel = 'Year ' . date('Y', strtotime($repStartDate));
} else {
    $formattedPeriodLabel = date('M d, Y', strtotime($repStartDate)) . ' – ' . date('M d, Y', strtotime($repEndDate));
}

$repWhereB = "1=1"; $repWhereR = "1=1"; $repWhereP = "1=1"; $repWhereUnified = "1=1";

if (!empty($repStartDate) && !empty($repEndDate)) {
    $startDateQuote = $db->quote($repStartDate);
    $endDateQuote = $db->quote($repEndDate);
    $repWhereB .= " AND (b.brwTransDate BETWEEN $startDateQuote AND $endDateQuote OR b.brwTransBorrowOnDate BETWEEN $startDateQuote AND $endDateQuote)";
    $repWhereR .= " AND r.retReturnedOnDate BETWEEN $startDateQuote AND $endDateQuote";
    $repWhereP .= " AND p.purDate BETWEEN $startDateQuote AND $endDateQuote";
    $repWhereUnified .= " AND ((b.brwTransDate BETWEEN $startDateQuote AND $endDateQuote) 
                               OR (b.brwTransBorrowOnDate BETWEEN $startDateQuote AND $endDateQuote) 
                               OR (r.retReturnedOnDate BETWEEN $startDateQuote AND $endDateQuote))";
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
    LEFT JOIN return_transaction r ON (b.brwTransID = r.brwTransID AND b.itemID = r.itemID)
    LEFT JOIN user u2 ON r.userID = u2.userID
    WHERE $repWhereUnified
    ORDER BY COALESCE(r.retReturnedOnDate, b.brwTransDate) DESC, b.brwTransID DESC
")->fetchAll();

$repBorrows = $db->query("SELECT b.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName FROM borrow_transaction b JOIN item i ON b.itemID = i.itemID JOIN borrower br ON b.brwID = br.brwID JOIN user u ON b.userID = u.userID WHERE $repWhereB ORDER BY COALESCE(b.brwTransBorrowOnDate, b.brwTransDate) DESC, b.brwTransID DESC")->fetchAll();
$repReturns = $db->query("SELECT r.*, i.itemDesc, br.brwFName, br.brwLName, u.userFName as staffFName, u.userLName as staffLName FROM return_transaction r JOIN item i ON r.itemID = i.itemID JOIN borrower br ON r.brwID = br.brwID JOIN user u ON r.userID = u.userID WHERE $repWhereR ORDER BY r.retReturnedOnDate DESC, r.retTransID DESC")->fetchAll();
$repPurchases = $db->query("SELECT p.*, i.itemDesc, u.userFName, u.userLName FROM purchase_transaction p JOIN item i ON p.itemID = i.itemID JOIN user u ON p.userID = u.userID WHERE $repWhereP ORDER BY p.purDate DESC, p.purTransID DESC")->fetchAll();
$repInventory = $items;

require_once 'views/layout/header.php';
?>

<?php require_once 'views/layout/topbar.php'; ?>
<div class="app-shell pb-0">
    <div class="app-layout">
        <?php require_once 'views/layout/sidebar.php'; ?>
        
        <main class="app-main" <?= ($sessionTab || $isReportActive) ? 'data-server-tab="' . htmlspecialchars($activeTab) . '"' : '' ?>>
            <?php if (!empty($alert['message'])): ?>
            <div class="app-alert-container no-print">
                <div class="alert alert-<?= $alert['type'] ?> app-alert alert-dismissible fade show" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <div>
                        <?= htmlspecialchars($alert['message']) ?>
                    </div>
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
require_once 'views/modals/modal_txn.php';
require_once 'views/modals/modal_item.php';
require_once 'views/modals/modal_borrower.php';
if ($isAdmin) { require_once 'views/modals/modal_user.php'; require_once 'views/modals/modal_category.php'; }
require_once 'views/modals/modal_purchase.php';

require_once 'views/layout/footer.php'; 
?>

