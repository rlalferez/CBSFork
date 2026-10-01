  <!-- Modal: Add Transaction (Merged Borrow & Return) -->
  <div class="modal fade" id="modalTransaction" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content overflow-hidden border-0 shadow-lg bg-light">
        
        <!-- Segmented Control Subtabs Header -->
        <div class="pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="modal-title fw-bold text-dark m-0">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <ul class="nav folder-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#modal-tab-checkout" type="button" role="tab">Desk Checkout</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#modal-tab-return" type="button" role="tab">Process Return</button>
              </li>
            </ul>
        </div>

        <div class="tab-content px-4 pb-4">
            <!-- Subtab: Checkout -->
            <div class="tab-pane fade show active" id="modal-tab-checkout" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_borrow">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrower (Name or ID)</label>
                      <div class="position-relative">
                        <input type="text" id="borrowerSearch" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <div id="borrowerResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      </div>
                      <input type="hidden" name="brwID" id="selectedBrwID" required>
                    </div>
                    <div class="row bg-light p-3 mb-4 rounded border mx-0" id="borrowerPreview" style="display:none;">
                        <div class="col-6 small"><strong>Name:</strong> <span id="pvName"></span></div>
                        <div class="col-6 small"><strong>College/Org:</strong> <span id="pvOrg"></span></div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Equipment</label>
                        <select name="itemID" class="form-select select2-init" required style="width:100%;">
                          <option value="">Select Item...</option>
                          <?php foreach($items as $i): if($i['itemAvailableQty']>0): ?>
                            <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
                          <?php endif; endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4 mb-3"><label class="form-label fw-bold">Quantity</label><input type="number" name="qty" class="form-control" value="1" min="1" required></div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Borrow Date</label><input type="date" name="brwTransBorrowOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Return By Date</label><input type="date" name="brwTransReturnByDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-0 rounded-bottom"><button type="submit" class="btn btn-primary-action py-2 px-4">Process Checkout</button></div>
                </form>
            </div>
            
            <!-- Subtab: Return -->
            <div class="tab-pane fade" id="modal-tab-return" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_return">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrow Transaction ID</label>
                      <div class="position-relative">
                        <input type="text" id="transactionSearch" name="brwTransID" class="form-control" placeholder="Type TXN-..." autocomplete="off" required>
                        <div id="transactionResults" class="list-group position-absolute w-100 shadow-sm mt-1" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                      </div>
                    </div>
                    
                    <div id="transactionPreview" class="alert border bg-light p-3 mb-3" style="display:none;">
                      <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                          <h6 class="fw-bold text-primary mb-1" id="ptTxnID"></h6>
                          <div class="small text-muted mb-1">Item: <span id="ptItemDesc" class="fw-semibold text-dark"></span></div>
                          <div class="small text-muted">Borrower: <span id="ptBorrower" class="fw-semibold text-dark"></span></div>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Item ID</label>
                        <input type="text" id="retItemID" name="itemID" class="form-control" placeholder="Auto-filled" readonly required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Borrower ID</label>
                        <input type="text" id="retBrwID" name="brwID" class="form-control" placeholder="Auto-filled" readonly required>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Quantity Returned</label>
                        <input type="number" id="retQty" name="qty" class="form-control" value="1" min="1" required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Returned On Date</label>
                        <input type="date" name="retReturnedOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-0 rounded-bottom"><button type="submit" class="btn btn-success py-2 px-4">Confirm Return</button></div>
                </form>
            </div>
        </div>

      </div>
    </div>
  </div>
