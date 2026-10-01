<?php
/**
 * Helper Functions & Business Logic
 * Confederates Student Council Resource Management
 */

require_once __DIR__ . '/db.php';

function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function json_response($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function generate_tracking_code() {
    $db = get_db_connection();
    $prefix = 'CSC-' . date('Y') . '-';
    
    do {
        $random = mt_rand(1000, 9999);
        $code = $prefix . $random;
        $stmt = $db->prepare("SELECT id FROM borrow_requests WHERE tracking_code = ? LIMIT 1");
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    return $code;
}

function log_activity($action, $details, $actor = 'System') {
    try {
        $db = get_db_connection();
        $stmt = $db->prepare("INSERT INTO activity_logs (action, details, actor, created_at) VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$action, $details, $actor]);
    } catch (Exception $e) {
        error_log("Log error: " . $e->getMessage());
    }
}

function get_status_badge($status) {
    $status = ucfirst(strtolower(trim($status)));
    $classMap = [
        'Pending' => 'badge bg-warning text-dark',
        'Approved' => 'badge bg-primary',
        'Released' => 'badge bg-success',
        'Returned' => 'badge bg-secondary',
        'Cancelled' => 'badge bg-dark',
        'Rejected' => 'badge bg-danger',
        'Overdue' => 'badge bg-danger'
    ];
    $badgeClass = $classMap[$status] ?? 'badge bg-secondary';
    return '<span class="' . $badgeClass . ' px-2 py-1">' . htmlspecialchars($status) . '</span>';
}

function get_payment_badge($status) {
    $status = trim($status);
    $map = [
        'Free / Waived' => 'badge bg-info text-dark',
        'Deposit Paid' => 'badge bg-success',
        'Pending Deposit' => 'badge bg-warning text-dark',
        'Paid' => 'badge bg-success',
        'Refunded' => 'badge bg-secondary'
    ];
    $badgeClass = $map[$status] ?? 'badge bg-light text-dark border';
    return '<span class="' . $badgeClass . ' px-2 py-1">' . htmlspecialchars($status) . '</span>';
}

function format_display_date($dateStr, $includeTime = false) {
    if (!$dateStr) return '—';
    $time = strtotime($dateStr);
    return date($includeTime ? 'M d, Y h:i A' : 'M d, Y', $time);
}

function format_currency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_current_user_info() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'] ?? '',
        'contact_number' => $_SESSION['contact_number'] ?? ''
    ];
}

function is_admin() {
    $u = get_current_user_info();
    return $u && $u['role'] === 'admin';
}

function is_staff() {
    $u = get_current_user_info();
    return $u && ($u['role'] === 'staff' || $u['role'] === 'admin');
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: admin.php?error=unauthorized');
        exit;
    }
}
