    $(document).ready(function() {
        // Mobile Sidebar Toggle
        $('#sidebarToggle').on('click', function() {
            $('#sidebarMenu').addClass('show-sidebar');
        });
        $('#sidebarClose').on('click', function() {
            $('#sidebarMenu').removeClass('show-sidebar');
        });

        // AJAX Search for Borrower Auto-population (Checkout)
        let searchTimeout;
        $('#borrowerSearch').on('input', function() {
            clearTimeout(searchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#borrowerResults').hide();
                return;
            }
            searchTimeout = setTimeout(function() {
                $.post('actions/borrower_action.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectBorrower('${b.brwID}', '${b.brwStudentID}', '${b.brwFName}', '${b.brwLName}', '${b.brwCollege}', '${b.brwOrg}', '${b.brwContactNo}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    if (data.length === 0) {
                        html = '<div class="list-group-item text-muted">No results found.</div>';
                    }
                    $('#borrowerResults').html(html).show();
                });
            }, 300);
        });

        // AJAX Search for Borrower (Return)
        let retSearchTimeout;
        $('#retBorrowerSearch').on('input', function() {
            clearTimeout(retSearchTimeout);
            let query = $(this).val();
            if(query.length < 2) {
                $('#retBorrowerResults').hide();
                return;
            }
            retSearchTimeout = setTimeout(function() {
                $.post('actions/borrower_action.php', { action: 'search_borrower', query: query }, function(data) {
                    let html = '';
                    data.forEach(function(b) {
                        html += `<a href="#" class="list-group-item list-group-item-action" onclick="selectRetBorrower('${b.brwID}', '${b.brwStudentID}', '${b.brwFName}', '${b.brwLName}')">
                                  ${b.brwFName} ${b.brwLName} (${b.brwStudentID})
                                 </a>`;
                    });
                    if (data.length === 0) {
                        html = '<div class="list-group-item text-muted">No results found.</div>';
                    }
                    $('#retBorrowerResults').html(html).show();
                });
            }, 300);
        });
        
        // Setup Table Search Filter
        $('.table-search').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            let targetTable = $(this).data('target');
            $(targetTable + ' tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Setup Select2 for Item Modal Category
        if ($('.select2-init').length > 0) {
            $('.select2-init').select2({
                tags: true,
                dropdownParent: $('#modalItem')
            });
        }
    });

    // Helper function to handle checkout borrower selection
    function selectBorrower(id, studentId, fname, lname, college, dept, contact) {
        $('#selectedBrwID').val(id);
        $('#borrowerSearch').val(studentId);
        $('#bStudentID').val(studentId).attr('readonly', true);
        $('#bfName').val(fname).attr('readonly', true);
        $('#blName').val(lname).attr('readonly', true);
        $('#bCollege').val(college).attr('readonly', true);
        $('#bDept').val(dept).attr('readonly', true);
        $('#bContact').val(contact).attr('readonly', true);
        $('#borrowerResults').hide();
    }
    
    // Helper function to handle return borrower selection
    function selectRetBorrower(id, studentId, fname, lname) {
        $('#retSelectedBrwID').val(id);
        $('#retBorrowerSearch').val(studentId);
        $('#retPvName').text(fname + ' ' + lname);
        $('#retPvStudentID').text(studentId);
        $('#retBorrowerPreview').show();
        $('#retBorrowerResults').hide();
        
        // Fetch active borrows
        $.post('actions/txn_action.php', { action: 'search_active_borrows', brwID: id }, function(data) {
            let options = '<option value="">Select borrowed item...</option>';
            data.forEach(function(item) {
                let remaining = item.brwTransItemQty - item.returned_qty;
                options += `<option value="${item.brwTransID}" data-date="${item.brwTransBorrowOnDate}" data-qty="${remaining}">
                              ${item.itemDesc} (Txn: ${item.brwTransID}) - ${remaining} unreturned
                            </option>`;
            });
            // Update all active-borrows-select in case there are multiple
            $('.active-borrows-select').html(options);
        });
    }
    
    // Table Filter by Pill
    function filterTableByPill(tableId, filterText, colIndex) {
        if(filterText === 'All') {
            $(tableId + ' tbody tr').show();
        } else {
            $(tableId + ' tbody tr').each(function() {
                let cellText = $(this).find('td').eq(colIndex).text();
                $(this).toggle(cellText.indexOf(filterText) > -1);
            });
        }
    }

    // Open Subtab from Sidebar
    function openSubtab(parentTabId, subTabId) {
        const parentBtn = document.querySelector(`[data-bs-target="${parentTabId}"]`);
        if (parentBtn) {
            new bootstrap.Tab(parentBtn).show();
        }
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
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 2500);
            }
        }, 150);
    }
    
    function openSubtabAndHighlight(parentTabId, subTabId, rowId) {
        openSubtab(parentTabId, subTabId);
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 2500);
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

    // Item Edit Helper
    function editItem(id, desc, category, rate) {
        document.querySelector('#modalItem input[name="itemID"]').value = id;
        document.querySelector('#modalItem input[name="itemDesc"]').value = desc;
        document.querySelector('#modalItem select[name="itemCategory"]').value = category;
        document.querySelector('#modalItem input[name="itemRate"]').value = rate;
        
        document.getElementById('itemModalTitle').textContent = 'Edit Item';
        document.getElementById('itemModalBtn').textContent = 'Save Changes';
        
        var modal = new bootstrap.Modal(document.getElementById('modalItem'));
        modal.show();
    }

    // Reset modalItem when hidden
    document.getElementById('modalItem')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="itemID"]').value = 'NEW';
        document.getElementById('itemModalTitle').textContent = 'Add Item';
        document.getElementById('itemModalBtn').textContent = 'Save Item';
    });

