        // AJAX Search for Borrower Auto-population
        let searchTimeout;
        $('#borrowerSearch').on('input', function() {
            clearTimeout(searchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#borrowerResults').hide();
                return;
            }
            // Delay request to prevent spam
            searchTimeout = setTimeout(function() {
                $.post('index.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectBorrower('${b.brwID}', '${b.brwFName} ${b.brwLName}', '${b.brwCollege} / ${b.brwOrg}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    $('#borrowerResults').html(html).show();
                });
            }, 300);
        });
    });

    // Helper function to handle borrower selection
    function selectBorrower(id, name, org) {
        $('#selectedBrwID').val(id);
        $('#borrowerSearch').val(name);
        $('#pvName').text(name);
        $('#pvOrg').text(org);
        $('#borrowerPreview').show();
        $('#borrowerResults').hide();
    }
    
    // AJAX Search for Transaction Auto-population
    let txnSearchTimeout;
    $('#transactionSearch').on('input', function() {
        clearTimeout(txnSearchTimeout);
        let query = $(this).val();
        if(query.length < 3) {
            $('#transactionResults').hide();
            return;
        }
        txnSearchTimeout = setTimeout(function() {
            $.post('index.php', { action: 'search_transaction', query: query }, function(data) {
                let html = '';
                data.forEach(function(t) {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectTransaction('${t.brwTransID}', '${t.itemID}', '${t.itemDesc}', '${t.brwID}', '${t.brwFName} ${t.brwLName}', '${t.brwTransItemQty}')">
                              <strong>${t.brwTransID}</strong> - ${t.itemDesc} (Borrower: ${t.brwFName} ${t.brwLName})
                             </a>`;
                });
                $('#transactionResults').html(html).show();
            });
        }, 300);
    });

    // Helper function to handle transaction selection
    function selectTransaction(txnID, itemID, itemDesc, brwID, brwName, maxQty) {
        $('#transactionSearch').val(txnID);
        $('#retItemID').val(itemID);
        $('#retBrwID').val(brwID);
        $('#retQty').val(maxQty);
        $('#retQty').attr('max', maxQty);
        
        $('#ptTxnID').text(txnID);
        $('#ptItemDesc').text(itemDesc + ' (ID: ' + itemID + ')');
        $('#ptBorrower').text(brwName + ' (ID: ' + brwID + ')');
        $('#transactionPreview').show();
        $('#transactionResults').hide();
    }
    
    // Reports Period Toggler
    function togglePeriodInputs() {
        let val = document.getElementById('repPeriod').value;
        document.getElementById('repDayContainer').style.display = (val === 'day') ? 'block' : 'none';
        document.getElementById('repMonthContainer').style.display = (val === 'month') ? 'block' : 'none';
        document.getElementById('repYearContainer').style.display = (val === 'year') ? 'block' : 'none';
    }

    // Open Subtab from Sidebar
    function openSubtab(parentTabId, subTabId) {
        const parentBtn = document.querySelector(`[data-bs-target="${parentTabId}"]`);
        if (parentBtn) {
            new bootstrap.Tab(parentBtn).show();
        }
        
        // Wait a small delay to ensure parent pane is active before showing subtab
        setTimeout(() => {
            const childBtn = document.querySelector(`[data-bs-target="${subTabId}"]`);
            if (childBtn) {
                new bootstrap.Tab(childBtn).show();
            }
        }, 50);
    }
    
    // Cross-tab link highlighting
    function switchTabAndHighlight(tabId, rowId) {
        const tabBtn = document.querySelector(`[data-bs-target="${tabId}"]`);
        if (tabBtn) new bootstrap.Tab(tabBtn).show();
        
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 2500);
            }
        }, 150);
    }
    
    function openSubtabAndHighlight(parentTabId, subTabId, rowId) {
        openSubtab(parentTabId, subTabId);
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 2500);
            }
        }, 200);
    }

    // User Edit Helper
    function editUser(id, fname, lname, email, contact, role) {
        document.querySelector('#modalUser input[name="userID"]').value = id;
        document.querySelector('#modalUser input[name="userFName"]').value = fname;
        document.querySelector('#modalUser input[name="userLName"]').value = lname;
        document.querySelector('#modalUser input[name="userEmail"]').value = email;
        document.querySelector('#modalUser input[name="userContactNo"]').value = contact;
        document.querySelector('#modalUser select[name="userRole"]').value = role;
        
        document.querySelector('#modalUser input[name="password"]').required = false;
        document.querySelector('#modalUser input[name="password"]').placeholder = "Leave blank to keep unchanged";
        
        var modal = new bootstrap.Modal(document.getElementById('modalUser'));
        modal.show();
    }
    
    // Reset modalUser when hidden
    document.getElementById('modalUser')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="userID"]').value = "NEW";
        this.querySelector('input[name="password"]').required = true;
        this.querySelector('input[name="password"]').placeholder = "";
    });
  
</body>
</html>

