  <!-- Modal: Add/Edit Item -->
  <div class="modal fade" id="modalItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/item_action.php" method="POST">
          <input type="hidden" name="action" value="save_item">
          <input type="hidden" name="itemID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add Equipment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Description / Name</label><input type="text" name="itemDesc" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Category</label><input type="text" name="itemCategory" class="form-control" value="Audio & Visual" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Rate (Fee)</label><input type="number" step="0.01" name="itemRate" class="form-control" value="0" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save Item</button></div>
        </form>
      </div>
    </div>
  </div>
