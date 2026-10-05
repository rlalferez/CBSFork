<?php
require 'config/db.php';
$db = get_db();
try {
    $stmt = $db->query("SELECT * FROM borrower LIMIT 1");
    $row = $stmt->fetch();
    var_dump(array_keys($row));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
