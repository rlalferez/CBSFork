File: index.php
``php
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
if ($isAdmin) { require_once 'views/modals/modal_user.php'; }
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

``

File: login.php
``php
<?php
require_once 'config/db.php';
require_once 'config/session.php';
if ($currentUser) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .modal-body .form-label { font-size: 1.05rem; margin-bottom: 0.5rem; }
      @media print {
          @page { size: landscape; margin: 10mm; }
          body { overflow: visible !important; }
          .app-main { overflow: visible !important; height: auto !important; }
          .table-responsive { overflow: visible !important; }
          .no-print { display: none !important; }
      }
      /* Sidebar Mobile Toggle CSS */
      .app-sidebar { transition: transform 0.3s ease; z-index: 1040; }
      @media (max-width: 991.98px) {
          .app-sidebar {
              position: fixed; top: 0; left: 0; height: 100vh; width: 270px;
              transform: translateX(-100%); background: #fff;
          }
          .app-sidebar.show-sidebar { transform: translateX(0); }
      }
      
      /* Mobile Login Responsive Layout */
      @media (max-width: 767.98px) {
          .login-left-panel {
              height: auto !important;
              padding: 1.25rem 1rem !important;
          }
          .login-logo {
              width: 40px !important;
              height: 40px !important;
          }
          .login-logo span {
              font-size: 7px !important;
              line-height: 1 !important;
          }
          .login-title-1 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; margin-right: 0.3rem !important; }
          .login-title-2 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; }
          .login-subtitle { font-size: 0.7rem !important; letter-spacing: 0.5px !important; margin-top: 0.3rem !important; }
          .login-logo-gap { gap: 0.75rem !important; margin-bottom: 0.75rem !important; }
          .login-text-container { padding: 0 !important; max-width: 100% !important; margin-left: 0 !important; }
          .login-right-panel { height: calc(100vh - 120px) !important; justify-content: flex-start !important; padding-top: 2rem !important; }
      }
  </style>
</head>
<body>


  <!-- ==========================================================
       SPLIT-SCREEN LOGIN WITH CANVAS ANIMATION
       ========================================================== -->
  <div class="row g-0 vh-100">
    <!-- Left Panel: Brand & Animation -->
    <div class="col-md-6 position-relative d-flex align-items-center justify-content-center overflow-hidden login-left-panel" style="background-color: #1d4ed8;">
      <!-- === START OF ANTIGRAVITY ANIMATION HTML (REMOVE IF NOT WANTED) === -->
      <canvas id="particlesCanvas" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 0; pointer-events: none;"></canvas>
      <!-- === END OF ANTIGRAVITY ANIMATION HTML === -->
      
      <div class="text-white text-start p-5 position-relative w-100 ms-md-4 login-text-container" style="z-index: 1; max-width: 650px;">
        <!-- Logos (Replace src with actual image paths) -->
        <div class="d-flex align-items-center gap-4 mb-4 login-logo-gap">
           <!-- SU CCS LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden;">
              <span class="text-primary fw-bold text-center" style="font-size: 11px;">SU CCS<br>LOGO</span>
              <!-- <img src="path/to/su-ccs-logo.png" alt="SU CCS" style="width: 100%; height: auto;"> -->
           </div>
           <!-- CCS CONFEDERATE STUDENT COUNCIL LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden;">
              <span class="text-primary fw-bold text-center" style="font-size: 11px;">CONFED<br>LOGO</span>
              <!-- <img src="path/to/confed-logo.png" alt="Confederates" style="width: 100%; height: auto;"> -->
           </div>
        </div>
        
        <!-- Text content -->
        <div class="d-block">
          <h1 class="fw-bold display-4 mb-0 login-title-1" style="line-height: 1.1; letter-spacing: -1px;">CONFEDERATES</h1>
          <h1 class="fw-bold display-4 mb-4 login-title-2" style="line-height: 1.1; letter-spacing: -1px;">STUDENT COUNCIL</h1>
          <h4 class="fw-light text-uppercase login-subtitle" style="letter-spacing: 1.5px; font-size: 1.3rem;">ITEM MANAGEMENT AND LENDING SYSTEM</h4>
        </div>
      </div>
    </div>
    
    <!-- Right Panel: Login Form -->
    <div class="col-md-6 d-flex flex-column align-items-center justify-content-center bg-light p-4 login-right-panel">
      <div style="max-width: 400px; width: 100%;">
        <?php if (!empty($alert['message'])): ?>
          <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4">
            <span class="fw-semibold"><?= htmlspecialchars($alert['message']) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 p-4 p-md-5 rounded-4">
          <h4 class="fw-bold mb-4 text-center">Sign In</h4>
          <form method="POST" action="actions/auth_action.php">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" id="loginEmail" name="email" class="form-control" placeholder="user@csc.edu.ph" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label">Password</label>
              <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <!-- Quick Sign-In Buttons (Testing Purposes) -->
            <div class="bg-light p-3 rounded border text-center mb-4 small">
              <span class="text-muted d-block mb-2">Quick Sign-In (Testing):</span>
              <div class="btn-group w-100">
                <!-- Council role is effectively the admin/core -->
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('loginEmail').value='admin@csc.edu.ph'; document.getElementById('loginPassword').value='admin123';">
                  Council
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('loginEmail').value='staff@csc.edu.ph'; document.getElementById('loginPassword').value='staff123';">
                  Committee
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- === START OF ANTIGRAVITY ANIMATION SCRIPT (REMOVE IF NOT WANTED) === -->
  <script>
    // Antigravity-style Particle Animation for Left Panel
    const canvas = document.getElementById('particlesCanvas');
    if(canvas) {
        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];
        const mouse = { x: -1000, y: -1000 };
        
        function resize() {
            width = canvas.width = canvas.parentElement.clientWidth;
            height = canvas.height = canvas.parentElement.clientHeight;
        }
        window.addEventListener('resize', resize);
        
        // Track mouse movement over the left panel
        canvas.parentElement.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });
        canvas.parentElement.addEventListener('mouseleave', () => {
            mouse.x = -1000;
            mouse.y = -1000;
        });
        
        // Simple Particle class
        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 1;
                this.vy = (Math.random() - 0.5) * 1;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if(this.x < 0 || this.x > width) this.vx *= -1;
                if(this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(220, 220, 220, 0.4)';
                ctx.fill();
            }
        }
        
        function init() {
            resize();
            particles = [];
            for(let i=0; i<60; i++) particles.push(new Particle());
            animate();
        }
        
        function animate() {
            ctx.clearRect(0, 0, width, height);
            for(let i=0; i<particles.length; i++) {
                particles[i].update();
                particles[i].draw();
                
                // Draw connecting lines between particles
                for(let j=i+1; j<particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if(dist < 100) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(220, 220, 220, ${0.3 - (dist/100)*0.3})`;
                        ctx.stroke();
                    }
                }
                
                // Draw line from particle to cursor
                const dx = particles[i].x - mouse.x;
                const dy = particles[i].y - mouse.y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if(dist < 150) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(220, 220, 220, ${0.5 - (dist/150)*0.5})`;
                    ctx.stroke();
                }
            }
            requestAnimationFrame(animate);
        }
        init();
    }
  </script>
  <!-- === END OF ANTIGRAVITY ANIMATION SCRIPT === -->

</body>
</html>

``

File: print_receipt.php
``php
<?php
/**
 * Transaction Receipt
 */
require_once 'config/db.php';
require_once 'config/session.php';

$db = get_db();

// Verify user is logged in
if (!$currentUser) {
    die("Unauthorized.");
}

$idsParam = $_GET['ids'] ?? '[]';
$transIDs = json_decode($idsParam, true);

if (!is_array($transIDs) || empty($transIDs)) {
    die("Invalid transaction data.");
}

