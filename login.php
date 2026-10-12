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
       SPLIT-SCREEN LOGIN INTERFACE
       ========================================================== -->
  <div class="login-wrapper">
    <!-- Left Panel: Brand & Organization Info -->
    <div class="login-brand-panel">
      <div class="login-brand-content">
        <!-- Logos -->
        <div class="login-logos">
           <!-- SU CCS Logo Badge -->
           <div class="login-logo-badge">
              <img src="assets/images/imgCCS.png" alt="SU CCS Logo" class="login-logo-image">
           </div>
           <!-- Confederates Student Council Logo Badge -->
           <div class="login-logo-badge">
              <img src="assets/images/ccs_confederates_logo.png" alt="Confederates Student Council" class="login-logo-image">
           </div>
        </div>
        
        <!-- Brand Title & Subtitle -->
        <div class="login-brand-headings">
          <h1 class="login-brand-title">CONFEDERATES</h1>
          <h1 class="login-brand-title-sub">STUDENT COUNCIL</h1>
          <h4 class="login-brand-subtitle">ITEM MANAGEMENT AND LENDING SYSTEM</h4>
        </div>
      </div>
    </div>
    
    <!-- Right Panel: Authentication Form -->
    <div class="login-form-panel">
      <div class="login-form-container">
        <?php if (!empty($alert['message'])): ?>
          <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show login-alert">
            <span class="login-alert-text"><?= htmlspecialchars($alert['message']) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="login-card">
          <h4 class="login-card-title">Sign In</h4>
          
          <form method="POST" action="actions/auth_action.php" class="login-form">
            <input type="hidden" name="action" value="login">
            
            <div class="login-form-group">
              <label class="login-form-label" for="loginEmail">Email</label>
              <input type="email" id="loginEmail" name="email" class="form-control" placeholder="user@csc.edu.ph" required autofocus>
            </div>
            
            <div class="login-form-group">
              <label class="login-form-label" for="loginPassword">Password</label>
              <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <!-- DELETE BEFORE DEPLOYMENT -->
            <!-- Quick Sign-In Buttons (Testing Purposes) -->
            <div class="login-quick-signin">
              <span class="login-quick-signin-label">Quick Sign-In (Testing):</span>
              <div class="login-quick-signin-buttons">
                <!-- Council role is effectively the admin/core -->
                <button type="button" class="btn btn-outline-primary btn-sm login-quick-btn" onclick="document.getElementById('loginEmail').value='admin@csc.edu.ph'; document.getElementById('loginPassword').value='admin123';">
                  Council
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm login-quick-btn" onclick="document.getElementById('loginEmail').value='staff@csc.edu.ph'; document.getElementById('loginPassword').value='staff123';">
                  Committee
                </button>
              </div>
            </div>
            <!-- /DELETE BEFORE DEPLOYMENT -->

            <button type="submit" class="btn btn-primary-action login-submit-btn">Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
