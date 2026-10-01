<?php
/**
 * ID Generation Helper Function (University-Level Method)
 */
function generate_id($db, $table, $column, $prefix) {
    // Basic auto-increment logic for alphanumeric IDs
    $stmt = $db->query("SELECT $column FROM $table ORDER BY $column DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();
    if ($lastId) {
        // Strip prefix and increment the number
        $num = (int)str_replace($prefix, '', $lastId);
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    // Default if table is empty
    return $prefix . '001';
}
