<?php
/**
 * ID Generation Helper Function (University-Level Method)
 */
function generate_id($db, $table, $column, $prefix) {
    // Alphanumeric ID auto-increment logic with natural length sorting
    $stmt = $db->query("SELECT $column FROM $table ORDER BY LENGTH($column) DESC, $column DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();
    if ($lastId) {
        // Strip prefix and increment the number
        $num = (int)str_replace($prefix, '', $lastId);
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    // Default if table is empty
    return $prefix . '001';
}

/**
 * HTML Specialchars Escaping Helper
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Centralized Flash Alert & Redirect Helper
 * Sets session alert, sets active dashboard tab if provided, and terminates script execution.
 */
function redirect_with_alert($tab, $type, $message, $url = '../index.php') {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
    if (!empty($tab)) {
        $_SESSION['active_tab'] = $tab;
    }
    header("Location: $url");
    exit;
}

/**
 * Validate and sanitize email format
 */
function validate_email_format($email) {
    $clean = trim((string)($email ?? ''));
    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : false;
}

/**
 * Validate and sanitize standard Philippine mobile number.
 * Standard format: 11 digits starting with 09 (09XXXXXXXXX).
 * Accepts 09XXXXXXXXX, +639XXXXXXXXX, 639XXXXXXXXX, and numbers with spaces/hyphens.
 * Returns normalized 11-digit string (09XXXXXXXXX) on success, or false on failure.
 */
function validate_ph_contact($contact) {
    if (empty($contact)) return false;
    // Strip spaces, dashes, dots, parentheses
    $clean = preg_replace('/[\s\-\(\)\.]/', '', trim((string)$contact));
    // Convert international prefix +63 or 63 to local 0
    if (strpos($clean, '+63') === 0) {
        $clean = '0' . substr($clean, 3);
    } elseif (strpos($clean, '63') === 0 && strlen($clean) === 12) {
        $clean = '0' . substr($clean, 2);
    }
    // Must be exactly 11 digits starting with 09
    if (preg_match('/^09\d{9}$/', $clean)) {
        return $clean;
    }
    return false;
}

/**
 * Validate Student ID format (##-#-#####)
 * Example: 23-1-12345
 */
function validate_student_id($studentId) {
    $clean = trim((string)($studentId ?? ''));
    return preg_match('/^\d{2}-\d-\d{5}$/', $clean) ? $clean : false;
}

/**
 * Check Item Stock & Booking Date Conflicts for a Given Date Range
 * 
 * Supports future reservations and prevents overlapping overbooking.
 * Checks against both Released (currently active) and Reserved (future bookings) transactions.
 * 
 * @param PDO $db
 * @param string $itemID
 * @param int $qtyRequested
 * @param string $startDate (YYYY-MM-DD)
 * @param string $endDate (YYYY-MM-DD)
 * @param string|null $excludeTransID
 * @return array ['success' => bool, 'message' => string, 'available' => int, 'total' => int, 'item' => array|null]
 */
function check_item_availability_for_dates($db, $itemID, $qtyRequested, $startDate, $endDate, $excludeTransID = null) {
    $qtyRequested = max(1, (int)$qtyRequested);
    
    // 1. Fetch Item Record
    $stmt = $db->prepare("SELECT itemID, itemDesc, itemTotalQty, itemAvailableQty, itemRate FROM item WHERE itemID = ? AND is_archived = 0");
    $stmt->execute([$itemID]);
    $item = $stmt->fetch();
    
    if (!$item) {
        return [
            'success' => false,
            'message' => "Item ID " . e($itemID) . " not found or is archived.",
            'available' => 0,
            'total' => 0,
            'item' => null
        ];
    }
    
    $totalQty = (int)$item['itemTotalQty'];
    
    // 2. Find all active or reserved bookings that overlap with requested [startDate, endDate]
    // A booking overlaps if: b.brwTransBorrowOnDate <= endDate AND effective_end >= startDate
    // For unreturned Released bookings, effective_end is at least today's date.
    $today = date('Y-m-d');
    $query = "
        SELECT b.brwTransID, b.brwTransBorrowOnDate, b.brwTransReturnByDate, b.brwTransItemQty, b.brwTransStatus,
               COALESCE((SELECT SUM(r.brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0) as returned_qty
        FROM borrow_transaction b
        WHERE b.itemID = ?
          AND b.brwTransStatus IN ('Released', 'Reserved')
    ";
    $params = [$itemID];
    if ($excludeTransID) {
        $query .= " AND b.brwTransID != ?";
        $params[] = $excludeTransID;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $allActive = $stmt->fetchAll();
    
    // 4. Filter for overlapping active bookings and calculate net unreturned quantities
    $overlapping = [];
    $milestoneDates = [$startDate, $endDate];
    
    foreach ($allActive as $b) {
        $netQty = (int)$b['brwTransItemQty'] - (int)$b['returned_qty'];
        if ($netQty <= 0) continue; // Fully returned, no conflict
        
        $bStart = $b['brwTransBorrowOnDate'];
        $bEnd = $b['brwTransReturnByDate'];
        
        // If items are physically released and overdue, they are still out today
        if ($b['brwTransStatus'] === 'Released' && $today > $bEnd) {
            $bEnd = $today;
        }
        
        // Check date interval overlap
        if ($bStart <= $endDate && $bEnd >= $startDate) {
            $overlapping[] = [
                'id' => $b['brwTransID'],
                'start' => $bStart,
                'end' => $bEnd,
                'qty' => $netQty,
                'status' => $b['brwTransStatus']
            ];
            
            if ($bStart >= $startDate && $bStart <= $endDate) {
                $milestoneDates[] = $bStart;
            }
            if ($bEnd >= $startDate && $bEnd <= $endDate) {
                $milestoneDates[] = $bEnd;
            }
        }
    }
    
    // 5. Evaluate peak booked quantity across milestone dates in the requested window
    $milestoneDates = array_unique($milestoneDates);
    sort($milestoneDates);
    
    $peakBooked = 0;
    foreach ($milestoneDates as $d) {
        $bookedOnDate = 0;
        foreach ($overlapping as $ov) {
            if ($d >= $ov['start'] && $d <= $ov['end']) {
                $bookedOnDate += $ov['qty'];
            }
        }
        if ($bookedOnDate > $peakBooked) {
            $peakBooked = $bookedOnDate;
        }
    }
    
    $availableForPeriod = max(0, $totalQty - $peakBooked);
    $startFmt = date('M j, Y', strtotime($startDate));
    $endFmt = date('M j, Y', strtotime($endDate));
    $dateText = ($startDate === $endDate) ? "for $startFmt" : "from $startFmt to $endFmt";
    
    if ($qtyRequested > $availableForPeriod) {
        $detailText = ($peakBooked > 0)
            ? "($peakBooked of $totalQty already booked)"
            : "(Total stock is $totalQty)";
        return [
            'success' => false,
            'message' => "Only $availableForPeriod unit(s) available $dateText $detailText. You requested $qtyRequested unit(s).",
            'available' => $availableForPeriod,
            'total' => $totalQty,
            'item' => $item
        ];
    }
    
    return [
        'success' => true,
        'message' => "Available $dateText ($availableForPeriod of $totalQty in stock).",
        'available' => $availableForPeriod,
        'total' => $totalQty,
        'item' => $item
    ];
}
