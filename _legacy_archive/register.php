<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$errorMsg = '';
$successMsg = '';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = clean_input($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $contactNumber = clean_input($_POST['contact_number'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $errorMsg = 'Please fill out all required fields.';
    } elseif ($password !== $confirmPassword) {
        $errorMsg = 'Passwords do not match. Please verify.';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'Password must be at least 6 characters long.';
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errorMsg = "Username '{$username}' is already taken. Please choose another.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'staff', 'active', datetime('now'))");
            $ins->execute([$username, $hash, $fullName, $email, $contactNumber]);
            $newUserId = $db->lastInsertId();

            // Auto-login
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = 'staff';
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contactNumber;

            log_activity('Staff Registered', "New staff account created online: {$username} ({$fullName})", $fullName);
            header('Location: admin.php?welcome=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Account Registration &bull; <?= htmlspecialchars(APP_NAME) ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom Design System -->
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/></svg>">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
    <div class="container">
      <a href="index.php" class="brand-logo text-decoration-none">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
        </div>
        <div class="brand-text ms-2">
          <h1 class="h6 mb-0 fw-bold"><?= htmlspecialchars(ORG_NAME) ?></h1>
          <span class="small text-primary fw-semibold">Staff Registration</span>
        </div>
      </a>
      <div class="ms-auto">
        <a href="login.php" class="btn btn-outline-primary btn-sm">Already Have an Account? Sign In</a>
      </div>
    </div>
  </nav>

  <main class="container my-auto py-5" style="max-width: 560px;">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="p-4 p-sm-5 bg-white">
        <div class="text-center mb-4">
          <h2 class="h4 fw-bold text-dark mb-1">Create Council Staff Account</h2>
          <p class="text-muted small">Register as an authorized student council member or equipment custodian.</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4" role="alert">
            <?= htmlspecialchars($errorMsg) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
          <div class="row g-3">
            <div class="col-12">
              <label for="fullName" class="form-label small fw-semibold text-dark">Full Legal Name *</label>
              <input type="text" id="fullName" name="full_name" class="form-control" placeholder="e.g. Maria Clara Santos" required>
            </div>

            <div class="col-sm-6">
              <label for="username" class="form-label small fw-semibold text-dark">Desired Username *</label>
              <input type="text" id="username" name="username" class="form-control" placeholder="e.g. maria_council" required>
            </div>

            <div class="col-sm-6">
              <label for="contactNumber" class="form-label small fw-semibold text-dark">Mobile Contact *</label>
              <input type="tel" id="contactNumber" name="contact_number" class="form-control" placeholder="0917-000-0000" required>
            </div>

            <div class="col-12">
              <label for="email" class="form-label small fw-semibold text-dark">Institutional / University Email *</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="maria.santos@university.edu" required>
            </div>

            <div class="col-sm-6">
              <label for="password" class="form-label small fw-semibold text-dark">Password *</label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
            </div>

            <div class="col-sm-6">
              <label for="confirmPassword" class="form-label small fw-semibold text-dark">Confirm Password *</label>
              <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>

            <div class="col-12 mt-4">
              <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                Complete Registration & Sign In
              </button>
            </div>
          </div>
        </form>

        <div class="text-center mt-4">
          <p class="small text-muted mb-0">
            Already registered? <a href="login.php" class="fw-bold text-primary text-decoration-none">Log in here</a>
          </p>
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
