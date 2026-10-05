<?php
//Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_TYPE', 'mysql'); //Set to 'mysql' for phpMyAdmin, or 'sqlite' for portable file

//MySQL Credentials (Standard XAMPP Settings)
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');
define('MYSQL_DB',   'confed_borrowing');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

/**
 * Get the PDO Database Instance
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        die('
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Database Setup Required &bull; CSC Borrowing System</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light py-5">
          <div class="container" style="max-width: 680px;">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
              <div class="d-flex align-items-center gap-3 mb-3 text-danger">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <h4 class="fw-bold mb-0">MySQL Database Setup Required</h4>
              </div>
              <p class="text-muted small">The system could not connect to MySQL database <strong>' . htmlspecialchars(MYSQL_DB) . '</strong>.</p>
              
              <div class="alert alert-danger py-2 px-3 small font-monospace mb-4">
                ' . htmlspecialchars($errorMsg) . '
              </div>

              <h6 class="fw-bold text-dark mb-2">How to Setup the Database in phpMyAdmin:</h6>
              <ol class="small text-secondary lh-lg mb-4 ps-3">
                <li>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are started in the <strong>XAMPP Control Panel</strong>.</li>
                <li>Open your browser and navigate to: <a href="http://localhost/phpmyadmin/index.php" target="_blank" class="fw-bold text-primary">http://localhost/phpmyadmin/index.php</a></li>
                <li>Click the <strong>"SQL"</strong> tab in the top navigation bar.</li>
                <li>Open the file <strong class="text-dark">confed_borrowing.sql</strong> in your project folder, copy its entire contents, paste it into the box, and click <strong>"Go"</strong>.</li>
                <li>Once imported, <a href="index.php" class="fw-bold text-success">Click here to refresh this page</a>.</li>
              </ol>

            </div>
          </div>
        </body>
        </html>');
    }
}


