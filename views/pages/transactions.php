      <main class="app-main">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
          <div>
            <h3 class="fw-bold text-dark mb-1">Council Desk Station</h3>
            <div class="text-muted small">
              Welcome, <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong> &bull; Assigned as <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-uppercase"><?= htmlspecialchars($currentUser['role']) ?></span>
            </div>
          </div>
          <div class="bg-white px-3 py-2 rounded-pill border shadow-sm small text-muted d-flex align-items-center gap-2">
            <svg width="16" height="16" class="text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Date: <strong><?= date('F d, Y') ?></strong></span>
          </div>
        </div>

        <div class="tab-content">
          <!-- TRANSACTIONS TAB -->
          <div class="tab-pane fade <?= !$isReportActive ? 'show active' : '' ?>" id="tab-transactions">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Resource Transactions</h5>
                  <p class="text-muted small mb-0">View borrows, returns, and process checkout operations.</p>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary-action btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransaction">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>New Transaction</span>
                  </button>
                </div>
              </div>
              
              <ul class="nav folder-tabs px-4 pt-3" id="txnTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#txn-all">Unified Log</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-borrow">Borrow Ledger</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-return">Return Ledger</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="txn-all">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($unifiedTransactions as $t): ?>
                          <tr>
                            <td>
                                <?php if($t['retTransID']): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Free') === 0 || $t['itemRate'] == 0): ?>
                                    <span class="badge bg-secondary">Free</span>
                                <?php elseif(strcasecmp($t['brwTransPayStat'], 'Paid') === 0): ?>
                                    <span class="badge bg-info text-dark">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Paid</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $t['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $t['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['itemDesc']) ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $t['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></a></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-return', 'row-<?= $t['retTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $t['retTransID'] ?></a></td>
                                <td><?= $t['return_qty'] ?></td>
                                <td><?= $t['retReturnedOnDate'] ?></td>
                                <td><?= htmlspecialchars($t['return_staff_f'].' '.$t['return_staff_l']) ?></td>
                            <?php else: ?>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                                <td class="text-muted text-center">-</td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-borrow">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($borrows as $b): ?>
                          <tr id="row-<?= $b['brwTransID'] ?>">
                            <td><?= $b['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $b['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc']) ?></a></td>
                            <td><?= $b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $b['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-return">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($returns as $r): ?>
                          <tr id="row-<?= $r['retTransID'] ?>">
                            <td><?= $r['retTransID'] ?></td>
                            <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-borrow', 'row-<?= $r['brwTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $r['brwTransID'] ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $r['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['itemDesc']) ?></a></td>
                            <td><?= $r['brwTransQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-<?= $r['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></a></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>