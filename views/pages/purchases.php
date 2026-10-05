

          <!-- PURCHASES TAB -->
          <div class="tab-pane fade" id="tab-purchases">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Purchases</h5>
                  <p class="text-muted small mb-0">Record and track inventory restocks.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#purchasesTable" placeholder="Search purchases..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalPurchase">Log Purchase</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <?php if (count($purchases) == 0): ?>
                  <div class="text-center py-5">
                    <div class="text-muted mb-3"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div>
                    <h6 class="text-secondary fw-semibold">No purchases recorded yet</h6>
                  </div>
                <?php else: ?>
                <table class="table-custom" id="purchasesTable">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th><?php if($isAdmin): ?><th>Action</th><?php endif; ?></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-pur-<?= $p['purTransID'] ?>">
                        <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $p['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc']) ?></a></td><td><?= $p['purQty'] ?></td>
                        <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                        <?php if($isAdmin): ?>
                        <td>
                          <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive purchase? This will deduct the stocked quantity from the item.');">
                            <input type="hidden" name="action" value="archive_purchase">
                            <input type="hidden" name="purTransID" value="<?= $p['purTransID'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                        <?php endif; ?>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </div>
          </div>