// Fetch the transaction details
$inClause = implode(',', array_fill(0, count($transIDs), '?'));
$stmt = $db->prepare("
    SELECT b.*, i.itemDesc, br.brwFName, br.brwLName, br.brwStudentID, br.brwCollege, u.userFName as staffFName, u.userLName as staffLName
    FROM borrow_transaction b
    JOIN item i ON b.itemID = i.itemID
    JOIN borrower br ON b.brwID = br.brwID
    JOIN user u ON b.userID = u.userID
    WHERE b.brwTransID IN ($inClause)
");
$stmt->execute($transIDs);
$transactions = $stmt->fetchAll();

if (empty($transactions)) {
    die("Transactions not found.");
}

$firstTxn = $transactions[0];
$borrowerName = $firstTxn['brwFName'] . ' ' . $firstTxn['brwLName'];
$studentId = $firstTxn['brwStudentID'];
$college = $firstTxn['brwCollege'];
$date = date('F j, Y', strtotime($firstTxn['brwTransDate']));
$staff = $firstTxn['staffFName'] . ' ' . $firstTxn['staffLName'];

$totalAmount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f8f9fa;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #333;
            padding-bottom: 15px;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .details {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .details p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 5px 0;
            border-bottom: 1px dashed #ddd;
        }
        th {
            border-bottom: 1px dashed #333;
        }
        .totals {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 15px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border: none; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <div class="header">
            <h3>Confederates Student Council</h3>
            <p>Item Management & Lending System</p>
            <p>Borrowing Receipt</p>
        </div>
        
        <div class="details">
            <p><strong>Date:</strong> <?= htmlspecialchars($date) ?></p>
            <p><strong>Borrower:</strong> <?= htmlspecialchars($borrowerName) ?></p>
            <p><strong>Student ID:</strong> <?= htmlspecialchars($studentId) ?></p>
            <p><strong>College/Org:</strong> <?= htmlspecialchars($college) ?></p>
            <p><strong>Processed By:</strong> <?= htmlspecialchars($staff) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Fee</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <?php $totalAmount += $t['brwTransTotal']; ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($t['itemDesc']) ?><br>
                        <small>ID: <?= $t['brwTransID'] ?></small>
                    </td>
                    <td><?= $t['brwTransItemQty'] ?></td>
                    <td><?= number_format($t['itemRate'], 2) ?></td>
                    <td><?= number_format($t['brwTransTotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <p>Total Fee: <?= number_format($totalAmount, 2) ?></p>
        </div>

        <div class="footer">
            <p>Please return all items on or before the due date to avoid penalties.</p>
            <p>Thank you!</p>
        </div>
    </div>
</body>
</html>

``

File: database/confed_borrowing.sql
``sql
-- ==========================================================
-- CONFEDERATES STUDENT COUNCIL (CSC) - BORROWING SYSTEM
-- Database Schema (Refactored)
-- Database: confed_borrowing
-- ==========================================================
-- Instructions:
-- 1. Open http://localhost/phpmyadmin/index.php
-- 2. Click the "SQL" tab at the top
-- 3. Paste this entire script into the box and click "Go"
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `confed_borrowing` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `confed_borrowing`;

-- Temporarily disable foreign key constraints so we can drop tables cleanly
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. USER TABLE (Council Administrators & Committee Members)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `userID` VARCHAR(15) NOT NULL, -- e.g., USR-001
  `userFName` VARCHAR(50) NOT NULL,
  `userLName` VARCHAR(50) NOT NULL,
  `userEmail` VARCHAR(100) NOT NULL, -- Needed to open Gmail
  `userContactNo` VARCHAR(25) NOT NULL,
  `userRole` ENUM('Council', 'Committee') NOT NULL DEFAULT 'Committee',
  `userPassword` VARCHAR(255) NOT NULL, -- 255 for Bcrypt hashes
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete instead of hard delete
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. ITEM TABLE (Equipment Inventory)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `itemID` VARCHAR(15) NOT NULL, -- e.g., ITM-001
  `itemDesc` VARCHAR(150) NOT NULL, -- Name / Description of item
  `itemCategory` VARCHAR(50) NOT NULL,
  `itemTotalQty` INT NOT NULL DEFAULT 0, -- Will be incremented via purchases
  `itemAvailableQty` INT NOT NULL DEFAULT 0,
  `itemRate` DOUBLE NOT NULL DEFAULT 0.00, -- Rental Fee
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete flag
  PRIMARY KEY (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 3. BORROWER TABLE (Students and Organizations)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `borrower`;
CREATE TABLE `borrower` (
  `brwID` VARCHAR(15) NOT NULL, -- e.g., BRW-001
  `brwStudentID` VARCHAR(20) NOT NULL,
  `brwFName` VARCHAR(50) NOT NULL,
  `brwLName` VARCHAR(50) NOT NULL,
  `brwCollege` VARCHAR(100) NOT NULL,
  `brwOrg` VARCHAR(100) NULL, -- Added back for non-academic orgs
  `brwContactNo` VARCHAR(25) NOT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete flag
  PRIMARY KEY (`brwID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 4. BORROW TRANSACTION (Composite Key for multiple items)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `borrow_transaction`;
CREATE TABLE `borrow_transaction` (
  `brwTransID` VARCHAR(15) NOT NULL, -- e.g., TXN-001
  `itemID` VARCHAR(15) NOT NULL,     -- The item borrowed
  `userID` VARCHAR(15) NOT NULL,     -- Council/Committee member who processed this
  `brwID` VARCHAR(15) NOT NULL,      -- The borrower
  
  -- Snapshot data (saves info at the time of transaction)
  `brwTransItemQty` INT NOT NULL DEFAULT 1,
  `itemRate` DOUBLE NOT NULL, 
  
  -- Dates and Status
  `brwTransDate` DATE NOT NULL, -- Date the transaction was recorded
  `brwTransBorrowOnDate` DATE NOT NULL, -- When they took the item
  `brwTransReturnByDate` DATE NOT NULL, -- Deadline to return
  `brwTransPayStat` VARCHAR(20) DEFAULT 'Free',
  `brwTransTotal` DOUBLE DEFAULT 0.00,
  `brwTransStatus` VARCHAR(20) NOT NULL DEFAULT 'Released',
  
  -- COMPOSITE PRIMARY KEY: Allows same transaction ID to have multiple different items
  PRIMARY KEY (`brwTransID`, `itemID`),
  CONSTRAINT `fk_borrow_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 5. RETURN TRANSACTION (Composite Key)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `return_transaction`;
CREATE TABLE `return_transaction` (
  `retTransID` VARCHAR(15) NOT NULL, -- e.g., RET-001
  `brwTransID` VARCHAR(15) NOT NULL, -- Links back to the borrow transaction
  `itemID` VARCHAR(15) NOT NULL,     -- The item returned
  `userID` VARCHAR(15) NOT NULL,     -- Staff who processed the return
  `brwID` VARCHAR(15) NOT NULL,      -- Borrower who returned it
  
  `brwTransQty` INT NOT NULL DEFAULT 1, -- Quantity returned
  `retReturnedOnDate` DATE NOT NULL,    -- Date returned
  
  -- COMPOSITE PRIMARY KEY
  PRIMARY KEY (`retTransID`, `itemID`),
  CONSTRAINT `fk_return_brwtrans` FOREIGN KEY (`brwTransID`, `itemID`) REFERENCES `borrow_transaction` (`brwTransID`, `itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 6. PURCHASE TRANSACTION (Restock Ledger)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `purchase_transaction`;
CREATE TABLE `purchase_transaction` (
  `purTransID` VARCHAR(15) NOT NULL, -- e.g., PUR-001
  `itemID` VARCHAR(15) NOT NULL,     -- The item being restocked (must exist in item table)
  `userID` VARCHAR(15) NOT NULL,     -- Council member who logged the purchase
  `purORNo` VARCHAR(50) NOT NULL,    -- Official Receipt Number
  `purQty` INT NOT NULL,             -- Quantity purchased
  `purDate` DATE NOT NULL,           -- Date of purchase
  
  PRIMARY KEY (`purTransID`),
  CONSTRAINT `fk_pur_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pur_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- DEFAULT INITIAL DATA
-- ==========================================================
-- Insert a default Administrator (Council) account
-- Password is 'admin123' hashed with bcrypt
INSERT INTO `user` (`userID`, `userFName`, `userLName`, `userEmail`, `userContactNo`, `userRole`, `userPassword`) 
VALUES (
  'USR-001',
  'Council',
  'Administrator',
  'admin@csc.edu.ph',
  '09123456789',
  'Council',
  '$2y$10$fhXdAJw791ZaaJAFJ52lReP4mAjmzfXNtnJ7sgN0Q6sMCsrYs2AzK'
);

``

File: config/db.php
``php
<?php
/**
 * ==========================================================
 * DATABASE CONNECTION & CONFIGURATION (db.php)
 * Confederates Student Council - Resource Management System
 * ==========================================================
 * Configured for MySQL (phpMyAdmin / XAMPP).
 * Can be switched to 'sqlite' for zero-config portable testing.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------
// DATABASE CONFIGURATION (Default: MySQL for phpMyAdmin)
// ----------------------------------------------------------
define('DB_TYPE', 'mysql'); // Set to 'mysql' for phpMyAdmin, or 'sqlite' for portable file

// MySQL Credentials (Standard XAMPP Settings)
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');
define('MYSQL_DB',   'confed_borrowing');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

// SQLite Path (Fallback)
define('SQLITE_PATH', __DIR__ . '/database.sqlite');

/**
 * Get the PDO Database Instance
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        if (DB_TYPE === 'mysql') {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } else {
            $isNew = !file_exists(SQLITE_PATH);
            $pdo = new PDO('sqlite:' . SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Automatically build clean tables with only initial Admin account
            if ($isNew) {
                init_clean_tables($pdo);
            }
        }
        return $pdo;
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        die('
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Database Setup Required &bull; CSC Borrowing System</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light py-5">
          <div class="container" style="max-width: 680px;">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
              <div class="d-flex align-items-center gap-3 mb-3 text-danger">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <h4 class="fw-bold mb-0">MySQL Database Setup Required</h4>
              </div>
              <p class="text-muted small">The system could not connect to MySQL database <strong>' . htmlspecialchars(MYSQL_DB) . '</strong>.</p>
              
              <div class="alert alert-danger py-2 px-3 small font-monospace mb-4">
                ' . htmlspecialchars($errorMsg) . '
              </div>

              <h6 class="fw-bold text-dark mb-2">How to Setup the Database in phpMyAdmin:</h6>
              <ol class="small text-secondary lh-lg mb-4 ps-3">
                <li>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are started in the <strong>XAMPP Control Panel</strong>.</li>
                <li>Open your browser and navigate to: <a href="http://localhost/phpmyadmin/index.php" target="_blank" class="fw-bold text-primary">http://localhost/phpmyadmin/index.php</a></li>
                <li>Click the <strong>"SQL"</strong> tab in the top navigation bar.</li>
                <li>Open the file <strong class="text-dark">confed_borrowing.sql</strong> in your project folder, copy its entire contents, paste it into the box, and click <strong>"Go"</strong>.</li>
                <li>Once imported, <a href="index.php" class="fw-bold text-success">Click here to refresh this page</a>.</li>
              </ol>

              <div class="bg-light p-3 rounded-3 border small text-muted">
                <strong>Tip for Testing:</strong> If you wish to run the app without MySQL right now, open <code>db.php</code> line 18 and change <code>define(\'DB_TYPE\', \'mysql\');</code> to <code>define(\'DB_TYPE\', \'sqlite\');</code>.
              </div>
            </div>
          </div>
        </body>
        </html>');
    }
}

/**
 * Builds clean, unseeded schema if using SQLite fallback
 * (Only creates initial Administrator account; resources and bookings remain 100% empty)
 */
function init_clean_tables($pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        contact_number VARCHAR(50),
        role VARCHAR(20) NOT NULL DEFAULT 'staff',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Resources Table (Empty - Admin will input items)
    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(150) NOT NULL,
        model VARCHAR(100) DEFAULT 'Standard',
        category VARCHAR(50) NOT NULL,
        total_qty INTEGER NOT NULL DEFAULT 1,
        available_qty INTEGER NOT NULL DEFAULT 1,
        is_available INTEGER NOT NULL DEFAULT 1,
        location VARCHAR(100) DEFAULT 'Council Office Room 204',
        condition_status VARCHAR(50) DEFAULT 'Good Condition',
        fee_type VARCHAR(50) DEFAULT 'Free',
        fee_amount DECIMAL(10,2) DEFAULT 0.00,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Clients Table (Empty - Admin & Staff will input borrowers)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        contact_number VARCHAR(50) NOT NULL,
        organization_name VARCHAR(150) NOT NULL,
        role VARCHAR(50) DEFAULT 'Student',
        status VARCHAR(20) DEFAULT 'Active',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Bookings Table (Empty - Staff & Admin will record loans)
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_code VARCHAR(50) UNIQUE NOT NULL,
        client_id INTEGER NOT NULL,
        created_by_user_id INTEGER NULL,
        event_name VARCHAR(150) NOT NULL,
        event_location VARCHAR(150) NOT NULL,
        purpose TEXT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        actual_return_date DATE NULL,
        status VARCHAR(30) DEFAULT 'Approved',
        payment_status VARCHAR(30) DEFAULT 'Free / Waived',
        payment_amount DECIMAL(10,2) DEFAULT 0.00,
        payment_details TEXT,
        cancellation_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");

    // 5. Booking Items Junction Table (Empty)
    $pdo->exec("CREATE TABLE IF NOT EXISTS booking_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        resource_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE RESTRICT
    )");

    // Create Initial Admin User (admin / admin123)
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status) VALUES 
        ('admin', '{$adminPass}', 'Council Administrator', 'admin@csc.edu.ph', '09123456789', 'admin', 'active')
    ");
}

``

File: config/session.php
``php
<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current Logged-in User
$currentUser = isset($_SESSION['userID']) ? $_SESSION : null;
$isAdmin = ($currentUser && $currentUser['role'] === 'Council'); // 'Council' is the new admin

// Protect pages that require login (except login page)
$current_file = basename($_SERVER['PHP_SELF']);
if (!$currentUser && $current_file !== 'login.php' && $current_file !== 'auth_action.php') {
    header('Location: login.php');
    exit;
}

``

File: config/helpers.php
``php
<?php
/**
 * ID Generation Helper Function (University-Level Method)
 */
function generate_id($db, $table, $column, $prefix) {
    // Basic auto-increment logic for alphanumeric IDs
    $stmt = $db->query("SELECT $column FROM $table ORDER BY $column DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();
    if ($lastId) {
        // Strip prefix and increment the number
        $num = (int)str_replace($prefix, '', $lastId);
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    // Default if table is empty
    return $prefix . '001';
}

``

File: actions/auth_action.php
``php
<?php
require_once '../config/db.php';
require_once '../config/session.php';

$db = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $db->prepare("SELECT * FROM user WHERE userEmail = ? AND is_archived = 0 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['userPassword'])) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['full_name'] = $user['userFName'] . ' ' . $user['userLName'];
        $_SESSION['role'] = $user['userRole'];
        $_SESSION['email'] = $user['userEmail'];
        $_SESSION['contact_number'] = $user['userContactNo'];
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email or password.'];
        header('Location: ../login.php');
        exit;
    }
} elseif ($action === 'logout') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

``

File: actions/item_action.php
``php
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

``

File: actions/borrower_action.php
``php
<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'search_borrower') {
    header('Content-Type: application/json');
    $query = trim($_POST['query'] ?? '');
    $stmt = $db->prepare("SELECT * FROM borrower WHERE (brwFName LIKE ? OR brwLName LIKE ? OR CONCAT(brwFName, ' ', brwLName) LIKE ? OR brwStudentID LIKE ?) AND is_archived = 0 LIMIT 10");
    $like = "%$query%";
    $stmt->execute([$like, $like, $like, $like]);
    echo json_encode($stmt->fetchAll());
    exit;
} elseif ($action === 'save_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    $studentId = trim($_POST['brwStudentID'] ?? '');
    $fName = trim($_POST['brwFName'] ?? '');
    $lName = trim($_POST['brwLName'] ?? '');
    $college = trim($_POST['brwCollege'] ?? '');
    $org = trim($_POST['brwOrg'] ?? '');
    $contact = trim($_POST['brwContactNo'] ?? '');

    if (!empty($id) && $id !== 'NEW') {
        $db->prepare("UPDATE borrower SET brwStudentID=?, brwFName=?, brwLName=?, brwCollege=?, brwOrg=?, brwContactNo=? WHERE brwID=?")
           ->execute([$studentId, $fName, $lName, $college, $org, $contact, $id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower updated.'];
    } else {
        $newId = generate_id($db, 'borrower', 'brwID', 'BRW-');
        $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $studentId, $fName, $lName, $college, $org, $contact]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower added.'];
    }
    $_SESSION['active_tab'] = 'tab-borrowers';
    header('Location: ../index.php');
    exit;
} elseif ($action === 'archive_borrower') {
    $id = trim($_POST['brwID'] ?? '');
    $db->prepare("UPDATE borrower SET is_archived = 1 WHERE brwID = ?")->execute([$id]);
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Borrower archived.'];
    
    $_SESSION['active_tab'] = 'tab-borrowers';
    header('Location: ../index.php');
    exit;
}

``

File: actions/user_action.php
``php
<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'save_user' && $isAdmin) {
    $id = trim($_POST['userID'] ?? '');
    $fName = trim($_POST['userFName'] ?? '');
    $lName = trim($_POST['userLName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $contact = trim($_POST['userContactNo'] ?? '');
    $role = trim($_POST['userRole'] ?? 'Committee');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($id) && $id !== 'NEW') {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userRole=?, userPassword=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $role, $hash, $id]);
        } else {
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userRole=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $role, $id]);
        }
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User updated.'];
    } else {
        $newId = generate_id($db, 'user', 'userID', 'USR-');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO user (userID, userFName, userLName, userEmail, userContactNo, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$newId, $fName, $lName, $email, $contact, $role, $hash]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User created.'];
    }
    $_SESSION['active_tab'] = 'tab-users';
    header('Location: ../index.php');
    exit;

} elseif ($action === 'archive_user' && $isAdmin) {
    $id = trim($_POST['userID'] ?? '');
    if ($id === $currentUser['userID']) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Cannot archive yourself.'];
    } else {
        // Soft delete
        $db->prepare("UPDATE user SET is_archived = 1 WHERE userID = ?")->execute([$id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'User archived.'];
    }
    $_SESSION['active_tab'] = 'tab-users';
    header('Location: ../index.php');
    exit;
}

``

File: actions/profile_action.php
``php
<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$db = get_db();
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $uid = trim($_POST['userID'] ?? '');
    $fName = trim($_POST['userFName'] ?? '');
    $lName = trim($_POST['userLName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $contact = trim($_POST['userContactNo'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    // Ensure user can only update their own profile, unless Admin
    if ($uid === $currentUser['userID'] || $isAdmin) {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=?, userPassword=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $hash, $uid]);
        } else {
            $db->prepare("UPDATE user SET userFName=?, userLName=?, userEmail=?, userContactNo=? WHERE userID=?")
               ->execute([$fName, $lName, $email, $contact, $uid]);
        }
        if ($uid === $currentUser['userID']) {
            $_SESSION['full_name'] = $fName . ' ' . $lName;
        }
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
    }
    $_SESSION['active_tab'] = 'tab-profile';
    header('Location: ../index.php');
    exit;
}

``

File: actions/txn_action.php
``php
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

``

File: views/layout/header.php
``php
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .modal-body .form-label { font-size: 1.05rem; margin-bottom: 0.5rem; }
      @media print {
          @page { size: landscape; margin: 10mm; }
          body, html { overflow: visible !important; height: auto !important; min-height: auto !important; display: block !important; }
          .app-shell, .app-layout, .app-main { 
              display: block !important; 
              height: auto !important; 
              min-height: auto !important; 
              overflow: visible !important; 
              padding: 0 !important;
              margin: 0 !important;
          }
          .table-responsive { overflow: visible !important; }
          .no-print { display: none !important; }
          .content-card { margin: 0 !important; padding: 0 !important; overflow: visible !important; }
      }
      /* Sidebar Mobile Toggle CSS */
      .app-sidebar { transition: transform 0.3s ease; z-index: 1040; }
      @media (max-width: 991.98px) {
          .app-sidebar {
              position: fixed; top: 0; left: 0; height: 100vh; width: 270px;
              transform: translateX(-100%); background: #fff;
          }
          .app-sidebar.show-sidebar { transform: translateX(0); }
      }
      
      /* Mobile Login Responsive Layout */
      @media (max-width: 767.98px) {
          .login-left-panel {
              height: auto !important;
              padding: 1.25rem 1rem !important;
          }
          .login-logo {
              width: 40px !important;
              height: 40px !important;
          }
          .login-logo span {
              font-size: 7px !important;
              line-height: 1 !important;
          }
          .login-title-1 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; margin-right: 0.3rem !important; }
          .login-title-2 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; }
          .login-subtitle { font-size: 0.7rem !important; letter-spacing: 0.5px !important; margin-top: 0.3rem !important; }
          .login-logo-gap { gap: 0.75rem !important; margin-bottom: 0.75rem !important; }
          .login-text-container { padding: 0 !important; max-width: 100% !important; margin-left: 0 !important; }
          .login-right-panel { height: calc(100vh - 120px) !important; justify-content: flex-start !important; padding-top: 2rem !important; }
      }
  </style>
