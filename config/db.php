<?php
/**
 * ==========================================================
 * DATABASE CONNECTION & CONFIGURATION (db.php)
 * Confederates Student Council - Resource Management System
 * ==========================================================
 * Configured for MySQL (phpMyAdmin / XAMPP).
 * Can be switched to 'sqlite' for zero-config portable testing.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------
// DATABASE CONFIGURATION (Default: MySQL for phpMyAdmin)
// ----------------------------------------------------------
define('DB_TYPE', 'mysql'); // Set to 'mysql' for phpMyAdmin, or 'sqlite' for portable file

// MySQL Credentials (Standard XAMPP Settings)
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');
define('MYSQL_DB',   'confed_borrowing');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

// SQLite Path (Fallback)
define('SQLITE_PATH', __DIR__ . '/database.sqlite');

/**
 * Get the PDO Database Instance
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        if (DB_TYPE === 'mysql') {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } else {
            $isNew = !file_exists(SQLITE_PATH);
            $pdo = new PDO('sqlite:' . SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Automatically build clean tables with only initial Admin account
            if ($isNew) {
                init_clean_tables($pdo);
            }
        }
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

              <div class="bg-light p-3 rounded-3 border small text-muted">
                <strong>Tip for Testing:</strong> If you wish to run the app without MySQL right now, open <code>db.php</code> line 18 and change <code>define(\'DB_TYPE\', \'mysql\');</code> to <code>define(\'DB_TYPE\', \'sqlite\');</code>.
              </div>
            </div>
          </div>
        </body>
        </html>');
    }
}

/**
 * Builds clean, unseeded schema if using SQLite fallback
 * (Only creates initial Administrator account; resources and bookings remain 100% empty)
 */
function init_clean_tables($pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        contact_number VARCHAR(50),
        role VARCHAR(20) NOT NULL DEFAULT 'staff',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Resources Table (Empty - Admin will input items)
    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(150) NOT NULL,
        model VARCHAR(100) DEFAULT 'Standard',
        category VARCHAR(50) NOT NULL,
        total_qty INTEGER NOT NULL DEFAULT 1,
        available_qty INTEGER NOT NULL DEFAULT 1,
        is_available INTEGER NOT NULL DEFAULT 1,
        location VARCHAR(100) DEFAULT 'Council Office Room 204',
        condition_status VARCHAR(50) DEFAULT 'Good Condition',
        fee_type VARCHAR(50) DEFAULT 'Free',
        fee_amount DECIMAL(10,2) DEFAULT 0.00,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Clients Table (Empty - Admin & Staff will input borrowers)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        contact_number VARCHAR(50) NOT NULL,
        organization_name VARCHAR(150) NOT NULL,
        role VARCHAR(50) DEFAULT 'Student',
        status VARCHAR(20) DEFAULT 'Active',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Bookings Table (Empty - Staff & Admin will record loans)
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_code VARCHAR(50) UNIQUE NOT NULL,
        client_id INTEGER NOT NULL,
        created_by_user_id INTEGER NULL,
        event_name VARCHAR(150) NOT NULL,
        event_location VARCHAR(150) NOT NULL,
        purpose TEXT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        actual_return_date DATE NULL,
        status VARCHAR(30) DEFAULT 'Approved',
        payment_status VARCHAR(30) DEFAULT 'Free / Waived',
        payment_amount DECIMAL(10,2) DEFAULT 0.00,
        payment_details TEXT,
        cancellation_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");

    // 5. Booking Items Junction Table (Empty)
    $pdo->exec("CREATE TABLE IF NOT EXISTS booking_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        resource_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE RESTRICT
    )");

    // Create Initial Admin User (admin / admin123)
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status) VALUES 
        ('admin', '{$adminPass}', 'Council Administrator', 'admin@csc.edu.ph', '09123456789', 'admin', 'active')
    ");
}
