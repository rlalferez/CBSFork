<?php
/**
 * Database Connection Handler
 * Supports both SQLite (zero-config, automatic initialization) and MySQL
 */

require_once __DIR__ . '/config.php';

function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $is_new_db = !file_exists(SQLITE_FILE);
            
            // Ensure directory exists
            $dir = dirname(SQLITE_FILE);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            // If brand new database, seed with schema.sql
            if ($is_new_db) {
                $schema_file = __DIR__ . '/../database/schema.sql';
                if (file_exists($schema_file)) {
                    $sql = file_get_contents($schema_file);
                    $pdo->exec($sql);
                }
            }
        } else {
            // MySQL Driver
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $pdo;
    } catch (PDOException $e) {
        // Output friendly error or log
        error_log('Database Connection Error: ' . $e->getMessage());
        if (defined('IS_API_CALL') && IS_API_CALL) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        } else {
            die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;">
                <h3>Database Connection Failed</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p><small>Check includes/config.php to adjust DB configuration.</small></p>
            </div>');
        }
    }
}
