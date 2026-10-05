
          <!-- REPORTS TAB -->
          <div class="tab-pane fade <?= $isReportActive ? 'show active' : '' ?>" id="tab-reports">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Reports</h5>
                  <p class="text-muted small mb-0">Generate and print transaction snapshots.</p>
                </div>
              </div>
              
              <div class="p-4 bg-light border-bottom no-print">
                <form method="GET" class="row g-3 align-items-end" id="reportFilterForm">
                  <div class="col-md-4">
                     <label class="form-label fw-bold small">Start Date</label>
                     <input type="date" class="form-control form-control-sm" name="start_date" id="repStartDate" value="<?= htmlspecialchars($repStartDate ?? '') ?>" onchange="document.getElementById('repEndDate').min=this.value; this.form.submit();">
                  </div>
                  <div class="col-md-4">
                     <label class="form-label fw-bold small">End Date</label>
                     <input type="date" class="form-control form-control-sm" name="end_date" id="repEndDate" value="<?= htmlspecialchars($repEndDate ?? '') ?>" min="<?= htmlspecialchars($repStartDate ?? '') ?>" onchange="this.form.submit();">
                  </div>
                  <div class="col-md-4">
                     <button type="button" class="btn btn-primary-action btn-sm w-100" onclick="window.print()">Print Report</button>
                  </div>
                </form>
              </div>

              <!-- Subtabs for Report Types -->
              <ul class="nav folder-tabs px-4 pt-3 no-print" id="repTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rep-unified">All</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-borrow">Borrows</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-return">Returns</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-purchase">Purchases</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rep-inventory">Items</button></li>
              </ul>
              
              <div class="tab-content border-top bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <div class="tab-pane fade show active" id="rep-unified">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>Status</th><th>TXN ID</th><th>Item</th><th>Borrower</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Processed By</th><th>RET ID</th><th>Qty Returned</th><th>Return Date</th><th>Returned To</th></tr></thead>
                      <tbody>
                        <?php foreach($repUnified as $t): ?>
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
                            <td><?= htmlspecialchars($t['itemDesc']) ?></td>
                            <td><?= htmlspecialchars($t['brwFName'].' '.$t['brwLName']) ?></td>
                            <td><?= $t['borrow_qty'] ?></td>
                            <td><?= $t['brwTransDate'] ?></td>
                            <td><?= htmlspecialchars($t['borrow_staff_f'].' '.$t['borrow_staff_l']) ?></td>
                            
                            <?php if($t['retTransID']): ?>
                                <td><?= $t['retTransID'] ?></td>
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

                <div class="tab-pane fade" id="rep-borrow">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Fee</th></tr></thead>
                      <tbody>
                        <?php foreach($repBorrows as $b): ?>
                          <tr>
                            <td><?= $b['brwTransID'] ?></td><td><?= htmlspecialchars($b['itemDesc']) ?></td><td><?= $b['brwTransItemQty'] ?></td>
                            <td><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></td>
                            <td><?= $b['brwTransBorrowOnDate'] ?></td><td><?= $b['brwTransReturnByDate'] ?></td><td><?= $b['brwTransTotal'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade" id="rep-return">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>RET ID</th><th>Borrow TXN ID</th><th>Item</th><th>Qty</th><th>Borrower</th><th>Return Date</th></tr></thead>
                      <tbody>
                        <?php foreach($repReturns as $r): ?>
                          <tr>
                            <td><?= $r['retTransID'] ?></td><td><?= $r['brwTransID'] ?></td>
                            <td><?= htmlspecialchars($r['itemDesc']) ?></td><td><?= $r['brwTransQty'] ?></td>
                            <td><?= htmlspecialchars($r['brwFName'].' '.$r['brwLName']) ?></td>
                            <td><?= $r['retReturnedOnDate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <div class="tab-pane fade" id="rep-purchase">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                      <tbody>
                        <?php foreach($repPurchases as $p): ?>
                          <tr>
                            <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                            <td><?= htmlspecialchars($p['itemDesc']) ?></td><td><?= $p['purQty'] ?></td>
                            <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <div class="tab-pane fade" id="rep-inventory">
                  <div class="table-responsive">
                    <table class="table-custom">
                      <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th></tr></thead>
                      <tbody>
                        <?php foreach($repInventory as $i): ?>
                          <tr>
                            <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                            <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>                  </div>
                </div>
              </div>
            </div>
          </div>
