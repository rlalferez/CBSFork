  <!-- Modal: Add Purchase -->
  <div class="modal fade" id="modalPurchase" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/purchase_action.php" method="POST">
          <input type="hidden" name="action" value="purchase_item">
          <div class="modal-header"><h5 class="modal-title fw-bold">Log Purchase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Equipment</label>
              <select name="itemID" class="form-select select2-init" required style="width:100%;">
                <?php foreach($items as $i): ?><option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">OR Number</label><input type="text" name="purORNo" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Quantity Bought</label><input type="number" name="purQty" class="form-control" value="1" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-success py-2 px-4">Log Purchase</button></div>
        </form>
      </div>
    </div>
  </div>
