<?php
/**
 * Confederates Student Council (CSC)
 * Resource Management & Borrowing System
 * Configuration Settings
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Information
define('APP_NAME', 'CSC Resource Management');
define('APP_SUBTITLE', 'Confederates Student Council - Asset Allocation & Borrowing System');
define('ORG_NAME', 'Confederates Student Council (CSC)');
define('COUNCIL_OFFICE', 'Student Council Headquarters, Room 204');
define('OFFICE_HOURS', 'Monday - Friday: 8:00 AM - 5:00 PM');
define('CONTACT_EMAIL', 'council.borrowing@university.edu');
define('CONTACT_PHONE', '(02) 8920-5000 loc. 2040');

// Database Driver: 'sqlite' (zero-config, immediate plug-and-play) or 'mysql'
define('DB_DRIVER', 'sqlite');

// SQLite Settings
define('SQLITE_FILE', __DIR__ . '/../database/confed_borrowing.sqlite');

// MySQL Settings (for XAMPP/Production)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'confed_borrowing');
define('DB_USER', 'root');
define('DB_PASS', '');

// Policies
define('MAX_BORROW_DAYS', 5);
define('CURRENCY_SYMBOL', '₱');

// Timezone
date_default_timezone_set('Asia/Manila');
