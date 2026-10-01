<?php
/**
 * ==========================================================
 * CONFEDERATES STUDENT COUNCIL - RESOURCE MANAGEMENT SYSTEM
 * Main Application Controller & View (index.php) - REFACTORED
 * ==========================================================
 */

require_once __DIR__ . '/db.php';
$db = get_db();

$alert = ['type' => '', 'message' => ''];

// Current Logged-in User
$currentUser = isset($_SESSION['userID']) ? $_SESSION : null;
$isAdmin = ($currentUser && $currentUser['role'] === 'Council'); // 'Council' is the new admin

// ID Generation Helper Function (University-Level Method)
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

// ==========================================================
// 1. BACK-END ACTION HANDLERS (POST REQUESTS)
// ==========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- A. AUTHENTICATION: LOGIN ---
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Query user by email, ensure they are not archived
        $stmt = $db->prepare("SELECT * FROM user WHERE userEmail = ? AND is_archived = 0 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify bcrypt password hash
        if ($user && password_verify($password, $user['userPassword'])) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['full_name'] = $user['userFName'] . ' ' . $user['userLName'];
            $_SESSION['role'] = $user['userRole'];
            $_SESSION['email'] = $user['userEmail'];
            $_SESSION['contact_number'] = $user['userContactNo'];
            header('Location: index.php');
            exit;
        } else {
            $alert = ['type' => 'danger', 'message' => 'Invalid email or password.'];
        }
    }

    // --- B. AJAX SEARCH: BORROWER ---
    elseif ($action === 'search_borrower') {
        header('Content-Type: application/json');
        $query = trim($_POST['query'] ?? '');
        // Search by first name, last name, or student ID
        $stmt = $db->prepare("SELECT * FROM borrower WHERE (brwFName LIKE ? OR brwLName LIKE ? OR CONCAT(brwFName, ' ', brwLName) LIKE ? OR brwStudentID LIKE ?) AND is_archived = 0 LIMIT 10");
        $like = "%$query%";
        $stmt->execute([$like, $like, $like, $like]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // --- B.5 AJAX SEARCH: TRANSACTION ---
    elseif ($action === 'search_transaction') {
        header('Content-Type: application/json');
        $query = trim($_POST['query'] ?? '');
        $stmt = $db->prepare("SELECT b.*, i.itemDesc, br.brwFName, br.brwLName FROM borrow_transaction b JOIN item i ON b.itemID = i.itemID JOIN borrower br ON b.brwID = br.brwID WHERE b.brwTransID LIKE ? OR CONCAT(br.brwFName, ' ', br.brwLName) LIKE ? OR br.brwStudentID LIKE ? LIMIT 10");
        $like = "%$query%";
        $stmt->execute([$like, $like, $like]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // --- C. ITEM CRUD: SAVE (ADD / EDIT) ---
    elseif ($action === 'save_item') {
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied.'];
        } else {
            $id = trim($_POST['itemID'] ?? '');
            $desc = trim($_POST['itemDesc'] ?? '');
            $category = trim($_POST['itemCategory'] ?? 'Audio & Visual');
            $rate = (float)($_POST['itemRate'] ?? 0);

            if (!empty($id) && $id !== 'NEW') {
                $stmt = $db->prepare("UPDATE item SET itemDesc = ?, itemCategory = ?, itemRate = ? WHERE itemID = ?");
                $stmt->execute([$desc, $category, $rate, $id]);
                $alert = ['type' => 'success', 'message' => "Item updated."];
            } else {
                $newId = generate_id($db, 'item', 'itemID', 'ITM-');
                $stmt = $db->prepare("INSERT INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate) VALUES (?, ?, ?, 0, 0, ?)");
                $stmt->execute([$newId, $desc, $category, $rate]);
                $alert = ['type' => 'success', 'message' => "New item added."];
            }
        }
    }

    // --- D. ITEM CRUD: ARCHIVE ---
    elseif ($action === 'archive_item') {
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied.'];
        } else {
            $id = trim($_POST['itemID'] ?? '');
            // Soft delete
            $db->prepare("UPDATE item SET is_archived = 1 WHERE itemID = ?")->execute([$id]);
            $alert = ['type' => 'success', 'message' => 'Item archived successfully.'];
        }
    }

    // --- E. TRANSACTION: PURCHASE (RESTOCK) ---
    elseif ($action === 'purchase_item') {
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied.'];
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
            
            $alert = ['type' => 'success', 'message' => 'Purchase logged and inventory restocked.'];
        }
    }

    // --- F. BORROWER CRUD: SAVE ---
    elseif ($action === 'save_borrower') {
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
            $alert = ['type' => 'success', 'message' => 'Borrower updated.'];
        } else {
            $newId = generate_id($db, 'borrower', 'brwID', 'BRW-');
            $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$newId, $studentId, $fName, $lName, $college, $org, $contact]);
            $alert = ['type' => 'success', 'message' => 'Borrower added.'];
        }
    }

    // --- G. BORROWER CRUD: ARCHIVE ---
    elseif ($action === 'archive_borrower') {
        $id = trim($_POST['brwID'] ?? '');
        // Soft delete
        $db->prepare("UPDATE borrower SET is_archived = 1 WHERE brwID = ?")->execute([$id]);
        $alert = ['type' => 'success', 'message' => 'Borrower archived.'];
    }

    // --- H. TRANSACTION: DESK CHECKOUT (BORROW) ---
    elseif ($action === 'create_borrow') {
        $brwID = trim($_POST['brwID'] ?? '');
        $itemID = trim($_POST['itemID'] ?? '');
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $borrowOnDate = trim($_POST['brwTransBorrowOnDate'] ?? date('Y-m-d'));
        $returnByDate = trim($_POST['brwTransReturnByDate'] ?? date('Y-m-d'));
        $payStat = trim($_POST['brwTransPayStat'] ?? 'Free');

        // Verify stock is sufficient before proceeding
        $item = $db->query("SELECT itemRate, itemAvailableQty FROM item WHERE itemID = '$itemID'")->fetch();
        if ($item['itemAvailableQty'] < $qty) {
            $alert = ['type' => 'danger', 'message' => 'Not enough stock available.'];
        } else {
            $transID = generate_id($db, 'borrow_transaction', 'brwTransID', 'TXN-');
            $total = $item['itemRate'] * $qty;
            
            // Record Borrow Ledger Entry
            $stmt = $db->prepare("INSERT INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$transID, $itemID, $currentUser['userID'], $brwID, $qty, $item['itemRate'], date('Y-m-d'), $borrowOnDate, $returnByDate, $payStat, $total]);
            
            // Deduct available stock
            $db->prepare("UPDATE item SET itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?")->execute([$qty, $itemID]);
            $alert = ['type' => 'success', 'message' => "Checkout successful. Transaction ID: $transID"];
        }
    }

    // --- I. TRANSACTION: RETURN ---
    elseif ($action === 'create_return') {
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
        $alert = ['type' => 'success', 'message' => "Return processed successfully."];
    }

    // --- J. USER MANAGEMENT (SAVE) ---
    elseif ($action === 'save_user' && $isAdmin) {
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
            $alert = ['type' => 'success', 'message' => 'User updated.'];
        } else {
            $newId = generate_id($db, 'user', 'userID', 'USR-');
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO user (userID, userFName, userLName, userEmail, userContactNo, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$newId, $fName, $lName, $email, $contact, $role, $hash]);
            $alert = ['type' => 'success', 'message' => 'User created.'];
        }
    }
    
    // --- K. USER MANAGEMENT: ARCHIVE ---
    elseif ($action === 'archive_user' && $isAdmin) {
        $id = trim($_POST['userID'] ?? '');
        if ($id === $currentUser['userID']) {
            $alert = ['type' => 'danger', 'message' => 'Cannot archive yourself.'];
        } else {
            // Soft delete
            $db->prepare("UPDATE user SET is_archived = 1 WHERE userID = ?")->execute([$id]);
            $alert = ['type' => 'success', 'message' => 'User archived.'];
        }
    }
    
    // --- L. PROFILE UPDATE ---
    elseif ($action === 'update_profile') {
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
                $_SESSION['user']['full_name'] = $fName . ' ' . $lName;
                $currentUser['full_name'] = $fName . ' ' . $lName;
            }
            $alert = ['type' => 'success', 'message' => 'Profile updated successfully.'];
        }
    }
}

