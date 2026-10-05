

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
                <table class="table-custom" id="purchasesTable">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-pur-<?= $p['purTransID'] ?>">
                        <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-item-<?= $p['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc']) ?></a></td><td><?= $p['purQty'] ?></td>
                        <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>                </table>
              </div>
            </div>
          </div>
