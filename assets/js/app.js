    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 4000);

        $('#sidebarToggle').on('click', function() {
            $('#sidebarMenu').addClass('show-sidebar');
        });
        $('#sidebarClose').on('click', function() {
            $('#sidebarMenu').removeClass('show-sidebar');
        });
        
        $('form').on('submit', function(e) {
            if (e.isDefaultPrevented()) {
                return;
            }
            let form = this;
            if (form.checkValidity && !form.checkValidity()) {
                return;
            }
            let $btn = $(this).find('button[type="submit"]');
            if ($btn.length) {
                if ($btn.data('submitted') === true) {
                    e.preventDefault();
                    return false;
                }
                $btn.data('submitted', true);
                setTimeout(function() {
                    $btn.prop('disabled', true);
                    $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...');
                }, 10);
            }
        });

        let serverTab = $('main.app-main').attr('data-server-tab');
        if (serverTab) {
            let serverTarget = serverTab.startsWith('#') ? serverTab : '#' + serverTab;
            localStorage.setItem('activeTab', serverTarget);
            let tabBtn = document.querySelector(`[data-bs-target="${serverTarget}"]`);
            if (tabBtn) new bootstrap.Tab(tabBtn).show();
        } else {
            let activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                let tabBtn = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (tabBtn) new bootstrap.Tab(tabBtn).show();
            }
        }
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            let target = $(e.target).attr("data-bs-target");
            localStorage.setItem('activeTab', target);
        });

        setupBorrowerSearch('#borrowerSearch', '#borrowerResults', 'borrower-search-item', function(b) {
            selectBorrower(b.brwID, b.brwStudentID, b.brwFName, b.brwLName, b.brwCollege, b.brwOrg, b.brwContactNo);
        });

        setupBorrowerSearch('#retBorrowerSearch', '#retBorrowerResults', 'ret-borrower-search-item', function(b) {
            selectRetBorrower(b.brwID, b.brwStudentID, b.brwFName, b.brwLName);
        });
        
        $('.table-search').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            let targetTable = $(this).data('target');
            if (targetTable) {
                $(targetTable + ' tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            }
        });

        $('#txnGlobalSearch').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            let activePane = $('#tab-transactions .tab-content .tab-pane.active');
            activePane.find('table tbody tr').filter(function() {
                if ($(this).find('td[colspan]').length > 0) return true;
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        $('#txnTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            let value = $('#txnGlobalSearch').val().toLowerCase();
            let targetPane = $($(this).data('bs-target'));
            if (value) {
                targetPane.find('table tbody tr').filter(function() {
                    if ($(this).find('td[colspan]').length > 0) return true;
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            } else {
                targetPane.find('table tbody tr').show();
            }
        });

        $('#add-pur-item-btn').on('click', function() {
            const firstRow = document.querySelector('.purchase-item-row');
            if (firstRow) {
                const clone = firstRow.cloneNode(true);
                clone.querySelector('input[name="purItemDesc[]"]').value = '';
                clone.querySelector('input[name="purQty[]"]').value = '1';
                const catSelect = clone.querySelector('.item-category-select');
                if (catSelect) {
                    catSelect.selectedIndex = 0;
                    catSelect.style.pointerEvents = '';
                    catSelect.style.backgroundColor = '';
                    catSelect.removeAttribute('tabindex');
                }
                const lockMsg = clone.querySelector('.category-lock-msg');
                if (lockMsg) lockMsg.style.display = 'none';
                document.getElementById('purchase-item-list').appendChild(clone);
            }
        });
        
        $(document).on('click', '.remove-pur-item-btn', function() {
            if ($('.purchase-item-row').length > 1) {
                $(this).closest('.purchase-item-row').remove();
            }
        });

        document.getElementById('modalPurchase')?.addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            const rows = this.querySelectorAll('.purchase-item-row');
            for (let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
            const firstSelect = this.querySelector('.item-category-select');
            if (firstSelect) {
                firstSelect.style.pointerEvents = '';
                firstSelect.style.backgroundColor = '';
                firstSelect.removeAttribute('tabindex');
            }
            const lockMsg = this.querySelector('.category-lock-msg');
            if (lockMsg) lockMsg.style.display = 'none';
        });
        
        $('#borrowOnDate').on('change', function() {
            $('#returnByDate').attr('min', $(this).val());
            if ($('#returnByDate').val() < $(this).val()) {
                $('#returnByDate').val($(this).val());
            }
            if (typeof onBorrowDatesChanged === 'function') {
                onBorrowDatesChanged();
            }
        });
        
        $('#returnByDate').on('change', function() {
            if (typeof onBorrowDatesChanged === 'function') {
                onBorrowDatesChanged();
            }
        });

        $(document).on('click', '.edit-item-btn', function() {
            const id = $(this).data('id');
            const desc = $(this).data('desc');
            const category = $(this).data('category');
            const qty = $(this).data('qty');
            const rate = $(this).data('rate');
            editItem(id, desc, category, qty, rate);
        });

        $(document).on('click', '.edit-user-btn', function() {
            const id = $(this).data('id');
            const fname = $(this).data('fname');
            const lname = $(this).data('lname');
            const email = $(this).data('email');
            const contact = $(this).data('contact');
            const role = $(this).data('role');
            editUser(id, fname, lname, email, contact, role);
        });

        $(document).on('click', '.edit-borrower-btn', function() {
            const id = $(this).data('id');
            const studentId = $(this).data('studentid');
            const fname = $(this).data('fname');
            const lname = $(this).data('lname');
            const college = $(this).data('college');
            const org = $(this).data('org');
            const contact = $(this).data('contact');
            editBorrower(id, studentId, fname, lname, college, org, contact);
        });
    });

    function setupBorrowerSearch(inputId, resultsId, itemClass, onSelect) {
        let searchTimeout;
        $(inputId).on('input', function() {
            clearTimeout(searchTimeout);
            let query = $(this).val().trim();
            if (query.length < 2) {
                $(resultsId).hide().empty();
                return;
            }
            searchTimeout = setTimeout(function() {
                $.post('actions/borrower_action.php', { action: 'search_borrower', query: query }, function(data) {
                    $(resultsId).empty();
                    if (!data || data.length === 0) {
                        $(resultsId).html('<div class="list-group-item text-muted">No results found.</div>').show();
                        return;
                    }
                    data.forEach(function(b) {
                        let item = $(`<a href="#" class="list-group-item list-group-item-action ${itemClass}"></a>`);
                        item.text(`${b.brwFName} ${b.brwLName} (${b.brwStudentID})`);
                        item.data('borrower', b);
                        $(resultsId).append(item);
                    });
                    $(resultsId).show();
                }, 'json');
            }, 300);
        });

        $(document).on('click', '.' + itemClass, function(e) {
            e.preventDefault();
            let b = $(this).data('borrower');
            if (b && typeof onSelect === 'function') {
                onSelect(b);
            }
        });
    }

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
    
    function selectRetBorrower(id, studentId, fname, lname) {
        $('#retSelectedBrwID').val(id);
        $('#retBorrowerSearch').val(studentId);
        $('#retPvName').text(fname + ' ' + lname);
        $('#retPvStudentID').text(studentId);
        $('#retBorrowerPreview').show();
        $('#retBorrowerResults').hide();
        
        $.post('actions/txn_action.php', { action: 'search_active_borrows', brwID: id }, function(data) {
            let options = '<option value="">Select borrowed item...</option>';
            data.forEach(function(item) {
                let remaining = item.brwTransItemQty - item.returned_qty;
                options += `<option value="${item.brwTransID}" data-date="${item.brwTransBorrowOnDate}" data-qty="${remaining}">
                              ${item.itemDesc} (Txn: ${item.brwTransID}) - ${remaining} unreturned
                            </option>`;
            });
            $('.active-borrows-select').html(options);
            
            window.maxReturnItems = data.length;
            if (data.length <= 1) {
                $('#btnAddNewReturnItem').hide();
            } else {
                $('#btnAddNewReturnItem').show();
            }
        });
    }
    
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

    function openSubtab(parentTabId, subTabId) {
        if (subTabId === '#txn-borrow') subTabId = '#txn-released';
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
    
    function highlightTableRow(rowId, delay = 150) {
        setTimeout(() => {
            const row = document.getElementById(rowId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 2500);
            }
        }, delay);
    }

    function switchTabAndHighlight(tabId, rowId) {
        const tabBtn = document.querySelector(`[data-bs-target="${tabId}"]`);
        if (tabBtn) new bootstrap.Tab(tabBtn).show();
        highlightTableRow(rowId, 150);
    }
    
    function openSubtabAndHighlight(parentTabId, subTabId, rowId) {
        if (subTabId === '#txn-borrow') subTabId = '#txn-released';
        openSubtab(parentTabId, subTabId);
        highlightTableRow(rowId, 200);
    }

    function editUser(id, fname, lname, email, contact, role) {
        document.querySelector('#modalUser input[name="userID"]').value = id;
        document.querySelector('#modalUser input[name="userFName"]').value = fname;
        document.querySelector('#modalUser input[name="userLName"]').value = lname;
        document.querySelector('#modalUser input[name="userEmail"]').value = email;
        document.querySelector('#modalUser input[name="userContactNo"]').value = contact;
        document.querySelector('#modalUser select[name="userRole"]').value = role;
        
        document.querySelector('#modalUser input[name="password"]').required = false;
        document.querySelector('#modalUser input[name="password"]').placeholder = "Leave blank to keep unchanged";
        
        let title = document.getElementById('userModalTitle');
        let btn = document.getElementById('userModalBtn');
        if (title) title.textContent = 'Edit User';
        if (btn) btn.textContent = 'Save Changes';

        var modal = new bootstrap.Modal(document.getElementById('modalUser'));
        modal.show();
    }
    
    document.getElementById('modalUser')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="userID"]').value = "NEW";
        this.querySelector('input[name="password"]').required = true;
        this.querySelector('input[name="password"]').placeholder = "";
        let title = document.getElementById('userModalTitle');
        let btn = document.getElementById('userModalBtn');
        if (title) title.textContent = 'Add User';
        if (btn) btn.textContent = 'Save User';
    });

    function editItem(id, desc, category, qty, rate) {
        document.querySelector('#modalItem input[name="itemID"]').value = id;
        document.querySelector('#modalItem input[name="itemDesc"]').value = desc;
        document.querySelector('#modalItem select[name="itemCategory"]').value = category;
        document.querySelector('#modalItem input[name="itemTotalQty"]').value = qty;
        document.querySelector('#modalItem input[name="itemTotalQty"]').readOnly = false;
        document.querySelector('#modalItem input[name="itemRate"]').value = rate;
        
        document.getElementById('itemModalTitle').textContent = 'Edit Item';
        document.getElementById('itemModalBtn').textContent = 'Save Changes';
        
        var modal = new bootstrap.Modal(document.getElementById('modalItem'));
        modal.show();
    }

    document.getElementById('modalItem')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="itemID"]').value = 'NEW';
        this.querySelector('input[name="itemTotalQty"]').readOnly = false;
        document.getElementById('itemModalTitle').textContent = 'Add Item';
        document.getElementById('itemModalBtn').textContent = 'Save Item';
    });

    function editBorrower(id, studentId, fname, lname, college, org, contact) {
        document.querySelector('#modalBorrower input[name="brwID"]').value = id;
        document.querySelector('#modalBorrower input[name="brwStudentID"]').value = studentId;
        document.querySelector('#modalBorrower input[name="brwFName"]').value = fname;
        document.querySelector('#modalBorrower input[name="brwLName"]').value = lname;
        document.querySelector('#modalBorrower input[name="brwCollege"]').value = college;
        document.querySelector('#modalBorrower input[name="brwOrg"]').value = org || '';
        document.querySelector('#modalBorrower input[name="brwContactNo"]').value = contact;

        let title = document.getElementById('borrowerModalTitle');
        let btn = document.getElementById('borrowerModalBtn');
        if (title) title.textContent = 'Edit Borrower Profile';
        if (btn) btn.textContent = 'Save Changes';

        var modal = new bootstrap.Modal(document.getElementById('modalBorrower'));
        modal.show();
    }

    document.getElementById('modalBorrower')?.addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('input[name="brwID"]').value = 'NEW';
        let title = document.getElementById('borrowerModalTitle');
        let btn = document.getElementById('borrowerModalBtn');
        if (title) title.textContent = 'Add Borrower Profile';
        if (btn) btn.textContent = 'Save Borrower';
    });

    window.applyPeriod = function(period) {
        localStorage.setItem('activeTab', '#tab-reports');
        let hash = window.location.hash || '';
        window.location.href = 'index.php?period=' + encodeURIComponent(period) + hash;
    };

    window.applySpecificDay = function() {
        let el = document.getElementById('repCustomDay');
        if (el && el.value) {
            localStorage.setItem('activeTab', '#tab-reports');
            let hash = window.location.hash || '';
            window.location.href = 'index.php?period=day&day=' + encodeURIComponent(el.value) + hash;
        }
    };

    window.applySpecificWeek = function() {
        let el = document.getElementById('repCustomWeek');
        if (el && el.value) {
            localStorage.setItem('activeTab', '#tab-reports');
            let hash = window.location.hash || '';
            window.location.href = 'index.php?period=week&week=' + encodeURIComponent(el.value) + hash;
        }
    };

    window.applySpecificMonth = function() {
        let el = document.getElementById('repCustomMonth');
        if (el && el.value) {
            localStorage.setItem('activeTab', '#tab-reports');
            let hash = window.location.hash || '';
            window.location.href = 'index.php?period=month&month=' + encodeURIComponent(el.value) + hash;
        }
    };

    window.applySpecificYear = function() {
        let el = document.getElementById('repCustomYear');
        if (el && el.value) {
            localStorage.setItem('activeTab', '#tab-reports');
            let hash = window.location.hash || '';
            window.location.href = 'index.php?period=year&year=' + encodeURIComponent(el.value) + hash;
        }
    };
