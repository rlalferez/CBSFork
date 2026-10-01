                </div>
              </div>
            </div>
          </div>

          <!-- PURCHASES TAB -->
          <div class="tab-pane fade" id="tab-purchases">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Purchases</h5>
                  <p class="text-muted small mb-0">Record and track inventory restocks.</p>
                </div>
                <?php if($isAdmin): ?>
                  <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalPurchase">Log Purchase</button>
                <?php endif; ?>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>PUR ID</th><th>OR No.</th><th>Item</th><th>Qty</th><th>Date</th><th>Logged By</th></tr></thead>
                  <tbody>
                    <?php foreach($purchases as $p): ?>
                      <tr id="row-<?= $p['purTransID'] ?>">
                        <td><?= $p['purTransID'] ?></td><td><?= htmlspecialchars($p['purORNo']) ?></td>
                        <td><a href="#" onclick="switchTabAndHighlight('#tab-inventory', 'row-<?= $p['itemID'] ?>'); return false;" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['itemDesc']) ?></a></td><td><?= $p['purQty'] ?></td>
                        <td><?= $p['purDate'] ?></td><td><?= htmlspecialchars($p['userFName'].' '.$p['userLName']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>