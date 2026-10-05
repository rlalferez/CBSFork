<?php
/**
 * Transaction Receipt
 */
require_once 'config/db.php';
require_once 'config/session.php';

$db = get_db();

// Verify user is logged in
if (!$currentUser) {
    die("Unauthorized.");
}

$idsParam = $_GET['ids'] ?? '[]';
$transIDs = json_decode($idsParam, true);

if (!is_array($transIDs) || empty($transIDs)) {
    die("Invalid transaction data.");
}

// Fetch the transaction details
$inClause = implode(',', array_fill(0, count($transIDs), '?'));
$stmt = $db->prepare("
    SELECT b.*, i.itemDesc, br.brwFName, br.brwLName, br.brwStudentID, br.brwCollege, u.userFName as staffFName, u.userLName as staffLName
    FROM borrow_transaction b
    JOIN item i ON b.itemID = i.itemID
    JOIN borrower br ON b.brwID = br.brwID
    JOIN user u ON b.userID = u.userID
    WHERE b.brwTransID IN ($inClause)
");
$stmt->execute($transIDs);
$transactions = $stmt->fetchAll();

if (empty($transactions)) {
    die("Transactions not found.");
}

$firstTxn = $transactions[0];
$borrowerName = $firstTxn['brwFName'] . ' ' . $firstTxn['brwLName'];
$studentId = $firstTxn['brwStudentID'];
$college = $firstTxn['brwCollege'];
$date = date('F j, Y', strtotime($firstTxn['brwTransDate']));
$staff = $firstTxn['staffFName'] . ' ' . $firstTxn['staffLName'];

$totalAmount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f8f9fa;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #333;
            padding-bottom: 15px;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .details {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .details p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 5px 0;
            border-bottom: 1px dashed #ddd;
        }
        th {
            border-bottom: 1px dashed #333;
        }
        .totals {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 15px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border: none; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <div class="header">
            <h3>Confederates Student Council</h3>
            <p>Item Management & Lending System</p>
            <p>Borrowing Receipt</p>
        </div>
        
        <div class="details">
            <p><strong>Date:</strong> <?= htmlspecialchars($date) ?></p>
            <p><strong>Borrower:</strong> <?= htmlspecialchars($borrowerName) ?></p>
            <p><strong>Student ID:</strong> <?= htmlspecialchars($studentId) ?></p>
            <p><strong>College/Org:</strong> <?= htmlspecialchars($college) ?></p>
            <p><strong>Processed By:</strong> <?= htmlspecialchars($staff) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Fee</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <?php $totalAmount += $t['brwTransTotal']; ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($t['itemDesc']) ?><br>
                        <small>ID: <?= $t['brwTransID'] ?></small>
                    </td>
                    <td><?= $t['brwTransItemQty'] ?></td>
                    <td><?= number_format($t['itemRate'], 2) ?></td>
                    <td><?= number_format($t['brwTransTotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <p>Total Fee: <?= number_format($totalAmount, 2) ?></p>
        </div>

        <div class="footer">
            <p>Please return all items on or before the due date to avoid penalties.</p>
            <p>Thank you!</p>
        </div>
    </div>
</body>
</html>
