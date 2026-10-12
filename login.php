<?php
require_once 'config/db.php';
require_once 'config/session.php';
if ($currentUser) { header('Location: index.php'); exit; }
$alert = $_SESSION['alert'] ?? ['type' => '', 'message' => ''];
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>


  <!-- ==========================================================
       SPLIT-SCREEN LOGIN WITH CANVAS ANIMATION
       ========================================================== -->
  <div class="row g-0 vh-100">
    <!-- Left Panel: Brand & Animation -->
    <div class="col-md-6 position-relative d-flex align-items-center justify-content-center overflow-hidden login-left-panel" style="background-color: #1d4ed8;">
      
      
      <div class="text-white text-start p-5 position-relative w-100 ms-md-4 login-text-container" style="z-index: 1; max-width: 650px;">
        <!-- Logos (Replace src with actual image paths) -->
        <div class="d-flex align-items-center gap-4 mb-4 login-logo-gap">
           <!-- SU CCS LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden;">
              <span class="text-primary fw-bold text-center" style="font-size: 11px;">SU CCS<br>LOGO</span>
              <!-- <img src="path/to/su-ccs-logo.png" alt="SU CCS" style="width: 100%; height: auto;"> -->
           </div>
           <!-- CCS CONFEDERATE STUDENT COUNCIL LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden; padding: 2px;">
              <img src="assets/images/ccs_confederates_logo.png" alt="Confederates Student Council" style="width: 100%; height: 100%; object-fit: contain;">
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

            <!-- DELETE BEFORE DEPLOYMENT -->
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
            <!-- /DELETE BEFORE DEPLOYMENT -->

            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
