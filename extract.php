<?php
$content = file_get_contents('index_backup.php');
$lines = explode("\n", $content);

function extract_lines($start, $end) {
    global $lines;
    return implode("\n", array_slice($lines, $start - 1, $end - $start + 1));
}

// Extract login.php (Lines 392 to 618, plus require session)
$login = "<?php\nrequire_once 'config/db.php';\nrequire_once 'config/session.php';\nif (\$currentUser) { header('Location: index.php'); exit; }\n?>\n";
$login .= extract_lines(392, 618);
$login .= "\n</body>\n</html>";
file_put_contents('login.php', $login);

// Extract topbar.php
$topbar = extract_lines(623, 672);
file_put_contents('views/layout/topbar.php', $topbar);

// Extract sidebar.php
$sidebar = extract_lines(676, 770);
file_put_contents('views/layout/sidebar.php', $sidebar);

// Extract transactions.php
$transactions = extract_lines(776, 893);
file_put_contents('views/pages/transactions.php', $transactions);

// Extract reports.php
$reports = extract_lines(895, 1060);
file_put_contents('views/pages/reports.php', $reports);

// Extract purchases.php
$purchases = extract_lines(1062, 1090);
file_put_contents('views/pages/purchases.php', $purchases);

// Extract inventory.php
$inventory = extract_lines(1092, 1121);
file_put_contents('views/pages/inventory.php', $inventory);

// Extract borrowers.php
$borrowers = extract_lines(1123, 1157);
file_put_contents('views/pages/borrowers.php', $borrowers);

// Extract users.php
$users = extract_lines(1159, 1188);
$users = "<?php if(\$isAdmin): ?>\n" . $users . "\n<?php endif; ?>";
file_put_contents('views/pages/users.php', $users);

// Extract profile.php
$profile = extract_lines(1190, 1222);
file_put_contents('views/pages/profile.php', $profile);

// Extract modals
$modal_txn = extract_lines(1234, 1313);
file_put_contents('views/modals/modal_txn.php', $modal_txn);

$modal_item = extract_lines(1315, 1353);
file_put_contents('views/modals/modal_item.php', $modal_item);

$modal_borrower = extract_lines(1355, 1400);
file_put_contents('views/modals/modal_borrower.php', $modal_borrower);

$modal_user = extract_lines(1402, 1445);
file_put_contents('views/modals/modal_user.php', $modal_user);

$modal_purchase = extract_lines(1447, 1481);
file_put_contents('views/modals/modal_purchase.php', $modal_purchase);

// Extract JS
$js = extract_lines(1485, 1634);
// Remove <?php if($currentUser): ? > and <?php endif; ? > if present
$js = str_replace("<?php if(\$currentUser): ?" . ">\n", "", $js);
$js = str_replace("<?php endif; ?" . ">\n", "", $js);
// Remove <script> and </script>
$js = str_replace("<script>\n", "", $js);
$js = str_replace("</script>", "", $js);
file_put_contents('assets/js/app.js', $js);

// Extract footer.php
$footer = "
<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js\"></script>
<script src=\"assets/js/app.js?v=<?= time() ?>\"></script>
</body>
</html>
";
file_put_contents('views/layout/footer.php', $footer);

echo "Extraction complete.";