</head>
<body>

``

File: views/layout/topbar.php
``php
  <nav class="navbar navbar-expand-lg app-navbar sticky-top no-print">
    <div class="container-fluid px-0">
      <div class="d-flex align-items-center">
        <!-- Hamburger button for mobile sidebar -->
        <button class="btn btn-light border d-lg-none me-3" id="sidebarToggle">
           <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
          <div class="brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
          </div>
          <div class="d-none d-sm-block">
            <span class="fw-bold d-block text-dark lh-1 fs-5">Confederates Student Council</span>
            <small class="text-primary fw-semibold" style="font-size: 12px; letter-spacing: 0.3px;">Item Management & Lending System</small>
          </div>
        </a>
      </div>
      <div class="d-flex align-items-center gap-3 ms-auto">
        <!-- 
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold d-none d-md-inline-flex align-items-center gap-2" style="font-size: 12px; border-radius: 9999px;">
          <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span>
          System Online
        </span>
        -->
        <div class="dropdown">
          <button class="btn btn-light border dropdown-toggle px-3 py-2 rounded-pill d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="dropdown">
            <span class="badge bg-primary text-uppercase px-2 py-1" style="font-size: 10px;"><?= htmlspecialchars($currentUser['role']) ?></span>
            <span class="fw-semibold text-dark small"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
            <li><button type="button" class="dropdown-item small py-2 d-flex align-items-center gap-2" onclick="document.querySelector('.app-sidebar [data-bs-target=\'#tab-profile\']').click()" style="border:none; background:none; width:100%; text-align:left;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Profile Management</span>
            </button></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger py-2 d-flex align-items-center gap-2" href="actions/auth_action.php?action=logout">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign Out</span>
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

