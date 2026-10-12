          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-inventory' ? 'show active' : '' ?>" id="tab-inventory">
            <div class="content-card">
              <div class="content-card-header">
                <div class="card-title-group">
                  <h5 class="card-title">Items</h5>
                  <p class="card-subtitle">Manage resources, stock, and fees.</p>
                </div>
                <div class="card-header-actions">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#inventoryTable" placeholder="Search items..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-outline-secondary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCategory">Manage Categories</button>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalItem">Add Item</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="inventoryTable">
                  <thead><tr><th>ID</th><th>Description</th><th>Category</th><th class="col-number">Total Qty</th><th class="col-number">Available</th><th class="col-number">Rate</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($items as $i): ?>
                      <tr id="row-item-<?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($i['itemDesc'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($i['itemCategory'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-number"><?= (int)$i['itemTotalQty'] ?></td>
                        <td class="col-number"><?= (int)$i['itemAvailableQty'] ?></td>
                        <td class="col-number"><?= number_format((float)$i['itemRate'], 2) ?></td>
                        <td>
                          <?php if($isAdmin): ?>
                          <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 me-1 edit-item-btn" style="font-size:12px;"
                            data-id="<?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?>"
                            data-desc="<?= htmlspecialchars($i['itemDesc'], ENT_QUOTES, 'UTF-8') ?>"
                            data-category="<?= htmlspecialchars($i['itemCategory'], ENT_QUOTES, 'UTF-8') ?>"
                            data-qty="<?= (int)$i['itemTotalQty'] ?>"
                            data-rate="<?= htmlspecialchars($i['itemRate'], ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                          <form action="actions/item_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive item?');">
                            <input type="hidden" name="action" value="archive_item">
                            <input type="hidden" name="itemID" value="<?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) == 0): ?>
                      <tr><td colspan="7" class="text-center text-muted py-4">No items found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
