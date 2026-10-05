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
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#modal-tab-checkout" type="button" role="tab">Process Borrow</button>
              </li>
              <?php if(count($borrows) > 0): ?>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#modal-tab-return" type="button" role="tab">Process Return</button>
              </li>
              <?php endif; ?>
            </ul>
        </div>

        <div class="tab-content px-4 pb-4">
            <!-- Subtab: Checkout -->
            <div class="tab-pane fade show active" id="modal-tab-checkout" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" class="bg-white rounded-bottom rounded-end border shadow-sm">
                  <input type="hidden" name="action" value="create_borrow">
                  <div class="modal-body p-4">
                    <?php if (count($borrowers) > 0): ?>
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrower (Student ID)</label>
                      <div class="position-relative">
                        <input type="text" id="borrowerSearch" name="searchStudentID" class="form-control" placeholder="Search Student ID..." autocomplete="off">
                        <div id="borrowerResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      </div>
                      <!-- Hidden ID (empty if new borrower) -->
                      <input type="hidden" name="brwID" id="selectedBrwID">
                    </div>
                    
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddNewBorrower" onclick="enableNewBorrower()">+ Add New Borrower</button>
                    </div>
                    <?php else: ?>
                        <!-- Hidden ID (empty if new borrower) -->
                        <input type="hidden" name="brwID" id="selectedBrwID">
                    <?php endif; ?>

                    <div class="bg-light p-3 mb-4 rounded border">
                        <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Borrower Details</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="brwFName" id="bfName" class="form-control form-control-sm" placeholder="First Name" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwLName" id="blName" class="form-control form-control-sm" placeholder="Last Name" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwStudentID" id="bStudentID" class="form-control form-control-sm" placeholder="Student ID" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwContact" id="bContact" class="form-control form-control-sm" placeholder="Contact No." required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwCollege" id="bCollege" class="form-control form-control-sm" placeholder="College" required readonly>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwDept" id="bDept" class="form-control form-control-sm" placeholder="Organization/Dept" required readonly>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Items to Borrow</h6>
                    <div id="borrowItemsContainer">
                        <div class="row borrow-item-row mb-2">
                          <div class="col-md-8">
                            <select name="itemID[]" class="form-select form-select-sm" required>
                              <option value="">Select Item...</option>
                              <?php $availableItemsCount = 0; foreach($items as $i): if($i['itemAvailableQty']>0): $availableItemsCount++; ?>
                                <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
                              <?php endif; endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" value="1" min="1" required>
                          </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="btnAddNewBorrowItem" onclick="addBorrowItemRow()" <?= $availableItemsCount <= 1 ? 'style="display:none;"' : '' ?>>+ Add Item</button>

                    <div class="row">
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Borrow Date</label><input type="date" name="brwTransBorrowOnDate" id="borrowOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                      <div class="col-md-6 mb-3"><label class="form-label fw-bold">Return By Date</label><input type="date" name="brwTransReturnByDate" id="returnByDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
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
                    <div class="mb-4">
                      <label class="form-label fw-bold">Search Borrower (Student ID)</label>
                      <div class="position-relative">
                        <input type="text" id="retBorrowerSearch" class="form-control" placeholder="Search Student ID..." autocomplete="off" required>
                        <div id="retBorrowerResults" class="list-group position-absolute w-100 shadow-sm mt-1" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                      </div>
                      <input type="hidden" name="brwID" id="retSelectedBrwID" required>
                    </div>
                    
                    <div id="retBorrowerPreview" class="alert border bg-light p-3 mb-4" style="display:none;">
                      <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                          <h6 class="fw-bold text-primary mb-1" id="retPvName"></h6>
                          <div class="small text-muted">Student ID: <span id="retPvStudentID" class="fw-semibold text-dark"></span></div>
                        </div>
                      </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Items to Return</h6>
                    
                    <!-- Header Labels -->
                    <div class="row mb-1 px-1">
                      <div class="col-md-5"><label class="form-label small fw-bold text-muted mb-0">Item Borrowed</label></div>
                      <div class="col-md-4"><label class="form-label small fw-bold text-muted mb-0">Date Borrowed</label></div>
                      <div class="col-md-3"><label class="form-label small fw-bold text-muted mb-0">Qty Returned</label></div>
                    </div>
                    
                    <div id="returnItemsContainer">
                        <!-- Cloned row goes here -->
                        <div class="row return-item-row mb-2">
                          <div class="col-md-5">
                            <select name="brwTransID[]" class="form-select form-select-sm active-borrows-select" onchange="updateReturnRow(this)" required>
                                <option value="">Search borrower first...</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <input type="date" class="form-control form-control-sm return-borrow-date" disabled>
                          </div>
                          <div class="col-md-3">
                            <input type="number" name="qty[]" class="form-control form-control-sm return-qty" placeholder="Ret Qty" value="1" min="1" required>
                          </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="btnAddNewReturnItem" onclick="addReturnItemRow()" style="display:none;">+ Add Item</button>

                    <div class="row">
                      <div class="col-md-12 mb-3">
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

  <script>
    // Templates for adding rows dynamically
    const borrowRowTemplate = `
        <div class="row borrow-item-row mb-2">
          <div class="col-md-8">
            <select name="itemID[]" class="form-select form-select-sm" required>
              <option value="">Select Item...</option>
              <?php foreach($items as $i): if($i['itemAvailableQty']>0): ?>
                <option value="<?= $i['itemID'] ?>"><?= htmlspecialchars($i['itemDesc']) ?> (Stock: <?= $i['itemAvailableQty'] ?>)</option>
              <?php endif; endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" value="1" min="1" required>
            <button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('.borrow-item-row').remove()">&times;</button>
          </div>
        </div>
    `;

    function addBorrowItemRow() {
        const maxItems = <?= $availableItemsCount ?>;
        const currentRows = document.querySelectorAll('.borrow-item-row').length;
        if (currentRows < maxItems) {
            document.getElementById('borrowItemsContainer').insertAdjacentHTML('beforeend', borrowRowTemplate);
            if (currentRows + 1 >= maxItems) {
                document.getElementById('btnAddNewBorrowItem').style.display = 'none';
            }
        }
    }
    
    document.getElementById('borrowItemsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.btn-danger')) {
            e.target.closest('.borrow-item-row').remove();
            document.getElementById('btnAddNewBorrowItem').style.display = 'inline-block';
        }
    });
    
    function addReturnItemRow() {
        const maxItems = window.maxReturnItems || 1;
        const currentRows = document.querySelectorAll('.return-item-row').length;
        
        if (currentRows < maxItems) {
            const firstRow = document.querySelector('.return-item-row');
            if (firstRow) {
                const clone = firstRow.cloneNode(true);
                const col3 = clone.querySelector('.col-md-3');
                col3.classList.replace('col-md-3', 'col-md-3');
                col3.classList.add('d-flex', 'gap-2');
                
                clone.querySelector('.active-borrows-select').value = '';
                clone.querySelector('.return-borrow-date').value = '';
                clone.querySelector('.return-qty').value = '1';
                clone.querySelector('.return-qty').max = '';
                
                if(!clone.querySelector('.btn-danger')) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-danger px-2';
                    btn.innerHTML = '&times;';
                    btn.onclick = function() { 
                        this.closest('.return-item-row').remove(); 
                        document.getElementById('btnAddNewReturnItem').style.display = 'inline-block';
                    };
                    col3.appendChild(btn);
                }
                
                document.getElementById('returnItemsContainer').appendChild(clone);
                
                if (currentRows + 1 >= maxItems) {
                    document.getElementById('btnAddNewReturnItem').style.display = 'none';
                }
            }
        }
    }
    
    function updateReturnRow(selectElement) {
        const row = selectElement.closest('.return-item-row');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        const dateInput = row.querySelector('.return-borrow-date');
        const qtyInput = row.querySelector('.return-qty');
        
        if (selectedOption && selectedOption.value) {
            dateInput.value = selectedOption.getAttribute('data-date');
            qtyInput.max = selectedOption.getAttribute('data-qty');
            qtyInput.value = selectedOption.getAttribute('data-qty');
        } else {
            dateInput.value = '';
            qtyInput.max = '';
            qtyInput.value = '1';
        }
    }

    function enableNewBorrower() {
        document.getElementById('selectedBrwID').value = '';
        ['bfName', 'blName', 'bStudentID', 'bContact', 'bCollege', 'bDept'].forEach(id => {
            const el = document.getElementById(id);
            el.removeAttribute('readonly');
            el.value = '';
        });
        const query = document.getElementById('borrowerSearch').value;
        if(query) document.getElementById('bStudentID').value = query;
    }
  </script>
