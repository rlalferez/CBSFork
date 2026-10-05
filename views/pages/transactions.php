
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
                  <?php 
                  $availableItemsCount = 0;
                  foreach($items as $i) { if($i['itemAvailableQty'] > 0) $availableItemsCount++; }
                  if ($availableItemsCount == 0 && count($borrows) == 0): 
                  ?>
                    <button class="btn btn-secondary btn-sm shadow-sm" disabled title="No items available to borrow and no active borrows to return.">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      <span>New Transaction</span>
                    </button>
                  <?php else: ?>
                    <button class="btn btn-primary-action btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransaction">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      <span>New Transaction</span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
              
              <ul class="nav folder-tabs px-4 pt-3" id="txnTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#txn-all">All</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-borrow">Borrowed</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#txn-return">Returned</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="txn-all">
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <div class="btn-group" role="group">
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_all" autocomplete="off" checked onclick="filterTableByPill('#txnAllTable', 'All', 0)">
                          <label class="btn btn-outline-primary btn-sm rounded-start-pill px-3" for="btn_txn_all">All</label>
                        
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_res" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Reserved', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_res">Reserved</label>

                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_rel" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Released', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_rel">Released</label>
                        
                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_ret" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Returned', 0)">
                          <label class="btn btn-outline-primary btn-sm px-3" for="btn_txn_ret">Returned</label>

                          <input type="radio" class="btn-check" name="btnradio_txn" id="btn_txn_can" autocomplete="off" onclick="filterTableByPill('#txnAllTable', 'Cancelled', 0)">
                          <label class="btn btn-outline-primary btn-sm rounded-end-pill px-3" for="btn_txn_can">Cancelled</label>
                      </div>
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnAllTable" placeholder="Search transactions..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnAllTable">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($unifiedTransactions as $t): ?>
                          <tr>
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
                            <td><?= $t['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $t['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['itemDesc']) ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $t['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></a></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-return', 'row-ret-<?= $t['retTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $t['retTransID'] ?></a></td>
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
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnBorrowTable" placeholder="Search borrowed items..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnBorrowTable">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($borrows as $b): ?>
                          <tr id="row-txn-<?= $b['brwTransID'] ?>">
                            <td><?= $b['brwTransID'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $b['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['itemDesc']) ?></a></td>
                            <td><?= $b['brwTransItemQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $b['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="txn-return">
                  <div class="px-4 pt-4 pb-3 d-flex gap-3 align-items-center border-bottom bg-light">
                      <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#txnReturnTable" placeholder="Search returned items..." style="max-width: 250px;">
                  </div>
                  <div class="table-responsive">
                    <table class="table-custom" id="txnReturnTable">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($returns as $r): ?>
                          <tr id="row-ret-<?= $r['retTransID'] ?>">
                            <td><?= $r['retTransID'] ?></td>
                            <td><a href="#" onclick="openSubtabAndHighlight('#tab-transactions', '#txn-borrow', 'row-txn-<?= $r['brwTransID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= $r['brwTransID'] ?></a></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $r['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['itemDesc']) ?></a></td>
                            <td><?= $r['brwTransQty'] ?></td>
                            <td><a href="#" onclick="switchTabAndHighlight('#tab-borrowers', 'row-brw-<?= $r['brwID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></a></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>                </div>
              </div>
            </div>
          </div>