``

File: views/layout/sidebar.php
``php

      <aside class="app-sidebar no-print" id="sidebarMenu">
        <div class="sidebar-panel">
          <div class="d-flex justify-content-between align-items-center d-lg-none border-bottom pb-2 mb-3">
              <span class="fw-bold">Menu</span>
              <button class="btn-close" id="sidebarClose"></button>
          </div>
          
          <div class="officer-badge-box">
            <div class="officer-avatar">
              <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
              <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($currentUser['full_name']) ?></div>
              <span class="badge <?= $isAdmin ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info-emphasis' ?> text-uppercase px-2 py-0" style="font-size: 10px;">
                <?= $isAdmin ? 'Council' : 'Committee' ?>
              </span>
            </div>
          </div>

          <div class="text-uppercase small text-muted fw-bold mb-2 ps-1" style="font-size: 11px; letter-spacing: 0.05em;">Council Operations</div>
          
          <div class="d-flex flex-column nav" id="v-pills-tab" role="tablist">
            <div class="sidebar-item-group">
              <button class="nav-tab-btn <?= !$isReportActive ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-transactions">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <span>Transactions</span>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-return'); return false;">Returns</a>
              </div>
            </div>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-purchases">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Purchases</span>
              </div>
            </button>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-inventory">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Items</span>
              </div>
            </button>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-borrowers">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Borrower Management</span>
              </div>
            </button>
            <?php if ($isAdmin): ?>
              <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-users">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <span>User Management</span>
                </div>
              </button>
            <?php endif; ?>
            <div class="sidebar-item-group">
              <button class="nav-tab-btn <?= $isReportActive ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-reports">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  <span>Reports</span>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-unified'); return false;">Unified View</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-return'); return false;">Returns</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-purchase'); return false;">Purchases</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-inventory'); return false;">Items</a>
              </div>
            </div>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-profile">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Management</span>
              </div>
            </button>          </div>
        </div>
      </aside>

``
File: views/layout/footer.php
``php

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>

``

