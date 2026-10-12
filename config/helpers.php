<?php
function generate_id($db, $table, $column, $prefix) {
    $stmt = $db->query("SELECT $column FROM $table ORDER BY LENGTH($column) DESC, $column DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();
    if ($lastId) {
        $num = (int)str_replace($prefix, '', $lastId);
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return $prefix . '001';
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect_with_alert($tab, $type, $message, $url = '../index.php') {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
    if (!empty($tab)) {
        $_SESSION['active_tab'] = $tab;
    }
    header("Location: $url");
    exit;
}

function validate_email_format($email) {
    $clean = trim((string)($email ?? ''));
    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : false;
}

function validate_ph_contact($contact) {
    if (empty($contact)) return false;
    $clean = preg_replace('/[\s\-\(\)\.]/', '', trim((string)$contact));
    if (preg_match('/^09\d{9}$/', $clean)) {
        return $clean;
    }
    return false;
}

function validate_student_id($studentId) {
    $clean = trim((string)($studentId ?? ''));
    return preg_match('/^\d{2}-\d-\d{5}$/', $clean) ? $clean : false;
}

function check_item_availability_for_dates($db, $itemID, $qtyRequested, $startDate, $endDate, $excludeTransID = null) {
    if (strtotime($endDate) < strtotime($startDate)) {
        return [
            'success' => false,
            'message' => 'Return date cannot be earlier than borrow date.',
            'available' => 0,
            'total' => 0,
            'item' => null
        ];
    }

    $qtyRequested = max(1, (int)$qtyRequested);
    
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
    
    $overlapping = [];
    $milestoneDates = [$startDate, $endDate];
    
    foreach ($allActive as $b) {
        $netQty = (int)$b['brwTransItemQty'] - (int)$b['returned_qty'];
        if ($netQty <= 0) continue;
        
        $bStart = $b['brwTransBorrowOnDate'];
        $bEnd = $b['brwTransReturnByDate'];
        
        if ($b['brwTransStatus'] === 'Released' && $today > $bEnd) {
            $bEnd = $today;
        }
        
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
