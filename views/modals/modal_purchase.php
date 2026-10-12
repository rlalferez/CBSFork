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
                  <input type="text" name="purItemDesc[]" class="form-control item-desc-input" list="purchaseItemsDatalist" required autocomplete="off" oninput="autoFillCategory(this)">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Category</label>
                  <select name="purCategory[]" class="form-select item-category-select" required>
                    <?php foreach($categories as $c): ?><option value="<?= htmlspecialchars($c['categoryName']) ?>"><?= htmlspecialchars($c['categoryName']) ?></option><?php endforeach; ?>
                  </select>
                  <div class="category-lock-msg text-muted small mt-1" style="display:none; font-size:11px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>Category locked to item
                  </div>
                </div>
                <div class="col-md-2">
                  <label class="form-label fw-bold small">Qty</label>
                  <input type="number" name="purQty[]" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-1">
                  <button type="button" class="btn btn-danger remove-pur-item-btn" title="Remove row">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </div>
            </div>
            <datalist id="purchaseItemsDatalist">
              <?php foreach($items as $i): ?>
                  <option value="<?= htmlspecialchars($i['itemDesc'], ENT_QUOTES, 'UTF-8') ?>" data-category="<?= htmlspecialchars($i['itemCategory'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></option>
              <?php endforeach; ?>
            </datalist>
            
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-pur-item-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <span>Add Item</span>
            </button>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-success py-2 px-4">Log Purchase</button></div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function autoFillCategory(inputElement) {
        let val = (inputElement.value || '').trim().toLowerCase();
        let opts = document.getElementById('purchaseItemsDatalist').querySelectorAll('option');
        let matchedCat = null;
        for (let i = 0; i < opts.length; i++) {
            if ((opts[i].value || '').trim().toLowerCase() === val) {
                matchedCat = opts[i].getAttribute('data-category');
                break;
            }
        }
        let row = inputElement.closest('.purchase-item-row');
        if (!row) return;
        let selectElement = row.querySelector('.item-category-select');
        let lockIndicator = row.querySelector('.category-lock-msg');
        
        if (selectElement) {
            if (matchedCat) {
                selectElement.value = matchedCat;
                selectElement.style.pointerEvents = 'none';
                selectElement.style.backgroundColor = '#e9ecef';
                selectElement.setAttribute('tabindex', '-1');
                if (lockIndicator) lockIndicator.style.display = 'block';
            } else {
                selectElement.style.pointerEvents = '';
                selectElement.style.backgroundColor = '';
                selectElement.removeAttribute('tabindex');
                if (lockIndicator) lockIndicator.style.display = 'none';
            }
        }
    }
  </script>