File: views/pages/transactions.php
``php

        <div class="tab-content">
          <!-- TRANSACTIONS TAB -->
          <div class="tab-pane fade <?= !$isReportActive ? 'show active' : '' ?>" id="tab-transactions">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Resource Transactions</h5>
                  <p class="text-muted small mb-0">View borrows, returns, and process checkout operations.</p>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary-action btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransaction">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>New Transaction</span>
                  </button>
                </div>
              </div>
              
              <ul class="nav folder-tabs px-4 pt-3" id="txnTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#txn-all">All</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-borrow">Borrowed</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-return">Returned</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="txn-all">
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <div class="btn-group" role="group">
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_all" autocomplete="off" checked onclick="filterTableByPill('#txnAllTable', 'All', 0)">
                          <label class="btn btn-outline-primary btn-sm rounded-start-pill px-3" for="btn_txn_all">All</label>
                        
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_res" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Reserved', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_res">Reserved</label>

                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_rel" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Released', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_rel">Released</label>
                        
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_ret" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Returned', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_ret">Returned</label>

                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_can" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Cancelled', 0)">
                          <label class="btn btn-outline-primary btn-sm rounded-end-pill px-3" for="btn_txn_can">Cancelled</label>
                      </div>
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnAllTable" placeholder="Search transactions..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnAllTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($unifiedTransactions as $t): ?>
                          <tr>
                            <td>
                                <?php if($t['retTransID'] || strcasecmp($t['brwTransStatus'], 'Returned') === 0): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php elseif(strcasecmp($t['brwTransStatus'], 'Reserved') === 0): ?>
                                    <span class="badge bg-warning text-dark">Reserved</span>
                                <?php elseif(strcasecmp($t['brwTransStatus'], 'Cancelled') === 0): ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Released</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $t['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $t['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['itemDesc']) ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $t['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></a></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-return', 'row-ret-<?= $t['retTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $t['retTransID'] ?></a></td>
                                <td><?= $t['return_qty'] ?></td>
                                <td><?= $t['retReturnedOnDate'] ?></td>
                                <td><?= htmlspecialchars($t['return_staff_f'].' '.$t['return_staff_l']) ?></td>
                            <?php else: ?>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-borrow">
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnBorrowTable" placeholder="Search borrowed items..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnBorrowTable">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($borrows as $b): ?>
                          <tr id="row-txn-<?= $b['brwTransID'] ?>">
                            <td><?= $b['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $b['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc']) ?></a></td>
                            <td><?= $b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $b['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-return">
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnReturnTable" placeholder="Search returned items..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnReturnTable">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($returns as $r): ?>
                          <tr id="row-ret-<?= $r['retTransID'] ?>">
                            <td><?= $r['retTransID'] ?></td>
                            <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-borrow', 'row-txn-<?= $r['brwTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $r['brwTransID'] ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $r['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['itemDesc']) ?></a></td>
                            <td><?= $r['brwTransQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $r['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></a></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>                </div>
              </div>
            </div>
          </div>

``

File: views/pages/purchases.php
``php


          <!-- PURCHASES TAB -->
          <div class="tab-pane fade" id="tab-purchases">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Purchases</h5>
                  <p class="text-muted small mb-0">Record and track inventory restocks.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#purchasesTable" placeholder="Search purchases..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalPurchase">Log Purchase</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="purchasesTable">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-pur-<?= $p['purTransID'] ?>">
                        <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $p['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc']) ?></a></td><td><?= $p['purQty'] ?></td>
                        <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>                </table>
              </div>
            </div>
          </div>

``

File: views/pages/inventory.php
``php
          <div class="tab-pane fade" id="tab-inventory">
            <div class="content-card">
              <div class="content-card-header border-bottom d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Items</h5>
                  <p class="text-muted small mb-0">Manage resources, stock, and fees.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#inventoryTable" placeholder="Search items..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalItem">Add Item</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="inventoryTable">
                  <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($items as $i): ?>
                      <tr id="row-item-<?= $i['itemID'] ?>">
                        <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                        <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                        <td>
                          <?php if($isAdmin): ?>
                          <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" style="font-size:12px;" onclick="editItem('<?= $i['itemID'] ?>', '<?= addslashes(htmlspecialchars($i['itemDesc'])) ?>', '<?= addslashes(htmlspecialchars($i['itemCategory'])) ?>', <?= $i['itemRate'] ?>)">Edit</button>
                          <form action="actions/item_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive item?');">
                            <input type="hidden" name="action" value="archive_item"><input type="hidden" name="itemID" value="<?= $i['itemID'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

``

File: views/pages/borrowers.php
``php
          <div class="tab-pane fade" id="tab-borrowers">
            <div class="content-card">
              <div class="content-card-header border-bottom d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Borrower Management</h5>
                  <p class="text-muted small mb-0">Manage student and organization profiles.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#borrowersTable" placeholder="Search borrowers..." style="max-width: 250px;">
                  <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalBorrower">Add Borrower</button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="borrowersTable">
                  <thead><tr><th>ID</th><th>Student ID</th><th>Name</th><th>College</th><th>Org</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($borrowers as $b): ?>
                      <tr id="row-brw-<?= $b['brwID'] ?>">
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= $b['brwID'] ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwStudentID']) ?></td>
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwCollege']) ?></td><td><?= htmlspecialchars($b['brwOrg']) ?></td><td><?= htmlspecialchars($b['brwContactNo']) ?></td>
                        <td>
                          <form action="actions/borrower_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
                            <input type="hidden" name="action" value="archive_borrower"><input type="hidden" name="brwID" value="<?= $b['brwID'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

``

File: views/pages/reports.php
``php

          <!-- REPORTS TAB -->
          <div class="tab-pane fade <?= $isReportActive ? 'show active' : '' ?>" id="tab-reports">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">System Reports</h5>
                  <p class="text-muted small mb-0">Generate and print transaction snapshots.</p>
                </div>
              </div>
              
              <div class="p-4 bg-light border-bottom no-print">
                <form method="GET" class="row g-3 align-items-end" id="reportFilterForm">
                  <div class="col-md-4">
                     <label class="form-label fw-bold small">Start Date</label>
                     <input type="date" class="form-control form-control-sm" name="start_date" id="repStartDate" value="<?= htmlspecialchars($repStartDate ?? '') ?>" onchange="document.getElementById('repEndDate').min=this.value; this.form.submit();">
                  </div>
                  <div class="col-md-4">
                     <label class="form-label fw-bold small">End Date</label>
                     <input type="date" class="form-control form-control-sm" name="end_date" id="repEndDate" value="<?= htmlspecialchars($repEndDate ?? '') ?>" min="<?= htmlspecialchars($repStartDate ?? '') ?>" onchange="this.form.submit();">
                  </div>
                  <div class="col-md-4">
                     <button type="button" class="btn btn-primary-action btn-sm w-100" onclick="window.print()">Print Report</button>
                  </div>
                </form>
              </div>

              <!-- Subtabs for Report Types -->
              <ul class="nav folder-tabs px-4 pt-3 no-print" id="repTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rep-unified">Unified View</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-borrow">Borrows</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-return">Returns</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-purchase">Purchases</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-inventory">Items</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="rep-unified">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($repUnified as $t): ?>
                          <tr>
                            <td>
                                <?php if($t['retTransID'] || strcasecmp($t['brwTransStatus'], 'Returned') === 0): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php elseif(strcasecmp($t['brwTransStatus'], 'Reserved') === 0): ?>
                                    <span class="badge bg-warning text-dark">Reserved</span>
                                <?php elseif(strcasecmp($t['brwTransStatus'], 'Cancelled') === 0): ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Released</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $t['brwTransID'] ?></td>
                            <td><?= htmlspecialchars($t['itemDesc']) ?></td>
                            <td><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><?= $t['retTransID'] ?></td>
                                <td><?= $t['return_qty'] ?></td>
                                <td><?= $t['retReturnedOnDate'] ?></td>
                                <td><?= htmlspecialchars($t['return_staff_f'].' '.$t['return_staff_l']) ?></td>
                            <?php else: ?>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="rep-borrow">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($repBorrows as $b): ?>
                          <tr>
                            <td><?= $b['brwTransID'] ?></td><td><?= htmlspecialchars($b['itemDesc']) ?></td><td><?= $b['brwTransItemQty'] ?></td>
                            <td><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="rep-return">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($repReturns as $r): ?>
                          <tr>
                            <td><?= $r['retTransID'] ?></td><td><?= $r['brwTransID'] ?></td>
                            <td><?= htmlspecialchars($r['itemDesc']) ?></td><td><?= $r['brwTransQty'] ?></td>
                            <td><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <div class="tab-pane fade" id="rep-purchase">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                      <tbody>
                        <?php foreach($repPurchases as $p): ?>
                          <tr>
                            <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                            <td><?= htmlspecialchars($p['itemDesc']) ?></td><td><?= $p['purQty'] ?></td>
                            <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <div class="tab-pane fade" id="rep-inventory">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th></tr></thead>
                      <tbody>
                        <?php foreach($repInventory as $i): ?>
                          <tr>
                            <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                            <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>                  </div>
                </div>
              </div>
            </div>
          </div>

``

File: views/pages/profile.php
``php
          <div class="tab-pane fade" id="tab-profile">
            <div class="content-card">
              <div class="content-card-header">
                <h5 class="fw-bold mb-1 text-dark">Profile Management</h5>
                <p class="text-muted small mb-0">Update your personal account details.</p>
              </div>
              <div class="p-4 bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <form action="actions/profile_action.php" method="POST" style="max-width: 600px;">
                  <input type="hidden" name="action" value="update_profile">
                  <input type="hidden" name="userID" value="<?= htmlspecialchars($profileUser['userID']) ?>">
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold small">First Name</label>
                      <input type="text" class="form-control" name="userFName" value="<?= htmlspecialchars($profileUser['userFName']) ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-bold small">Last Name</label>
                      <input type="text" class="form-control" name="userLName" value="<?= htmlspecialchars($profileUser['userLName']) ?>" required>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-bold small">Email Address</label>
                    <input type="email" class="form-control" name="userEmail" value="<?= htmlspecialchars($profileUser['userEmail']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-bold small">Contact No</label>
                    <input type="text" class="form-control" name="userContactNo" value="<?= htmlspecialchars($profileUser['userContactNo']) ?>" required>
                  </div>
                  <div class="mb-4">
                    <label class="form-label fw-bold small">New Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                  </div>
                  <button type="submit" class="btn btn-primary-action px-4 py-2">Save Changes</button>
                </form>
              </div>
            </div>
          </div>

``

File: views/pages/users.php
``php
          <?php if($isAdmin): ?>
          <div class="tab-pane fade" id="tab-users">
            <div class="content-card">
              <div class="content-card-header border-bottom-0 pb-0 d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">User Management</h5>
                  <p class="text-muted small mb-0">Manage Council and Committee member access.</p>
                </div>
                <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalUser">Add User</button>
              </div>
              <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom">
                  <div class="btn-group" role="group">
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_all" autocomplete="off" checked onclick="filterTableByPill('#usersTable', 'All', 2)">
                      <label class="btn btn-outline-primary btn-sm rounded-start-pill px-3" for="btn_user_all">All</label>
                    
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_council" autocomplete="off" onclick="filterTableByPill('#usersTable', 'Council', 2)">
                      <label class="btn btn-outline-primary btn-sm px-3" for="btn_user_council">Council</label>
                    
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_committee" autocomplete="off" onclick="filterTableByPill('#usersTable', 'Committee', 2)">
                      <label class="btn btn-outline-primary btn-sm rounded-end-pill px-3" for="btn_user_committee">Committee</label>
                  </div>
                  <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#usersTable" placeholder="Search users..." style="max-width: 250px;">
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="usersTable">
                  <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($users as $u): ?>
                      <tr>
                        <td><?= $u['userID'] ?></td><td><?= htmlspecialchars($u['userFName'].' '.$u['userLName']) ?></td>
                        <td><?= htmlspecialchars($u['userRole']) ?></td>
                        <td><a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($u['userEmail']) ?>" target="_blank"><?= htmlspecialchars($u['userEmail']) ?></a></td>
                        <td><?= htmlspecialchars($u['userContactNo']) ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;" onclick="editUser('<?= $u['userID'] ?>', '<?= addslashes($u['userFName']) ?>', '<?= addslashes($u['userLName']) ?>', '<?= addslashes($u['userEmail']) ?>', '<?= addslashes($u['userContactNo']) ?>', '<?= addslashes($u['userRole']) ?>')">Edit</button>
                          <form action="actions/user_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
                            <input type="hidden" name="action" value="archive_user"><input type="hidden" name="userID" value="<?= $u['userID'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>

``

File: views/modals/modal_txn.php
``php
  <!-- Modal: Add Transaction (Merged Borrow & Return) -->
  <div class="modal fade" id="modalTransaction" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content overflow-hidden border-0 shadow-lg bg-light">
        
        <!-- Segmented Control Subtabs Header -->
        <div class="pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="modal-title fw-bold text-dark m-0">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <ul class="nav folder-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#modal-tab-checkout" type="button" role="tab">Desk Checkout</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#modal-tab-return" type="button" role="tab">Process Return</button>
              </li>
            </ul>
        </div>

        <div class="tab-content px-4 pb-4">
            <!-- Subtab: Checkout -->
            <div class="tab-pane fade show active" id="modal-tab-checkout" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_borrow">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrower (Student ID)</label>
                      <div class="position-relative">
                        <input type="text" id="borrowerSearch" name="searchStudentID" class="form-control" placeholder="Search Student ID..." autocomplete="off">
                        <div id="borrowerResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      </div>
                      <!-- Hidden ID (empty if new borrower) -->
                      <input type="hidden" name="brwID" id="selectedBrwID">
                    </div>
                    
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddNewBorrower" onclick="enableNewBorrower()">+ Add New Borrower</button>
                    </div>

                    <div class="bg-light p-3 mb-4 rounded border">
                        <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Borrower Details</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="brwFName" id="bfName" class="form-control form-control-sm" placeholder="First Name" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwLName" id="blName" class="form-control form-control-sm" placeholder="Last Name" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwStudentID" id="bStudentID" class="form-control form-control-sm" placeholder="Student ID" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwContact" id="bContact" class="form-control form-control-sm" placeholder="Contact No." required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwCollege" id="bCollege" class="form-control form-control-sm" placeholder="College" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwDept" id="bDept" class="form-control form-control-sm" placeholder="Organization/Dept" required readonly>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Equipment to Borrow</h6>
                    <div id="borrowItemsContainer">
                        <div class="row borrow-item-row mb-2">
                          <div class="col-md-8">
                            <select name="itemID[]" class="form-select form-select-sm" required>
                              <option value="">Select Item...</option>
                              <?php foreach($items as $i): if($i['itemAvailableQty']>0): ?>
                                <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
                              <?php endif; endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" value="1" min="1" required>
                          </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" onclick="addBorrowItemRow()">+ Add Item</button>

                    <div class="row">
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Borrow Date</label><input type="date" name="brwTransBorrowOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Return By Date</label><input type="date" name="brwTransReturnByDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-0 rounded-bottom"><button type="submit" class="btn btn-primary-action py-2 px-4">Process Checkout</button></div>
                </form>
            </div>
            
            <!-- Subtab: Return -->
            <div class="tab-pane fade" id="modal-tab-return" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_return">
                  <div class="modal-body p-4">
                    <div class="mb-4">
                      <label class="form-label fw-bold">Search Borrower (Student ID)</label>
                      <div class="position-relative">
                        <input type="text" id="retBorrowerSearch" class="form-control" placeholder="Search Student ID..." autocomplete="off" required>
                        <div id="retBorrowerResults" class="list-group position-absolute w-100 shadow-sm mt-1" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                      </div>
                      <input type="hidden" name="brwID" id="retSelectedBrwID" required>
                    </div>
                    
                    <div id="retBorrowerPreview" class="alert border bg-light p-3 mb-4" style="display:none;">
                      <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                          <h6 class="fw-bold text-primary mb-1" id="retPvName"></h6>
                          <div class="small text-muted">Student ID: <span id="retPvStudentID" class="fw-semibold text-dark"></span></div>
                        </div>
                      </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Items to Return</h6>
                    
                    <!-- Header Labels -->
                    <div class="row mb-1 px-1">
                      <div class="col-md-5"><label class="form-label small fw-bold text-muted mb-0">Item Borrowed</label></div>
                      <div class="col-md-4"><label class="form-label small fw-bold text-muted mb-0">Date Borrowed</label></div>
                      <div class="col-md-3"><label class="form-label small fw-bold text-muted mb-0">Qty Returned</label></div>
                    </div>
                    
                    <div id="returnItemsContainer">
                        <!-- Cloned row goes here -->
                        <div class="row return-item-row mb-2">
                          <div class="col-md-5">
                            <select name="brwTransID[]" class="form-select form-select-sm active-borrows-select" onchange="updateReturnRow(this)" required>
                                <option value="">Search borrower first...</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <input type="date" class="form-control form-control-sm return-borrow-date" disabled>
                          </div>
                          <div class="col-md-3">
                            <input type="number" name="qty[]" class="form-control form-control-sm return-qty" placeholder="Ret Qty" value="1" min="1" required>
                          </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" onclick="addReturnItemRow()">+ Add Item</button>

                    <div class="row">
                      <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Returned On Date</label>
                        <input type="date" name="retReturnedOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-0 rounded-bottom"><button type="submit" class="btn btn-success py-2 px-4">Confirm Return</button></div>
                </form>
            </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    // Templates for adding rows dynamically
    const borrowRowTemplate = `
        <div class="row borrow-item-row mb-2">
          <div class="col-md-8">
            <select name="itemID[]" class="form-select form-select-sm" required>
              <option value="">Select Item...</option>
              <?php foreach($items as $i): if($i['itemAvailableQty']>0): ?>
                <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
              <?php endif; endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" value="1" min="1" required>
            <button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('.borrow-item-row').remove()">&times;</button>
          </div>
        </div>
    `;

    function addBorrowItemRow() {
        document.getElementById('borrowItemsContainer').insertAdjacentHTML('beforeend', borrowRowTemplate);
    }
    
    function addReturnItemRow() {
        // Clone the first row to preserve options if populated
        const firstRow = document.querySelector('.return-item-row');
        if (firstRow) {
            const clone = firstRow.cloneNode(true);
            // Add a remove button
            const col3 = clone.querySelector('.col-md-3');
            col3.classList.replace('col-md-3', 'col-md-3');
            col3.classList.add('d-flex', 'gap-2');
            
            // clear values
            clone.querySelector('.active-borrows-select').value = '';
            clone.querySelector('.return-borrow-date').value = '';
            clone.querySelector('.return-qty').value = '1';
            clone.querySelector('.return-qty').max = '';
            
            if(!clone.querySelector('.btn-danger')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-danger px-2';
                btn.innerHTML = '&times;';
                btn.onclick = function() { this.closest('.return-item-row').remove(); };
                col3.appendChild(btn);
            }
            
            document.getElementById('returnItemsContainer').appendChild(clone);
        }
    }
    
    function updateReturnRow(selectElement) {
        const row = selectElement.closest('.return-item-row');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        const dateInput = row.querySelector('.return-borrow-date');
        const qtyInput = row.querySelector('.return-qty');
        
        if (selectedOption && selectedOption.value) {
            dateInput.value = selectedOption.getAttribute('data-date');
            qtyInput.max = selectedOption.getAttribute('data-qty');
            qtyInput.value = selectedOption.getAttribute('data-qty');
        } else {
            dateInput.value = '';
            qtyInput.max = '';
            qtyInput.value = '1';
        }
    }

    function enableNewBorrower() {
        document.getElementById('selectedBrwID').value = '';
        ['bfName', 'blName', 'bStudentID', 'bContact', 'bCollege', 'bDept'].forEach(id => {
            const el = document.getElementById(id);
            el.removeAttribute('readonly');
            el.value = '';
        });
        const query = document.getElementById('borrowerSearch').value;
        if(query) document.getElementById('bStudentID').value = query;
    }
  </script>

``

File: views/modals/modal_item.php
``php
  <!-- Modal: Add/Edit Item -->
  <div class="modal fade" id="modalItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/item_action.php" method="POST">
          <input type="hidden" name="action" value="save_item">
          <input type="hidden" name="itemID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold" id="itemModalTitle">Add Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Description / Name</label><input type="text" name="itemDesc" class="form-control" required></div>
            <div class="mb-3">
                <label class="form-label fw-bold">Category</label>
                <select name="itemCategory" class="form-select" required>
                    <option value="Audio & Visual">Audio & Visual</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Event Supplies">Event Supplies</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Rate (Fee)</label><input type="number" step="0.01" name="itemRate" class="form-control" value="0" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4" id="itemModalBtn">Save Item</button></div>
        </form>
      </div>
    </div>
  </div>

``

File: views/modals/modal_borrower.php
``php
  <!-- Modal: Add Borrower -->
  <div class="modal fade" id="modalBorrower" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/borrower_action.php" method="POST">
          <input type="hidden" name="action" value="save_borrower">
          <input type="hidden" name="brwID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add Borrower Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Student ID</label><input type="text" name="brwStudentID" class="form-control" required></div>
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">First Name</label><input type="text" name="brwFName" class="form-control" required></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">Last Name</label><input type="text" name="brwLName" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">College</label><input type="text" name="brwCollege" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Organization</label><input type="text" name="brwOrg" class="form-control"></div>
            <div class="mb-3"><label class="form-label fw-bold">Contact No.</label><input type="text" name="brwContactNo" class="form-control"></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save Borrower</button></div>
        </form>
      </div>
    </div>
  </div>

``

File: views/modals/modal_user.php
``php
  <!-- Modal: Add User -->
  <div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/user_action.php" method="POST">
          <input type="hidden" name="action" value="save_user">
          <input type="hidden" name="userID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">First Name</label><input type="text" name="userFName" class="form-control" required></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">Last Name</label><input type="text" name="userLName" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="userEmail" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Contact No.</label><input type="text" name="userContactNo" class="form-control"></div>
            <div class="mb-3">
              <label class="form-label fw-bold">Role</label>
              <select name="userRole" class="form-select">
                <option value="Committee">Committee</option>
                <option value="Council">Council (Admin)</option>
              </select>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Password</label><input type="password" name="password" class="form-control" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save User</button></div>
        </form>
      </div>
    </div>
  </div>

``

File: views/modals/modal_purchase.php
``php
  <!-- Modal: Add Purchase -->
  <div class="modal fade" id="modalPurchase" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/purchase_action.php" method="POST">
          <input type="hidden" name="action" value="purchase_item">
          <div class="modal-header"><h5 class="modal-title fw-bold">Log Purchase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Equipment</label>
              <select name="itemID" class="form-select select2-init" required style="width:100%;">
                <?php foreach($items as $i): ?><option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">OR Number</label><input type="text" name="purORNo" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Quantity Bought</label><input type="number" name="purQty" class="form-control" value="1" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-success py-2 px-4">Log Purchase</button></div>
        </form>
      </div>
    </div>
  </div>

``

File: assets/css/style.css
``css
/* ==========================================================
   CONFEDERATES STUDENT COUNCIL - RESOURCE MANAGEMENT SYSTEM
   Modern, Spacious & User-Friendly Design System (style.css)
   ========================================================== */

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

:root {
  /* Brand Color Palette */
  --csc-primary: #1d4ed8;
  --csc-primary-hover: #1e40af;
  --csc-primary-light: #eff6ff;
  --csc-primary-subtle: #dbeafe;
  --csc-primary-border: #bfdbfe;

  --csc-dark: #0f172a;
  --csc-slate: #334155;
  --csc-muted: #64748b;
  --csc-light: #f8fafc;
  --csc-card-bg: #ffffff;
  --csc-border: #e2e8f0;
  --csc-border-subtle: #f1f5f9;

  /* Status Colors */
  --csc-success: #059669;
  --csc-success-bg: #ecfdf5;
  --csc-success-border: #a7f3d0;

  --csc-warning: #d97706;
  --csc-warning-bg: #fffbeb;
  --csc-warning-border: #fde68a;

  --csc-danger: #dc2626;
  --csc-danger-bg: #fef2f2;
  --csc-danger-border: #fecaca;

  --csc-info: #0284c7;
  --csc-info-bg: #f0f9ff;
  --csc-info-border: #bae6fd;

  --csc-purple: #7c3aed;
  --csc-purple-bg: #f5f3ff;
  --csc-purple-border: #ddd6fe;

  /* Shadows */
  --csc-shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --csc-shadow-sm: 0 1px 3px 0 rgba(15, 23, 42, 0.08), 0 1px 2px -1px rgba(15, 23, 42, 0.08);
  --csc-shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
  --csc-shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
  --csc-shadow-xl: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);

  /* Radius */
  --csc-radius-sm: 8px;
  --csc-radius-md: 12px;
  --csc-radius-lg: 16px;
  --csc-radius-xl: 20px;
}

/* Base Document Styles */
html {
  font-size: 15px;
  scroll-behavior: smooth;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  color: var(--csc-slate);
  /* Gray glassmorphism foundation */
  background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 50%, #cbd5e1 100%);
  background-attachment: fixed;
  line-height: 1.55;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Monospace text */
.font-monospace {
  font-family: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
}

/* ==========================================================
   APP SHELL & SPACIOUS LAYOUT
   ========================================================== */
.app-shell {
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  padding: 1.75rem 2rem 0 2rem;
  min-height: calc(100vh - 75px);
  display: flex;
  flex-direction: column;
}

.app-layout {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  width: 100%;
  flex: 1;
}

/* Sidebar with fixed spacious width */
.app-sidebar {
  width: 270px;
  min-width: 270px;
  flex-shrink: 0;
  z-index: 1040;
  position: sticky;
  top: 75px;
  height: calc(100vh - 75px);
  display: flex;
  flex-direction: column;
}

/* Main Area that flexes cleanly */
.app-main {
  flex: 1;
  min-width: 0;
  width: 100%;
}

@media (max-width: 1100px) {
  .app-layout {
    gap: 1.5rem;
  }

  .app-sidebar {
    width: 250px;
    min-width: 250px;
  }
}

@media (max-width: 991.98px) {
  .app-shell {
    padding: 1rem 1.25rem;
  }

  .app-layout {
    flex-direction: column;
    gap: 1.25rem;
  }

  .app-sidebar {
    width: 100%;
    min-width: 100%;
    position: static;
  }
}

/* ==========================================================
   TOP NAVBAR
   ========================================================== */
.app-navbar {
  background: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  z-index: 1050;
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
  padding: 0.75rem 2rem;
  transition: all 0.2s ease;
}

.brand-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #3b82f6 100%);
  color: #fff;
  border-radius: var(--csc-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
  transition: transform 0.2s ease;
}

.brand-icon:hover {
  transform: scale(1.04);
}

/* ==========================================================
   SIDEBAR NAVIGATION CARDS & TABS
   ========================================================== */
.sidebar-panel {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-bottom: none;
  border-radius: var(--csc-radius-lg) var(--csc-radius-lg) 0 0;
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
  padding: 1.35rem;
  flex: 1;
}

.officer-badge-box {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding-bottom: 1rem;
  margin-bottom: 1.15rem;
  border-bottom: 1px solid var(--csc-border-subtle);
}

.officer-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(30, 64, 175, 0.25);
}

.btn-primary-action {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff !important;
  border: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 0.75rem 1rem;
  border-radius: var(--csc-radius-md);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  transition: all 0.2s ease;
}

.btn-primary-action:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.nav-tab-btn {
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 0.35rem;
  border-radius: var(--csc-radius-md);
  color: #475569;
  font-weight: 600;
  font-size: 0.925rem;
  text-decoration: none;
  transition: all 0.18s ease-in-out;
  border: 1px solid transparent;
  background: transparent;
  width: 100%;
  text-align: left;
}

.nav-tab-btn .nav-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.nav-tab-btn svg {
  color: #64748b;
  transition: color 0.18s ease, transform 0.18s ease;
}

.nav-tab-btn:hover {
  color: var(--csc-primary);
  background-color: var(--csc-primary-light);
  border-color: var(--csc-primary-border);
}

.nav-tab-btn:hover svg {
  color: var(--csc-primary);
  transform: translateX(2px);
}

.nav-tab-btn.active {
  color: #1d4ed8;
  background: #eff6ff;
  border-color: #bfdbfe;
  font-weight: 700;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.08);
}

.nav-tab-btn.active svg {
  color: #1d4ed8;
}

.nav-count-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  background: #e2e8f0;
  color: #475569;
  transition: all 0.18s ease;
}

