

          <!-- PURCHASES TAB -->
          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-purchases' ? 'show active' : '' ?>" id="tab-purchases">
            <div class="content-card">
              <div class="content-card-header">
                <div class="card-title-group">
                  <h5 class="card-title">Purchases</h5>
                  <p class="card-subtitle">Record and track inventory restocks.</p>
                </div>
                <div class="card-header-actions">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#purchasesTable" placeholder="Search purchases..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalPurchase">Log Purchase</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <?php if (count($purchases) == 0): ?>
                  <div class="empty-state-box">
                    <div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div>
                    <h6 class="empty-state-text">No purchases recorded yet</h6>
                  </div>
                <?php else: ?>
                <table class="table-custom" id="purchasesTable">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th class="col-number">Qty</th><th>Date</th><th>Logged By</th><?php if($isAdmin): ?><th>Action</th><?php endif; ?></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-pur-<?= htmlspecialchars($p['purTransID'], ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars($p['purTransID'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($p['purORNo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= htmlspecialchars($p['itemID'], ENT_QUOTES, 'UTF-8') ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc'], ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td class="col-number"><?= (int)$p['purQty'] ?></td>
                        <td><?= htmlspecialchars($p['purDate'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($p['userFName'].' '.$p['userLName'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if($isAdmin): ?>
                        <td>
                          <form action="actions/txn_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive purchase? This will deduct the stocked quantity from the item.');">
                            <input type="hidden" name="action" value="archive_purchase">
                            <input type="hidden" name="purTransID" value="<?= htmlspecialchars($p['purTransID'], ENT_QUOTES, 'UTF-8') ?>">
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
