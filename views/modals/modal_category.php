  <!-- Modal: Manage Categories -->
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
                    <input type="hidden" name="categoryID" value="<?= $c['categoryID'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 border-0"><i class="fas fa-trash"></i></button>
                </form>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