.nav-tab-btn.active .nav-count-badge {
  background: #dbeafe;
  color: #1d4ed8;
}

/* ==========================================================
   KPI STAT CARDS (SPACIOUS & ENGAGING)
   ========================================================== */
.kpi-card {
  background: var(--csc-card-bg);
  border: 1px solid var(--csc-border);
  border-radius: var(--csc-radius-lg);
  box-shadow: var(--csc-shadow-sm);
  padding: 1.35rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  transition: all 0.2s ease;
  height: 100%;
}

.kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--csc-shadow-lg);
  border-color: #cbd5e1;
}

.kpi-info-title {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 700;
  color: var(--csc-muted);
  margin-bottom: 0.25rem;
}

.kpi-info-value {
  font-size: 2.15rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--csc-dark);
  margin-bottom: 0.35rem;
  letter-spacing: -0.02em;
}

.kpi-info-sub {
  font-size: 0.825rem;
  color: var(--csc-muted);
}

.kpi-icon-halo {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.kpi-card:hover .kpi-icon-halo {
  transform: scale(1.08);
}

.kpi-icon-halo.blue {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.kpi-icon-halo.green {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.kpi-icon-halo.purple {
  background: #f5f3ff;
  color: #7c3aed;
  border: 1px solid #ddd6fe;
}

.kpi-icon-halo.amber {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}

/* ==========================================================
   CONTENT CARDS & SPACIOUS TABLES
   ========================================================== */
.content-card {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: var(--csc-radius-lg);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  margin-bottom: 2rem;
  transition: box-shadow 0.2s ease;
}

.content-card-header {
  background: #ffffff;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid var(--csc-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: gap;
  gap: 1rem;
}

/* Filter pills bar */
.filter-pills-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.75rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--csc-border);
  overflow-x: auto;
  white-space: nowrap;
}

.filter-pill {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.825rem;
  font-weight: 600;
  color: #64748b;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  transition: all 0.15s ease;
  user-select: none;
}

.filter-pill:hover {
  color: var(--csc-primary);
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.filter-pill.active {
  color: #ffffff;
  background: var(--csc-primary);
  border-color: var(--csc-primary);
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}

.filter-pill-badge {
  font-size: 0.75rem;
  padding: 0.1rem 0.45rem;
  border-radius: 9999px;
  background: rgba(0, 0, 0, 0.08);
}

.filter-pill.active .filter-pill-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* Spacious Tables */
.table-responsive {
  border-radius: 0;
  overflow-x: auto;
  overflow-y: auto;
  max-height: calc(100vh - 280px);
}

.table-custom {
  width: 100%;
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.table-custom th {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #f8fafc;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.25rem;
  border-bottom: 1.5px solid var(--csc-border);
  border-top: none;
  white-space: nowrap;
  vertical-align: middle;
}

.table-custom td {
  padding: 1.15rem 1.25rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--csc-border-subtle);
  font-size: 0.925rem;
  color: #334155;
  transition: background-color 0.15s ease;
}

.table-custom tr:hover td {
  background-color: #f8fafc;
}

.table-custom tr:last-child td {
  border-bottom: none;
}

/* Modern Status & Monospace Badges */
.badge-code {
  font-family: 'JetBrains Mono', monospace;
  font-weight: 600;
  font-size: 0.85rem;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #dbeafe;
  display: inline-block;
}

.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.2;
}

.badge-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
}

