  <div class="modal fade" id="modalCategory" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Manage Categories</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
          <form action="actions/item_action.php" method="POST" class="mb-4">
            <input type="hidden" name="action" value="add_category">
            <div class="input-group">
                <input type="text" name="categoryName" class="form-control" placeholder="New category name..." required>
                <button type="submit" class="btn btn-primary-action">Add</button>
            </div>
          </form>
          
          <h6 class="fw-bold mb-2">Existing Categories</h6>
          <ul class="list-group">
            <?php foreach($categories as $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($c['categoryName']) ?>
                <form action="actions/item_action.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this category? This will not affect items that already use this category name.');">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="categoryID" value="<?= htmlspecialchars($c['categoryID'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 border-0" title="Delete category">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </form>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
