<?php
/**
 * ==========================================================
 * CONFEDERATES STUDENT COUNCIL - RESOURCE MANAGEMENT SYSTEM
 * Main Application Controller & View (index.php)
 * ==========================================================
 * Simple, unified architecture containing:
 * 1. Authentication (Login, Register, Logout)
 * 2. Resources Management (CRUD + Availability toggle)
 * 3. Bookings & Scheduling (Search, Book, Reschedule, Cancel, Payment)
 * 4. Client & Staff Records (CRUD)
 * 5. Booking Reports (Periodic, per Resource, per Client)
 */

require_once __DIR__ . '/db.php';
$db = get_db();

$alert = ['type' => '', 'message' => ''];

// Current Logged-in User
$currentUser = isset($_SESSION['user_id']) ? $_SESSION : null;
$isAdmin = ($currentUser && $currentUser['role'] === 'admin');

// ==========================================================
// 1. BACK-END ACTION HANDLERS (POST REQUESTS)
// ==========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- A. AUTHENTICATION: LOGIN ---
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $alert = ['type' => 'danger', 'message' => 'Account is inactive. Contact Admin.'];
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['contact_number'] = $user['contact_number'];
                header('Location: index.php');
                exit;
            }
        } else {
            $alert = ['type' => 'danger', 'message' => 'Invalid username or password.'];
        }
    }

    // --- B. AUTHENTICATION: REGISTER STAFF ---
    elseif ($action === 'register') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($fullName) || empty($username) || empty($password)) {
            $alert = ['type' => 'danger', 'message' => 'Please fill in all required fields.'];
        } else {
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $alert = ['type' => 'danger', 'message' => 'Username already taken.'];
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status) VALUES (?, ?, ?, ?, ?, 'staff', 'active')");
                $stmt->execute([$username, $hash, $fullName, $email, $contact]);
                $newId = $db->lastInsertId();

                $_SESSION['user_id'] = $newId;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['role'] = 'staff';
                $_SESSION['email'] = $email;
                $_SESSION['contact_number'] = $contact;
                header('Location: index.php#resources');
                exit;
            }
        }
    }

    // --- C. PERSONAL ACCOUNT: UPDATE PROFILE ---
    elseif ($action === 'update_profile') {
        if (!$currentUser) exit;
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');

        if (!empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $contact, $hash, $currentUser['user_id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $contact, $currentUser['user_id']]);
        }
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['contact_number'] = $contact;
        $alert = ['type' => 'success', 'message' => 'Personal account updated successfully.'];
    }

    // --- D. RESOURCES CRUD: SAVE (ADD / EDIT) ---
    elseif ($action === 'save_resource') {
        // Staff is NOT allowed to add or edit equipment resources - Admin only!
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied: Staff accounts are not allowed to add or modify equipment resources. Only Administrators have this authority.'];
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $model = trim($_POST['model'] ?? 'Standard');
            $category = trim($_POST['category'] ?? 'Audio & Visual');
            $totalQty = max(1, (int)($_POST['total_qty'] ?? 1));
            $condition = trim($_POST['condition_status'] ?? 'Good');
            $location = trim($_POST['location'] ?? 'Council Office');
            $feeType = trim($_POST['fee_type'] ?? 'Free');
            $feeAmount = (float)($_POST['fee_amount'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $isAvail = isset($_POST['is_available']) ? 1 : 0;

            if ($id > 0) {
                // Edit resource
                $curr = $db->query("SELECT total_qty, available_qty FROM resources WHERE id = $id")->fetch();
                $qtyDiff = $totalQty - $curr['total_qty'];
                $newAvail = max(0, $curr['available_qty'] + $qtyDiff);

                $stmt = $db->prepare("UPDATE resources SET code = ?, name = ?, model = ?, category = ?, total_qty = ?, available_qty = ?, is_available = ?, condition_status = ?, location = ?, fee_type = ?, fee_amount = ?, description = ? WHERE id = ?");
                $stmt->execute([$code, $name, $model, $category, $totalQty, $newAvail, $isAvail, $condition, $location, $feeType, $feeAmount, $desc, $id]);
                $alert = ['type' => 'success', 'message' => "Resource {$code} updated successfully."];
            } else {
                // Add new resource
                $stmt = $db->prepare("INSERT INTO resources (code, name, model, category, total_qty, available_qty, is_available, condition_status, location, fee_type, fee_amount, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $name, $model, $category, $totalQty, $totalQty, $isAvail, $condition, $location, $feeType, $feeAmount, $desc]);
                $alert = ['type' => 'success', 'message' => "New resource {$code} added to inventory."];
            }
        }
    }

    // --- E. RESOURCES: TOGGLE AVAILABILITY ---
    elseif ($action === 'toggle_availability') {
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied: Staff accounts cannot modify equipment service status. Only Administrators have this authority.'];
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $val = (int)($_POST['is_available'] ?? 0);
            $db->prepare("UPDATE resources SET is_available = ? WHERE id = ?")->execute([$val, $id]);
            header('Location: index.php#resources');
            exit;
        }
    }

    // --- F. RESOURCES: DELETE ---
    elseif ($action === 'delete_resource') {
        if (!$isAdmin) {
            $alert = ['type' => 'danger', 'message' => 'Permission Denied: Staff accounts are not allowed to delete equipment resources. Only Administrators have this authority.'];
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $chk = $db->query("SELECT COUNT(*) FROM booking_items bi JOIN bookings b ON bi.booking_id = b.id WHERE bi.resource_id = $id AND b.status IN ('Pending', 'Approved', 'Released')")->fetchColumn();
            if ($chk > 0) {
                $alert = ['type' => 'danger', 'message' => 'Cannot delete resource: it is currently booked in an active or pending reservation.'];
            } else {
                $db->prepare("DELETE FROM resources WHERE id = ?")->execute([$id]);
                $alert = ['type' => 'success', 'message' => 'Resource deleted successfully.'];
            }
        }
    }

    // --- G. BOOKINGS CRUD: CREATE BOOKING ---
    elseif ($action === 'create_booking') {
        $studentId = trim($_POST['student_id'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $org = trim($_POST['organization_name'] ?? '');
        $eventName = trim($_POST['event_name'] ?? '');
        $eventLocation = trim($_POST['event_location'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));

        // 1. Get or create client
        $cStmt = $db->prepare("SELECT id FROM clients WHERE student_id = ?");
        $cStmt->execute([$studentId]);
        $client = $cStmt->fetch();

        if ($client) {
            $clientId = $client['id'];
        } else {
            $cIns = $db->prepare("INSERT INTO clients (student_id, full_name, email, contact_number, organization_name) VALUES (?, ?, ?, ?, ?)");
            $cIns->execute([$studentId, $fullName, $email, $contact, $org]);
            $clientId = $db->lastInsertId();
        }

        // 2. Create booking
        $bookingCode = 'CSC-' . date('Y') . '-' . mt_rand(1000, 9999);
        $createdBy = $currentUser['user_id'] ?? null;

        $bStmt = $db->prepare("INSERT INTO bookings (booking_code, client_id, created_by_user_id, event_name, event_location, purpose, start_date, end_date, status, payment_status, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Free / Waived', 0.00)");
        $bStmt->execute([$bookingCode, $clientId, $createdBy, $eventName, $eventLocation, $purpose, $startDate, $endDate]);
        $bookingId = $db->lastInsertId();

        // 3. Attach resource
        $biStmt = $db->prepare("INSERT INTO booking_items (booking_id, resource_id, quantity) VALUES (?, ?, ?)");
        $biStmt->execute([$bookingId, $resourceId, $qty]);

        $alert = ['type' => 'success', 'message' => "Booking {$bookingCode} successfully created!"];
    }

    // --- H. BOOKINGS: RESCHEDULE DATES ---
    elseif ($action === 'reschedule_booking') {
        $id = (int)($_POST['id'] ?? 0);
        $event = trim($_POST['event_name'] ?? '');
        $loc = trim($_POST['event_location'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');

        $db->prepare("UPDATE bookings SET event_name = ?, event_location = ?, start_date = ?, end_date = ? WHERE id = ?")
           ->execute([$event, $loc, $start, $end, $id]);
        $alert = ['type' => 'success', 'message' => "Booking schedule updated successfully."];
    }

    // --- I. BOOKINGS: UPDATE PAYMENT / DEPOSIT ---
    elseif ($action === 'update_payment') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['payment_status'] ?? 'Free / Waived');
        $amount = (float)($_POST['payment_amount'] ?? 0);
        $details = trim($_POST['payment_details'] ?? '');

        $db->prepare("UPDATE bookings SET payment_status = ?, payment_amount = ?, payment_details = ? WHERE id = ?")
           ->execute([$status, $amount, $details, $id]);
        $alert = ['type' => 'success', 'message' => "Payment / deposit details updated."];
    }

    // --- J. BOOKINGS: CANCEL BOOKING ---
    elseif ($action === 'cancel_booking') {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Cancelled by staff');

        // Restore inventory if it was on loan
        $b = $db->query("SELECT status FROM bookings WHERE id = $id")->fetch();
        if ($b && ($b['status'] === 'Released' || $b['status'] === 'Overdue')) {
            $items = $db->query("SELECT resource_id, quantity FROM booking_items WHERE booking_id = $id")->fetchAll();
            foreach ($items as $it) {
                $db->prepare("UPDATE resources SET available_qty = MIN(total_qty, available_qty + ?) WHERE id = ?")->execute([$it['quantity'], $it['resource_id']]);
            }
        }
        $db->prepare("UPDATE bookings SET status = 'Cancelled', admin_notes = ? WHERE id = ?")->execute(["Cancelled: {$reason}", $id]);
        $alert = ['type' => 'warning', 'message' => 'Booking cancelled.'];
    }

    // --- K. BOOKINGS: STATUS CHANGE (Approve / Release / Return) ---
    elseif ($action === 'update_booking_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        $b = $db->query("SELECT status FROM bookings WHERE id = $id")->fetch();
        $oldStatus = $b['status'];

        $bItems = $db->query("SELECT resource_id, quantity FROM booking_items WHERE booking_id = $id")->fetchAll();

        // Release: Deduct stock
        if ($newStatus === 'Released' && $oldStatus !== 'Released') {
            foreach ($bItems as $it) {
                $db->prepare("UPDATE resources SET available_qty = available_qty - ? WHERE id = ?")->execute([$it['quantity'], $it['resource_id']]);
            }
        }
        // Return: Restore stock
        if ($newStatus === 'Returned' && ($oldStatus === 'Released' || $oldStatus === 'Overdue')) {
            foreach ($bItems as $it) {
                $db->prepare("UPDATE resources SET available_qty = MIN(total_qty, available_qty + ?) WHERE id = ?")->execute([$it['quantity'], $it['resource_id']]);
            }
            $db->prepare("UPDATE bookings SET actual_return_date = ? WHERE id = ?")->execute([date('Y-m-d'), $id]);
        }

        $db->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        $alert = ['type' => 'success', 'message' => "Booking marked as {$newStatus}."];
    }

    // --- L. CLIENTS CRUD: SAVE (ADD / EDIT) ---
    elseif ($action === 'save_client') {
        $id = (int)($_POST['id'] ?? 0);
        $studentId = trim($_POST['student_id'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $org = trim($_POST['organization_name'] ?? '');
        $role = trim($_POST['role'] ?? 'Student');
        $status = trim($_POST['status'] ?? 'Active');

        if ($id > 0) {
            $db->prepare("UPDATE clients SET student_id = ?, full_name = ?, email = ?, contact_number = ?, organization_name = ?, role = ?, status = ? WHERE id = ?")
               ->execute([$studentId, $fullName, $email, $contact, $org, $role, $status, $id]);
            $alert = ['type' => 'success', 'message' => 'Client updated successfully.'];
        } else {
            $db->prepare("INSERT INTO clients (student_id, full_name, email, contact_number, organization_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$studentId, $fullName, $email, $contact, $org, $role, $status]);
            $alert = ['type' => 'success', 'message' => 'New client added.'];
        }
    }

    // --- M. CLIENTS CRUD: DELETE ---
    elseif ($action === 'delete_client') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
        $alert = ['type' => 'success', 'message' => 'Client deleted.'];
    }

    // --- N. STAFF CRUD (ADMIN ONLY): SAVE ---
    elseif ($action === 'save_staff' && $isAdmin) {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $role = trim($_POST['role'] ?? 'staff');
        $status = trim($_POST['status'] ?? 'active');
        $pass = trim($_POST['password'] ?? '');

        if ($id > 0) {
            if (!empty($pass)) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, role = ?, status = ?, password_hash = ? WHERE id = ?")
                   ->execute([$username, $fullName, $email, $contact, $role, $status, $hash, $id]);
            } else {
                $db->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, role = ?, status = ? WHERE id = ?")
                   ->execute([$username, $fullName, $email, $contact, $role, $status, $id]);
            }
            $alert = ['type' => 'success', 'message' => 'Staff record updated.'];
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$username, $hash, $fullName, $email, $contact, $role, $status]);
            $alert = ['type' => 'success', 'message' => 'Staff member created.'];
        }
    }

    // --- O. STAFF CRUD (ADMIN ONLY): DELETE ---
    elseif ($action === 'delete_staff' && $isAdmin) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$currentUser['user_id']) {
            $alert = ['type' => 'danger', 'message' => 'Cannot delete your own active account.'];
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $alert = ['type' => 'success', 'message' => 'Staff account deleted.'];
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
// KPI Stats
$totalResources = $db->query("SELECT SUM(total_qty) FROM resources")->fetchColumn() ?: 0;
$availableResources = $db->query("SELECT SUM(available_qty) FROM resources WHERE is_available = 1")->fetchColumn() ?: 0;
$activeOnLoan = $totalResources - $availableResources;
$pendingCount = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn() ?: 0;
$today = date('Y-m-d');
$overdueCount = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'Overdue' OR (status = 'Released' AND end_date < '{$today}')")->fetchColumn() ?: 0;

// Cross-database items summary SQL (MySQL vs SQLite)
$isMysql = (defined('DB_TYPE') && DB_TYPE === 'mysql');
$itemsSummarySql = $isMysql 
    ? "SELECT GROUP_CONCAT(CONCAT(r.name, ' (', bi.quantity, ')') SEPARATOR ', ') FROM booking_items bi JOIN resources r ON bi.resource_id = r.id WHERE bi.booking_id = b.id"
    : "SELECT GROUP_CONCAT(r.name || ' (' || bi.quantity || ')', ', ') FROM booking_items bi JOIN resources r ON bi.resource_id = r.id WHERE bi.booking_id = b.id";

// Resources list
$resources = $db->query("SELECT * FROM resources ORDER BY category ASC, name ASC")->fetchAll();

// Bookings list
$bookings = $db->query("SELECT b.*, c.student_id, c.full_name as client_name, c.organization_name,
                        ({$itemsSummarySql}) as items_summary
                        FROM bookings b 
                        JOIN clients c ON b.client_id = c.id 
                        ORDER BY b.start_date DESC")->fetchAll();

// Clients list
$clients = $db->query("SELECT c.*, (SELECT COUNT(*) FROM bookings b WHERE b.client_id = c.id) as booking_count FROM clients c ORDER BY c.full_name ASC")->fetchAll();

// Staff list (for Admin)
$staffMembers = $isAdmin ? $db->query("SELECT * FROM users ORDER BY role ASC, full_name ASC")->fetchAll() : [];

// Reports filter
$reportType = $_GET['report_type'] ?? 'periodic';
$reportStart = $_GET['report_start'] ?? date('Y-m-01');
$reportEnd = $_GET['report_end'] ?? date('Y-m-t');

if ($reportType === 'by_resource') {
    $reportData = $db->query("SELECT r.code, r.name, r.model, r.category, r.total_qty, COUNT(bi.id) as times_booked, COALESCE(SUM(bi.quantity), 0) as units_borrowed
                              FROM resources r
                              LEFT JOIN booking_items bi ON r.id = bi.resource_id
                              LEFT JOIN bookings b ON bi.booking_id = b.id AND b.start_date BETWEEN '{$reportStart}' AND '{$reportEnd}'
                              GROUP BY r.id ORDER BY times_booked DESC")->fetchAll();
} elseif ($reportType === 'by_client') {
    $reportData = $db->query("SELECT c.student_id, c.full_name, c.organization_name, COUNT(b.id) as total_bookings, COALESCE(SUM(b.payment_amount), 0) as total_fees
                              FROM clients c
                              LEFT JOIN bookings b ON c.id = b.client_id AND b.start_date BETWEEN '{$reportStart}' AND '{$reportEnd}'
                              GROUP BY c.id ORDER BY total_bookings DESC")->fetchAll();
} else {
    // Periodic
    $reportData = $db->query("SELECT b.*, c.student_id, c.full_name as client_name, c.organization_name,
                              ({$itemsSummarySql}) as items_summary
                              FROM bookings b JOIN clients c ON b.client_id = c.id
                              WHERE b.start_date BETWEEN '{$reportStart}' AND '{$reportEnd}'
                              ORDER BY b.start_date DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom Modern Spacious Styles -->
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231d4ed8'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/></svg>">
</head>
<body>

<?php if (!$currentUser): ?>
  <!-- ==========================================================
       EXCLUSIVE COUNCIL CLERK & OFFICER LOGIN TERMINAL
       (NO PUBLIC ACCESS - ADMIN & STAFF DESK TERMINAL ONLY)
       ========================================================== -->
  <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center py-5 px-3" style="background: radial-gradient(circle at 50% 20%, #eff6ff 0%, #f1f5f9 100%);">
    
    <div style="max-width: 480px; width: 100%;">
      <!-- Council Logo & Terminal Header -->
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center brand-icon mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 18px;">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
        </div>
        <h3 class="fw-bold text-dark mb-1">Confederates Student Council</h3>
        <p class="text-muted small mt-2 mb-0">Internal Property Borrowing, Custody & Gate Pass System</p>
      </div>

      <!-- Alert Notification Banner -->
      <?php if (!empty($alert['message'])): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4" role="alert">
          <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold"><?= htmlspecialchars($alert['message']) ?></span>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Card Container with Tabs for Sign In vs Register Staff -->
      <div class="content-card shadow-lg bg-white">
        <div class="card-header bg-white border-bottom p-3">
          <ul class="nav nav-pills nav-fill fw-semibold" id="authTabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active py-2" id="signin-tab" data-bs-toggle="pill" data-bs-target="#signin-panel" type="button">Officer Sign In</button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-2" id="register-tab" data-bs-toggle="pill" data-bs-target="#register-panel" type="button">Register Staff Account</button>
            </li>
          </ul>
        </div>

        <div class="card-body p-4 p-md-5">
          <div class="tab-content" id="authTabContent">
            
            <!-- Tab 1: Officer Sign In -->
            <div class="tab-pane fade show active" id="signin-panel">
              <form method="POST" action="index.php">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" id="loginUsername" name="username" class="form-control" placeholder="Enter username (e.g. admin or staff1)" required autofocus>
                </div>
                <div class="mb-4">
                  <label class="form-label">Password</label>
                  <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <!-- 1-Click Demo Buttons for Fast Defense Presentation -->
                <div class="bg-light p-3 rounded-3 border text-center mb-4">
                  <small class="text-muted d-block fw-semibold mb-2" style="font-size: 12px;">Quick 1-Click Credentials for Professor & Defense Review:</small>
                  <div class="btn-group w-100">
                    <button type="button" class="btn btn-outline-primary btn-sm py-2 fw-semibold" onclick="document.getElementById('loginUsername').value='admin'; document.getElementById('loginPassword').value='admin123';">
                      👤 Fill Admin (admin)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-2 fw-semibold" onclick="document.getElementById('loginUsername').value='staff1'; document.getElementById('loginPassword').value='staff123';">
                      👔 Fill Staff (staff1)
                    </button>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary-action w-100 py-3 shadow">
                  <span>Sign In to Terminal</span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
              </form>
            </div>

            <!-- Tab 2: Register Staff Account -->
            <div class="tab-pane fade" id="register-panel">
              <form method="POST" action="index.php">
                <input type="hidden" name="action" value="register">
                <div class="mb-3">
                  <label class="form-label">Full Legal Name *</label>
                  <input type="text" name="full_name" class="form-control" placeholder="e.g. Maria Santos" required>
                </div>
                <div class="row g-3 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label">Desired Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="msantos" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label">Contact Number</label>
                    <input type="tel" name="contact_number" class="form-control" placeholder="09123456789">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Institutional Email *</label>
                  <input type="email" name="email" class="form-control" placeholder="msantos@csc.edu.ph" required>
                </div>
                <div class="mb-4">
                  <label class="form-label">Password *</label>
                  <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary-action w-100 py-3">
                  <span>Create Council Staff Account</span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- ==========================================================
       AUTHENTICATED COUNCIL CLERK & ADMIN WORKSPACE
       ========================================================== -->
  <!-- TOP NAVBAR -->
  <nav class="navbar navbar-expand-lg app-navbar sticky-top no-print">
    <div class="container-fluid px-0">
      <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
        </div>
        <div>
          <span class="fw-bold d-block text-dark lh-1 fs-5">Confederates Student Council</span>
          <small class="text-primary fw-semibold" style="font-size: 12px; letter-spacing: 0.3px;">Desk Clerk & Asset Management Terminal</small>
        </div>
      </a>

      <div class="d-flex align-items-center gap-3 ms-auto">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold d-none d-md-inline-flex align-items-center gap-2" style="font-size: 12px; border-radius: 9999px;">
          <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span>
          Desk Terminal Online
        </span>

        <div class="dropdown">
          <button class="btn btn-light border dropdown-toggle px-3 py-2 rounded-pill d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="dropdown">
            <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-info text-dark' ?> text-uppercase px-2 py-1" style="font-size: 10px;"><?= htmlspecialchars($currentUser['role']) ?></span>
            <span class="fw-semibold text-dark small"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
            <li><button class="dropdown-item small py-2 d-flex align-items-center gap-2" onclick="showTab('profile')">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
              <span>Manage Personal Account</span>
            </button></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger py-2 d-flex align-items-center gap-2" href="index.php?action=logout">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign Out of Terminal</span>
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- ALERT NOTIFICATION BANNER -->
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

  <!-- ==========================================================
       MAIN VIEW CONTAINER WITH SPACIOUS APP-SHELL
       ========================================================== -->
  <div class="app-shell">
    <div class="app-layout">

      <!-- LEFT SIDEBAR NAVIGATION (270px Dedicated Comfortable Width) -->
      <aside class="app-sidebar no-print">
        <div class="sidebar-panel">
          
          <!-- Officer Badge Box -->
          <div class="officer-badge-box">
            <div class="officer-avatar">
              <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
              <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($currentUser['full_name']) ?></div>
              <span class="badge <?= $isAdmin ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info-emphasis' ?> text-uppercase px-2 py-0" style="font-size: 10px;">
                <?= $isAdmin ? 'System Admin' : 'Desk Officer' ?>
              </span>
            </div>
          </div>

          <!-- Primary Action Button: Desk Checkout -->
          <button class="btn btn-primary-action w-100 mb-3" data-bs-toggle="modal" data-bs-target="#newBookingModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Desk Checkout</span>
          </button>

          <!-- Navigation Links -->
          <div class="text-uppercase small text-muted fw-bold mb-2 ps-1" style="font-size: 11px; letter-spacing: 0.05em;">Council Operations</div>
          <div class="d-flex flex-column">
            
            <button class="nav-tab-btn active" data-tab="bookings" onclick="showTab('bookings')">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>Bookings & Loans</span>
              </div>
              <span class="nav-count-badge"><?= count($bookings) ?></span>
            </button>

            <button class="nav-tab-btn" data-tab="resources" onclick="showTab('resources')">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Equipment Inventory</span>
              </div>
              <span class="nav-count-badge"><?= count($resources) ?></span>
            </button>

            <button class="nav-tab-btn" data-tab="clients" onclick="showTab('clients')">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Borrower Directory</span>
              </div>
              <span class="nav-count-badge"><?= count($clients) ?></span>
            </button>

            <?php if ($isAdmin): ?>
              <button class="nav-tab-btn" data-tab="staff" onclick="showTab('staff')">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <span>Council Officers</span>
                </div>
                <span class="nav-count-badge"><?= count($staffMembers) ?></span>
              </button>
            <?php endif; ?>

            <button class="nav-tab-btn" data-tab="reports" onclick="showTab('reports')">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>Activity Reports</span>
              </div>
            </button>

            <button class="nav-tab-btn" data-tab="profile" onclick="showTab('profile')">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                <span>My Account</span>
              </div>
            </button>

          </div>

          <!-- Gate Pass Tip in Sidebar -->
          <div class="mt-4 p-3 bg-light rounded-3 border">
            <div class="d-flex align-items-center gap-2 mb-1">
              <svg width="16" height="16" class="text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <strong class="text-dark small">Gate Pass Slips</strong>
            </div>
            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.4;">
              Click <strong>Slip</strong> in any row to generate an official printable property gate pass.
            </p>
          </div>

        </div>
      </aside>

      <!-- MAIN CONTENT AREA (Flexes Cleanly with Ample Room) -->
      <main class="app-main">

        <!-- SHIFT HEADER & GREETING -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
          <div>
            <h3 class="fw-bold text-dark mb-1">Council Property Desk Station</h3>
            <div class="text-muted small">
              Welcome, <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong> &bull; Assigned as <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-uppercase"><?= htmlspecialchars($currentUser['role']) ?></span>
            </div>
          </div>
          <div class="bg-white px-3 py-2 rounded-pill border shadow-sm small text-muted d-flex align-items-center gap-2">
            <svg width="16" height="16" class="text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Current Date: <strong><?= date('F d, Y') ?></strong></span>
          </div>
        </div>
        <!-- ==========================================================
             TAB 1: BOOKINGS & LOANS (SPACIOUS TABLE + FILTER PILLS)
             ========================================================== -->
        <section id="tab-bookings" class="tab-pane-content">
          <div class="content-card">
            
            <!-- Card Header -->
            <div class="content-card-header">
              <div>
                <h5 class="fw-bold mb-1 text-dark">Resource Bookings & Desk Loans</h5>
                <p class="text-muted small mb-0">Process desk checkouts, update schedules, record payments, and manage returns.</p>
              </div>
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="search-box-wrapper">
                  <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  <input type="text" id="bookingSearchInput" class="form-control" placeholder="Search tracking, client, event..." onkeyup="filterTable('bookingSearchInput', 'bookingsTableBody')">
                </div>
                <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>Desk Checkout</span>
                </button>
              </div>
            </div>

            <!-- Quick Status Filter Pills -->
            <div class="filter-pills-bar" id="bookingFilterPills">
              <span class="small text-muted fw-bold me-2">Quick Filter:</span>
              <button type="button" class="filter-pill active" onclick="filterBookingStatus('all', this)">
                <span>All Loans</span>
                <span class="filter-pill-badge"><?= count($bookings) ?></span>
              </button>
              <button type="button" class="filter-pill" onclick="filterBookingStatus('Pending', this)">
                <span>● Pending</span>
                <span class="filter-pill-badge"><?= $pendingCount ?></span>
              </button>
              <button type="button" class="filter-pill" onclick="filterBookingStatus('Approved', this)">
                <span>● Approved</span>
              </button>
              <button type="button" class="filter-pill" onclick="filterBookingStatus('Released', this)">
                <span>● Released (On Loan)</span>
                <span class="filter-pill-badge"><?= $activeOnLoan ?></span>
              </button>
              <button type="button" class="filter-pill" onclick="filterBookingStatus('Returned', this)">
                <span>● Returned</span>
              </button>
              <button type="button" class="filter-pill" onclick="filterBookingStatus('Cancelled', this)">
                <span>● Cancelled</span>
              </button>
            </div>

            <!-- Table Responsive Container (Properly Nested Inside Card) -->
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Tracking Code</th>
                    <th>Borrower & Student ID</th>
                    <th>Organization & Purpose</th>
                    <th>Loan Dates</th>
                    <th>Status</th>
                    <th>Deposit / Fee</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="bookingsTableBody">
                  <?php if (empty($bookings)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <svg width="48" height="48" class="text-muted mb-2 d-block mx-auto opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <p class="mb-2 fw-semibold">No equipment bookings on record yet.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBookingModal">+ Create First Desk Loan</button>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                      <tr data-status="<?= htmlspecialchars($b['status']) ?>">
                        <!-- Tracking Code & Resource Summary -->
                        <td>
                          <div class="badge-code mb-1"><?= htmlspecialchars($b['booking_code']) ?></div>
                          <div class="text-dark fw-semibold small"><?= htmlspecialchars($b['items_summary']) ?></div>
                        </td>

                        <!-- Borrower Info -->
                        <td>
                          <div class="fw-bold text-dark"><?= htmlspecialchars($b['client_name']) ?></div>
                          <div class="text-muted small">
                            <span class="font-monospace text-primary fw-semibold"><?= htmlspecialchars($b['student_id']) ?></span>
                            <?php if (!empty($b['client_contact'])): ?>
                              &bull; <?= htmlspecialchars($b['client_contact']) ?>
                            <?php endif; ?>
                          </div>
                        </td>

                        <!-- Organization & Event -->
                        <td>
                          <div class="fw-semibold text-dark"><?= htmlspecialchars($b['organization_name']) ?></div>
                          <div class="text-muted small">
                            <strong><?= htmlspecialchars($b['event_name']) ?></strong> &bull; <?= htmlspecialchars($b['event_location']) ?>
                          </div>
                        </td>

                        <!-- Dates -->
                        <td>
                          <div class="small">
                            <span class="text-muted">Release:</span> <strong><?= date('M d, Y', strtotime($b['start_date'])) ?></strong>
                          </div>
                          <div class="small">
                            <span class="text-muted">Return Due:</span> <strong><?= date('M d, Y', strtotime($b['end_date'])) ?></strong>
                          </div>
                        </td>

                        <!-- Status Badge -->
                        <td>
                          <?php
                            $statusClass = 'pending';
                            if ($b['status'] === 'Released') $statusClass = 'released';
                            elseif ($b['status'] === 'Approved') $statusClass = 'approved';
                            elseif ($b['status'] === 'Returned') $statusClass = 'returned';
                            elseif ($b['status'] === 'Cancelled') $statusClass = 'cancelled';
                          ?>
                          <span class="badge-status <?= $statusClass ?>">
                            <span class="badge-status-dot"></span>
                            <span><?= htmlspecialchars($b['status']) ?></span>
                          </span>
                        </td>

                        <!-- Fee / Payment -->
                        <td>
                          <div class="fw-bold text-dark">₱<?= number_format($b['payment_amount'], 2) ?></div>
                          <span class="badge bg-light text-dark border small" style="font-size: 11px;"><?= htmlspecialchars($b['payment_status']) ?></span>
                        </td>

                        <!-- Actions (Spaced & User-Friendly) -->
                        <td>
                          <div class="table-action-group">
                            
                            <!-- 1. Primary Workflow Action Button -->
                            <?php if ($b['status'] === 'Pending'): ?>
                              <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_booking_status">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" class="btn btn-action btn-action-success" title="Approve this loan">
                                  <span>✓ Approve</span>
                                </button>
                              </form>
                            <?php elseif ($b['status'] === 'Approved'): ?>
                              <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_booking_status">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="status" value="Released">
                                <button type="submit" class="btn btn-action btn-action-primary" title="Handover equipment to borrower">
                                  <span>📦 Release</span>
                                </button>
                              </form>
                            <?php elseif ($b['status'] === 'Released'): ?>
                              <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_booking_status">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="status" value="Returned">
                                <button type="submit" class="btn btn-action btn-action-success" title="Mark equipment as returned to council inventory">
                                  <span>📥 Return</span>
                                </button>
                              </form>
                            <?php endif; ?>

                            <!-- 2. Gate Pass Slip Button -->
                            <a href="receipt.php?code=<?= urlencode($b['booking_code']) ?>" target="_blank" class="btn btn-action btn-action-secondary" title="View & Print Official Gate Pass">
                              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                              <span>Slip</span>
                            </a>

                            <!-- 3. Dropdown Menu for Secondary Actions -->
                            <div class="dropdown d-inline-block">
                              <button class="btn btn-action btn-action-secondary dropdown-toggle" data-bs-toggle="dropdown" title="More loan options">
                                <span>⋯</span>
                              </button>
                              <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                                <li>
                                  <button class="dropdown-item small py-2 d-flex align-items-center gap-2" onclick='rescheduleBooking(<?= json_encode($b) ?>)'>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span>Reschedule Dates</span>
                                  </button>
                                </li>
                                <li>
                                  <button class="dropdown-item small py-2 d-flex align-items-center gap-2" onclick='editPayment(<?= json_encode($b) ?>)'>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    <span>Payment & Deposit</span>
                                  </button>
                                </li>
                                <?php if ($b['status'] !== 'Cancelled' && $b['status'] !== 'Returned'): ?>
                                  <li><hr class="dropdown-divider my-1"></li>
                                  <li>
                                    <button class="dropdown-item small text-danger py-2 d-flex align-items-center gap-2" onclick="document.getElementById('cancelId').value='<?= $b['id'] ?>'; new bootstrap.Modal(document.getElementById('cancelModal')).show();">
                                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                      <span>Cancel Loan</span>
                                    </button>
                                  </li>
                                <?php endif; ?>
                              </ul>
                            </div>

                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </section>

        <!-- ==========================================================
             TAB 2: EQUIPMENT INVENTORY (SPACIOUS TABLE + CATEGORY PILLS)
             ========================================================== -->
        <section id="tab-resources" class="tab-pane-content d-none">
          <div class="content-card">
            
            <!-- Card Header -->
            <div class="content-card-header">
              <div>
                <h5 class="fw-bold mb-1 text-dark">Council Equipment Inventory</h5>
                <p class="text-muted small mb-0">Speakers, sports gear, stage logistics, and electronic assets under council custody.</p>
              </div>
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="search-box-wrapper">
                  <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  <input type="text" id="resSearchInput" class="form-control" placeholder="Search equipment or code..." onkeyup="filterTable('resSearchInput', 'resourcesTableBody')">
                </div>
                <?php if ($isAdmin): ?>
                  <button class="btn btn-primary-action btn-sm" onclick="document.getElementById('resourceForm').reset(); document.getElementById('resId').value='0'; new bootstrap.Modal(document.getElementById('resourceModal')).show();">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add Equipment</span>
                  </button>
                <?php else: ?>
                  <span class="badge bg-light text-secondary border px-3 py-2 d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Read-Only Inventory (Admin Managed)</span>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Category Filter Pills -->
            <div class="filter-pills-bar" id="resourceFilterPills">
              <span class="small text-muted fw-bold me-2">Category:</span>
              <button type="button" class="filter-pill active" onclick="filterResourceCategory('all', this)">
                <span>All Assets</span>
                <span class="filter-pill-badge"><?= count($resources) ?></span>
              </button>
              <button type="button" class="filter-pill" onclick="filterResourceCategory('Audio', this)">
                <span>🔊 Audio & Visual</span>
              </button>
              <button type="button" class="filter-pill" onclick="filterResourceCategory('Sports', this)">
                <span>⚽ Sports & Recreation</span>
              </button>
              <button type="button" class="filter-pill" onclick="filterResourceCategory('Event', this)">
                <span>🎪 Event & Logistics</span>
              </button>
              <button type="button" class="filter-pill" onclick="filterResourceCategory('Electronics', this)">
                <span>⚡ Electronics & Safety</span>
              </button>
            </div>

            <!-- Table Responsive Container -->
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Item Code</th>
                    <th>Equipment & Model</th>
                    <th>Category</th>
                    <th>Stock Availability</th>
                    <th>Rate / Fee</th>
                    <th>Service Toggle</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="resourcesTableBody">
                  <?php if (empty($resources)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <p class="mb-2 fw-semibold">No equipment registered in the inventory yet.</p>
                        <?php if ($isAdmin): ?>
                          <button class="btn btn-primary btn-sm" onclick="document.getElementById('resourceForm').reset(); document.getElementById('resId').value='0'; new bootstrap.Modal(document.getElementById('resourceModal')).show();">+ Add Equipment Resource</button>
                        <?php else: ?>
                          <p class="small text-muted mb-0">Contact an Administrator to add equipment resources to the inventory.</p>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($resources as $r): ?>
                      <tr data-category="<?= htmlspecialchars($r['category']) ?>">
                        <!-- Item Code -->
                        <td>
                          <span class="badge-code"><?= htmlspecialchars($r['code']) ?></span>
                        </td>

                        <!-- Name & Model & Location -->
                        <td>
                          <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['name']) ?></div>
                          <div class="text-muted small">
                            Model: <strong><?= htmlspecialchars($r['model']) ?></strong> &bull; Storage: <?= htmlspecialchars($r['location']) ?>
                          </div>
                        </td>

                        <!-- Category -->
                        <td>
                          <span class="badge bg-light text-dark border px-3 py-1 fw-semibold"><?= htmlspecialchars($r['category']) ?></span>
                        </td>

                        <!-- Stock Avail / Total -->
                        <td>
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fs-6 fw-bold <?= $r['available_qty'] > 0 ? 'text-success' : 'text-danger' ?>">
                              <?= $r['available_qty'] ?>
                            </span>
                            <span class="text-muted">/ <?= $r['total_qty'] ?> units available</span>
                          </div>
                          <div class="small text-muted">Condition: <strong class="text-dark"><?= htmlspecialchars($r['condition_status']) ?></strong></div>
                        </td>

                        <!-- Fee Type & Amount -->
                        <td>
                          <span class="badge bg-light text-dark border"><?= htmlspecialchars($r['fee_type']) ?></span>
                          <?php if ($r['fee_amount'] > 0): ?>
                            <div class="fw-bold text-primary mt-1">₱<?= number_format($r['fee_amount'], 2) ?></div>
                          <?php endif; ?>
                        </td>

                        <!-- Service Status / Toggle -->
                        <td>
                          <?php if ($isAdmin): ?>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="action" value="toggle_availability">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <input type="hidden" name="is_available" value="<?= $r['is_available'] == 1 ? 0 : 1 ?>">
                              <button type="submit" class="btn btn-action <?= $r['is_available'] == 1 ? 'btn-action-success' : 'btn-action-secondary text-danger' ?>" title="Click to toggle availability status">
                                <?= $r['is_available'] == 1 ? '✓ Available' : '✕ Out of Service' ?>
                              </button>
                            </form>
                          <?php else: ?>
                            <span class="badge-status <?= $r['is_available'] == 1 ? 'released' : 'returned' ?>" title="Admin controlled status">
                              <span class="badge-status-dot"></span>
                              <?= $r['is_available'] == 1 ? 'Available' : 'Out of Service' ?>
                            </span>
                          <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td>
                          <?php if ($isAdmin): ?>
                            <div class="table-action-group">
                              <button class="btn btn-action btn-action-secondary" onclick='editResource(<?= json_encode($r) ?>)' title="Edit Details">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <span>Edit</span>
                              </button>
                              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                <input type="hidden" name="action" value="delete_resource">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-action btn-action-secondary text-danger" title="Delete Resource">
                                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                  <span>Delete</span>
                                </button>
                              </form>
                            </div>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border px-2 py-1 d-inline-flex align-items-center gap-1">
                              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                              <span>Admin Only</span>
                            </span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </section>

        <!-- ==========================================================
             TAB 3: BORROWER DIRECTORY (CLIENTS)
             ========================================================== -->
        <section id="tab-clients" class="tab-pane-content d-none">
          <div class="content-card">
            
            <!-- Card Header -->
            <div class="content-card-header">
              <div>
                <h5 class="fw-bold mb-1 text-dark">Borrower & Organization Directory</h5>
                <p class="text-muted small mb-0">Authorized student leaders, organizations, and borrowing history.</p>
              </div>
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="search-box-wrapper">
                  <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  <input type="text" id="cliSearchInput" class="form-control" placeholder="Search borrowers..." onkeyup="filterTable('cliSearchInput', 'clientsTableBody')">
                </div>
                <button class="btn btn-primary-action btn-sm" onclick="document.getElementById('clientForm').reset(); document.getElementById('cliId').value='0'; new bootstrap.Modal(document.getElementById('clientModal')).show();">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>+ Add Borrower</span>
                </button>
              </div>
            </div>

            <!-- Table Responsive Container -->
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>Student ID</th>
                    <th>Borrower Name</th>
                    <th>Organization & Designation</th>
                    <th>Contact & Email</th>
                    <th>Standing</th>
                    <th>Borrowing Record</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="clientsTableBody">
                  <?php if (empty($clients)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <p class="mb-2 fw-semibold">No borrower records yet.</p>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('clientForm').reset(); document.getElementById('cliId').value='0'; new bootstrap.Modal(document.getElementById('clientModal')).show();">+ Add First Client</button>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($clients as $c): ?>
                      <tr>
                        <td><span class="badge-code"><?= htmlspecialchars($c['student_id']) ?></span></td>
                        <td><strong class="text-dark fs-6"><?= htmlspecialchars($c['full_name']) ?></strong></td>
                        <td>
                          <div class="fw-semibold text-dark"><?= htmlspecialchars($c['organization_name']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($c['role']) ?></small>
                        </td>
                        <td>
                          <div class="small fw-semibold"><?= htmlspecialchars($c['contact_number']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small>
                        </td>
                        <td>
                          <span class="badge-status <?= $c['status'] === 'Active' ? 'released' : 'cancelled' ?>">
                            <span class="badge-status-dot"></span>
                            <span><?= htmlspecialchars($c['status']) ?></span>
                          </span>
                        </td>
                        <td>
                          <strong class="text-primary fs-6"><?= $c['booking_count'] ?></strong> <span class="text-muted small">loan(s)</span>
                        </td>
                        <td>
                          <div class="table-action-group">
                            <button class="btn btn-action btn-action-secondary" onclick='editClient(<?= json_encode($c) ?>)'>
                              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                              <span>Edit</span>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete client record?');">
                              <input type="hidden" name="action" value="delete_client">
                              <input type="hidden" name="id" value="<?= $c['id'] ?>">
                              <button type="submit" class="btn btn-action btn-action-secondary text-danger">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                <span>Delete</span>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </section>

        <!-- ==========================================================
             TAB 4: STAFF RECORDS (ADMIN ONLY)
             ========================================================== -->
        <?php if ($isAdmin): ?>
          <section id="tab-staff" class="tab-pane-content d-none">
            <div class="content-card">
              
              <!-- Card Header -->
              <div class="content-card-header">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Council Staff & Officer Accounts</h5>
                  <p class="text-muted small mb-0">Authorized clerks and administrators permitted to operate the desk terminal.</p>
                </div>
                <button class="btn btn-primary-action btn-sm" onclick="document.getElementById('staffForm').reset(); document.getElementById('stfId').value='0'; new bootstrap.Modal(document.getElementById('staffModal')).show();">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>Add Officer</span>
                </button>
              </div>

              <!-- Table Responsive Container -->
              <div class="table-responsive">
                <table class="table-custom">
                  <thead>
                    <tr>
                      <th>Username</th>
                      <th>Full Name</th>
                      <th>Email & Contact</th>
                      <th>System Role</th>
                      <th>Account Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($staffMembers as $s): ?>
                      <tr>
                        <td><span class="badge-code"><?= htmlspecialchars($s['username']) ?></span></td>
                        <td><strong class="text-dark fs-6"><?= htmlspecialchars($s['full_name']) ?></strong></td>
                        <td>
                          <div class="small fw-semibold"><?= htmlspecialchars($s['email']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($s['contact_number']) ?></small>
                        </td>
                        <td>
                          <span class="badge <?= $s['role'] === 'admin' ? 'bg-primary' : 'bg-info text-dark' ?> text-uppercase px-2 py-1 fw-semibold">
                            <?= htmlspecialchars($s['role']) ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge-status <?= $s['status'] === 'active' ? 'released' : 'returned' ?>">
                            <span class="badge-status-dot"></span>
                            <span><?= htmlspecialchars($s['status']) ?></span>
                          </span>
                        </td>
                        <td>
                          <div class="table-action-group">
                            <button class="btn btn-action btn-action-secondary" onclick='editStaff(<?= json_encode($s) ?>)'>
                              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                              <span>Edit</span>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete staff account?');">
                              <input type="hidden" name="action" value="delete_staff">
                              <input type="hidden" name="id" value="<?= $s['id'] ?>">
                              <button type="submit" class="btn btn-action btn-action-secondary text-danger">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                <span>Delete</span>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </section>
        <?php endif; ?>

        <!-- ==========================================================
             TAB 5: ACTIVITY REPORTS
             ========================================================== -->
        <section id="tab-reports" class="tab-pane-content d-none">
          <div class="content-card">
            
            <!-- Card Header & Filter Form -->
            <div class="content-card-header no-print">
              <div>
                <h5 class="fw-bold mb-1 text-dark">Resource Booking Reports</h5>
                <p class="text-muted small mb-0">Periodic reports, utilization per equipment, and borrowing ledger per client.</p>
              </div>
              <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <select name="report_type" class="form-select form-select-sm" style="width: 190px;">
                  <option value="periodic" <?= $reportType === 'periodic' ? 'selected' : '' ?>>1. Periodic Report</option>
                  <option value="by_resource" <?= $reportType === 'by_resource' ? 'selected' : '' ?>>2. Booking per Resource</option>
                  <option value="by_client" <?= $reportType === 'by_client' ? 'selected' : '' ?>>3. Booking per Client</option>
                </select>
                <input type="date" name="report_start" class="form-control form-control-sm" value="<?= htmlspecialchars($reportStart) ?>">
                <span class="small text-muted">to</span>
                <input type="date" name="report_end" class="form-control form-control-sm" value="<?= htmlspecialchars($reportEnd) ?>">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Generate</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">Print Report</button>
              </form>
            </div>

            <div class="p-4 p-md-5">
              <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div>
                  <h5 class="fw-bold text-dark text-uppercase mb-1" style="letter-spacing: 0.05em;">
                    <?= $reportType === 'by_resource' ? 'Resource Utilization Breakdown' : ($reportType === 'by_client' ? 'Client / Organization Borrowing Activity' : 'Periodic Booking Activity Ledger') ?>
                  </h5>
                  <div class="text-muted small">Date Coverage: <strong><?= htmlspecialchars($reportStart) ?></strong> &mdash; <strong><?= htmlspecialchars($reportEnd) ?></strong></div>
                </div>
                <div class="badge bg-light text-dark border px-3 py-2">
                  Total Entries: <strong><?= count($reportData) ?></strong>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table-custom">
                  <?php if ($reportType === 'by_resource'): ?>
                    <thead>
                      <tr>
                        <th>Resource Code</th>
                        <th>Equipment & Model</th>
                        <th>Category</th>
                        <th>Total Inventory</th>
                        <th>Times Borrowed</th>
                        <th>Total Units Dispatched</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($reportData as $row): ?>
                        <tr>
                          <td><span class="badge-code"><?= htmlspecialchars($row['code']) ?></span></td>
                          <td><strong class="text-dark"><?= htmlspecialchars($row['name']) ?></strong> <span class="text-muted small">(<?= htmlspecialchars($row['model']) ?>)</span></td>
                          <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category']) ?></span></td>
                          <td><?= $row['total_qty'] ?> units</td>
                          <td><strong class="text-primary"><?= $row['times_booked'] ?></strong> bookings</td>
                          <td><strong class="text-success"><?= $row['units_borrowed'] ?: 0 ?></strong> unit(s)</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>

                  <?php elseif ($reportType === 'by_client'): ?>
                    <thead>
                      <tr>
                        <th>Student ID</th>
                        <th>Client Name</th>
                        <th>Student Organization</th>
                        <th>Total Bookings</th>
                        <th>Total Fees / Deposits (₱)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($reportData as $row): ?>
                        <tr>
                          <td><span class="badge-code"><?= htmlspecialchars($row['student_id']) ?></span></td>
                          <td><strong class="text-dark"><?= htmlspecialchars($row['full_name']) ?></strong></td>
                          <td><?= htmlspecialchars($row['organization_name']) ?></td>
                          <td><strong class="text-primary"><?= $row['total_bookings'] ?></strong> bookings</td>
                          <td><strong class="text-dark">₱<?= number_format($row['total_fees'] ?: 0, 2) ?></strong></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>

                  <?php else: ?>
                    <thead>
                      <tr>
                        <th>Booking Code</th>
                        <th>Borrower & Organization</th>
                        <th>Event & Venue</th>
                        <th>Schedule Dates</th>
                        <th>Resources Borrowed</th>
                        <th>Status</th>
                        <th>Payment</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($reportData as $row): ?>
                        <tr>
                          <td><span class="badge-code"><?= htmlspecialchars($row['booking_code']) ?></span></td>
                          <td>
                            <strong><?= htmlspecialchars($row['client_name']) ?></strong>
                            <div class="text-muted small"><?= htmlspecialchars($row['organization_name']) ?></div>
                          </td>
                          <td>
                            <strong><?= htmlspecialchars($row['event_name']) ?></strong>
                            <div class="text-muted small"><?= htmlspecialchars($row['event_location']) ?></div>
                          </td>
                          <td><?= date('M d, Y', strtotime($row['start_date'])) ?> to <?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                          <td><small class="fw-semibold text-dark"><?= htmlspecialchars($row['items_summary']) ?></small></td>
                          <td><span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span></td>
                          <td>₱<?= number_format($row['payment_amount'], 2) ?> (<?= htmlspecialchars($row['payment_status']) ?>)</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  <?php endif; ?>
                </table>
              </div>
            </div>

          </div>
        </section>

        <!-- ==========================================================
             TAB 6: PERSONAL ACCOUNT (PROFILE)
             ========================================================== -->
        <section id="tab-profile" class="tab-pane-content d-none">
          <div class="content-card" style="max-width: 680px;">
            <div class="content-card-header">
              <div>
                <h5 class="fw-bold mb-1 text-dark">Personal Officer Account</h5>
                <p class="text-muted small mb-0">Update your officer name, institutional contact information, or change password.</p>
              </div>
            </div>

            <div class="p-4 p-md-5">
              <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                  <label class="form-label">Username (System Identifier)</label>
                  <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($currentUser['username']) ?>" readonly disabled>
                </div>

                <div class="mb-3">
                  <label class="form-label">Full Legal Name *</label>
                  <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($currentUser['full_name']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label">Institutional Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label">Contact Number</label>
                    <input type="tel" name="contact_number" class="form-control" value="<?= htmlspecialchars($currentUser['contact_number'] ?? '') ?>">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label">Change Password <span class="text-muted fw-normal">(Leave blank to keep existing password)</span></label>
                  <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary-action px-4 py-2">
                  <span>Update Account Profile</span>
                </button>
              </form>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- ==========================================================
       USER-FRIENDLY & SPATIAL MODALS
       ========================================================== -->

  <!-- NEW BOOKING / DESK CHECKOUT MODAL (SPACIOUS 2-COLUMN MODAL-LG) -->
  <div class="modal fade" id="newBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        
        <div class="modal-header">
          <div>
            <h4 class="fw-bold mb-1 text-dark">Desk Checkout & Equipment Loan</h4>
            <p class="text-muted small mb-0">Record an equipment loan for a student or organization at the council desk.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="create_booking">
          
          <div class="modal-body">
            <div class="row g-4">
              
              <!-- LEFT COLUMN: Borrower Details -->
              <div class="col-md-6">
                <div class="form-section-title">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>1. Borrower Information</span>
                </div>

                <div class="mb-3">
                  <label class="form-label">Student ID *</label>
                  <input type="text" name="student_id" class="form-control font-monospace" placeholder="e.g. 2024-00192" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Borrower Full Legal Name *</label>
                  <input type="text" name="full_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Student Organization / Council *</label>
                  <input type="text" name="organization_name" class="form-control" placeholder="e.g. Confederates Engineering Society" required>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="jdelacruz@csc.edu.ph" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label">Contact Number *</label>
                    <input type="tel" name="contact_number" class="form-control" placeholder="09123456789" required>
                  </div>
                </div>
              </div>

              <!-- RIGHT COLUMN: Equipment & Schedule -->
              <div class="col-md-6">
                <div class="form-section-title">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                  <span>2. Equipment & Schedule</span>
                </div>

                <div class="mb-3">
                  <label class="form-label">Select Equipment Resource *</label>
                  <select name="resource_id" class="form-select" required>
                    <?php foreach ($resources as $r): ?>
                      <option value="<?= $r['id'] ?>" <?= ($r['available_qty'] <= 0 || $r['is_available'] == 0) ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($r['code']) ?> &bull; <?= htmlspecialchars($r['name']) ?> (Avail: <?= $r['available_qty'] ?>) <?= $r['fee_amount'] > 0 ? '- ₱' . $r['fee_amount'] : '- Free' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label">Quantity to Loan *</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label">Release Date *</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Expected Return Due Date *</label>
                  <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label">Event / Activity Name *</label>
                    <input type="text" name="event_name" class="form-control" placeholder="e.g. CSC General Assembly" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label">Event Venue / Location *</label>
                    <input type="text" name="event_location" class="form-control" placeholder="e.g. University Gym" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Purpose & Planned Use *</label>
                  <textarea name="purpose" class="form-control" rows="2" placeholder="Briefly describe purpose of loan..." required></textarea>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-action px-4 py-2">
              <span>Issue Loan & Generate Gate Pass &rarr;</span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- ADD / EDIT RESOURCE MODAL (ADMIN ONLY) -->
  <?php if ($isAdmin): ?>
    <div class="modal fade" id="resourceModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="fw-bold mb-0 text-dark" id="resourceModalTitle">Equipment Resource Details</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <form id="resourceForm" method="POST" action="index.php">
            <input type="hidden" name="action" value="save_resource">
            <input type="hidden" id="resId" name="id" value="0">
            
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Resource Code *</label>
                  <input type="text" id="resCode" name="code" class="form-control font-monospace" placeholder="e.g. SPK-001" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Category *</label>
                  <select id="resCategory" name="category" class="form-select">
                    <option value="Audio & Visual">Audio & Visual (Speakers, Mics)</option>
                    <option value="Sports & Recreation">Sports & Recreation</option>
                    <option value="Event & Logistics">Event & Logistics</option>
                    <option value="Electronics & Safety">Electronics & Safety</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Equipment Name *</label>
                  <input type="text" id="resName" name="name" class="form-control" placeholder="e.g. Portable PA Bluetooth Speaker" required>
                </div>

                <div class="col-sm-6">
                  <label class="form-label">Brand / Model</label>
                  <input type="text" id="resModel" name="model" class="form-control" placeholder="e.g. JBL EON One Pro">
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Total Quantity in Inventory *</label>
                  <input type="number" id="resTotalQty" name="total_qty" class="form-control" min="1" value="1" required>
                </div>

                <div class="col-sm-6">
                  <label class="form-label">Fee / Deposit Type</label>
                  <select id="resFeeType" name="fee_type" class="form-select">
                    <option value="Free">Free</option>
                    <option value="Deposit Required">Deposit Required</option>
                    <option value="Rental Fee">Rental Fee</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Fee Amount (₱)</label>
                  <input type="number" id="resFeeAmount" name="fee_amount" class="form-control" min="0" value="0.00" step="50">
                </div>

                <div class="col-sm-6">
                  <label class="form-label">Physical Condition</label>
                  <select id="resCondition" name="condition_status" class="form-select">
                    <option value="Excellent">Excellent</option>
                    <option value="Good" selected>Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Storage Location</label>
                  <input type="text" id="resLocation" name="location" class="form-control" value="Council Room 204">
                </div>

                <div class="col-12">
                  <label class="form-label">Description / Equipment Inclusions</label>
                  <textarea id="resDesc" name="description" class="form-control" rows="2" placeholder="e.g. Includes power cable and two wireless mics..."></textarea>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="resAvail" name="is_available" value="1" checked>
                    <label class="form-check-label fw-semibold text-dark" for="resAvail">Mark item as available for borrowing</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary-action px-4 py-2">Save Equipment Resource</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- RESCHEDULE MODAL -->
  <div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="fw-bold mb-0 text-dark">Reschedule Booking: <span id="rescheduleCode" class="text-primary font-monospace"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="reschedule_booking">
          <input type="hidden" id="rescheduleId" name="id">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Event Name *</label>
              <input type="text" id="rescheduleEvent" name="event_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Event Venue *</label>
              <input type="text" id="rescheduleLocation" name="event_location" class="form-control" required>
            </div>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label">Release Date *</label>
                <input type="date" id="rescheduleStartDate" name="start_date" class="form-control" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label">Return Due Date *</label>
                <input type="date" id="rescheduleEndDate" name="end_date" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-action px-4">Save New Schedule</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- PAYMENT MODAL -->
  <div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="fw-bold mb-0 text-dark">Record Payment / Deposit: <span id="paymentCode" class="text-primary font-monospace"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="update_payment">
          <input type="hidden" id="paymentId" name="id">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Payment Status</label>
              <select id="paymentStatusSelect" name="payment_status" class="form-select">
                <option value="Free / Waived">Free / Waived</option>
                <option value="Pending Deposit">Pending Deposit</option>
                <option value="Deposit Paid">Deposit Paid</option>
                <option value="Paid">Paid</option>
                <option value="Refunded">Refunded</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Amount (₱)</label>
              <input type="number" id="paymentAmountInput" name="payment_amount" class="form-control" min="0" step="50">
            </div>
            <div class="mb-3">
              <label class="form-label">Official Receipt / Reference Details</label>
              <textarea id="paymentDetailsInput" name="payment_details" class="form-control" rows="2" placeholder="e.g. OR #10492 received by Desk Officer..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-action px-4">Save Payment Record</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- CANCEL MODAL -->
  <div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="fw-bold mb-0 text-danger">Cancel Booking Loan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="cancel_booking">
          <input type="hidden" id="cancelId" name="id">
          <div class="modal-body">
            <p class="text-muted small mb-3">Please specify a reason for cancellation. Equipment quantities will be automatically restored to the active inventory.</p>
            <div class="mb-3">
              <label class="form-label">Cancellation Reason *</label>
              <input type="text" name="reason" class="form-control" placeholder="e.g. Event cancelled by organizer" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
            <button type="submit" class="btn btn-danger px-4">Confirm Cancellation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- CLIENT MODAL -->
  <div class="modal fade" id="clientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="fw-bold mb-0 text-dark" id="clientModalTitle">Borrower Directory Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="clientForm" method="POST" action="index.php">
          <input type="hidden" name="action" value="save_client">
          <input type="hidden" id="cliId" name="id" value="0">
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label">Student ID *</label>
                <input type="text" id="cliStudentId" name="student_id" class="form-control font-monospace" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label">Full Legal Name *</label>
                <input type="text" id="cliFullName" name="full_name" class="form-control" required>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label">Email Address *</label>
                <input type="email" id="cliEmail" name="email" class="form-control" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label">Contact Number *</label>
                <input type="tel" id="cliContact" name="contact_number" class="form-control" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Organization / Council *</label>
              <input type="text" id="cliOrg" name="organization_name" class="form-control" required>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label">Organization Role</label>
                <input type="text" id="cliRole" name="role" class="form-control" value="Representative">
              </div>
              <div class="col-sm-6">
                <label class="form-label">Borrower Standing</label>
                <select id="cliStatus" name="status" class="form-select">
                  <option value="Active">Active (Good Standing)</option>
                  <option value="Suspended">Suspended</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-action px-4">Save Borrower Record</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- STAFF MODAL (ADMIN ONLY) -->
  <?php if ($isAdmin): ?>
    <div class="modal fade" id="staffModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="fw-bold mb-0 text-dark" id="staffModalTitle">Council Staff Account</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form id="staffForm" method="POST" action="index.php">
            <input type="hidden" name="action" value="save_staff">
            <input type="hidden" id="stfId" name="id" value="0">
            <div class="modal-body">
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label">Username *</label>
                  <input type="text" id="stfUsername" name="username" class="form-control" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Full Name *</label>
                  <input type="text" id="stfFullName" name="full_name" class="form-control" required>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label">Email Address *</label>
                  <input type="email" id="stfEmail" name="email" class="form-control" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Contact Number</label>
                  <input type="tel" id="stfContact" name="contact_number" class="form-control">
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label">System Role *</label>
                  <select id="stfRole" name="role" class="form-select">
                    <option value="staff">Staff (Desk Officer)</option>
                    <option value="admin">Administrator (Full Access)</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Status *</label>
                  <select id="stfStatus" name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Account Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary-action px-4">Save Officer Account</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php endif; // End of authenticated workspace ?>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Main Script -->
  <script src="script.js"></script>
</body>
</html>
