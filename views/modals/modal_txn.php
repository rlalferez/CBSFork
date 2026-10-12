  <?php 
  $totalItemsCount = 0; 
  foreach($items as $it) { if($it['itemTotalQty'] > 0) $totalItemsCount++; } 
  ?>
  <div class="modal fade" id="modalTransaction" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content overflow-hidden border-0 shadow-lg bg-light">
        <div class="pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="modal-title fw-bold text-dark m-0">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <ul class="nav card-tabs folder-tabs" role="tablist">
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
            <div class="tab-pane fade show active" id="modal-tab-checkout" role="tabpanel">
                <form action="actions/txn_action.php" method="POST" id="borrowForm" class="bg-white rounded-bottom rounded-end border shadow-sm" onsubmit="return handleBorrowSubmit(event, this)">
                  <input type="hidden" name="action" value="create_borrow">
                  <div class="modal-body p-4">
                    <?php $hasBorrowers = count($borrowers) > 0; ?>
                    <?php if ($hasBorrowers): ?>
                    <div class="mb-3">
                      <label class="form-label fw-bold">Search Borrower (Student ID)</label>
                      <div class="position-relative">
                        <input type="text" id="borrowerSearch" name="searchStudentID" class="form-control" placeholder="Search Student ID..." autocomplete="off">
                        <div id="borrowerResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
                      </div>
                      <input type="hidden" name="brwID" id="selectedBrwID">
                    </div>
                    
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddNewBorrower" onclick="enableNewBorrower()">+ Add New Borrower</button>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="brwID" id="selectedBrwID" value="">
                    <?php endif; ?>

                    <div class="bg-light p-3 mb-4 rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Borrower Details</h6>
                            <?php if (!$hasBorrowers): ?>
                                <span class="badge bg-primary text-white small">Direct Registration</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$hasBorrowers): ?>
                            <div class="alert alert-info py-2 px-3 small mb-3 border-0 bg-info-subtle text-info-emphasis d-flex align-items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <span>No borrowers registered yet. Enter the borrower details directly below to register them and complete this borrow transaction.</span>
                            </div>
                        <?php endif; ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="brwFName" id="bfName" class="form-control form-control-sm" placeholder="First Name" required <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwLName" id="blName" class="form-control form-control-sm" placeholder="Last Name" required <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwStudentID" id="bStudentID" class="form-control form-control-sm" placeholder="Student ID (##-#-#####)" pattern="[0-9]{2}-[0-9]-[0-9]{5}" maxlength="10" title="Format: ##-#-##### (e.g. 23-1-12345)" required <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwContact" id="bContact" class="form-control form-control-sm" placeholder="Contact No. (09XXXXXXXXX)" pattern="09[0-9]{9}" maxlength="11" title="11-digit Philippine mobile number starting with 09" required <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwCollege" id="bCollege" class="form-control form-control-sm" placeholder="College" required <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="brwDept" id="bDept" class="form-control form-control-sm" placeholder="Organization/Dept (Optional)" <?= $hasBorrowers ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.85rem; text-transform:uppercase;">Items to Borrow</h6>
                    <div id="borrowItemsContainer">
                        <div class="borrow-item-row p-3 mb-3 rounded border bg-light">
                          <div class="row g-2 align-items-center">
                            <div class="col-md-8">
                              <label class="form-label small fw-bold text-muted mb-1">Item Description</label>
                              <select name="itemID[]" class="form-select form-select-sm borrow-item-select" required onchange="checkRowAvailability(this)">
                                <option value="">Select Item...</option>
                                <?php foreach($items as $i): if($i['itemTotalQty']>0): ?>
                                  <option value="<?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)$i['itemTotalQty'] ?>" data-rate="<?= (float)$i['itemRate'] ?>">
                                    <?= htmlspecialchars($i['itemDesc'], ENT_QUOTES, 'UTF-8') ?> (Total Stock: <?= (int)$i['itemTotalQty'] ?>)
                                  </option>
                                <?php endif; endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label small fw-bold text-muted mb-1">Quantity</label>
                              <div class="d-flex gap-2">
                                <input type="number" name="qty[]" class="form-control form-control-sm borrow-item-qty" placeholder="Qty" value="1" min="1" required oninput="checkRowAvailability(this)">
                                <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-remove-borrow-item" onclick="removeBorrowItemRow(this)" style="display:none;" title="Remove Item">&times;</button>
                              </div>
                            </div>
                          </div>
                          <div class="borrow-item-feedback-container mt-2">
                            <div class="borrow-item-feedback border rounded py-2 px-3 small bg-white text-muted" style="min-height: 38px; display: flex; align-items: center;">Choose an item</div>
                          </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="btnAddNewBorrowItem" onclick="addBorrowItemRow()" <?= $totalItemsCount <= 1 ? 'style="display:none;"' : '' ?>>+ Add Item</button>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Borrow Date</label>
                        <input type="date" name="brwTransBorrowOnDate" id="borrowOnDate" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="onBorrowDatesChanged()">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Return By Date</label>
                        <input type="date" name="brwTransReturnByDate" id="returnByDate" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="onBorrowDatesChanged()">
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-0 rounded-bottom"><button type="submit" class="btn btn-brand btn-primary-action py-2 px-4" id="btnSubmitBorrow">Process Checkout</button></div>
                </form>
            </div>
            
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
                    
                    <div class="row mb-1 px-1">
                      <div class="col-md-5"><label class="form-label small fw-bold text-muted mb-0">Item Borrowed</label></div>
                      <div class="col-md-4"><label class="form-label small fw-bold text-muted mb-0">Date Borrowed</label></div>
                      <div class="col-md-3"><label class="form-label small fw-bold text-muted mb-0">Qty Returned</label></div>
                    </div>
                    
                    <div id="returnItemsContainer">
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

  <div class="modal fade" id="modalStockConflictConfirm" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content overflow-hidden border-0 shadow-lg">
        <div class="modal-header border-bottom py-3 px-4">
          <h5 class="modal-title fw-bold text-dark m-0">Confirm Borrow Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4" id="modalStockConflictBody">
        </div>
        <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-end gap-2" id="modalStockConflictFooter">
          <button type="button" class="btn btn-outline-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-brand btn-primary-action py-2 px-4" id="btnConfirmAdjustStock">Continue with Available Items</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const borrowRowTemplate = `
        <div class="borrow-item-row p-3 mb-3 rounded border bg-light">
          <div class="row g-2 align-items-center">
            <div class="col-md-8">
              <label class="form-label small fw-bold text-muted mb-1">Item Description</label>
              <select name="itemID[]" class="form-select form-select-sm borrow-item-select" required onchange="checkRowAvailability(this)">
                <option value="">Select Item...</option>
                <?php foreach($items as $i): if($i['itemTotalQty']>0): ?>
                  <option value="<?= htmlspecialchars($i['itemID'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)$i['itemTotalQty'] ?>" data-rate="<?= (float)$i['itemRate'] ?>">
                    <?= htmlspecialchars($i['itemDesc'], ENT_QUOTES, 'UTF-8') ?> (Total Stock: <?= (int)$i['itemTotalQty'] ?>)
                  </option>
                <?php endif; endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-muted mb-1">Quantity</label>
              <div class="d-flex gap-2">
                <input type="number" name="qty[]" class="form-control form-control-sm borrow-item-qty" placeholder="Qty" value="1" min="1" required oninput="checkRowAvailability(this)">
                <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-remove-borrow-item" onclick="removeBorrowItemRow(this)" title="Remove Item">&times;</button>
              </div>
            </div>
          </div>
          <div class="borrow-item-feedback-container mt-2">
            <div class="borrow-item-feedback border rounded py-2 px-3 small bg-white text-muted" style="min-height: 38px; display: flex; align-items: center;">Choose an item</div>
          </div>
        </div>
    `;

    function addBorrowItemRow() {
        const maxItems = <?= (int)$totalItemsCount ?>;
        const currentRows = document.querySelectorAll('.borrow-item-row').length;
        if (currentRows < maxItems) {
            document.getElementById('borrowItemsContainer').insertAdjacentHTML('beforeend', borrowRowTemplate);
            if (currentRows + 1 >= maxItems) {
                document.getElementById('btnAddNewBorrowItem').style.display = 'none';
            }
        }
    }
    
    function removeBorrowItemRow(btn) {
        btn.closest('.borrow-item-row').remove();
        document.getElementById('btnAddNewBorrowItem').style.display = 'inline-block';
        validateBorrowForm();
    }

    function checkRowAvailability(el) {
        const row = el.closest('.borrow-item-row');
        if (!row) return;
        const select = row.querySelector('.borrow-item-select');
        const qtyInput = row.querySelector('.borrow-item-qty');
        const feedback = row.querySelector('.borrow-item-feedback');
        const itemID = select ? select.value : '';
        const qty = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
        const startDate = document.getElementById('borrowOnDate')?.value || '';
        const endDate = document.getElementById('returnByDate')?.value || '';

        if (!itemID) {
            if (feedback) {
                feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-muted';
                feedback.textContent = 'Choose an item';
            }
            row.removeAttribute('data-date-error');
            row.removeAttribute('data-conflict');
            row.removeAttribute('data-available');
            row.removeAttribute('data-requested');
            row.removeAttribute('data-item-name');
            validateBorrowForm();
            return;
        }

        if (startDate && endDate && endDate < startDate) {
            if (feedback) {
                feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-danger';
                feedback.textContent = 'Return date cannot be earlier than borrow date.';
            }
            row.setAttribute('data-date-error', '1');
            row.removeAttribute('data-conflict');
            validateBorrowForm();
            return;
        }

        row.removeAttribute('data-date-error');

        if (feedback) {
            feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-muted';
            feedback.textContent = 'Checking availability for selected dates...';
        }

        const params = new URLSearchParams();
        params.append('action', 'check_availability');
        params.append('itemID', itemID);
        params.append('qty', qty);
        params.append('borrowOnDate', startDate);
        params.append('returnByDate', endDate);

        fetch('actions/txn_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (feedback) {
                if (data.success) {
                    feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-success';
                    feedback.textContent = data.message;
                    row.removeAttribute('data-conflict');
                    row.removeAttribute('data-available');
                    row.removeAttribute('data-requested');
                    row.removeAttribute('data-item-name');
                } else {
                    feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-danger';
                    feedback.textContent = data.message;
                    row.setAttribute('data-conflict', '1');
                    row.setAttribute('data-available', data.available !== undefined ? data.available : 0);
                    row.setAttribute('data-requested', qty);
                    row.setAttribute('data-item-name', (data.item && data.item.itemDesc) ? data.item.itemDesc : (select.options[select.selectedIndex]?.text || 'Item'));
                }
            }
            validateBorrowForm();
        })
        .catch(err => {
            if (feedback) {
                feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-muted';
                feedback.textContent = 'Choose an item';
            }
            row.removeAttribute('data-conflict');
            validateBorrowForm();
        });
    }

    function onBorrowDatesChanged() {
        const startInput = document.getElementById('borrowOnDate');
        const endInput = document.getElementById('returnByDate');
        if (startInput && endInput) {
            endInput.min = startInput.value;
            if (endInput.value && endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
        }
        document.querySelectorAll('.borrow-item-row').forEach(row => {
            const select = row.querySelector('.borrow-item-select');
            if (select && select.value) {
                checkRowAvailability(select);
            }
        });
        validateBorrowForm();
    }

    function validateBorrowForm() {
        const btn = document.getElementById('btnSubmitBorrow');
        if (!btn) return;
        const dateErrorRows = document.querySelectorAll('.borrow-item-row[data-date-error="1"]');
        const startDate = document.getElementById('borrowOnDate')?.value || '';
        const endDate = document.getElementById('returnByDate')?.value || '';
        if (dateErrorRows.length > 0 || (startDate && endDate && endDate < startDate)) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    }

    function handleBorrowSubmit(e, form) {
        const startDate = document.getElementById('borrowOnDate')?.value || '';
        const endDate = document.getElementById('returnByDate')?.value || '';
        if (startDate && endDate && endDate < startDate) {
            alert("Return date cannot be earlier than borrow date.");
            e.preventDefault();
            return false;
        }

        const rows = form.querySelectorAll('.borrow-item-row');
        const conflictRows = [];
        const zeroAvailableRows = [];

        rows.forEach(row => {
            const select = row.querySelector('.borrow-item-select');
            if (!select || !select.value) return;

            if (row.getAttribute('data-conflict') === '1') {
                const avail = parseInt(row.getAttribute('data-available') || '0');
                const req = parseInt(row.getAttribute('data-requested') || '1');
                const name = row.getAttribute('data-item-name') || select.options[select.selectedIndex].text;
                if (avail <= 0) {
                    zeroAvailableRows.push({ row, name });
                } else {
                    conflictRows.push({ row, name, avail, req });
                }
            }
        });

        if (zeroAvailableRows.length > 0) {
            e.preventDefault();
            showZeroStockModal(zeroAvailableRows);
            return false;
        }

        if (conflictRows.length > 0) {
            e.preventDefault();
            showStockConflictModal(conflictRows, form);
            return false;
        }

        return true;
    }

    function showZeroStockModal(zeroRows) {
        const modalEl = document.getElementById('modalStockConflictConfirm');
        const bodyEl = document.getElementById('modalStockConflictBody');
        const footerEl = document.getElementById('modalStockConflictFooter');
        if (!modalEl || !bodyEl || !footerEl) return;

        let itemsList = zeroRows.map(z => `<li><strong>${escapeHtml(z.name)}</strong> (0 units available)</li>`).join('');
        bodyEl.innerHTML = `
            <div class="alert alert-warning py-2 px-3 small border-0 bg-warning-subtle text-warning-emphasis mb-3">
                The requested item has <strong>0 units</strong> available for the selected dates and cannot be checked out.
            </div>
            <ul class="small mb-3 ps-3">
                ${itemsList}
            </ul>
            <p class="small text-muted mb-0">Please cancel to adjust your dates or choose a different item.</p>
        `;

        footerEl.innerHTML = `
            <button type="button" class="btn btn-secondary py-2 px-4" data-bs-dismiss="modal">Close</button>
        `;

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }

    function showStockConflictModal(conflictRows, form) {
        const modalEl = document.getElementById('modalStockConflictConfirm');
        const bodyEl = document.getElementById('modalStockConflictBody');
        const footerEl = document.getElementById('modalStockConflictFooter');
        if (!modalEl || !bodyEl || !footerEl) return;

        let tableRows = conflictRows.map(c => `
            <tr>
                <td class="ps-3 py-2 fw-semibold">${escapeHtml(c.name)}</td>
                <td class="text-center py-2 text-muted text-decoration-line-through">${c.req}</td>
                <td class="text-center py-2 text-primary fw-bold">${c.avail}</td>
            </tr>
        `).join('');

        bodyEl.innerHTML = `
            <div class="table-responsive border rounded mb-3 bg-white">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3 py-2">Item Description</th>
                            <th class="text-center py-2">Requested</th>
                            <th class="text-center py-2 text-primary fw-bold">Available</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        ${tableRows}
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0">
                Clicking <strong>Continue with Available Items</strong> will adjust your quantity to the available amount and process the checkout.
            </p>
        `;

        footerEl.innerHTML = `
            <button type="button" class="btn btn-outline-secondary py-2 px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary-action py-2 px-4" id="btnConfirmAdjustStock">Continue with Available Items</button>
        `;

        const btnConfirm = document.getElementById('btnConfirmAdjustStock');
        if (btnConfirm) {
            btnConfirm.onclick = function() {
                conflictRows.forEach(c => {
                    const qtyInput = c.row.querySelector('.borrow-item-qty');
                    if (qtyInput) {
                        qtyInput.value = c.avail;
                    }
                    c.row.removeAttribute('data-conflict');
                    const feedback = c.row.querySelector('.borrow-item-feedback');
                    if (feedback) {
                        feedback.className = 'borrow-item-feedback border rounded py-2 px-3 small bg-white text-success';
                        feedback.textContent = 'Available (' + c.avail + ' unit' + (c.avail === 1 ? '' : 's') + ' selected).';
                    }
                });

                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) {
                    bsModal.hide();
                }

                setTimeout(() => {
                    form.submit();
                }, 150);
            };
        }

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    function addReturnItemRow() {
        const maxItems = window.maxReturnItems || 1;
        const currentRows = document.querySelectorAll('.return-item-row').length;
        
        if (currentRows < maxItems) {
            const firstRow = document.querySelector('.return-item-row');
            if (firstRow) {
                const clone = firstRow.cloneNode(true);
                const col3 = clone.querySelector('.col-md-3');
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
            if (el) {
                el.removeAttribute('readonly');
                el.value = '';
            }
        });
        const searchInput = document.getElementById('borrowerSearch');
        const query = searchInput ? searchInput.value.trim() : '';
        if (query && document.getElementById('bStudentID')) {
            document.getElementById('bStudentID').value = query;
        }
        const fNameInput = document.getElementById('bfName');
        if (fNameInput) fNameInput.focus();
    }

    function checkBorrowerStudentID(studentId) {
        if (!studentId || !/^[0-9]{2}-[0-9]-[0-9]{5}$/.test(studentId)) {
            return;
        }
        const selectedId = document.getElementById('selectedBrwID')?.value;
        if (selectedId) return;

        const params = new URLSearchParams();
        params.append('action', 'check_student_id');
        params.append('studentId', studentId);

        fetch('actions/borrower_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(res => res.json())
        .then(res => {
            if (res && res.exists) {
                const status = (parseInt(res.is_archived) === 1) ? 'Archived' : 'Active';
                alert(`Cannot use Student ID ${res.borrower.brwStudentID}: It is already registered to an ${status} borrower (${res.borrower.brwFName} ${res.borrower.brwLName}).\n\nPlease select them from the search bar above or enter a different Student ID.`);
                const studentInput = document.getElementById('bStudentID');
                if (studentInput) {
                    studentInput.value = '';
                    studentInput.focus();
                }
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const studentIdInput = document.getElementById('bStudentID');
        if (studentIdInput) {
            studentIdInput.addEventListener('blur', function() {
                if (!this.readOnly && this.value.trim().length >= 10) {
                    checkBorrowerStudentID(this.value.trim());
                }
            });
        }

        const modalTxn = document.getElementById('modalTransaction');
        if (modalTxn) {
            modalTxn.addEventListener('show.bs.modal', function() {
                const hasBorrowers = <?= $hasBorrowers ? 'true' : 'false' ?>;
                if (!hasBorrowers) {
                    ['bfName', 'blName', 'bStudentID', 'bContact', 'bCollege', 'bDept'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.removeAttribute('readonly');
                    });
                }
            });
        }

        const conflictModalEl = document.getElementById('modalStockConflictConfirm');
        if (conflictModalEl) {
            conflictModalEl.addEventListener('show.bs.modal', function () {
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 1) {
                        backdrops[backdrops.length - 1].style.zIndex = '1065';
                    }
                }, 10);
            });
            conflictModalEl.addEventListener('hidden.bs.modal', function () {
                if (document.getElementById('modalTransaction')?.classList.contains('show')) {
                    document.body.classList.add('modal-open');
                }
            });
        }
    });
  </script>
