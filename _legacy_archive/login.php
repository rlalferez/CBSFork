<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$loginError = '';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $loginError = 'Please enter both username and password.';
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $loginError = 'Your council account is currently inactive. Please contact the administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['contact_number'] = $user['contact_number'];

                log_activity('User Login', "User '{$user['username']}' logged in ({$user['role']}).", $user['full_name']);
                header('Location: admin.php');
                exit;
            }
        } else {
            $loginError = 'Invalid credentials. Please verify your username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Council Officer & Staff Login &bull; <?= htmlspecialchars(APP_NAME) ?></title>
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
          <span class="small text-primary fw-semibold">Resource Management Portal</span>
        </div>
      </a>
      <div class="ms-auto">
        <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">Public Catalog</a>
        <a href="register.php" class="btn btn-outline-primary btn-sm">Create Staff Account</a>
      </div>
    </div>
  </nav>

  <main class="container my-auto py-5" style="max-width: 480px;">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="p-4 p-sm-5 bg-white">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <h2 class="h4 fw-bold text-dark mb-1">Council Authentication</h2>
          <p class="text-muted small">Sign in to manage resource allocations, clients, and bookings.</p>
          <div class="d-flex justify-content-center gap-2 mt-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Administrator</span>
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Operational Staff</span>
          </div>
        </div>

        <?php if (!empty($loginError)): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4" role="alert">
            <?= htmlspecialchars($loginError) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="mb-3">
            <label for="usernameInput" class="form-label small fw-semibold text-dark">Username</label>
            <input type="text" id="usernameInput" name="username" class="form-control" placeholder="admin or staff username" required autofocus>
          </div>

          <div class="mb-4">
            <label for="passwordInput" class="form-label small fw-semibold text-dark">Password</label>
            <input type="password" id="passwordInput" name="password" class="form-control" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
            Sign In to Dashboard
          </button>
        </form>

        <!-- Quick Demo Login Helpers -->
        <div class="bg-light p-3 rounded-3 border mt-4 text-center">
          <p class="small text-muted fw-semibold mb-2">Quick Demo Accounts (1-Click Fill):</p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillDemo('admin', 'admin123')">
              Admin (Full Control)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillDemo('staff1', 'staff123')">
              Staff (Council Custodian)
            </button>
          </div>
        </div>

        <div class="text-center mt-4">
          <p class="small text-muted mb-0">
            Need an authorized staff account? <a href="register.php" class="fw-bold text-primary text-decoration-none">Sign up online</a>
          </p>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-3 text-center text-muted small mt-auto border-top bg-white">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(ORG_NAME) ?> &bull; Student Council Headquarters
  </footer>

  <script>
    function fillDemo(username, password) {
      document.getElementById('usernameInput').value = username;
      document.getElementById('passwordInput').value = password;
    }
  </script>
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
