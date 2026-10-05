  <!-- Modal: Add Purchase -->
  <div class="modal fade" id="modalPurchase" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form action="actions/txn_action.php" method="POST">
          <input type="hidden" name="action" value="purchase_item">
          <div class="modal-header"><h5 class="modal-title fw-bold">Log Purchase & Restock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="row mb-4">
              <div class="col-md-6"><label class="form-label fw-bold">OR Number</label><input type="text" name="purORNo" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label fw-bold">Purchase Date</label><input type="date" name="purDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            </div>
            
            <h6 class="fw-bold mb-3 border-bottom pb-2">Items Purchased</h6>
            <div id="purchase-item-list">
              <div class="row align-items-end mb-2 purchase-item-row">
                <div class="col-md-5">
                  <label class="form-label fw-bold small">Item Name / Desc</label>
                  <input type="text" name="purItemDesc[]" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Category</label>
                  <select name="purCategory[]" class="form-select" required>
                    <?php foreach($categories as $c): ?><option value="<?= htmlspecialchars($c['categoryName']) ?>"><?= htmlspecialchars($c['categoryName']) ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label fw-bold small">Qty</label>
                  <input type="number" name="purQty[]" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-1">
                  <button type="button" class="btn btn-danger remove-pur-item-btn"><i class="fas fa-trash"></i></button>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-pur-item-btn"><i class="fas fa-plus"></i> Add Item</button>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-success py-2 px-4">Log Purchase</button></div>
        </form>
      </div>
    </div>
  </div>
