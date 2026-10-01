                <form action="actions/item_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
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