// Logout handler
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ==========================================================
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
$repPeriod = $_GET['period'] ?? 'all';
$repDate = $_GET['date_val'] ?? '';
$repMonth = $_GET['month_val'] ?? '';
$repYear = $_GET['year_val'] ?? date('Y');
$repBrw = trim($_GET['brw_val'] ?? '');

$repWhereB = "1=1";
$repWhereR = "1=1";
$repWhereP = "1=1";

if ($repBrw !== '') {
    $repWhereB .= " AND b.brwID = " . $db->quote($repBrw);
    $repWhereR .= " AND r.brwID = " . $db->quote($repBrw);
    $repWhereP .= " AND 1=0"; // Purchases don't map to a borrower
}

if ($repPeriod === 'day' && !empty($repDate)) {
    $repWhereB .= " AND b.brwTransDate = " . $db->quote($repDate);
    $repWhereR .= " AND r.retReturnedOnDate = " . $db->quote($repDate);
    $repWhereP .= " AND p.purDate = " . $db->quote($repDate);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
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

<?php if (!$currentUser): ?>
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
          <form method="POST" action="index.php">
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

<?php else: ?>
  <!-- ==========================================================
       AUTHENTICATED WORKSPACE (REVERTED TO CUSTOM THEME)
       ========================================================== -->
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
            <li><button class="dropdown-item small py-2 d-flex align-items-center gap-2 nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-profile" style="border:none; background:none; width:100%; text-align:left;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Profile Management</span>
            </button></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger py-2 d-flex align-items-center gap-2" href="index.php?action=logout">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign Out</span>
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <?php if (!empty($alert['message'])): ?>
    <div class="app-shell pb-0 no-print">
      <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <strong>Notification:</strong> <?= htmlspecialchars($alert['message']) ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    </div>
  <?php endif; ?>

  <div class="app-shell">
    <div class="app-layout">
      
      <!-- LEFT SIDEBAR -->
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

          <button class="btn btn-primary-action w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalTransaction">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Add Transaction</span>
          </button>

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
                <span>Inventory</span>
              </div>
            </button>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-borrowers">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Borrower Directory</span>
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
                  <span>Report Generation</span>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-unified'); return false;">Unified View</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-return'); return false;">Returns</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-purchase'); return false;">Purchases</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-inventory'); return false;">Inventory</a>
              </div>
            </div>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-profile">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Management</span>
              </div>
            </button>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
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
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#txn-all">Unified Log</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-borrow">Borrow Ledger</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-return">Return Ledger</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="txn-all">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($unifiedTransactions as $t): ?>
                          <tr>
                            <td>
                                <?php if($t['retTransID']): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Free') === 0 || $t['itemRate'] == 0): ?>
                                    <span class="badge bg-secondary">Free</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Paid') === 0): ?>
                                    <span class="badge bg-info text-dark">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Paid</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $t['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $t['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['itemDesc']) ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $t['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></a></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-return', 'row-<?= $t['retTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $t['retTransID'] ?></a></td>
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
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($borrows as $b): ?>
                          <tr id="row-<?= $b['brwTransID'] ?>">
                            <td><?= $b['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $b['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc']) ?></a></td>
                            <td><?= $b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $b['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-return">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($returns as $r): ?>
                          <tr id="row-<?= $r['retTransID'] ?>">
                            <td><?= $r['retTransID'] ?></td>
                            <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-borrow', 'row-<?= $r['brwTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $r['brwTransID'] ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $r['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['itemDesc']) ?></a></td>
                            <td><?= $r['brwTransQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $r['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></a></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- REPORTS TAB -->
          <div class="tab-pane fade <?= $isReportActive ? 'show active' : '' ?>" id="tab-reports">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">System Reports</h5>
                  <p class="text-muted small mb-0">Generate and print transaction snapshots.</p>
                </div>
                <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">Print Report</button>
              </div>
              
              <div class="p-4 bg-light border-bottom no-print">
                <form method="GET" class="row g-3 align-items-end" id="reportFilterForm">
                  <div class="col-md-3">
                     <label class="form-label fw-bold small">Period Type</label>
                     <select class="form-select form-select-sm" id="repPeriod" name="period" onchange="togglePeriodInputs()">
                        <option value="all" <?= $repPeriod=='all'?'selected':'' ?>>All Time</option>
                        <option value="day" <?= $repPeriod=='day'?'selected':'' ?>>Specific Day</option>
                        <option value="month" <?= $repPeriod=='month'?'selected':'' ?>>Specific Month</option>
                        <option value="year" <?= $repPeriod=='year'?'selected':'' ?>>Specific Year</option>
                     </select>
                  </div>
                  <div class="col-md-3" id="repDayContainer" style="display:<?= $repPeriod=='day'?'block':'none' ?>;">
                     <label class="form-label fw-bold small">Select Date</label>
                     <input type="date" class="form-control form-control-sm" name="date_val" value="<?= htmlspecialchars($repDate) ?>">
                  </div>
                  <div class="col-md-3" id="repMonthContainer" style="display:<?= $repPeriod=='month'?'block':'none' ?>;">
                     <label class="form-label fw-bold small">Select Month</label>
                     <input type="month" class="form-control form-control-sm" name="month_val" value="<?= htmlspecialchars($repMonth) ?>">
                  </div>
                  <div class="col-md-3" id="repYearContainer" style="display:<?= $repPeriod=='year'?'block':'none' ?>;">
                     <label class="form-label fw-bold small">Select Year</label>
                     <input type="number" class="form-control form-control-sm" name="year_val" min="2000" max="2100" value="<?= htmlspecialchars($repYear) ?>">
                  </div>
                  <div class="col-md-3">
                     <label class="form-label fw-bold small">Borrower ID</label>
                     <input type="text" class="form-control form-control-sm" name="brw_val" placeholder="Leave blank for all" value="<?= htmlspecialchars($repBrw) ?>">
                  </div>
                  <div class="col-md-3">
                     <button type="submit" class="btn btn-primary-action btn-sm w-100">Generate Snapshot</button>
                  </div>
                </form>
              </div>

              <!-- Subtabs for Report Types -->
              <ul class="nav folder-tabs px-4 pt-3 no-print" id="repTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rep-unified">Unified View</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-borrow">Borrows</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-return">Returns</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-purchase">Purchases</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-inventory">Inventory</button></li>
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
                                <?php if($t['retTransID']): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Free') === 0 || $t['itemRate'] == 0): ?>
                                    <span class="badge bg-secondary">Free</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Paid') === 0): ?>
                                    <span class="badge bg-info text-dark">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Paid</span>
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
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PURCHASES TAB -->
          <div class="tab-pane fade" id="tab-purchases">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Purchases</h5>
                  <p class="text-muted small mb-0">Record and track inventory restocks.</p>
                </div>
                <?php if($isAdmin): ?>
                  <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalPurchase">Log Purchase</button>
                <?php endif; ?>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-<?= $p['purTransID'] ?>">
                        <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $p['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc']) ?></a></td><td><?= $p['purQty'] ?></td>
                        <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- INVENTORY TAB -->
          <div class="tab-pane fade" id="tab-inventory">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Equipment Inventory</h5>
                  <p class="text-muted small mb-0">Manage resources, stock, and fees.</p>
                </div>
                <?php if($isAdmin): ?>
                  <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalItem">Add Item</button>
                <?php endif; ?>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($items as $i): ?>
                      <tr id="row-<?= $i['itemID'] ?>">
                        <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                        <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                        <td>
                          <?php if($isAdmin): ?>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Archive item?');">
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

          <!-- BORROWERS TAB -->
          <div class="tab-pane fade" id="tab-borrowers">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Borrower Directory</h5>
                  <p class="text-muted small mb-0">Manage student and organization profiles.</p>
                </div>
                <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalBorrower">Add Borrower</button>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>ID</th><th>Student ID</th><th>Name</th><th>College</th><th>Org</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($borrowers as $b): ?>
                      <tr id="row-<?= $b['brwID'] ?>">
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= $b['brwID'] ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwStudentID']) ?></td>
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwCollege']) ?></td><td><?= htmlspecialchars($b['brwOrg']) ?></td><td><?= htmlspecialchars($b['brwContactNo']) ?></td>
                        <td>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
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

          <!-- USER MANAGEMENT TAB -->
          <?php if($isAdmin): ?>
          <div class="tab-pane fade" id="tab-users">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">User Management</h5>
                  <p class="text-muted small mb-0">Manage Council and Committee member access.</p>
                </div>
                <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser">Add User</button>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
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
                          <form method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
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

          <!-- PROFILE TAB -->
          <div class="tab-pane fade" id="tab-profile">
            <div class="content-card">
              <div class="content-card-header">
                <h5 class="fw-bold mb-1 text-dark">Profile Management</h5>
                <p class="text-muted small mb-0">Update your personal account details.</p>
              </div>
              <div class="p-4 bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <form method="POST" style="max-width: 600px;">
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
        </div>
      </main>
    </div>
  </div>

  <!-- MODALS -->
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
                <form method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_borrow">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrower (Name or ID)</label>
                      <div class="position-relative">
                        <input type="text" id="borrowerSearch" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <div id="borrowerResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      </div>
                      <input type="hidden" name="brwID" id="selectedBrwID" required>
                    </div>
                    <div class="row bg-light p-3 mb-4 rounded border mx-0" id="borrowerPreview" style="display:none;">
                        <div class="col-6 small"><strong>Name:</strong> <span id="pvName"></span></div>
                        <div class="col-6 small"><strong>College/Org:</strong> <span id="pvOrg"></span></div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Equipment</label>
                        <select name="itemID" class="form-select select2-init" required style="width:100%;">
                          <option value="">Select Item...</option>
                          <?php foreach($items as $i): if($i['itemAvailableQty']>0): ?>
                            <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
                          <?php endif; endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4 mb-3"><label class="form-label fw-bold">Quantity</label><input type="number" name="qty" class="form-control" value="1" min="1" required></div>
                    </div>
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
                <form method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_return">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrow Transaction ID</label>
                      <div class="position-relative">
                        <input type="text" id="transactionSearch" name="brwTransID" class="form-control" placeholder="Type TXN-..." autocomplete="off" required>
                        <div id="transactionResults" class="list-group position-absolute w-100 shadow-sm mt-1" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                      </div>
                    </div>
                    
                    <div id="transactionPreview" class="alert border bg-light p-3 mb-3" style="display:none;">
                      <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                          <h6 class="fw-bold text-primary mb-1" id="ptTxnID"></h6>
                          <div class="small text-muted mb-1">Item: <span id="ptItemDesc" class="fw-semibold text-dark"></span></div>
                          <div class="small text-muted">Borrower: <span id="ptBorrower" class="fw-semibold text-dark"></span></div>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Item ID</label>
                        <input type="text" id="retItemID" name="itemID" class="form-control" placeholder="Auto-filled" readonly required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Borrower ID</label>
                        <input type="text" id="retBrwID" name="brwID" class="form-control" placeholder="Auto-filled" readonly required>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Quantity Returned</label>
                        <input type="number" id="retQty" name="qty" class="form-control" value="1" min="1" required>
                      </div>
                      <div class="col-md-6 mb-3">
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
  
  <!-- Modal: Add Purchase -->
  <div class="modal fade" id="modalPurchase" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
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

  <!-- Modal: Add/Edit Item -->
  <div class="modal fade" id="modalItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="save_item">
          <input type="hidden" name="itemID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add Equipment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Description / Name</label><input type="text" name="itemDesc" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Category</label><input type="text" name="itemCategory" class="form-control" value="Audio & Visual" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Rate (Fee)</label><input type="number" step="0.01" name="itemRate" class="form-control" value="0" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save Item</button></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Add Borrower -->
  <div class="modal fade" id="modalBorrower" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
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

  <!-- Modal: Add User -->
  <div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {
        // Initialize Select2 dropdown inside bootstrap modals
        $('.select2-init').select2({ dropdownParent: $('.modal') });
        
        // Mobile Sidebar Toggle Logic
        $('#sidebarToggle').click(function() {
            $('#sidebarMenu').addClass('show-sidebar');
        });
        $('#sidebarClose').click(function() {
            $('#sidebarMenu').removeClass('show-sidebar');
        });
        
        // Handle Sidebar Tab switching to mimic the old custom active state logic
        $('.nav-tab-btn').click(function() {
           $('.nav-tab-btn').removeClass('active');
           $(this).addClass('active');
        });

        // AJAX Search for Borrower Auto-population
        let searchTimeout;
        $('#borrowerSearch').on('input', function() {
            clearTimeout(searchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#borrowerResults').hide();
                return;
            }
            // Delay request to prevent spam
            searchTimeout = setTimeout(function() {
                $.post('index.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectBorrower('${b.brwID}', '${b.brwFName} ${b.brwLName}', '${b.brwCollege} / ${b.brwOrg}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    $('#borrowerResults').html(html).show();
                });
            }, 300);
        });
    });

    // Helper function to handle borrower selection
    function selectBorrower(id, name, org) {
        $('#selectedBrwID').val(id);
        $('#borrowerSearch').val(name);
        $('#pvName').text(name);
        $('#pvOrg').text(org);
        $('#borrowerPreview').show();
        $('#borrowerResults').hide();
    }
    
    // AJAX Search for Transaction Auto-population
    let txnSearchTimeout;
    $('#transactionSearch').on('input', function() {
        clearTimeout(txnSearchTimeout);
        let query = $(this).val();
        if(query.length < 3) {
            $('#transactionResults').hide();
            return;
        }
        txnSearchTimeout = setTimeout(function() {
            $.post('index.php', { action: 'search_transaction', query: query }, function(data) {
                let html = '';
                data.forEach(function(t) {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectTransaction('${t.brwTransID}', '${t.itemID}', '${t.itemDesc}', '${t.brwID}', '${t.brwFName} ${t.brwLName}', '${t.brwTransItemQty}')">
                              <strong>${t.brwTransID}</strong> - ${t.itemDesc} (Borrower: ${t.brwFName} ${t.brwLName})
                             </a>`;
                });
                $('#transactionResults').html(html).show();
            });
        }, 300);
    });

    // Helper function to handle transaction selection
    function selectTransaction(txnID, itemID, itemDesc, brwID, brwName, maxQty) {
        $('#transactionSearch').val(txnID);
        $('#retItemID').val(itemID);
        $('#retBrwID').val(brwID);
        $('#retQty').val(maxQty);
        $('#retQty').attr('max', maxQty);
        
        $('#ptTxnID').text(txnID);
        $('#ptItemDesc').text(itemDesc + ' (ID: ' + itemID + ')');
        $('#ptBorrower').text(brwName + ' (ID: ' + brwID + ')');
        $('#transactionPreview').show();
        $('#transactionResults').hide();
    }
    
    // Reports Period Toggler
    function togglePeriodInputs() {
        let val = document.getElementById('repPeriod').value;
        document.getElementById('repDayContainer').style.display = (val === 'day') ? 'block' : 'none';
        document.getElementById('repMonthContainer').style.display = (val === 'month') ? 'block' : 'none';
        document.getElementById('repYearContainer').style.display = (val === 'year') ? 'block' : 'none';
    }

    // Open Subtab from Sidebar
    function openSubtab(parentTabId, subTabId) {
        const parentBtn = document.querySelector(`[data-bs-target="${parentTabId}"]`);
        if (parentBtn) {
            new bootstrap.Tab(parentBtn).show();
        }
        
        // Wait a small delay to ensure parent pane is active before showing subtab
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
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 2500);
            }
        }, 150);
    }
    
    function openSubtabAndHighlight(parentTabId, subTabId, rowId) {
        openSubtab(parentTabId, subTabId);
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 2500);
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
  </script>
<?php endif; ?>
</body>
</html>




