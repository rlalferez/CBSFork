          <div class="tab-pane fade" id="tab-inventory">
            <div class="content-card">
              <div class="content-card-header border-bottom d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Items</h5>
                  <p class="text-muted small mb-0">Manage resources, stock, and fees.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#inventoryTable" placeholder="Search items..." style="max-width: 250px;">
                  <?php if($isAdmin): ?>
                    <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalItem">Add Item</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="inventoryTable">
                  <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($items as $i): ?>
                      <tr id="row-item-<?= $i['itemID'] ?>">
                        <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                        <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                        <td>
                          <?php if($isAdmin): ?>
                          <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" style="font-size:12px;" onclick="editItem('<?= $i['itemID'] ?>', '<?= addslashes(htmlspecialchars($i['itemDesc'])) ?>', '<?= addslashes(htmlspecialchars($i['itemCategory'])) ?>', <?= $i['itemRate'] ?>)">Edit</button>
                          <form action="actions/item_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive item?');">
                            <input type="hidden" name="action" value="archive_item"><input type="hidden" name="itemID" value="<?= $i['itemID'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
