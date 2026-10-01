<?php
/**
 * RESTful JSON API Handler
 * Confederates Student Council Resource Management System
 */

define('IS_API_CALL', true);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$db = get_db_connection();
$currentUser = get_current_user_info();

switch ($action) {

    // ==========================================
    // 1. RESOURCES MANAGEMENT (CRUD)
    // ==========================================

    case 'get_categories':
        try {
            $stmt = $db->query("SELECT c.*, COUNT(i.id) as item_count 
                                FROM categories c 
                                LEFT JOIN items i ON c.id = i.category_id 
                                GROUP BY c.id 
                                ORDER BY c.id ASC");
            $categories = $stmt->fetchAll();
            json_response(true, 'Categories fetched successfully', $categories);
        } catch (Exception $e) {
            json_response(false, 'Failed to fetch categories: ' . $e->getMessage(), [], 500);
        }
        break;

    case 'get_items':
        try {
            $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $availableOnly = isset($_GET['available_only']) && $_GET['available_only'] == '1';

            $sql = "SELECT i.*, c.name as category_name, c.icon as category_icon 
                    FROM items i 
                    JOIN categories c ON i.category_id = c.id 
                    WHERE 1=1";
            $params = [];

            if ($categoryId > 0) {
                $sql .= " AND i.category_id = ?";
                $params[] = $categoryId;
            }

            if (!empty($search)) {
                $sql .= " AND (i.name LIKE ? OR i.item_code LIKE ? OR i.model LIKE ? OR i.description LIKE ?)";
                $w = "%{$search}%";
                $params[] = $w;
                $params[] = $w;
                $params[] = $w;
                $params[] = $w;
            }

            if ($availableOnly) {
                $sql .= " AND i.available_qty > 0 AND i.is_available = 1";
            }

            $sql .= " ORDER BY i.category_id ASC, i.name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            json_response(true, 'Resources retrieved successfully', $items);
        } catch (Exception $e) {
            json_response(false, 'Failed to retrieve resources: ' . $e->getMessage(), [], 500);
        }
        break;

    case 'get_item_detail':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(false, 'Invalid resource ID', [], 400);

            $stmt = $db->prepare("SELECT i.*, c.name as category_name, c.icon as category_icon 
                                  FROM items i 
                                  JOIN categories c ON i.category_id = c.id 
                                  WHERE i.id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) json_response(false, 'Resource not found', [], 404);

            json_response(true, 'Resource details fetched', $item);
        } catch (Exception $e) {
            json_response(false, 'Failed: ' . $e->getMessage(), [], 500);
        }
        break;

    case 'save_item':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);

        $itemId = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $itemCode = clean_input($_POST['item_code'] ?? '');
        $name = clean_input($_POST['name'] ?? '');
        $model = clean_input($_POST['model'] ?? 'Standard');
        $description = clean_input($_POST['description'] ?? '');
        $totalQty = max(1, (int)($_POST['total_qty'] ?? 1));
        $location = clean_input($_POST['location'] ?? 'Council Office');
        $condition = clean_input($_POST['condition_status'] ?? 'Good');
        $feeType = clean_input($_POST['fee_type'] ?? 'Free');
        $feeAmount = (float)($_POST['fee_amount'] ?? 0.00);
        $isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

        if (empty($name) || empty($itemCode)) {
            json_response(false, 'Resource code and name are required', [], 422);
        }

        try {
            if ($itemId > 0) {
                // Update
                $stmt = $db->prepare("SELECT total_qty, available_qty FROM items WHERE id = ?");
                $stmt->execute([$itemId]);
                $curr = $stmt->fetch();
                if (!$curr) throw new Exception('Resource not found.');

                $qtyDelta = $totalQty - $curr['total_qty'];
                $newAvailable = max(0, $curr['available_qty'] + $qtyDelta);

                $update = $db->prepare("UPDATE items SET category_id = ?, item_code = ?, name = ?, model = ?, description = ?, total_qty = ?, available_qty = ?, location = ?, condition_status = ?, fee_type = ?, fee_amount = ?, is_available = ? WHERE id = ?");
                $update->execute([$categoryId, $itemCode, $name, $model, $description, $totalQty, $newAvailable, $location, $condition, $feeType, $feeAmount, $isAvailable, $itemId]);

                log_activity('Resource Updated', "Resource {$itemCode} ({$name}) updated.", $currentUser['full_name'] ?? 'Staff');
                json_response(true, 'Resource updated successfully');
            } else {
                // Check duplicate
                $dup = $db->prepare("SELECT id FROM items WHERE item_code = ?");
                $dup->execute([$itemCode]);
                if ($dup->fetch()) throw new Exception("Resource code {$itemCode} is already in use.");

                $insert = $db->prepare("INSERT INTO items (category_id, item_code, name, model, description, total_qty, available_qty, location, condition_status, fee_type, fee_amount, is_available, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                $insert->execute([$categoryId, $itemCode, $name, $model, $description, $totalQty, $totalQty, $location, $condition, $feeType, $feeAmount, $isAvailable]);

                log_activity('Resource Added', "New resource {$itemCode} ({$name}) added to catalog.", $currentUser['full_name'] ?? 'Staff');
                json_response(true, 'Resource added successfully');
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'toggle_resource_availability':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $id = (int)($_POST['id'] ?? 0);
        $isAvailable = (int)($_POST['is_available'] ?? 1);

        try {
            $stmt = $db->prepare("UPDATE items SET is_available = ? WHERE id = ?");
            $stmt->execute([$isAvailable, $id]);
            log_activity('Availability Changed', "Resource #{$id} availability set to {$isAvailable}.", $currentUser['full_name'] ?? 'Staff');
            json_response(true, 'Resource availability status updated');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'delete_item':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $itemId = (int)($_POST['id'] ?? 0);

        try {
            $check = $db->prepare("SELECT COUNT(*) FROM borrow_items bi JOIN borrow_requests br ON bi.borrow_request_id = br.id WHERE bi.item_id = ? AND br.status IN ('Pending', 'Approved', 'Released')");
            $check->execute([$itemId]);
            if ($check->fetchColumn() > 0) {
                json_response(false, 'Cannot delete resource: it is currently booked in an active or pending reservation.', [], 400);
            }

            $it = $db->prepare("SELECT item_code, name FROM items WHERE id = ?");
            $it->execute([$itemId]);
            $item = $it->fetch();

            $del = $db->prepare("DELETE FROM items WHERE id = ?");
            $del->execute([$itemId]);

            log_activity('Resource Deleted', "Resource {$item['item_code']} deleted from inventory.", $currentUser['full_name'] ?? 'Admin');
            json_response(true, 'Resource deleted successfully');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;


    // ==========================================
    // 2. BOOKINGS & SCHEDULE (CRUD)
    // ==========================================

    case 'submit_borrow_request':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);

        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $data = !empty($jsonData) ? $jsonData : $_POST;

        $studentId       = clean_input($data['student_id'] ?? '');
        $fullName        = clean_input($data['full_name'] ?? '');
        $email           = clean_input($data['email'] ?? '');
        $contactNumber   = clean_input($data['contact_number'] ?? '');
        $organization    = clean_input($data['organization_name'] ?? '');
        $role            = clean_input($data['role'] ?? 'Student');
        
        $purpose         = clean_input($data['purpose'] ?? '');
        $eventName       = clean_input($data['event_name'] ?? '');
        $eventLocation   = clean_input($data['event_location'] ?? '');
        $borrowDate      = clean_input($data['borrow_date'] ?? '');
        $expectedReturn  = clean_input($data['expected_return_date'] ?? '');
        $paymentStatus   = clean_input($data['payment_status'] ?? 'Free / Waived');
        $paymentAmount   = (float)($data['payment_amount'] ?? 0.00);
        $paymentDetails  = clean_input($data['payment_details'] ?? '');
        $itemsList       = $data['items'] ?? [];

        if (empty($studentId) || empty($fullName) || empty($email) || empty($contactNumber) || empty($organization)) {
            json_response(false, 'Client identification and contact details are required.', [], 422);
        }
        if (empty($eventName) || empty($eventLocation) || empty($borrowDate) || empty($expectedReturn)) {
            json_response(false, 'Event information and booking dates are required.', [], 422);
        }
        if (empty($itemsList) || !is_array($itemsList)) {
            json_response(false, 'At least one resource must be selected.', [], 422);
        }

        try {
            $db->beginTransaction();

            // Client record (Find or create)
            $stmt = $db->prepare("SELECT id FROM borrowers WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $client = $stmt->fetch();

            if ($client) {
                $clientId = $client['id'];
                $updateClient = $db->prepare("UPDATE borrowers SET full_name = ?, email = ?, contact_number = ?, organization_name = ?, role = ? WHERE id = ?");
                $updateClient->execute([$fullName, $email, $contactNumber, $organization, $role, $clientId]);
            } else {
                $insertClient = $db->prepare("INSERT INTO borrowers (student_id, full_name, email, contact_number, organization_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                $insertClient->execute([$studentId, $fullName, $email, $contactNumber, $organization, $role]);
                $clientId = $db->lastInsertId();
            }

            // Verify resources availability
            $verifiedItems = [];
            foreach ($itemsList as $entry) {
                $itemId = (int)($entry['item_id'] ?? 0);
                $qty = (int)($entry['quantity'] ?? 1);
                if ($itemId <= 0 || $qty <= 0) continue;

                $itemCheck = $db->prepare("SELECT id, name, available_qty, is_available FROM items WHERE id = ?");
                $itemCheck->execute([$itemId]);
                $item = $itemCheck->fetch();

                if (!$item || $item['is_available'] == 0) {
                    throw new Exception("Resource #{$itemId} is currently unavailable for booking.");
                }
                if ($item['available_qty'] < $qty) {
                    throw new Exception("Insufficient available stock for '{$item['name']}'. Requested: {$qty}, Available: {$item['available_qty']}.");
                }

                $verifiedItems[] = [
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'notes' => clean_input($entry['notes'] ?? '')
                ];
            }

            // Booking record
            $trackingCode = generate_tracking_code();
            $createdBy = $currentUser['id'] ?? null;

            $bStmt = $db->prepare("INSERT INTO borrow_requests 
                (tracking_code, borrower_id, created_by_user_id, purpose, event_name, event_location, borrow_date, expected_return_date, status, payment_status, payment_amount, payment_details, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, datetime('now'), datetime('now'))");
            $bStmt->execute([
                $trackingCode,
                $clientId,
                $createdBy,
                $purpose,
                $eventName,
                $eventLocation,
                $borrowDate,
                $expectedReturn,
                $paymentStatus,
                $paymentAmount,
                $paymentDetails
            ]);
            $requestId = $db->lastInsertId();

            // Insert booking items
            $biStmt = $db->prepare("INSERT INTO borrow_items (borrow_request_id, item_id, quantity, return_condition, notes) VALUES (?, ?, ?, 'Pending', ?)");
            foreach ($verifiedItems as $v) {
                $biStmt->execute([$requestId, $v['item_id'], $v['quantity'], $v['notes']]);
            }

            log_activity('Booking Created', "Booking {$trackingCode} created for {$fullName} ({$organization}).", $currentUser['full_name'] ?? $fullName);

            $db->commit();
            json_response(true, 'Resource booking successfully filed!', [
                'tracking_code' => $trackingCode,
                'request_id' => $requestId,
                'receipt_url' => 'receipt.php?code=' . urlencode($trackingCode)
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'update_booking_schedule':
        // "Allow the user to book or change resource or booking date"
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newBorrowDate = clean_input($_POST['borrow_date'] ?? '');
        $newReturnDate = clean_input($_POST['expected_return_date'] ?? '');
        $eventName = clean_input($_POST['event_name'] ?? '');
        $eventLocation = clean_input($_POST['event_location'] ?? '');

        if ($requestId <= 0 || empty($newBorrowDate) || empty($newReturnDate)) {
            json_response(false, 'Request ID and new booking schedule dates are required.', [], 422);
        }

        try {
            $stmt = $db->prepare("UPDATE borrow_requests SET borrow_date = ?, expected_return_date = ?, event_name = ?, event_location = ?, updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$newBorrowDate, $newReturnDate, $eventName, $eventLocation, $requestId]);
            log_activity('Schedule Changed', "Booking #{$requestId} rescheduled to {$newBorrowDate} - {$newReturnDate}.", $currentUser['full_name'] ?? 'Staff');
            json_response(true, 'Booking schedule successfully updated.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'cancel_booking':
        // "Must allow cancellation options" / "Allow user to cancel booking"
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $requestId = (int)($_POST['request_id'] ?? 0);
        $reason = clean_input($_POST['reason'] ?? 'Cancelled by request');

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Booking record not found.');

            // If released, restore inventory
            if ($req['status'] === 'Released' || $req['status'] === 'Overdue') {
                $bItems = $db->prepare("SELECT * FROM borrow_items WHERE borrow_request_id = ?");
                $bItems->execute([$requestId]);
                foreach ($bItems->fetchAll() as $bi) {
                    $db->prepare("UPDATE items SET available_qty = MIN(total_qty, available_qty + ?) WHERE id = ?")->execute([$bi['quantity'], $bi['item_id']]);
                }
            }

            $update = $db->prepare("UPDATE borrow_requests SET status = 'Cancelled', admin_notes = ?, updated_at = datetime('now') WHERE id = ?");
            $update->execute(["Cancelled: {$reason}", $requestId]);

            log_activity('Booking Cancelled', "Booking {$req['tracking_code']} was cancelled. Reason: {$reason}", $currentUser['full_name'] ?? 'Staff');
            $db->commit();
            json_response(true, 'Booking has been cancelled.');
        } catch (Exception $e) {
            $db->rollBack();
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'update_payment_details':
        // "Set the payment status and details"
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $requestId = (int)($_POST['request_id'] ?? 0);
        $paymentStatus = clean_input($_POST['payment_status'] ?? 'Free / Waived');
        $paymentAmount = (float)($_POST['payment_amount'] ?? 0.00);
        $paymentDetails = clean_input($_POST['payment_details'] ?? '');

        try {
            $stmt = $db->prepare("UPDATE borrow_requests SET payment_status = ?, payment_amount = ?, payment_details = ?, updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$paymentStatus, $paymentAmount, $paymentDetails, $requestId]);
            log_activity('Payment Updated', "Booking #{$requestId} payment status updated to {$paymentStatus} ({$paymentAmount}).", $currentUser['full_name'] ?? 'Staff');
            json_response(true, 'Payment status and details updated successfully.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'get_requests':
        try {
            $status = clean_input($_GET['status'] ?? 'all');
            $search = clean_input($_GET['search'] ?? '');

            $sql = "SELECT r.*, b.student_id, b.full_name, b.organization_name, b.contact_number,
                    (SELECT COUNT(*) FROM borrow_items bi WHERE bi.borrow_request_id = r.id) as item_count,
                    u.full_name as created_by_name
                    FROM borrow_requests r
                    JOIN borrowers b ON r.borrower_id = b.id
                    LEFT JOIN users u ON r.created_by_user_id = u.id
                    WHERE 1=1";
            $params = [];

            if (!empty($status) && $status !== 'all') {
                if ($status === 'Overdue') {
                    $sql .= " AND (r.status = 'Overdue' OR (r.status = 'Released' AND r.expected_return_date < date('now')))";
                } else {
                    $sql .= " AND r.status = ?";
                    $params[] = $status;
                }
            }

            if (!empty($search)) {
                $sql .= " AND (r.tracking_code LIKE ? OR b.full_name LIKE ? OR b.organization_name LIKE ? OR b.student_id LIKE ? OR r.event_name LIKE ?)";
                $w = "%{$search}%";
                $params[] = $w;
                $params[] = $w;
                $params[] = $w;
                $params[] = $w;
                $params[] = $w;
            }

            $sql .= " ORDER BY CASE r.status 
                        WHEN 'Pending' THEN 1 
                        WHEN 'Approved' THEN 2 
                        WHEN 'Released' THEN 3 
                        WHEN 'Overdue' THEN 4 
                        ELSE 5 END, r.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $requests = $stmt->fetchAll();

            $today = date('Y-m-d');
            foreach ($requests as &$req) {
                $req['is_overdue'] = ($req['status'] === 'Released' && $req['expected_return_date'] < $today);
            }

            json_response(true, 'Bookings fetched', $requests);
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'update_request_status':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);

        $requestId = (int)($_POST['request_id'] ?? 0);
        $newStatus = clean_input($_POST['status'] ?? '');
        $adminNotes = clean_input($_POST['admin_notes'] ?? '');
        $actorName = $currentUser['full_name'] ?? 'Council Staff';

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            if (!$request) throw new Exception('Booking record not found.');

            $oldStatus = $request['status'];
            $itemStmt = $db->prepare("SELECT * FROM borrow_items WHERE borrow_request_id = ?");
            $itemStmt->execute([$requestId]);
            $borrowedItems = $itemStmt->fetchAll();

            // Status transitions
            if ($newStatus === 'Released' && $oldStatus !== 'Released') {
                foreach ($borrowedItems as $bItem) {
                    $inv = $db->prepare("SELECT available_qty, name FROM items WHERE id = ?");
                    $inv->execute([$bItem['item_id']]);
                    $row = $inv->fetch();
                    if ($row['available_qty'] < $bItem['quantity']) {
                        throw new Exception("Stock for '{$row['name']}' is insufficient to release.");
                    }
                    $db->prepare("UPDATE items SET available_qty = available_qty - ? WHERE id = ?")->execute([$bItem['quantity'], $bItem['item_id']]);
                }
            }

            if ($newStatus === 'Returned' && ($oldStatus === 'Released' || $oldStatus === 'Overdue')) {
                foreach ($borrowedItems as $bItem) {
                    $db->prepare("UPDATE items SET available_qty = MIN(total_qty, available_qty + ?) WHERE id = ?")->execute([$bItem['quantity'], $bItem['item_id']]);
                }
                $db->prepare("UPDATE borrow_requests SET actual_return_date = date('now') WHERE id = ?")->execute([$requestId]);
            }

            if (($newStatus === 'Cancelled' || $newStatus === 'Rejected') && ($oldStatus === 'Released' || $oldStatus === 'Overdue')) {
                foreach ($borrowedItems as $bItem) {
                    $db->prepare("UPDATE items SET available_qty = MIN(total_qty, available_qty + ?) WHERE id = ?")->execute([$bItem['quantity'], $bItem['item_id']]);
                }
            }

            $update = $db->prepare("UPDATE borrow_requests SET status = ?, admin_notes = ?, approved_by = ?, updated_at = datetime('now') WHERE id = ?");
            $notes = !empty($adminNotes) ? $adminNotes : $request['admin_notes'];
            $update->execute([$newStatus, $notes, $actorName, $requestId]);

            log_activity("Status: {$newStatus}", "Booking {$request['tracking_code']} changed from {$oldStatus} to {$newStatus}.", $actorName);

            $db->commit();
            json_response(true, "Booking updated to {$newStatus}.", ['status' => $newStatus, 'id' => $requestId]);
        } catch (Exception $e) {
            $db->rollBack();
            json_response(false, $e->getMessage(), [], 400);
        }
        break;


    // ==========================================
    // 3. CLIENT RECORDS MANAGEMENT (CRUD)
    // ==========================================

    case 'get_clients':
        try {
            $search = clean_input($_GET['search'] ?? '');
            $sql = "SELECT b.*, 
                    (SELECT COUNT(*) FROM borrow_requests r WHERE r.borrower_id = b.id) as total_bookings,
                    (SELECT MAX(created_at) FROM borrow_requests r WHERE r.borrower_id = b.id) as last_booking_date
                    FROM borrowers b 
                    WHERE 1=1";
            $params = [];
            if (!empty($search)) {
                $sql .= " AND (b.full_name LIKE ? OR b.student_id LIKE ? OR b.organization_name LIKE ? OR b.email LIKE ?)";
                $w = "%{$search}%";
                $params = [$w, $w, $w, $w];
            }
            $sql .= " ORDER BY b.full_name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            json_response(true, 'Clients retrieved', $stmt->fetchAll());
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'save_client':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $clientId = (int)($_POST['id'] ?? 0);
        $studentId = clean_input($_POST['student_id'] ?? '');
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $contactNumber = clean_input($_POST['contact_number'] ?? '');
        $orgName = clean_input($_POST['organization_name'] ?? '');
        $role = clean_input($_POST['role'] ?? 'Student');
        $status = clean_input($_POST['status'] ?? 'Active');
        $notes = clean_input($_POST['notes'] ?? '');

        if (empty($studentId) || empty($fullName) || empty($email)) {
            json_response(false, 'Student ID, Full Name, and Email are required.', [], 422);
        }

        try {
            if ($clientId > 0) {
                $update = $db->prepare("UPDATE borrowers SET student_id = ?, full_name = ?, email = ?, contact_number = ?, organization_name = ?, role = ?, status = ?, notes = ? WHERE id = ?");
                $update->execute([$studentId, $fullName, $email, $contactNumber, $orgName, $role, $status, $notes, $clientId]);
                log_activity('Client Updated', "Client record {$fullName} ({$studentId}) updated.", $currentUser['full_name'] ?? 'Staff');
                json_response(true, 'Client record updated successfully.');
            } else {
                $dup = $db->prepare("SELECT id FROM borrowers WHERE student_id = ?");
                $dup->execute([$studentId]);
                if ($dup->fetch()) throw new Exception("Student ID {$studentId} is already registered.");

                $insert = $db->prepare("INSERT INTO borrowers (student_id, full_name, email, contact_number, organization_name, role, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                $insert->execute([$studentId, $fullName, $email, $contactNumber, $orgName, $role, $status, $notes]);
                log_activity('Client Created', "New client record {$fullName} created.", $currentUser['full_name'] ?? 'Staff');
                json_response(true, 'New client record created successfully.');
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'delete_client':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $clientId = (int)($_POST['id'] ?? 0);

        try {
            $chk = $db->prepare("SELECT COUNT(*) FROM borrow_requests WHERE borrower_id = ? AND status IN ('Pending', 'Approved', 'Released')");
            $chk->execute([$clientId]);
            if ($chk->fetchColumn() > 0) {
                json_response(false, 'Cannot delete client with active or pending bookings.', [], 400);
            }

            $cl = $db->prepare("SELECT full_name FROM borrowers WHERE id = ?");
            $cl->execute([$clientId]);
            $client = $cl->fetch();

            $del = $db->prepare("DELETE FROM borrowers WHERE id = ?");
            $del->execute([$clientId]);
            log_activity('Client Deleted', "Client record {$client['full_name']} removed.", $currentUser['full_name'] ?? 'Admin');
            json_response(true, 'Client record removed successfully.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;


    // ==========================================
    // 4. STAFF RECORDS MANAGEMENT (CRUD)
    // ==========================================

    case 'get_staff':
        try {
            $stmt = $db->query("SELECT id, username, full_name, email, contact_number, role, status, created_at FROM users ORDER BY role ASC, full_name ASC");
            json_response(true, 'Staff accounts retrieved', $stmt->fetchAll());
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'save_staff':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $staffId = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $contactNumber = clean_input($_POST['contact_number'] ?? '');
        $role = clean_input($_POST['role'] ?? 'staff');
        $status = clean_input($_POST['status'] ?? 'active');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($fullName)) {
            json_response(false, 'Username and Full Name are required.', [], 422);
        }

        try {
            if ($staffId > 0) {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $db->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, role = ?, status = ?, password_hash = ? WHERE id = ?");
                    $update->execute([$username, $fullName, $email, $contactNumber, $role, $status, $hash, $staffId]);
                } else {
                    $update = $db->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, role = ?, status = ? WHERE id = ?");
                    $update->execute([$username, $fullName, $email, $contactNumber, $role, $status, $staffId]);
                }
                log_activity('Staff Updated', "User account {$username} updated.", $currentUser['full_name'] ?? 'Admin');
                json_response(true, 'Staff account updated successfully.');
            } else {
                if (empty($password)) json_response(false, 'Password is required for new accounts.', [], 422);
                $dup = $db->prepare("SELECT id FROM users WHERE username = ?");
                $dup->execute([$username]);
                if ($dup->fetch()) throw new Exception("Username '{$username}' is already taken.");

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                $ins->execute([$username, $hash, $fullName, $email, $contactNumber, $role, $status]);
                log_activity('Staff Created', "New staff account {$username} ({$fullName}) created.", $currentUser['full_name'] ?? 'Admin');
                json_response(true, 'Staff account created successfully.');
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'delete_staff':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $staffId = (int)($_POST['id'] ?? 0);

        if ($currentUser && $currentUser['id'] == $staffId) {
            json_response(false, 'You cannot delete your own active account.', [], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$staffId]);
            log_activity('Staff Deleted', "Staff user #{$staffId} was removed.", $currentUser['full_name'] ?? 'Admin');
            json_response(true, 'Staff account deleted successfully.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;


    // ==========================================
    // 5. PERSONAL ACCOUNT (REGISTER & PROFILE)
    // ==========================================

    case 'register_staff':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        $username = trim($_POST['username'] ?? '');
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $contactNumber = clean_input($_POST['contact_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($fullName) || empty($password)) {
            json_response(false, 'Username, Full Name, and Password are required.', [], 422);
        }

        try {
            $dup = $db->prepare("SELECT id FROM users WHERE username = ?");
            $dup->execute([$username]);
            if ($dup->fetch()) throw new Exception("Username '{$username}' is already in use.");

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (username, password_hash, full_name, email, contact_number, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'staff', 'active', datetime('now'))");
            $ins->execute([$username, $hash, $fullName, $email, $contactNumber]);
            $newId = $db->lastInsertId();

            // Automatically log in
            $_SESSION['user_id'] = $newId;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = 'staff';
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contactNumber;

            log_activity('Self Registration', "Staff user {$username} registered online.", $fullName);
            json_response(true, 'Account created successfully! Welcome to Council Resource Management.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;

    case 'update_profile':
        if ($method !== 'POST') json_response(false, 'POST required', [], 405);
        if (!is_logged_in()) json_response(false, 'Must be logged in.', [], 401);

        $userId = $_SESSION['user_id'];
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $contactNumber = clean_input($_POST['contact_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $contactNumber, $hash, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $contactNumber, $userId]);
            }

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contactNumber;

            log_activity('Profile Updated', "Personal account for {$fullName} updated.", $fullName);
            json_response(true, 'Profile updated successfully.');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
        break;


    // ==========================================
    // 6. REPORTS GENERATION (PERIODIC, RESOURCE, CLIENT)
    // ==========================================

    case 'get_reports':
        try {
            $reportType = clean_input($_GET['type'] ?? 'periodic'); // 'periodic', 'by_resource', 'by_client'
            $startDate = clean_input($_GET['start_date'] ?? date('Y-m-01'));
            $endDate = clean_input($_GET['end_date'] ?? date('Y-m-t'));

            if ($reportType === 'by_resource') {
                $sql = "SELECT i.id, i.item_code, i.name as resource_name, i.model, c.name as category_name, i.total_qty, i.available_qty,
                        COUNT(bi.id) as booking_frequency,
                        SUM(bi.quantity) as total_units_borrowed
                        FROM items i
                        JOIN categories c ON i.category_id = c.id
                        LEFT JOIN borrow_items bi ON i.id = bi.item_id
                        LEFT JOIN borrow_requests br ON bi.borrow_request_id = br.id AND br.borrow_date BETWEEN ? AND ?
                        GROUP BY i.id
                        ORDER BY total_units_borrowed DESC, i.name ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$startDate, $endDate]);
                $results = $stmt->fetchAll();
                json_response(true, 'Report by resource generated', ['report_type' => 'by_resource', 'data' => $results, 'start' => $startDate, 'end' => $endDate]);

            } elseif ($reportType === 'by_client') {
                $sql = "SELECT b.id, b.student_id, b.full_name as client_name, b.organization_name, b.role, b.contact_number,
                        COUNT(br.id) as total_bookings,
                        SUM(CASE WHEN br.status = 'Returned' THEN 1 ELSE 0 END) as returned_count,
                        SUM(CASE WHEN br.status IN ('Released', 'Overdue') THEN 1 ELSE 0 END) as active_count,
                        SUM(br.payment_amount) as total_fees_paid
                        FROM borrowers b
                        LEFT JOIN borrow_requests br ON b.id = br.borrower_id AND br.borrow_date BETWEEN ? AND ?
                        GROUP BY b.id
                        ORDER BY total_bookings DESC, b.full_name ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$startDate, $endDate]);
                $results = $stmt->fetchAll();
                json_response(true, 'Report by client generated', ['report_type' => 'by_client', 'data' => $results, 'start' => $startDate, 'end' => $endDate]);

            } else {
                // Periodic reporting
                $sql = "SELECT br.id, br.tracking_code, br.event_name, br.borrow_date, br.expected_return_date, br.actual_return_date, br.status, br.payment_status, br.payment_amount,
                        b.student_id, b.full_name as client_name, b.organization_name,
                        (SELECT COUNT(*) FROM borrow_items bi WHERE bi.borrow_request_id = br.id) as item_count,
                        (SELECT GROUP_CONCAT(i.name || ' (' || bi.quantity || ')', ', ') 
                         FROM borrow_items bi JOIN items i ON bi.item_id = i.id WHERE bi.borrow_request_id = br.id) as item_summary
                        FROM borrow_requests br
                        JOIN borrowers b ON br.borrower_id = b.id
                        WHERE br.borrow_date BETWEEN ? AND ?
                        ORDER BY br.borrow_date DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$startDate, $endDate]);
                $results = $stmt->fetchAll();

                // Summary calculations
                $totalBookings = count($results);
                $totalRevenue = array_sum(array_column($results, 'payment_amount'));

                json_response(true, 'Periodic report generated', [
                    'report_type' => 'periodic',
                    'start' => $startDate,
                    'end' => $endDate,
                    'summary' => [
                        'total_bookings' => $totalBookings,
                        'total_fees' => $totalRevenue
                    ],
                    'data' => $results
                ]);
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    case 'get_stats':
        try {
            $totalItems = $db->query("SELECT SUM(total_qty) FROM items")->fetchColumn() ?: 0;
            $availableItems = $db->query("SELECT SUM(available_qty) FROM items WHERE is_available = 1")->fetchColumn() ?: 0;
            $borrowedItems = $totalItems - $availableItems;

            $totalClients = $db->query("SELECT COUNT(*) FROM borrowers")->fetchColumn() ?: 0;
            $totalStaff = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

            $pendingReqs = $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Pending'")->fetchColumn() ?: 0;
            $activeBorrowed = $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Released'")->fetchColumn() ?: 0;
            $overdueCount = $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Overdue' OR (status = 'Released' AND expected_return_date < date('now'))")->fetchColumn() ?: 0;
            $completedReturns = $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Returned'")->fetchColumn() ?: 0;

            $logs = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();

            json_response(true, 'Statistics retrieved', [
                'total_items' => (int)$totalItems,
                'available_items' => (int)$availableItems,
                'borrowed_items' => (int)$borrowedItems,
                'total_clients' => (int)$totalClients,
                'total_staff' => (int)$totalStaff,
                'pending_requests' => (int)$pendingReqs,
                'active_borrowed' => (int)$activeBorrowed,
                'overdue_count' => (int)$overdueCount,
                'completed_returns' => (int)$completedReturns,
                'recent_logs' => $logs
            ]);
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
        break;

    default:
        json_response(false, "Unknown API action: '{$action}'.", [], 404);
        break;
}