.badge-status.released {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.badge-status.released .badge-status-dot {
  background: #059669;
}

.badge-status.approved {
  background: #eff6ff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}

.badge-status.approved .badge-status-dot {
  background: #2563eb;
}

.badge-status.returned {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.badge-status.returned .badge-status-dot {
  background: #64748b;
}

.badge-status.pending {
  background: #fffbeb;
  color: #92400e;
  border: 1px solid #fde68a;
}

.badge-status.pending .badge-status-dot {
  background: #d97706;
}

.badge-status.cancelled {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.badge-status.cancelled .badge-status-dot {
  background: #dc2626;
}

/* Action button spacing in tables */
.table-action-group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: nowrap;
}

.btn-action {
  font-size: 0.825rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  border-radius: var(--csc-radius-sm);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-action-primary {
  background: #2563eb;
  color: #ffffff !important;
  border: 1px solid #2563eb;
}

.btn-action-primary:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
  transform: translateY(-1px);
}

.btn-action-secondary {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-action-secondary:hover {
  background: #f8fafc;
  color: #1e293b;
  border-color: #94a3b8;
}

.btn-action-success {
  background: #059669;
  color: #ffffff !important;
  border: 1px solid #059669;
}

.btn-action-success:hover {
  background: #047857;
  border-color: #047857;
}

/* ==========================================================
   MODALS: USER-FRIENDLY & COMFORTABLE
   ========================================================== */
.modal-content {
  border: 1px solid var(--csc-border) !important;
  border-radius: var(--csc-radius-xl) !important;
  box-shadow: var(--csc-shadow-xl) !important;
  padding: 1.5rem !important;
}

.modal-header {
  border-bottom: 1px solid var(--csc-border) !important;
  padding-bottom: 1rem !important;
  margin-bottom: 1.25rem !important;
}

.modal-footer {
  border-top: 1px solid var(--csc-border) !important;
  padding-top: 1rem !important;
  margin-top: 1.25rem !important;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.4rem;
}

.form-control,
.form-select {
  border-radius: var(--csc-radius-sm);
  border: 1.5px solid #cbd5e1;
  padding: 0.55rem 0.85rem;
  font-size: 0.925rem;
  color: #1e293b;
  transition: all 0.15s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-section-title {
  font-size: 0.825rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #1d4ed8;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

/* Search input with icon */
.search-box-wrapper {
  position: relative;
  min-width: 240px;
}

.search-box-wrapper input {
  padding-left: 2.35rem;
  border-radius: var(--csc-radius-md);
  font-size: 0.875rem;
}

.search-box-wrapper .search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

/* Print Styles for Gate Pass Receipt */
@media print {
  .no-print {
    display: none !important;
  }

  body {
    background: #fff !important;
    padding: 0 !important;
  }

  .receipt-box {
    box-shadow: none !important;
    border: 1px solid #000 !important;
    margin: 0 !important;
    max-width: 100% !important;
  }
}


/* ==========================================================
   FOLDER TABS
   ========================================================== */
.folder-tabs {
  display: flex;
  gap: 0.4rem;
  border-bottom: none;
  padding: 0 1rem;
  margin-bottom: 0;
  list-style: none;
}

.folder-tabs .nav-link {
  background-color: #f1f5f9;
  color: #64748b;
  border-radius: 12px 12px 0 0 !important;
  border: 1px solid #e2e8f0;
  border-bottom: none;
  font-weight: 600;
  padding: 0.6rem 1.5rem;
  margin-bottom: -1px;
  z-index: 2;
  transition: all 0.2s ease;
  position: relative;
  z-index: 1;
}

.folder-tabs .nav-link:hover {
  background-color: #e2e8f0;
  color: #1e40af;
}

.folder-tabs .nav-link.active {
  background-color: #ffffff !important;
  color: #1d4ed8 !important;
  border-color: #e2e8f0;
  border-bottom: 2px solid #ffffff !important;
  box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.03);
  z-index: 2;
}


/* ==========================================================
   SIDEBAR SUBMENUS
   ========================================================== */
.sidebar-item-group {
  position: relative;
  display: flex;
  flex-direction: column;
}

.sidebar-submenu {
  display: none;
  flex-direction: column;
  padding-left: 2.8rem;
  padding-right: 0.5rem;
  margin-top: -0.2rem;
  margin-bottom: 0.5rem;
}

.sidebar-item-group:hover .sidebar-submenu {
  display: flex;
  animation: fadeIn 0.2s ease;
}

.sidebar-item-group:hover .dropdown-icon {
  transform: rotate(90deg);
}

.dropdown-icon {
  transition: transform 0.2s ease;
  width: 14px;
  height: 14px;
  margin-left: auto;
  opacity: 0.6;
}

.sidebar-submenu a {
  text-decoration: none;
  color: #475569;
  padding: 0.45rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  transition: all 0.2s;
}

.sidebar-submenu a:hover {
  background: #e2e8f0;
  color: #1d4ed8;
}




/* Custom Row Highlight */
.table-custom tr.row-highlight td {
  background-color: #fef08a !important;
  transition: background-color 0.3s ease;
}
@media print {
    .no-print { display: none !important; }
    .app-sidebar { display: none !important; }
    .app-navbar { display: none !important; }
    .main-content { 
        margin-left: 0 !important; 
        width: 100% !important; 
        padding: 0 !important;
        background-color: white !important;
    }
    body, html {
        background-color: white !important;
    }
    .content-card {
        box-shadow: none !important;
        border: none !important;
    }
}

``

File: assets/js/app.js
``javascript
    $(document).ready(function() {
        // AJAX Search for Borrower Auto-population (Checkout)
        let searchTimeout;
        $('#borrowerSearch').on('input', function() {
            clearTimeout(searchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#borrowerResults').hide();
                return;
            }
            searchTimeout = setTimeout(function() {
                $.post('actions/borrower_action.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectBorrower('${b.brwID}', '${b.brwStudentID}', '${b.brwFName}', '${b.brwLName}', '${b.brwCollege}', '${b.brwOrg}', '${b.brwContactNo}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    if (data.length === 0) {
                        html = '<div class="list-group-item text-muted">No results found.</div>';
                    }
                    $('#borrowerResults').html(html).show();
                });
            }, 300);
        });

        // AJAX Search for Borrower (Return)
        let retSearchTimeout;
        $('#retBorrowerSearch').on('input', function() {
            clearTimeout(retSearchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#retBorrowerResults').hide();
                return;
            }
            retSearchTimeout = setTimeout(function() {
                $.post('actions/borrower_action.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectRetBorrower('${b.brwID}', '${b.brwStudentID}', '${b.brwFName}', '${b.brwLName}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    if (data.length === 0) {
                        html = '<div class="list-group-item text-muted">No results found.</div>';
                    }
                    $('#retBorrowerResults').html(html).show();
                });
            }, 300);
        });
        
        // Setup Table Search Filter
        $('.table-search').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            let targetTable = $(this).data('target');
            $(targetTable + ' tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Setup Select2 for Item Modal Category
        if ($('.select2-init').length > 0) {
            $('.select2-init').select2({
                tags: true,
                dropdownParent: $('#modalItem')
            });
        }
    });

    // Helper function to handle checkout borrower selection
    function selectBorrower(id, studentId, fname, lname, college, dept, contact) {
        $('#selectedBrwID').val(id);
        $('#borrowerSearch').val(studentId);
        $('#bStudentID').val(studentId).attr('readonly', true);
        $('#bfName').val(fname).attr('readonly', true);
        $('#blName').val(lname).attr('readonly', true);
        $('#bCollege').val(college).attr('readonly', true);
        $('#bDept').val(dept).attr('readonly', true);
        $('#bContact').val(contact).attr('readonly', true);
        $('#borrowerResults').hide();
    }
    
    // Helper function to handle return borrower selection
    function selectRetBorrower(id, studentId, fname, lname) {
        $('#retSelectedBrwID').val(id);
        $('#retBorrowerSearch').val(studentId);
        $('#retPvName').text(fname + ' ' + lname);
        $('#retPvStudentID').text(studentId);
        $('#retBorrowerPreview').show();
        $('#retBorrowerResults').hide();
        
        // Fetch active borrows
        $.post('actions/txn_action.php', { action: 'search_active_borrows', brwID: id }, function(data) {
            let options = '<option value="">Select borrowed item...</option>';
            data.forEach(function(item) {
                let remaining = item.brwTransItemQty - item.returned_qty;
                options += `<option value="${item.brwTransID}" data-date="${item.brwTransBorrowOnDate}" data-qty="${remaining}">
                              ${item.itemDesc} (Txn: ${item.brwTransID}) - ${remaining} unreturned
                            </option>`;
            });
            // Update all active-borrows-select in case there are multiple
            $('.active-borrows-select').html(options);
        });
    }
    
    // Table Filter by Pill
    function filterTableByPill(tableId, filterText, colIndex) {
        if(filterText === 'All') {
            $(tableId + ' tbody tr').show();
        } else {
            $(tableId + ' tbody tr').each(function() {
                let cellText = $(this).find('td').eq(colIndex).text();
                $(this).toggle(cellText.indexOf(filterText) > -1);
            });
        }
    }

    // Open Subtab from Sidebar
    function openSubtab(parentTabId, subTabId) {
        const parentBtn = document.querySelector(`[data-bs-target="${parentTabId}"]`);
        if (parentBtn) {
            new bootstrap.Tab(parentBtn).show();
        }
        setTimeout(() => {
            const childBtn = document.querySelector(`[data-bs-target="${subTabId}"]`);
            if (childBtn) {
                new bootstrap.Tab(childBtn).show();
            }
        }, 50);
    }
    
    // Cross-tab link highlighting
    function switchTabAndHighlight(tabId, rowId) {
        const tabBtn = document.querySelector(`[data-bs-target="${tabId}"]`);
        if (tabBtn) new bootstrap.Tab(tabBtn).show();
        
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 2500);
            }
        }, 150);
    }
    
    function openSubtabAndHighlight(parentTabId, subTabId, rowId) {
        openSubtab(parentTabId, subTabId);
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 2500);
            }
        }, 200);
    }

    // User Edit Helper
    function editUser(id, fname, lname, email, contact, role) {
        document.querySelector('#modalUser input[name="userID"]').value = id;
        document.querySelector('#modalUser input[name="userFName"]').value = fname;
        document.querySelector('#modalUser input[name="userLName"]').value = lname;
        document.querySelector('#modalUser input[name="userEmail"]').value = email;
        document.querySelector('#modalUser input[name="userContactNo"]').value = contact;
        document.querySelector('#modalUser select[name="userRole"]').value = role;
        
        document.querySelector('#modalUser input[name="password"]').required = false;
        document.querySelector('#modalUser input[name="password"]').placeholder = "Leave blank to keep unchanged";
        
        var modal = new bootstrap.Modal(document.getElementById('modalUser'));
        modal.show();
    }
    
    // Reset modalUser when hidden
    document.getElementById('modalUser')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="userID"]').value = "NEW";
        this.querySelector('input[name="password"]').required = true;
        this.querySelector('input[name="password"]').placeholder = "";
    });

    // Item Edit Helper
    function editItem(id, desc, category, rate) {
        document.querySelector('#modalItem input[name="itemID"]').value = id;
        document.querySelector('#modalItem input[name="itemDesc"]').value = desc;
        document.querySelector('#modalItem select[name="itemCategory"]').value = category;
        document.querySelector('#modalItem input[name="itemRate"]').value = rate;
        
        document.getElementById('itemModalTitle').textContent = 'Edit Item';
        document.getElementById('itemModalBtn').textContent = 'Save Changes';
        
        var modal = new bootstrap.Modal(document.getElementById('modalItem'));
        modal.show();
    }

    // Reset modalItem when hidden
    document.getElementById('modalItem')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="itemID"]').value = 'NEW';
        document.getElementById('itemModalTitle').textContent = 'Add Item';
        document.getElementById('itemModalBtn').textContent = 'Save Item';
    });


``

