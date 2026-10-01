<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$searchQuery = clean_input($_GET['code'] ?? ($_GET['student_id'] ?? ''));
$requestData = null;
$errorMsg = '';

if (!empty($searchQuery)) {
    $db = get_db_connection();
    
    // Check by tracking code first
    $stmt = $db->prepare("SELECT r.*, b.student_id, b.full_name, b.email, b.contact_number, b.organization_name, b.role
                          FROM borrow_requests r
                          JOIN borrowers b ON r.borrower_id = b.id
                          WHERE r.tracking_code = ? LIMIT 1");
    $stmt->execute([$searchQuery]);
    $requestData = $stmt->fetch();

    if ($requestData) {
        $itemStmt = $db->prepare("SELECT bi.*, i.name as item_name, i.item_code, i.location, c.name as category_name
                                  FROM borrow_items bi
                                  JOIN items i ON bi.item_id = i.id
                                  JOIN categories c ON i.category_id = c.id
                                  WHERE bi.borrow_request_id = ?");
        $itemStmt->execute([$requestData['id']]);
        $requestData['items'] = $itemStmt->fetchAll();
    } else {
        // Fallback: check by student_id
        $stmt2 = $db->prepare("SELECT r.*, b.student_id, b.full_name, b.organization_name
                               FROM borrow_requests r
                               JOIN borrowers b ON r.borrower_id = b.id
                               WHERE b.student_id = ?
                               ORDER BY r.created_at DESC");
        $stmt2->execute([$searchQuery]);
        $studentRequests = $stmt2->fetchAll();

        if (empty($studentRequests)) {
            $errorMsg = "No borrowing records found for '{$searchQuery}'.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Reservation &bull; <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/></svg>">
</head>
<body>

  <!-- Navigation -->
  <nav class="navbar">
    <div class="container nav-container">
      <a href="index.php" class="brand-logo">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
        </div>
        <div class="brand-text">
          <h1>Confed Borrowing</h1>
          <span>CSO Asset Portal</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="index.php">Equipment Catalog</a></li>
        <li><a href="my-requests.php" class="active">Track Request</a></li>
        <li><a href="terms.html">Borrowing Policy</a></li>
        <li><a href="admin.php">Officer Portal</a></li>
      </ul>
    </div>
  </nav>

  <main class="container" style="padding: 40px 24px 80px; max-width: 900px;">
    <div style="text-align: center; margin-bottom: 36px;">
      <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">Track Equipment Reservation</h1>
      <p style="color: #64748b; font-size: 15px; margin-top: 6px;">Enter your official tracking code (e.g. CFB-2026-8941) or your Student ID number.</p>
    </div>

    <!-- Search Form -->
    <div style="background: #fff; padding: 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); margin-bottom: 32px;">
      <form method="GET" action="my-requests.php" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
          <input type="text" name="code" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control" placeholder="e.g. CSC-2026-8941 or 2024-00142" required style="font-size: 15px; padding: 12px 16px;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <span>Find Reservation</span>
        </button>
      </form>
    </div>

    <?php if (!empty($errorMsg)): ?>
      <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 20px; border-radius: 12px; text-align: center;">
        <p style="font-weight: 700;"><?= htmlspecialchars($errorMsg) ?></p>
        <p style="font-size: 13px; margin-top: 4px; color: #b91c1c;">Please verify your tracking code from your submission confirmation.</p>
      </div>
    <?php endif; ?>

    <?php if ($requestData): ?>
      <!-- Single Request Details Card -->
      <div class="data-card" style="box-shadow: var(--shadow-md);">
        <div class="data-card-header" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
          <div>
            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Official Tracking Reference</span>
            <h3 style="font-size: 22px; font-family: monospace; color: #1e40af; margin-top: 2px;">
              <?= htmlspecialchars($requestData['tracking_code']) ?>
            </h3>
          </div>
          <div style="display: flex; align-items: center; gap: 12px;">
            <?= get_status_badge($requestData['status']) ?>
            <a href="receipt.php?code=<?= urlencode($requestData['tracking_code']) ?>" target="_blank" class="btn btn-sm btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
              <span>Print Gate Pass</span>
            </a>
          </div>
        </div>

        <div style="padding: 28px;">
          <!-- Visual Progression Steps -->
          <?php
            $statusOrder = ['Pending' => 1, 'Approved' => 2, 'Released' => 3, 'Returned' => 4];
            $currentStep = $statusOrder[$requestData['status']] ?? 1;
          ?>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 32px; text-align: center;">
            <div style="padding: 12px; border-radius: 8px; background: <?= $currentStep >= 1 ? '#dbeafe' : '#f1f5f9' ?>; color: <?= $currentStep >= 1 ? '#1e40af' : '#64748b' ?>;">
              <div style="font-weight: 700; font-size: 13px;">1. Submitted</div>
              <small style="font-size: 11px;">Awaiting Review</small>
            </div>
            <div style="padding: 12px; border-radius: 8px; background: <?= $currentStep >= 2 ? '#dbeafe' : '#f1f5f9' ?>; color: <?= $currentStep >= 2 ? '#1e40af' : '#64748b' ?>;">
              <div style="font-weight: 700; font-size: 13px;">2. Approved</div>
              <small style="font-size: 11px;">Ready for Pickup</small>
            </div>
            <div style="padding: 12px; border-radius: 8px; background: <?= $currentStep >= 3 ? '#d1fae5' : '#f1f5f9' ?>; color: <?= $currentStep >= 3 ? '#065f46' : '#64748b' ?>;">
              <div style="font-weight: 700; font-size: 13px;">3. Released</div>
              <small style="font-size: 11px;">In Borrower Custody</small>
            </div>
            <div style="padding: 12px; border-radius: 8px; background: <?= $currentStep >= 4 ? '#f3f4f6' : '#f1f5f9' ?>; color: <?= $currentStep >= 4 ? '#111827' : '#64748b' ?>;">
              <div style="font-weight: 700; font-size: 13px;">4. Returned</div>
              <small style="font-size: 11px;">Inspected & Cleared</small>
            </div>
          </div>

          <!-- Metadata Grid -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;">
            <div>
              <span style="font-size: 12px; color: #64748b; font-weight: 600;">BORROWER PARTICULARS</span>
              <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?= htmlspecialchars($requestData['full_name']) ?></p>
              <p style="font-size: 13px; color: #475569;"><?= htmlspecialchars($requestData['student_id']) ?> &bull; <?= htmlspecialchars($requestData['role']) ?></p>
              <p style="font-size: 13px; color: #475569;"><?= htmlspecialchars($requestData['organization_name']) ?></p>
              <p style="font-size: 13px; color: #475569;"><?= htmlspecialchars($requestData['contact_number']) ?> &bull; <?= htmlspecialchars($requestData['email']) ?></p>
            </div>

            <div>
              <span style="font-size: 12px; color: #64748b; font-weight: 600;">EVENT & DATES</span>
              <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?= htmlspecialchars($requestData['event_name']) ?></p>
              <p style="font-size: 13px; color: #475569;"><strong>Venue:</strong> <?= htmlspecialchars($requestData['event_location']) ?></p>
              <p style="font-size: 13px; color: #475569;"><strong>Borrow Date:</strong> <?= format_display_date($requestData['borrow_date']) ?></p>
              <p style="font-size: 13px; color: #475569;"><strong>Expected Return:</strong> <?= format_display_date($requestData['expected_return_date']) ?></p>
              <?php if (!empty($requestData['actual_return_date'])): ?>
                <p style="font-size: 13px; color: #059669; font-weight: 700;"><strong>Actual Return:</strong> <?= format_display_date($requestData['actual_return_date']) ?></p>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($requestData['admin_notes'])): ?>
            <div style="background: #f8fafc; border-left: 4px solid #2563eb; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 13px;">
              <strong>Officer Remarks:</strong> <?= htmlspecialchars($requestData['admin_notes']) ?>
              <?php if (!empty($requestData['approved_by'])): ?>
                <span style="color: #64748b;">(Reviewed by <?= htmlspecialchars($requestData['approved_by']) ?>)</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Equipment Items Table -->
          <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 12px;">Requested Equipment Items</h4>
          <div class="data-table-wrapper" style="border: 1px solid #e2e8f0; border-radius: 8px;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Item Code</th>
                  <th>Equipment Description</th>
                  <th>Category</th>
                  <th>Qty</th>
                  <th>Assigned Location</th>
                  <th>Condition</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($requestData['items'] as $it): ?>
                  <tr>
                    <td><strong style="font-family: monospace; color: #1e40af;"><?= htmlspecialchars($it['item_code']) ?></strong></td>
                    <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
                    <td><span style="font-size: 12px;"><?= htmlspecialchars($it['category_name']) ?></span></td>
                    <td><span style="font-weight: 700;"><?= $it['quantity'] ?></span></td>
                    <td style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($it['location']) ?></td>
                    <td><span style="font-size: 12px; color: #059669; font-weight: 600;"><?= htmlspecialchars($it['return_condition']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <?php elseif (!empty($studentRequests)): ?>
      <!-- Multiple requests for Student ID -->
      <div class="data-card">
        <div class="data-card-header">
          <h3>Borrowing History for Student ID: <?= htmlspecialchars($searchQuery) ?></h3>
        </div>
        <div class="data-table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tracking Code</th>
                <th>Event / Purpose</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($studentRequests as $sReq): ?>
                <tr>
                  <td><strong style="font-family: monospace; color: #1e40af;"><?= htmlspecialchars($sReq['tracking_code']) ?></strong></td>
                  <td><?= htmlspecialchars($sReq['event_name']) ?></td>
                  <td><?= format_display_date($sReq['borrow_date']) ?></td>
                  <td><?= format_display_date($sReq['expected_return_date']) ?></td>
                  <td><?= get_status_badge($sReq['status']) ?></td>
                  <td>
                    <a href="my-requests.php?code=<?= urlencode($sReq['tracking_code']) ?>" class="btn btn-sm btn-secondary">
                      View Status
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container" style="text-align: center;">
      <p style="font-size: 13px; color: #64748b;">Confederation of Student Organizations &bull; Student Activity Center, Room 204 &bull; Email: confed.borrowing@university.edu</p>
    </div>
  </footer>

</body>
</html>
