          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-transactions' ? 'show active' : '' ?>" id="tab-transactions">
            <div class="content-card">
              <div class="content-card-header">
                <div class="card-title-group">
                  <h5 class="card-title">Manage Transactions</h5>
                  <p class="card-subtitle">View borrows, returns, reservations, and process checkout operations.</p>
                </div>
                <div class="card-header-actions">
                  <?php 
                  $totalItemsCount = 0;
                  foreach($items as $i) { if($i['itemTotalQty'] > 0) $totalItemsCount++; }
                  $canTransact = ($totalItemsCount > 0 || count($borrows) > 0);
                  ?>
                  <button class="btn <?= $canTransact ? 'btn-primary-action' : 'btn-secondary' ?> btn-sm shadow-sm" 
                          <?= $canTransact ? 'data-bs-toggle="modal" data-bs-target="#modalTransaction"' : 'disabled title="No items in inventory to borrow or reserve."' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>New Transaction</span>
                  </button>
                </div>
              </div>
              
              <div class="card-tabs-bar">
                <ul class="nav card-tabs" id="txnTabs">
                  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#txn-all">All</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-reserved">Reserved</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-released">Released</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-return">Returned</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-cancelled">Cancelled</button></li>
                </ul>
                <div class="card-tabs-search">
                  <input type="text" class="form-control form-control-sm table-search" id="txnGlobalSearch" placeholder="Search transactions..." style="max-width: 250px;">
                </div>
              </div>
              
              <div class="tab-content card-tab-content">
                <div class="tab-pane fade show active" id="txn-all">
                  <div class="table-responsive">
                    <table class="table-custom" id="txnAllTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th class="col-number">Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th class="col-number">Qty Returned</th><th>Return Date</th><th>Returned To</th><th>Action</th></tr></thead>
                      <tbody>
                        <?php if(empty($unifiedTransactions)): ?>
                          <tr><td colspan="12" class="text-center text-muted py-4">No transactions recorded yet.</td></tr>
                        <?php else: ?>
                          <?php foreach($unifiedTransactions as $t): ?>
                            <tr id="row-txn-<?= htmlspecialchars($t['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                              <td>
                                  <?php if($t['retTransID'] || strcasecmp($t['brwTransStatus'], 'Returned') === 0): ?>
                                      <span class="badge bg-success">Returned</span>
                                  <?php elseif(strcasecmp($t['brwTransStatus'], 'Reserved') === 0): ?>
                                      <span class="badge bg-warning text-dark">Reserved</span>
                                  <?php elseif(strcasecmp($t['brwTransStatus'], 'Cancelled') === 0): ?>
                                      <span class="badge bg-secondary">Cancelled</span>
                                  <?php else: ?>
                                      <span class="badge bg-primary">Released</span>
                                  <?php endif; ?>
                              </td>
                              <td><?= htmlspecialchars($t['brwTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                              <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($t['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                              <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= htmlspecialchars($t['brwID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName'], ENT_QUOTES, 'UTF-8') ?></a></td>
                              <td class="col-number"><?= (int)$t['borrow_qty'] ?></td>
                              <td><?= htmlspecialchars($t['brwTransDate'], ENT_QUOTES, 'UTF-8') ?></td>
                              <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l'], ENT_QUOTES, 'UTF-8') ?></td>
                              
                              <?php if($t['retTransID']): ?>
                                  <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-return', 'row-ret-<?= htmlspecialchars($t['retTransID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['retTransID'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                  <td class="col-number"><?= (int)$t['return_qty'] ?></td>
                                  <td><?= htmlspecialchars($t['retReturnedOnDate'], ENT_QUOTES, 'UTF-8') ?></td>
                                  <td><?= htmlspecialchars($t['return_staff_f'].' '.$t['return_staff_l'], ENT_QUOTES, 'UTF-8') ?></td>
                              <?php else: ?>
                                  <td class="text-muted text-center">-</td>
                                  <td class="text-muted text-center">-</td>
                                  <td class="text-muted text-center">-</td>
                                  <td class="text-muted text-center">-</td>
                              <?php endif; ?>
                              <td>
                                <?php if(strcasecmp($t['brwTransStatus'], 'Reserved') === 0): ?>
                                  <div class="d-flex gap-1">
                                    <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Release reserved item(s) to borrower now?');">
                                      <input type="hidden" name="action" value="release_reservation">
                                      <input type="hidden" name="brwTransID" value="<?= htmlspecialchars($t['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                                      <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;" title="Mark as Released">Release</button>
                                    </form>
                                    <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Cancel this reservation?');">
                                      <input type="hidden" name="action" value="cancel_reservation">
                                      <input type="hidden" name="brwTransID" value="<?= htmlspecialchars($t['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                                      <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px;" title="Cancel Reservation">Cancel</button>
                                    </form>
                                  </div>
                                <?php else: ?>
                                  <span class="text-muted small">-</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-reserved">
                  <div class="table-responsive">
                    <table class="table-custom" id="txnReservedTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th class="col-number">Qty</th><th>Borrower</th><th>Reserve Date</th><th>Due Date</th><th class="col-number">Fee</th><th>Action</th></tr></thead>
                      <tbody>
                        <?php 
                        $hasReserved = false;
                        foreach($borrows as $b): 
                          if(strcasecmp($b['brwTransStatus'] ?? '', 'Reserved') !== 0) continue;
                          $hasReserved = true;
                        ?>
                          <tr id="row-txn-<?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><span class="badge bg-warning text-dark">Reserved</span></td>
                            <td><?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($b['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td class="col-number"><?= (int)$b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars($b['brwTransBorrowOnDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($b['brwTransReturnByDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-number"><?= number_format((float)$b['brwTransTotal'], 2) ?></td>
                            <td>
                              <div class="d-flex gap-1">
                                <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Release reserved item(s) to borrower now?');">
                                  <input type="hidden" name="action" value="release_reservation">
                                  <input type="hidden" name="brwTransID" value="<?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;" title="Mark as Released">Release</button>
                                </form>
                                <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Cancel this reservation?');">
                                  <input type="hidden" name="action" value="cancel_reservation">
                                  <input type="hidden" name="brwTransID" value="<?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px;" title="Cancel Reservation">Cancel</button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                        <?php if(!$hasReserved): ?>
                          <tr><td colspan="9" class="text-center text-muted py-4">No reserved transactions found.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-released">
                  <div class="table-responsive">
                    <table class="table-custom" id="txnReleasedTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th class="col-number">Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th class="col-number">Fee</th></tr></thead>
                      <tbody>
                        <?php 
                        $hasReleased = false;
                        foreach($borrows as $b): 
                          $status = $b['brwTransStatus'] ?? '';
                          if(strcasecmp($status, 'Released') !== 0 && (!empty($status) && in_array(strtolower($status), ['reserved', 'cancelled', 'returned']))) continue;
                          $hasReleased = true;
                        ?>
                          <tr id="row-txn-<?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><span class="badge bg-primary">Released</span></td>
                            <td><?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($b['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td class="col-number"><?= (int)$b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars($b['brwTransBorrowOnDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($b['brwTransReturnByDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-number"><?= number_format((float)$b['brwTransTotal'], 2) ?></td>
                          </tr>
                        <?php endforeach; ?>
                        <?php if(!$hasReleased): ?>
                          <tr><td colspan="8" class="text-center text-muted py-4">No released transactions found.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-return">
                  <div class="table-responsive">
                    <table class="table-custom" id="txnReturnTable">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th class="col-number">Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php if(empty($returns)): ?>
                          <tr><td colspan="6" class="text-center text-muted py-4">No returned transactions found.</td></tr>
                        <?php else: ?>
                          <?php foreach($returns as $r): ?>
                            <tr id="row-ret-<?= htmlspecialchars($r['retTransID'], ENT_QUOTES, 'UTF-8') ?>">
                              <td><?= htmlspecialchars($r['retTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                              <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-all', 'row-txn-<?= htmlspecialchars($r['brwTransID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwTransID'], ENT_QUOTES, 'UTF-8') ?></a></td>
                              <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($r['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                              <td class="col-number"><?= (int)$r['brwTransQty'] ?></td>
                              <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= htmlspecialchars($r['brwID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName'], ENT_QUOTES, 'UTF-8') ?></a></td>
                              <td><?= htmlspecialchars($r['retReturnedOnDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-cancelled">
                  <div class="table-responsive">
                    <table class="table-custom" id="txnCancelledTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th class="col-number">Qty</th><th>Borrower</th><th>Reserve Date</th><th>Due Date</th><th class="col-number">Fee</th></tr></thead>
                      <tbody>
                        <?php 
                        $hasCancelled = false;
                        foreach($borrows as $b): 
                          if(strcasecmp($b['brwTransStatus'] ?? '', 'Cancelled') !== 0) continue;
                          $hasCancelled = true;
                        ?>
                          <tr id="row-txn-<?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><span class="badge bg-secondary">Cancelled</span></td>
                            <td><?= htmlspecialchars($b['brwTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($b['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td class="col-number"><?= (int)$b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars($b['brwTransBorrowOnDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($b['brwTransReturnByDate'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-number"><?= number_format((float)$b['brwTransTotal'], 2) ?></td>
                          </tr>
                        <?php endforeach; ?>
                        <?php if(!$hasCancelled): ?>
                          <tr><td colspan="8" class="text-center text-muted py-4">No cancelled transactions found.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
