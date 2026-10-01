<?php
/**
 * ==========================================================
 * OFFICIAL GATE PASS & CUSTODY SLIP (receipt.php)
 * Confederates Student Council - Resource Management System
 * ==========================================================
 */
require_once __DIR__ . '/db.php';

$code = $_GET['code'] ?? '';
if (empty($code)) {
    die("Please provide a booking code. Example: receipt.php?code=CSC-2026-8941");
}

$db = get_db();
$stmt = $db->prepare("SELECT b.*, c.student_id, c.full_name, c.email, c.contact_number, c.organization_name, c.role as client_role, u.full_name as officer_name 
                      FROM bookings b 
                      JOIN clients c ON b.client_id = c.id 
                      LEFT JOIN users u ON b.created_by_user_id = u.id 
                      WHERE b.booking_code = ? LIMIT 1");
$stmt->execute([$code]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found with code: " . htmlspecialchars($code));
}

// Fetch borrowed items
$iStmt = $db->prepare("SELECT bi.*, r.code as resource_code, r.name as resource_name, r.model, r.location 
                       FROM booking_items bi 
                       JOIN resources r ON bi.resource_id = r.id 
                       WHERE bi.booking_id = ?");
$iStmt->execute([$booking['id']]);
$items = $iStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gate Pass &bull; <?= htmlspecialchars($booking['booking_code']) ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light py-4">

  <!-- Print Actions Bar -->
  <div class="container mb-3 no-print" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Return to Dashboard</a>
      <button onclick="window.print()" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
        Print Official Gate Pass
      </button>
    </div>
  </div>

  <!-- Gate Pass Box -->
  <div class="container bg-white p-4 p-sm-5 border rounded-4 shadow-sm receipt-box" style="max-width: 760px;">
    
    <div class="text-center border-bottom pb-4 mb-4">
      <div class="text-uppercase small fw-bold text-primary tracking-wide">Confederates Student Council (CSC)</div>
      <h3 class="fw-bold mb-1">Official Resource Gate Pass & Custody Slip</h3>
      <p class="text-muted small mb-2">Student Council Headquarters &bull; Equipment Custody Desk &bull; Room 204</p>
      <div class="d-inline-block bg-dark text-white font-monospace px-3 py-1 rounded small">
        <?= htmlspecialchars($booking['booking_code']) ?>
      </div>
    </div>

    <!-- Booking Metadata -->
    <div class="row g-3 mb-4 small">
      <div class="col-sm-6">
        <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Borrower / Client</span>
        <strong class="fs-6"><?= htmlspecialchars($booking['full_name']) ?></strong>
        <div class="text-muted"><?= htmlspecialchars($booking['student_id']) ?> &bull; <?= htmlspecialchars($booking['client_role']) ?></div>
        <div><?= htmlspecialchars($booking['organization_name']) ?></div>
        <div class="text-muted"><?= htmlspecialchars($booking['contact_number']) ?> &bull; <?= htmlspecialchars($booking['email']) ?></div>
      </div>

      <div class="col-sm-6">
        <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Activity & Schedule</span>
        <strong class="fs-6"><?= htmlspecialchars($booking['event_name']) ?></strong>
        <div class="text-muted">Venue: <?= htmlspecialchars($booking['event_location']) ?></div>
        <div>Release Date: <strong><?= htmlspecialchars($booking['start_date']) ?></strong></div>
        <div>Return Due: <strong><?= htmlspecialchars($booking['end_date']) ?></strong></div>
        <div>Status: <span class="badge bg-secondary"><?= htmlspecialchars($booking['status']) ?></span></div>
      </div>

      <div class="col-12 bg-light p-3 rounded-3 border">
        <div class="row g-2">
          <div class="col-sm-6">
            <span class="text-muted d-block" style="font-size: 11px;">Fee / Payment Status:</span>
            <strong><?= htmlspecialchars($booking['payment_status']) ?></strong> (₱<?= number_format($booking['payment_amount'], 2) ?>)
          </div>
          <div class="col-sm-6">
            <span class="text-muted d-block" style="font-size: 11px;">Receipt / Notes:</span>
            <span><?= htmlspecialchars($booking['payment_details'] ?: 'N/A') ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Authorized Equipment Items -->
    <div class="mb-4">
      <h6 class="fw-bold small text-uppercase text-muted mb-2">Authorized Equipment Manifest</h6>
      <table class="table table-bordered table-sm small align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Resource Code</th>
            <th>Equipment Name & Model</th>
            <th class="text-center">Qty</th>
            <th>Storage Location</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($item['resource_code']) ?></td>
              <td>
                <strong><?= htmlspecialchars($item['resource_name']) ?></strong>
                <small class="text-muted">(<?= htmlspecialchars($item['model']) ?>)</small>
              </td>
              <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
              <td class="text-muted"><?= htmlspecialchars($item['location']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Terms Undertaking -->
    <div class="border-top pt-3 text-muted small mb-5" style="font-size: 11px; line-height: 1.5;">
      <strong>BORROWER UNDERTAKING:</strong> The borrower agrees to take full custody of the equipment listed above in clean and operable condition. In accordance with the Confederates Student Council Policy, any damages, losses, or overdue returns are subject to replacement liability or fine.
    </div>

    <!-- Signatures -->
    <div class="row text-center pt-3">
      <div class="col-6">
        <div class="border-top border-dark pt-2 mx-3">
          <strong><?= htmlspecialchars($booking['full_name']) ?></strong><br>
          <small class="text-muted">Client / Borrower Signature</small>
        </div>
      </div>
      <div class="col-6">
        <div class="border-top border-dark pt-2 mx-3">
          <strong><?= htmlspecialchars($booking['officer_name'] ?: 'Council Custodian') ?></strong><br>
          <small class="text-muted">Releasing Officer Signature</small>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
