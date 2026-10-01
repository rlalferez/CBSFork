<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$currentUser = get_current_user_info();
$db = get_db_connection();

$feedbackMsg = '';
$feedbackType = 'success';

// Fetch fresh user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';

    if ($actionType === 'update_info') {
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $contactNumber = clean_input($_POST['contact_number'] ?? '');

        if (empty($fullName) || empty($email)) {
            $feedbackMsg = 'Full name and email are required.';
            $feedbackType = 'danger';
        } else {
            $up = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE id = ?");
            $up->execute([$fullName, $email, $contactNumber, $user['id']]);

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contactNumber;

            log_activity('Profile Updated', "User {$user['username']} updated personal info.", $fullName);
            $feedbackMsg = 'Personal account profile successfully updated!';
            $feedbackType = 'success';

            // Refresh user
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    } elseif ($actionType === 'change_password') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        if (!password_verify($currentPass, $user['password_hash'])) {
            $feedbackMsg = 'Current password is incorrect.';
            $feedbackType = 'danger';
        } elseif ($newPass !== $confirmPass) {
            $feedbackMsg = 'New passwords do not match.';
            $feedbackType = 'danger';
        } elseif (strlen($newPass) < 6) {
            $feedbackMsg = 'New password must be at least 6 characters.';
            $feedbackType = 'danger';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $up = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $up->execute([$hash, $user['id']]);

            log_activity('Password Changed', "User {$user['username']} changed their password.", $user['full_name']);
            $feedbackMsg = 'Password successfully updated!';
            $feedbackType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Personal Account &bull; <?= htmlspecialchars(APP_NAME) ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom Design System -->
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/></svg>">
</head>
<body class="bg-light min-vh-100 d-flex flex-column">

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
    <div class="container">
      <a href="admin.php" class="brand-logo text-decoration-none">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
        </div>
        <div class="brand-text ms-2">
          <h1 class="h6 mb-0 fw-bold"><?= htmlspecialchars(ORG_NAME) ?></h1>
          <span class="small text-primary fw-semibold">Personal Account Profile</span>
        </div>
      </a>
      <div class="ms-auto d-flex align-items-center gap-2">
        <a href="admin.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
        <a href="admin.php?action=logout" class="btn btn-outline-danger btn-sm">Sign Out</a>
      </div>
    </div>
  </nav>

  <main class="container my-5" style="max-width: 820px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="h4 fw-bold text-dark mb-1">Personal Account Settings</h2>
        <p class="text-muted small mb-0">Update your profile information and manage credentials.</p>
      </div>
      <div>
        <span class="badge <?= $user['role'] === 'admin' ? 'bg-primary' : 'bg-info text-dark' ?> fs-6 px-3 py-2 text-uppercase">
          <?= htmlspecialchars($user['role']) ?>
        </span>
      </div>
    </div>

    <?php if (!empty($feedbackMsg)): ?>
      <div class="alert alert-<?= $feedbackType ?> alert-dismissible fade show rounded-3 mb-4" role="alert">
        <?= htmlspecialchars($feedbackMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Profile Information Card -->
      <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h5 class="card-title fw-bold text-dark mb-3">Profile Information</h5>
            <form method="POST" action="profile.php">
              <input type="hidden" name="action_type" value="update_info">

              <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Username</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                <small class="text-muted">Username cannot be modified.</small>
              </div>

              <div class="mb-3">
                <label for="fullNameInput" class="form-label small fw-semibold text-dark">Full Legal Name *</label>
                <input type="text" id="fullNameInput" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
              </div>

              <div class="mb-3">
                <label for="emailInput" class="form-label small fw-semibold text-dark">Institutional Email *</label>
                <input type="email" id="emailInput" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
              </div>

              <div class="mb-4">
                <label for="contactInput" class="form-label small fw-semibold text-dark">Contact Number</label>
                <input type="tel" id="contactInput" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" placeholder="0917-000-0000">
              </div>

              <button type="submit" class="btn btn-primary fw-semibold px-4">
                Save Profile Changes
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Password Change Card -->
      <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h5 class="card-title fw-bold text-dark mb-3">Security & Password</h5>
            <form method="POST" action="profile.php">
              <input type="hidden" name="action_type" value="change_password">

              <div class="mb-3">
                <label for="currentPass" class="form-label small fw-semibold text-dark">Current Password *</label>
                <input type="password" id="currentPass" name="current_password" class="form-control" placeholder="••••••••" required>
              </div>

              <div class="mb-3">
                <label for="newPass" class="form-label small fw-semibold text-dark">New Password *</label>
                <input type="password" id="newPass" name="new_password" class="form-control" placeholder="Min. 6 chars" required>
              </div>

              <div class="mb-4">
                <label for="confirmPass" class="form-label small fw-semibold text-dark">Confirm New Password *</label>
                <input type="password" id="confirmPass" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
              </div>

              <button type="submit" class="btn btn-outline-primary fw-semibold w-100">
                Update Password
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-3 text-center text-muted small mt-auto border-top bg-white">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(ORG_NAME) ?> &bull; Student Council Headquarters
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
