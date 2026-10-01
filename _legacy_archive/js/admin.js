/**
 * Confederates Student Council - Admin & Staff Portal JS
 * Powers Bookings CRUD, Resources CRUD & Availability, Clients CRUD, Staff CRUD, and Reports
 */

(function () {
  'use strict';

  const state = {
    currentTab: 'bookings',
    bookings: [],
    resources: [],
    clients: [],
    staff: [],
    bookingSearch: '',
    bookingStatus: 'all',
    clientSearch: '',
    categories: []
  };

  // Bootstrap Modal Instances cache
  let resourceModalInstance = null;
  let scheduleModalInstance = null;
  let paymentModalInstance = null;
  let cancelModalInstance = null;
  let clientModalInstance = null;
  let staffModalInstance = null;

  function initBootstrapModals() {
    if (window.bootstrap) {
      const resEl = document.getElementById('resourceModal');
      if (resEl) resourceModalInstance = new bootstrap.Modal(resEl);

      const schEl = document.getElementById('scheduleModal');
      if (schEl) scheduleModalInstance = new bootstrap.Modal(schEl);

      const payEl = document.getElementById('paymentModal');
      if (payEl) paymentModalInstance = new bootstrap.Modal(payEl);

      const canEl = document.getElementById('cancelModal');
      if (canEl) cancelModalInstance = new bootstrap.Modal(canEl);

      const cliEl = document.getElementById('clientModal');
      if (cliEl) clientModalInstance = new bootstrap.Modal(cliEl);

      const stfEl = document.getElementById('staffModal');
      if (stfEl) staffModalInstance = new bootstrap.Modal(stfEl);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ==========================================
  // STATS & KPIS
  // ==========================================
  async function loadStats() {
    try {
      const res = await fetch('api/api.php?action=get_stats');
      const data = await res.json();
      if (data.success) {
        document.getElementById('metricTotalItems').textContent = data.data.total_items;
        document.getElementById('metricBorrowed').textContent = data.data.borrowed_items;
        document.getElementById('metricPending').textContent = data.data.pending_requests;
        document.getElementById('metricOverdue').textContent = data.data.overdue_count;

        renderLogs(data.data.recent_logs);
      }
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  }

  // ==========================================
  // TAB 1: BOOKINGS & SCHEDULE (CRUD)
  // ==========================================
  async function loadBookings() {
    const tbody = document.getElementById('bookingsTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Loading bookings...</td></tr>`;

    try {
      const res = await fetch(`api/api.php?action=get_requests&status=${state.bookingStatus}&search=${encodeURIComponent(state.bookingSearch)}`);
      const data = await res.json();

      if (data.success) {
        state.bookings = data.data;
        renderBookingsTable();
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(data.message)}</td></tr>`;
      }
    } catch (err) {
      console.error('Failed to load bookings:', err);
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Network error loading bookings.</td></tr>`;
    }
  }

  function renderBookingsTable() {
    const tbody = document.getElementById('bookingsTableBody');
    if (!tbody) return;

    if (state.bookings.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No bookings match the selected filters.</td></tr>`;
      return;
    }

    tbody.innerHTML = state.bookings.map(b => {
      const isOverdue = b.is_overdue;
      let statusBadge = `<span class="badge bg-secondary">${b.status}</span>`;
      if (isOverdue) statusBadge = `<span class="badge bg-danger">Overdue Alert</span>`;
      else if (b.status === 'Pending') statusBadge = `<span class="badge bg-warning text-dark">Pending Review</span>`;
      else if (b.status === 'Approved') statusBadge = `<span class="badge bg-primary">Approved</span>`;
      else if (b.status === 'Released') statusBadge = `<span class="badge bg-success">Released (On Loan)</span>`;
      else if (b.status === 'Returned') statusBadge = `<span class="badge bg-secondary">Returned</span>`;
      else if (b.status === 'Cancelled') statusBadge = `<span class="badge bg-dark">Cancelled</span>`;

      // Payment badge
      let paymentBadge = `<span class="badge bg-light text-dark border">${escapeHtml(b.payment_status || 'Free / Waived')}</span>`;
      if (b.payment_status === 'Deposit Paid') paymentBadge = `<span class="badge bg-success">Deposit Paid (₱${b.payment_amount})</span>`;
      else if (b.payment_status === 'Pending Deposit') paymentBadge = `<span class="badge bg-warning text-dark">Pending Deposit (₱${b.payment_amount})</span>`;
      else if (b.payment_status === 'Paid') paymentBadge = `<span class="badge bg-success">Paid (₱${b.payment_amount})</span>`;
      else if (b.payment_status === 'Refunded') paymentBadge = `<span class="badge bg-secondary">Deposit Refunded</span>`;

      // Context Action Buttons
      let actionButtons = `
        <a href="receipt.php?code=${encodeURIComponent(b.tracking_code)}" target="_blank" class="btn btn-outline-secondary btn-sm" title="View / Print Slip">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </a>
        <button class="btn btn-outline-primary btn-sm" onclick="window.ConfedAdmin.openRescheduleModal(${b.id})" title="Change Dates / Reschedule">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
        </button>
        <button class="btn btn-outline-info btn-sm text-dark" onclick="window.ConfedAdmin.openPaymentModal(${b.id})" title="Payment / Deposit Details">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </button>
      `;

      if (b.status === 'Pending') {
        actionButtons += `
          <button class="btn btn-success btn-sm" onclick="window.ConfedAdmin.updateBookingStatus(${b.id}, 'Approved')">Approve</button>
          <button class="btn btn-danger btn-sm" onclick="window.ConfedAdmin.openCancelModal(${b.id}, '${escapeHtml(b.tracking_code)}')">Cancel</button>
        `;
      } else if (b.status === 'Approved') {
        actionButtons += `
          <button class="btn btn-primary btn-sm" onclick="window.ConfedAdmin.updateBookingStatus(${b.id}, 'Released')">Release Items</button>
          <button class="btn btn-outline-danger btn-sm" onclick="window.ConfedAdmin.openCancelModal(${b.id}, '${escapeHtml(b.tracking_code)}')">Cancel</button>
        `;
      } else if (b.status === 'Released' || isOverdue) {
        actionButtons += `
          <button class="btn btn-success btn-sm" onclick="window.ConfedAdmin.updateBookingStatus(${b.id}, 'Returned')">Record Return</button>
        `;
      }

      return `
        <tr>
          <td>
            <strong class="font-monospace text-primary">${escapeHtml(b.tracking_code)}</strong>
            <div class="text-dark fw-semibold mt-1">${escapeHtml(b.event_name)}</div>
            <small class="text-muted">${escapeHtml(b.event_location)}</small>
          </td>
          <td>
            <div class="fw-bold">${escapeHtml(b.full_name)}</div>
            <small class="text-muted d-block">${escapeHtml(b.student_id)} &bull; ${escapeHtml(b.organization_name)}</small>
            <small class="text-muted">${escapeHtml(b.contact_number)}</small>
          </td>
          <td>
            <div><span class="text-muted">Release:</span> <strong>${escapeHtml(b.borrow_date)}</strong></div>
            <div><span class="text-muted">Return:</span> <strong class="${isOverdue ? 'text-danger' : ''}">${escapeHtml(b.expected_return_date)}</strong></div>
            <small class="text-muted">${b.item_count || 1} asset unit(s)</small>
          </td>
          <td>${statusBadge}</td>
          <td>
            <div>${paymentBadge}</div>
            ${b.payment_details ? `<small class="text-muted d-block mt-1" style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(b.payment_details)}">${escapeHtml(b.payment_details)}</small>` : ''}
          </td>
          <td>
            <div class="d-flex align-items-center gap-1 flex-wrap">
              ${actionButtons}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Reschedule Booking
  function openRescheduleModal(requestId) {
    const b = state.bookings.find(x => parseInt(x.id) === requestId);
    if (!b) return;

    document.getElementById('scheduleRequestId').value = b.id;
    document.getElementById('scheduleEventName').value = b.event_name;
    document.getElementById('scheduleEventLocation').value = b.event_location;
    document.getElementById('scheduleBorrowDate').value = b.borrow_date;
    document.getElementById('scheduleReturnDate').value = b.expected_return_date;

    if (scheduleModalInstance) scheduleModalInstance.show();
  }

  // Payment Status
  function openPaymentModal(requestId) {
    const b = state.bookings.find(x => parseInt(x.id) === requestId);
    if (!b) return;

    document.getElementById('paymentRequestId').value = b.id;
    document.getElementById('paymentStatusSelect').value = b.payment_status || 'Free / Waived';
    document.getElementById('paymentAmountInput').value = b.payment_amount || '0.00';
    document.getElementById('paymentDetailsInput').value = b.payment_details || '';

    if (paymentModalInstance) paymentModalInstance.show();
  }

  // Cancel Booking
  function openCancelModal(requestId, trackingCode) {
    document.getElementById('cancelRequestId').value = requestId;
    document.getElementById('cancelTrackingCode').textContent = trackingCode;
    document.getElementById('cancelReasonInput').value = '';

    if (cancelModalInstance) cancelModalInstance.show();
  }

  // Quick Status Update
  async function updateBookingStatus(requestId, newStatus) {
    if (!confirm(`Confirm changing booking #${requestId} status to "${newStatus}"?`)) return;

    const fd = new FormData();
    fd.append('action', 'update_request_status');
    fd.append('request_id', requestId);
    fd.append('status', newStatus);

    try {
      const res = await fetch('api/api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        loadBookings();
        loadStats();
        loadResources();
      } else {
        alert('Action failed: ' + data.message);
      }
    } catch (err) {
      alert('Network error.');
    }
  }


  // ==========================================
  // TAB 2: RESOURCES AVAILABILITY (CRUD)
  // ==========================================
  async function loadResources() {
    const tbody = document.getElementById('resourcesTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Loading resources...</td></tr>`;

    try {
      const res = await fetch('api/api.php?action=get_items');
      const data = await res.json();
      if (data.success) {
        state.resources = data.data;
        renderResourcesTable();
      }
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error loading resources.</td></tr>`;
    }
  }

  function renderResourcesTable() {
    const tbody = document.getElementById('resourcesTableBody');
    if (!tbody) return;

    tbody.innerHTML = state.resources.map(item => {
      const isAvail = item.is_available == 1;
      return `
        <tr>
          <td><span class="font-monospace fw-bold text-primary">${escapeHtml(item.item_code)}</span></td>
          <td>
            <div class="fw-bold">${escapeHtml(item.name)}</div>
            <small class="text-muted">Model: ${escapeHtml(item.model || 'Standard')} &bull; Loc: ${escapeHtml(item.location)}</small>
          </td>
          <td><span class="badge bg-light text-dark border">${escapeHtml(item.category_name)}</span></td>
          <td>
            <strong class="${item.available_qty > 0 ? 'text-success' : 'text-danger'}">${item.available_qty}</strong>
            <span class="text-muted"> / ${item.total_qty}</span>
            <div class="small text-muted">${escapeHtml(item.condition_status)}</div>
          </td>
          <td>
            <span class="badge bg-light text-dark border">${escapeHtml(item.fee_type)}</span>
            ${item.fee_amount > 0 ? `<div class="small fw-semibold text-primary">₱${parseFloat(item.fee_amount).toFixed(2)}</div>` : ''}
          </td>
          <td>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" ${isAvail ? 'checked' : ''} onchange="window.ConfedAdmin.toggleResourceAvailability(${item.id}, this.checked)">
              <label class="form-check-label small ${isAvail ? 'text-success fw-bold' : 'text-danger'}">
                ${isAvail ? 'Available' : 'Unavailable'}
              </label>
            </div>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" onclick="window.ConfedAdmin.openResourceModal(${item.id})">Edit</button>
              <button class="btn btn-outline-danger" onclick="window.ConfedAdmin.deleteResource(${item.id}, '${escapeHtml(item.name)}')">Delete</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openResourceModal(resourceId = 0) {
    document.getElementById('resourceForm').reset();
    document.getElementById('resourceId').value = resourceId;

    if (resourceId > 0) {
      const r = state.resources.find(x => parseInt(x.id) === resourceId);
      if (r) {
        document.getElementById('resourceModalTitle').textContent = `Edit Resource: ${r.item_code}`;
        document.getElementById('resourceCode').value = r.item_code;
        document.getElementById('resourceCategory').value = r.category_id;
        document.getElementById('resourceName').value = r.name;
        document.getElementById('resourceModel').value = r.model || '';
        document.getElementById('resourceLocation').value = r.location || '';
        document.getElementById('resourceTotalQty').value = r.total_qty;
        document.getElementById('resourceCondition').value = r.condition_status;
        document.getElementById('resourceFeeType').value = r.fee_type || 'Free';
        document.getElementById('resourceFeeAmount').value = r.fee_amount || '0.00';
        document.getElementById('resourceDesc').value = r.description || '';
        document.getElementById('resourceAvailableToggle').checked = r.is_available == 1;
      }
    } else {
      document.getElementById('resourceModalTitle').textContent = 'Add New Resource Equipment';
      document.getElementById('resourceCode').value = 'CSC-RES-' + Math.floor(100 + Math.random() * 900);
      document.getElementById('resourceAvailableToggle').checked = true;
    }

    if (resourceModalInstance) resourceModalInstance.show();
  }

  async function toggleResourceAvailability(id, isChecked) {
    const fd = new FormData();
    fd.append('action', 'toggle_resource_availability');
    fd.append('id', id);
    fd.append('is_available', isChecked ? 1 : 0);

    try {
      const res = await fetch('api/api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) {
        alert(data.message);
        loadResources();
      }
    } catch (e) {
      alert('Error updating availability.');
      loadResources();
    }
  }

  async function deleteResource(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}" from inventory?`)) return;

    const fd = new FormData();
    fd.append('action', 'delete_item');
    fd.append('id', id);

    try {
      const res = await fetch('api/api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        loadResources();
        loadStats();
      } else {
        alert(data.message);
      }
    } catch (e) {
      alert('Network error.');
    }
  }


  // ==========================================
  // TAB 3: CLIENT RECORDS (CRUD)
  // ==========================================
  async function loadClients() {
    const tbody = document.getElementById('clientsTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Loading clients...</td></tr>`;

    try {
      const res = await fetch(`api/api.php?action=get_clients&search=${encodeURIComponent(state.clientSearch)}`);
      const data = await res.json();
      if (data.success) {
        state.clients = data.data;
        renderClientsTable();
      }
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error loading clients.</td></tr>`;
    }
  }

  function renderClientsTable() {
    const tbody = document.getElementById('clientsTableBody');
    if (!tbody) return;

    if (state.clients.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No client records found.</td></tr>`;
      return;
    }

    tbody.innerHTML = state.clients.map(c => `
      <tr>
        <td><strong class="font-monospace text-primary">${escapeHtml(c.student_id)}</strong></td>
        <td><div class="fw-bold">${escapeHtml(c.full_name)}</div></td>
        <td>
          <div>${escapeHtml(c.organization_name)}</div>
          <small class="text-muted">${escapeHtml(c.role)}</small>
        </td>
        <td>
          <div>${escapeHtml(c.email)}</div>
          <small class="text-muted">${escapeHtml(c.contact_number)}</small>
        </td>
        <td>
          <span class="badge ${c.status === 'Active' ? 'bg-success' : 'bg-danger'}">${escapeHtml(c.status)}</span>
        </td>
        <td><strong class="text-dark">${c.total_bookings || 0}</strong> bookings</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" onclick="window.ConfedAdmin.openClientModal(${c.id})">Edit</button>
            <button class="btn btn-outline-danger" onclick="window.ConfedAdmin.deleteClient(${c.id}, '${escapeHtml(c.full_name)}')">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function openClientModal(clientId = 0) {
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = clientId;

    if (clientId > 0) {
      const c = state.clients.find(x => parseInt(x.id) === clientId);
      if (c) {
        document.getElementById('clientModalTitle').textContent = `Edit Client: ${c.full_name}`;
        document.getElementById('clientStudentId').value = c.student_id;
        document.getElementById('clientFullName').value = c.full_name;
        document.getElementById('clientEmail').value = c.email;
        document.getElementById('clientContact').value = c.contact_number;
        document.getElementById('clientOrg').value = c.organization_name;
        document.getElementById('clientRole').value = c.role;
        document.getElementById('clientStatus').value = c.status;
      }
    } else {
      document.getElementById('clientModalTitle').textContent = 'Add Client / Student Organization';
    }

    if (clientModalInstance) clientModalInstance.show();
  }

  async function deleteClient(id, name) {
    if (!confirm(`Delete client record for "${name}"?`)) return;

    const fd = new FormData();
    fd.append('action', 'delete_client');
    fd.append('id', id);

    try {
      const res = await fetch('api/api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        loadClients();
        loadStats();
      } else {
        alert(data.message);
      }
    } catch (e) {
      alert('Network error.');
    }
  }


  // ==========================================
  // TAB 4: STAFF RECORDS (CRUD)
  // ==========================================
  async function loadStaff() {
    const tbody = document.getElementById('staffTableBody');
    if (!tbody) return;

    try {
      const res = await fetch('api/api.php?action=get_staff');
      const data = await res.json();
      if (data.success) {
        state.staff = data.data;
        renderStaffTable();
      }
    } catch (err) {
      console.error(err);
    }
  }

  function renderStaffTable() {
    const tbody = document.getElementById('staffTableBody');
    if (!tbody) return;

    tbody.innerHTML = state.staff.map(s => `
      <tr>
        <td><strong class="font-monospace text-primary">${escapeHtml(s.username)}</strong></td>
        <td><div class="fw-bold">${escapeHtml(s.full_name)}</div></td>
        <td>
          <div>${escapeHtml(s.email || '—')}</div>
          <small class="text-muted">${escapeHtml(s.contact_number || '—')}</small>
        </td>
        <td>
          <span class="badge ${s.role === 'admin' ? 'bg-primary' : 'bg-info text-dark'} text-uppercase">${escapeHtml(s.role)}</span>
        </td>
        <td>
          <span class="badge ${s.status === 'active' ? 'bg-success' : 'bg-secondary'}">${escapeHtml(s.status)}</span>
        </td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" onclick="window.ConfedAdmin.openStaffModal(${s.id})">Edit</button>
            <button class="btn btn-outline-danger" onclick="window.ConfedAdmin.deleteStaff(${s.id}, '${escapeHtml(s.username)}')">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function openStaffModal(staffId = 0) {
    document.getElementById('staffForm').reset();
    document.getElementById('staffId').value = staffId;

    if (staffId > 0) {
      const s = state.staff.find(x => parseInt(x.id) === staffId);
      if (s) {
        document.getElementById('staffModalTitle').textContent = `Edit Staff: ${s.username}`;
        document.getElementById('staffUsername').value = s.username;
        document.getElementById('staffFullName').value = s.full_name;
        document.getElementById('staffEmail').value = s.email || '';
        document.getElementById('staffRole').value = s.role;
        document.getElementById('staffStatus').value = s.status;
      }
    } else {
      document.getElementById('staffModalTitle').textContent = 'Add Council Staff Member';
    }

    if (staffModalInstance) staffModalInstance.show();
  }

  async function deleteStaff(id, username) {
    if (!confirm(`Delete staff account for "${username}"?`)) return;

    const fd = new FormData();
    fd.append('action', 'delete_staff');
    fd.append('id', id);

    try {
      const res = await fetch('api/api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        loadStaff();
        loadStats();
      } else {
        alert(data.message);
      }
    } catch (e) {
      alert('Network error.');
    }
  }


  // ==========================================
  // TAB 5: REPORTS GENERATION
  // ==========================================
  async function generateReports() {
    const container = document.getElementById('reportContainer');
    if (!container) return;

    const type = document.getElementById('reportTypeSelect').value;
    const start = document.getElementById('reportStartDate').value;
    const end = document.getElementById('reportEndDate').value;

    container.innerHTML = `<div class="text-center py-4 text-muted">Generating report...</div>`;

    try {
      const res = await fetch(`api/api.php?action=get_reports&type=${type}&start_date=${start}&end_date=${end}`);
      const json = await res.json();

      if (!json.success) {
        container.innerHTML = `<div class="alert alert-danger">${escapeHtml(json.message)}</div>`;
        return;
      }

      if (type === 'by_resource') {
        container.innerHTML = `
          <div class="mb-3">
            <h6 class="fw-bold text-dark mb-1">Resource Utilization Report</h6>
            <small class="text-muted">Period: ${start} to ${end}</small>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle small">
              <thead class="table-light text-uppercase">
                <tr>
                  <th>Resource Code</th>
                  <th>Equipment & Model</th>
                  <th>Category</th>
                  <th>Total Units Owned</th>
                  <th>Booking Count</th>
                  <th>Total Units Borrowed</th>
                </tr>
              </thead>
              <tbody>
                ${json.data.data.map(r => `
                  <tr>
                    <td class="font-monospace text-primary fw-bold">${escapeHtml(r.item_code)}</td>
                    <td><strong>${escapeHtml(r.resource_name)}</strong> <small class="text-muted">(${escapeHtml(r.model || 'Standard')})</small></td>
                    <td>${escapeHtml(r.category_name)}</td>
                    <td>${r.total_qty}</td>
                    <td><strong class="text-primary">${r.booking_frequency}</strong> bookings</td>
                    <td><strong class="text-success">${r.total_units_borrowed || 0}</strong> unit(s) loaned</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
      } else if (type === 'by_client') {
        container.innerHTML = `
          <div class="mb-3">
            <h6 class="fw-bold text-dark mb-1">Client Booking Activity Report</h6>
            <small class="text-muted">Period: ${start} to ${end}</small>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle small">
              <thead class="table-light text-uppercase">
                <tr>
                  <th>Student ID</th>
                  <th>Client Name</th>
                  <th>Student Organization</th>
                  <th>Total Bookings</th>
                  <th>Returned</th>
                  <th>Active on Loan</th>
                  <th>Total Fees / Deposits (₱)</th>
                </tr>
              </thead>
              <tbody>
                ${json.data.data.map(c => `
                  <tr>
                    <td class="font-monospace text-primary fw-bold">${escapeHtml(c.student_id)}</td>
                    <td><strong>${escapeHtml(c.client_name)}</strong></td>
                    <td>${escapeHtml(c.organization_name)}</td>
                    <td><strong class="text-primary">${c.total_bookings}</strong></td>
                    <td><span class="text-success">${c.returned_count}</span></td>
                    <td><span class="${c.active_count > 0 ? 'text-danger fw-bold' : 'text-muted'}">${c.active_count}</span></td>
                    <td><strong>₱${parseFloat(c.total_fees_paid || 0).toFixed(2)}</strong></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
      } else {
        // Periodic Report
        container.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h6 class="fw-bold text-dark mb-1">Periodic Booking Ledger Report</h6>
              <small class="text-muted">Coverage: ${start} to ${end}</small>
            </div>
            <div class="text-end">
              <span class="badge bg-primary px-3 py-2 fs-6">Total Bookings: ${json.data.summary.total_bookings}</span>
              <span class="badge bg-success px-3 py-2 fs-6">Total Collected/Deposited: ₱${parseFloat(json.data.summary.total_fees).toFixed(2)}</span>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle small">
              <thead class="table-light text-uppercase">
                <tr>
                  <th>Tracking Code</th>
                  <th>Client & Organization</th>
                  <th>Event & Purpose</th>
                  <th>Dates (Release - Return)</th>
                  <th>Resources Manifest</th>
                  <th>Status</th>
                  <th>Payment Status</th>
                </tr>
              </thead>
              <tbody>
                ${json.data.data.map(b => `
                  <tr>
                    <td class="font-monospace text-primary fw-bold">${escapeHtml(b.tracking_code)}</td>
                    <td><strong>${escapeHtml(b.client_name)}</strong><br><small class="text-muted">${escapeHtml(b.organization_name)}</small></td>
                    <td>${escapeHtml(b.event_name)}</td>
                    <td>${escapeHtml(b.borrow_date)} to ${escapeHtml(b.expected_return_date)}</td>
                    <td><small>${escapeHtml(b.item_summary || '1 item')}</small></td>
                    <td><span class="badge bg-secondary">${escapeHtml(b.status)}</span></td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(b.payment_status)} (₱${parseFloat(b.payment_amount).toFixed(2)})</span></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
      }

    } catch (err) {
      container.innerHTML = `<div class="alert alert-danger">Error generating report.</div>`;
    }
  }

  function renderLogs(logs) {
    const tbody = document.getElementById('logsTableBody');
    if (!tbody || !logs) return;

    tbody.innerHTML = logs.map(l => `
      <tr>
        <td class="font-monospace text-muted">${escapeHtml(l.created_at)}</td>
        <td><strong class="text-dark">${escapeHtml(l.action)}</strong></td>
        <td>${escapeHtml(l.details)}</td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(l.actor)}</span></td>
      </tr>
    `).join('');
  }

  // ==========================================
  // TAB NAVIGATION
  // ==========================================
  function switchTab(tabName) {
    state.currentTab = tabName;

    document.querySelectorAll('.nav-tab-link').forEach(btn => {
      if (btn.dataset.tab === tabName) btn.classList.add('active');
      else btn.classList.remove('active');
    });

    document.querySelectorAll('.admin-tab-pane').forEach(pane => {
      if (pane.id === `tab-${tabName}`) pane.style.display = 'block';
      else pane.style.display = 'none';
    });

    if (tabName === 'bookings') loadBookings();
    else if (tabName === 'resources') loadResources();
    else if (tabName === 'clients') loadClients();
    else if (tabName === 'staff') loadStaff();
    else if (tabName === 'reports') generateReports();
  }

  // Form Submissions Listeners
  function initForms() {
    // Resource Form
    const resForm = document.getElementById('resourceForm');
    if (resForm) {
      resForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(resForm);
        fd.append('action', 'save_item');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (resourceModalInstance) resourceModalInstance.hide();
            loadResources();
            loadStats();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error saving resource.');
        }
      });
    }

    // Schedule Reschedule Form
    const schForm = document.getElementById('scheduleForm');
    if (schForm) {
      schForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(schForm);
        fd.append('action', 'update_booking_schedule');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (scheduleModalInstance) scheduleModalInstance.hide();
            loadBookings();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error updating schedule.');
        }
      });
    }

    // Payment Form
    const payForm = document.getElementById('paymentForm');
    if (payForm) {
      payForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(payForm);
        fd.append('action', 'update_payment_details');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (paymentModalInstance) paymentModalInstance.hide();
            loadBookings();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error updating payment.');
        }
      });
    }

    // Cancel Form
    const canForm = document.getElementById('cancelForm');
    if (canForm) {
      canForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(canForm);
        fd.append('action', 'cancel_booking');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (cancelModalInstance) cancelModalInstance.hide();
            loadBookings();
            loadStats();
            loadResources();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error cancelling booking.');
        }
      });
    }

    // Client Form
    const cliForm = document.getElementById('clientForm');
    if (cliForm) {
      cliForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(cliForm);
        fd.append('action', 'save_client');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (clientModalInstance) clientModalInstance.hide();
            loadClients();
            loadStats();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error saving client.');
        }
      });
    }

    // Staff Form
    const stfForm = document.getElementById('staffForm');
    if (stfForm) {
      stfForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(stfForm);
        fd.append('action', 'save_staff');
        try {
          const res = await fetch('api/api.php', { method: 'POST', body: fd });
          const d = await res.json();
          if (d.success) {
            alert(d.message);
            if (staffModalInstance) staffModalInstance.hide();
            loadStaff();
            loadStats();
          } else {
            alert(d.message);
          }
        } catch (e) {
          alert('Error saving staff.');
        }
      });
    }
  }

  // Filter Listeners
  function initListeners() {
    document.querySelectorAll('.nav-tab-link').forEach(btn => {
      btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    const bSearch = document.getElementById('bookingSearchInput');
    if (bSearch) {
      let t;
      bSearch.addEventListener('input', (e) => {
        clearTimeout(t);
        t = setTimeout(() => {
          state.bookingSearch = e.target.value.trim();
          loadBookings();
        }, 300);
      });
    }

    const bStatus = document.getElementById('bookingStatusFilter');
    if (bStatus) {
      bStatus.addEventListener('change', (e) => {
        state.bookingStatus = e.target.value;
        loadBookings();
      });
    }

    const cSearch = document.getElementById('clientSearchInput');
    if (cSearch) {
      let t;
      cSearch.addEventListener('input', (e) => {
        clearTimeout(t);
        t = setTimeout(() => {
          state.clientSearch = e.target.value.trim();
          loadClients();
        }, 300);
      });
    }
  }

  // Global methods
  window.ConfedAdmin = {
    openResourceModal: openResourceModal,
    toggleResourceAvailability: toggleResourceAvailability,
    deleteResource: deleteResource,
    openRescheduleModal: openRescheduleModal,
    openPaymentModal: openPaymentModal,
    openCancelModal: openCancelModal,
    updateBookingStatus: updateBookingStatus,
    openClientModal: openClientModal,
    deleteClient: deleteClient,
    openStaffModal: openStaffModal,
    deleteStaff: deleteStaff,
    generateReports: generateReports,
    openNewBookingModal: () => { window.location.href = 'index.php'; }
  };

  document.addEventListener('DOMContentLoaded', () => {
    initBootstrapModals();
    initForms();
    initListeners();
    loadStats();
    loadBookings();
  });

})();
