<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Require login (Redirects to login.php if not authenticated)
require_login();

$currentUser = get_current_user_info();
$isAdmin = is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Council Resource Console &bull; <?= htmlspecialchars(APP_NAME) ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom Design System -->
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/></svg>">
  <style>
    .nav-tab-link {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 8px;
      color: #475569;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }
    .nav-tab-link:hover, .nav-tab-link.active {
      color: #1e40af;
      background-color: #eff6ff;
    }
  </style>
</head>
<body class="bg-light">

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom sticky-top py-2 px-3 shadow-sm">
    <div class="container-fluid">
      <a href="admin.php" class="brand-logo text-decoration-none">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
        </div>
        <div class="brand-text ms-2">
          <h1 class="h6 mb-0 fw-bold"><?= htmlspecialchars(ORG_NAME) ?></h1>
          <span class="small text-primary fw-semibold">Resource Management Console</span>
        </div>
      </a>

      <div class="d-flex align-items-center gap-3 ms-auto">
        <a href="index.php" target="_blank" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex align-items-center gap-1">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          <span>View Public Portal</span>
        </a>

        <!-- User Profile Pill -->
        <div class="dropdown">
          <button class="btn btn-light btn-sm dropdown-toggle border d-flex align-items-center gap-2 px-3 py-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-info text-dark' ?> text-uppercase" style="font-size: 10px;">
              <?= htmlspecialchars($currentUser['role']) ?>
            </span>
            <span class="fw-semibold small text-dark"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><h6 class="dropdown-header">Signed in as <strong><?= htmlspecialchars($currentUser['username']) ?></strong></h6></li>
            <li><a class="dropdown-item small" href="profile.php">Manage Personal Account</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger" href="admin.php?action=logout">Sign Out</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Management Shell -->
  <div class="container-fluid">
    <div class="row">

      <!-- Left Sidebar Nav Tabs -->
      <aside class="col-lg-2 col-md-3 bg-white border-end min-vh-100 p-3">
        <div class="text-uppercase small text-muted fw-bold mb-3 px-2" style="font-size: 11px; letter-spacing: 0.05em;">
          Functionalities
        </div>

        <nav class="d-flex flex-column gap-1">
          <a class="nav-tab-link active" data-tab="bookings">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Bookings & Schedule</span>
          </a>

          <a class="nav-tab-link" data-tab="resources">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
            <span>Resources Availability</span>
          </a>

          <a class="nav-tab-link" data-tab="clients">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Client Records</span>
          </a>

          <?php if ($isAdmin): ?>
            <a class="nav-tab-link" data-tab="staff">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span>Staff Management</span>
            </a>
          <?php endif; ?>

          <a class="nav-tab-link" data-tab="reports">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Booking Reports</span>
          </a>

          <a class="nav-tab-link" data-tab="logs">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Activity Audit</span>
          </a>
        </nav>

        <div class="mt-4 p-3 bg-light rounded-3 border small">
          <span class="fw-bold d-block text-primary mb-1">Council Role:</span>
          <span>Logged in as <strong><?= htmlspecialchars($currentUser['role']) ?></strong> (<?= htmlspecialchars($currentUser['username']) ?>).</span>
        </div>
      </aside>

      <!-- Main Content Container -->
      <main class="col-lg-10 col-md-9 p-4">

        <!-- Top Metrics Cards -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div>
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Total Resources</div>
                  <h3 class="h4 fw-bold mb-0" id="metricTotalItems">—</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Active on Loan</div>
                  <h3 class="h4 fw-bold mb-0 text-success" id="metricBorrowed">—</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Pending Approval</div>
                  <h3 class="h4 fw-bold mb-0 text-warning-emphasis" id="metricPending">—</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                </div>
                <div>
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Overdue Alerts</div>
                  <h3 class="h4 fw-bold mb-0 text-danger" id="metricOverdue">—</h3>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ==========================================
             TAB 1: BOOKINGS & SCHEDULE (CRUD)
             ========================================== -->
        <section id="tab-bookings" class="admin-tab-pane">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h5 class="fw-bold mb-0">Resource Bookings & Schedule</h5>
                <small class="text-muted">Search, reserve resources, change booking dates, set payments, and process returns.</small>
              </div>

              <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="bookingSearchInput" class="form-control form-control-sm" placeholder="Search tracking, client, org..." style="width: 220px;">
                <select id="bookingStatusFilter" class="form-select form-select-sm" style="width: 150px;">
                  <option value="all">All Statuses</option>
                  <option value="Pending">Pending Review</option>
                  <option value="Approved">Approved</option>
                  <option value="Released">Released (On Loan)</option>
                  <option value="Returned">Returned (Cleared)</option>
                  <option value="Cancelled">Cancelled</option>
                  <option value="Overdue">Overdue Alerts</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.openNewBookingModal()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>Book Resource</span>
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Tracking Code</th>
                    <th>Client / Organization</th>
                    <th>Schedule Dates</th>
                    <th>Booking Status</th>
                    <th>Payment / Fee Status</th>
                    <th>Actions & Management</th>
                  </tr>
                </thead>
                <tbody id="bookingsTableBody" class="small">
                  <!-- Populated by admin.js -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==========================================
             TAB 2: RESOURCES AVAILABILITY (CRUD)
             ========================================== -->
        <section id="tab-resources" class="admin-tab-pane" style="display: none;">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h5 class="fw-bold mb-0">Resource Inventory & Availability</h5>
                <small class="text-muted">Manage speakers, sports equipment, stage gear, fee schedules, and live availability toggles.</small>
              </div>
              <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.openResourceModal(0)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Add New Resource</span>
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Resource Code</th>
                    <th>Name / Model</th>
                    <th>Category</th>
                    <th>Stock (Avail / Total)</th>
                    <th>Fee / Deposit</th>
                    <th>Availability Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="resourcesTableBody" class="small">
                  <!-- Populated by admin.js -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==========================================
             TAB 3: CLIENT RECORDS (CRUD)
             ========================================== -->
        <section id="tab-clients" class="admin-tab-pane" style="display: none;">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h5 class="fw-bold mb-0">Client & Borrower Directory</h5>
                <small class="text-muted">Maintain client profiles, student organizations, contacts, and account standings.</small>
              </div>
              <div class="d-flex gap-2">
                <input type="text" id="clientSearchInput" class="form-control form-control-sm" placeholder="Search clients..." style="width: 200px;">
                <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.openClientModal(0)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>Add Client</span>
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Organization & Role</th>
                    <th>Contact & Email</th>
                    <th>Standing</th>
                    <th>Bookings Count</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="clientsTableBody" class="small">
                  <!-- Populated by admin.js -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==========================================
             TAB 4: STAFF RECORDS (CRUD) [ADMIN ONLY]
             ========================================== -->
        <?php if ($isAdmin): ?>
          <section id="tab-staff" class="admin-tab-pane" style="display: none;">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
              <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h5 class="fw-bold mb-0">Council Staff & Officer Accounts</h5>
                  <small class="text-muted">Manage system administrators, council equipment officers, and account roles.</small>
                </div>
                <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.openStaffModal(0)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>Add Staff Member</span>
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light small text-uppercase text-muted">
                    <tr>
                      <th>Username</th>
                      <th>Staff Name</th>
                      <th>Email & Phone</th>
                      <th>Role</th>
                      <th>Account Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="staffTableBody" class="small">
                    <!-- Populated by admin.js -->
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- ==========================================
             TAB 5: BOOKING REPORTS (PERIODIC, RESOURCE, CLIENT)
             ========================================== -->
        <section id="tab-reports" class="admin-tab-pane" style="display: none;">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h5 class="fw-bold mb-0">Resource Booking Reports</h5>
                <small class="text-muted">Generate official periodic reports, utilization per resource, and client booking history.</small>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="reportTypeSelect" class="form-select form-select-sm" style="width: 190px;">
                  <option value="periodic">1. Periodic Reporting</option>
                  <option value="by_resource">2. Booking per Resource</option>
                  <option value="by_client">3. Booking per Client</option>
                </select>
                <input type="date" id="reportStartDate" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                <span class="small text-muted">to</span>
                <input type="date" id="reportEndDate" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>">
                <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.generateReports()">
                  <span>Generate</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                  <span>Print Report</span>
                </button>
              </div>
            </div>

            <div class="p-4" id="reportContainer">
              <!-- Rendered dynamically by admin.js -->
            </div>
          </div>
        </section>

        <!-- ==========================================
             TAB 6: AUDIT TRAIL LOGS
             ========================================== -->
        <section id="tab-logs" class="admin-tab-pane" style="display: none;">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
              <h5 class="fw-bold mb-0">System Activity Audit Trail</h5>
              <small class="text-muted">Immutable ledger of all booking releases, returns, payments, and inventory modifications.</small>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Actor / Staff</th>
                  </tr>
                </thead>
                <tbody id="logsTableBody" class="small">
                  <!-- Populated by admin.js -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- ==========================================
       MODAL 1: ADD/EDIT RESOURCE (CRUD)
       ========================================== -->
  <div class="modal fade" id="resourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="resourceModalTitle">Resource Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="resourceForm">
          <input type="hidden" id="resourceId" name="id" value="0">
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Resource Code *</label>
                <input type="text" id="resourceCode" name="item_code" class="form-control form-control-sm" placeholder="e.g. CSC-SPK-03" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Category *</label>
                <select id="resourceCategory" name="category_id" class="form-select form-select-sm" required>
                  <option value="1">Audio & Visual (Speakers/Mics)</option>
                  <option value="2">Sports & Recreation</option>
                  <option value="3">Event & Stage Logistics</option>
                  <option value="4">Electronics & Cables</option>
                  <option value="5">Office & Protocol Assets</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Resource Name *</label>
                <input type="text" id="resourceName" name="name" class="form-control form-control-sm" placeholder="e.g. Electro-Voice 12-inch Powered PA Speaker" required>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Model / Specification</label>
                <input type="text" id="resourceModel" name="model" class="form-control form-control-sm" placeholder="e.g. ZLX-12BT">
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Storage Location</label>
                <input type="text" id="resourceLocation" name="location" class="form-control form-control-sm" placeholder="e.g. Audio Rack 3">
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Total Quantity Owned *</label>
                <input type="number" id="resourceTotalQty" name="total_qty" class="form-control form-control-sm" min="1" value="1" required>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Condition Status</label>
                <select id="resourceCondition" name="condition_status" class="form-select form-select-sm">
                  <option value="Excellent">Excellent (New / Pristine)</option>
                  <option value="Good" selected>Good (Functional)</option>
                  <option value="Fair">Fair (Operational Wear)</option>
                  <option value="Under Maintenance">Under Maintenance</option>
                </select>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Fee / Policy Type</label>
                <select id="resourceFeeType" name="fee_type" class="form-select form-select-sm">
                  <option value="Free">Free / Waived</option>
                  <option value="Deposit Required">Deposit Required</option>
                  <option value="Rental Fee">Rental Fee</option>
                </select>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Fee / Deposit Amount (₱)</label>
                <input type="number" id="resourceFeeAmount" name="fee_amount" class="form-control form-control-sm" min="0" step="50" value="0.00">
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Description & Accessories</label>
                <textarea id="resourceDesc" name="description" rows="2" class="form-control form-control-sm" placeholder="Includes power cable, cover bag, tripod stand..."></textarea>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="resourceAvailableToggle" name="is_available" value="1" checked>
                  <label class="form-check-label small fw-semibold text-dark" for="resourceAvailableToggle">
                    Active & Available for Booking
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Resource</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================================
       MODAL 2: CHANGE SCHEDULE & BOOKING DETAILS
       ========================================== -->
  <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="scheduleModalTitle">Reschedule Booking</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="scheduleForm">
          <input type="hidden" id="scheduleRequestId" name="request_id">
          <div class="modal-body p-4">
            <p class="small text-muted">Update reservation dates, event title, or activity venue.</p>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Event Title *</label>
              <input type="text" id="scheduleEventName" name="event_name" class="form-control form-control-sm" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Event Venue *</label>
              <input type="text" id="scheduleEventLocation" name="event_location" class="form-control form-control-sm" required>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Release Date *</label>
                <input type="date" id="scheduleBorrowDate" name="borrow_date" class="form-control form-control-sm" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Expected Return Date *</label>
                <input type="date" id="scheduleReturnDate" name="expected_return_date" class="form-control form-control-sm" required>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save New Schedule</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================================
       MODAL 3: PAYMENT / DEPOSIT STATUS
       ========================================== -->
  <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold">Update Payment & Fee Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="paymentForm">
          <input type="hidden" id="paymentRequestId" name="request_id">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Payment / Deposit Status *</label>
              <select id="paymentStatusSelect" name="payment_status" class="form-select form-select-sm" required>
                <option value="Free / Waived">Free / Waived (Council Activity)</option>
                <option value="Pending Deposit">Pending Deposit</option>
                <option value="Deposit Paid">Deposit Paid (Held in Trust)</option>
                <option value="Paid">Rental Fee Paid</option>
                <option value="Refunded">Deposit Refunded</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Amount (₱)</label>
              <input type="number" id="paymentAmountInput" name="payment_amount" class="form-control form-control-sm" min="0" step="50" value="0.00">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Official Receipt / Reference / Notes</label>
              <textarea id="paymentDetailsInput" name="payment_details" rows="2" class="form-control form-control-sm" placeholder="e.g. Official Receipt #OR-9912. Cash deposited with Treasurer."></textarea>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Payment Details</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================================
       MODAL 4: CANCEL BOOKING MODAL
       ========================================== -->
  <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold text-danger">Cancel Booking</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="cancelForm">
          <input type="hidden" id="cancelRequestId" name="request_id">
          <div class="modal-body p-4">
            <p class="small text-dark mb-2">Are you sure you want to cancel booking <strong id="cancelTrackingCode"></strong>?</p>
            <p class="small text-muted">Any released equipment will be restored back to live inventory stock.</p>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Cancellation Reason *</label>
              <input type="text" id="cancelReasonInput" name="reason" class="form-control form-control-sm" placeholder="e.g. Event postponed / Client requested cancellation" required>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-danger btn-sm">Confirm Cancellation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================================
       MODAL 5: CLIENT RECORD (CRUD)
       ========================================== -->
  <div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="clientModalTitle">Client Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="clientForm">
          <input type="hidden" id="clientId" name="id" value="0">
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Student ID *</label>
                <input type="text" id="clientStudentId" name="student_id" class="form-control form-control-sm" placeholder="e.g. 2024-00192" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Full Legal Name *</label>
                <input type="text" id="clientFullName" name="full_name" class="form-control form-control-sm" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Institutional Email *</label>
                <input type="email" id="clientEmail" name="email" class="form-control form-control-sm" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Contact Number *</label>
                <input type="tel" id="clientContact" name="contact_number" class="form-control form-control-sm" required>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Student Organization / Council *</label>
                <input type="text" id="clientOrg" name="organization_name" class="form-control form-control-sm" placeholder="e.g. Debate & Forensics Guild" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Designation / Role</label>
                <input type="text" id="clientRole" name="role" class="form-control form-control-sm" value="Student">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold">Account Standing</label>
                <select id="clientStatus" name="status" class="form-select form-select-sm">
                  <option value="Active" selected>Active (Good Standing)</option>
                  <option value="Suspended">Suspended (Delinquent)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Client</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================================
       MODAL 6: STAFF RECORD (CRUD) [ADMIN ONLY]
       ========================================== -->
  <?php if ($isAdmin): ?>
    <div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold" id="staffModalTitle">Council Staff Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="staffForm">
            <input type="hidden" id="staffId" name="id" value="0">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Username *</label>
                  <input type="text" id="staffUsername" name="username" class="form-control form-control-sm" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Full Legal Name *</label>
                  <input type="text" id="staffFullName" name="full_name" class="form-control form-control-sm" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Role *</label>
                  <select id="staffRole" name="role" class="form-select form-select-sm" required>
                    <option value="staff" selected>Council Staff (Operational)</option>
                    <option value="admin">Administrator (Full Control)</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Status *</label>
                  <select id="staffStatus" name="status" class="form-select form-select-sm" required>
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Email *</label>
                  <input type="email" id="staffEmail" name="email" class="form-control form-control-sm" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Password (Leave blank to keep existing)</label>
                  <input type="password" id="staffPassword" name="password" class="form-control form-control-sm" placeholder="••••••••">
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Save Staff Record</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Portal Script -->
  <script src="js/admin.js"></script>
</body>
</html>
