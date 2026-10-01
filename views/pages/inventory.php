          <div class="tab-pane fade" id="tab-inventory">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Equipment Inventory</h5>
                  <p class="text-muted small mb-0">Manage resources, stock, and fees.</p>
                </div>
                <?php if($isAdmin): ?>
                  <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalItem">Add Item</button>
                <?php endif; ?>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Rate</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($items as $i): ?>
                      <tr id="row-item-<?= $i['itemID'] ?>">
                        <td><?= $i['itemID'] ?></td><td><?= htmlspecialchars($i['itemDesc']) ?></td><td><?= htmlspecialchars($i['itemCategory']) ?></td>
                        <td><?= $i['itemTotalQty'] ?></td><td><?= $i['itemAvailableQty'] ?></td><td><?= $i['itemRate'] ?></td>
                        <td>
                          <?php if($isAdmin): ?>
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
