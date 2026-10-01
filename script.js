/**
 * ==========================================================
 * CLIENT-SIDE INTERACTION SCRIPT (script.js)
 * Confederates Student Council - Resource Management System
 * ==========================================================
 * Simple, clean JavaScript functions for:
 * 1. Switching dashboard tabs
 * 2. Status & category quick-filter pills
 * 3. Instant table search filtering
 * 4. Filling edit modals with existing data
 */

// 1. Switch Dashboard Tabs
function showTab(tabId) {
  // Hide all tab panes
  document.querySelectorAll('.tab-pane-content').forEach(el => el.classList.add('d-none'));
  
  // Show target tab pane
  const target = document.getElementById('tab-' + tabId);
  if (target) target.classList.remove('d-none');

  // Update active state on tab buttons
  document.querySelectorAll('.nav-tab-btn').forEach(btn => {
    if (btn.dataset.tab === tabId) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });

  // Save current tab in session/hash
  window.location.hash = tabId;
}

// 2. Table Search Filter Helper (Real-time live search)
function filterTable(inputId, tableBodyId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll('#' + tableBodyId + ' tr');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? '' : 'none';
  });
}

// 3. Quick Status Filter Pills for Bookings
function filterBookingStatus(status, pillEl) {
  // Update active pill UI
  document.querySelectorAll('#bookingFilterPills .filter-pill').forEach(p => p.classList.remove('active'));
  if (pillEl) pillEl.classList.add('active');

  const rows = document.querySelectorAll('#bookingsTableBody tr');
  rows.forEach(row => {
    if (status === 'all') {
      row.style.display = '';
    } else {
      const rowStatus = row.getAttribute('data-status') || '';
      row.style.display = (rowStatus.toLowerCase() === status.toLowerCase()) ? '' : 'none';
    }
  });
}

// 4. Quick Category Filter Pills for Resources
function filterResourceCategory(category, pillEl) {
  // Update active pill UI
  document.querySelectorAll('#resourceFilterPills .filter-pill').forEach(p => p.classList.remove('active'));
  if (pillEl) pillEl.classList.add('active');

  const rows = document.querySelectorAll('#resourcesTableBody tr');
  rows.forEach(row => {
    if (category === 'all') {
      row.style.display = '';
    } else {
      const rowCategory = row.getAttribute('data-category') || '';
      row.style.display = (rowCategory.toLowerCase().includes(category.toLowerCase())) ? '' : 'none';
    }
  });
}

// 5. Edit Resource Modal Pre-filler
function editResource(item) {
  const modalEl = document.getElementById('resourceModal');
  if (!modalEl) return;

  document.getElementById('resourceModalTitle').textContent = 'Edit Resource: ' + item.code;
  document.getElementById('resId').value = item.id;
  document.getElementById('resCode').value = item.code;
  document.getElementById('resName').value = item.name;
  document.getElementById('resModel').value = item.model || '';
  document.getElementById('resCategory').value = item.category;
  document.getElementById('resTotalQty').value = item.total_qty;
  document.getElementById('resCondition').value = item.condition_status;
  document.getElementById('resLocation').value = item.location;
  document.getElementById('resFeeType').value = item.fee_type;
  document.getElementById('resFeeAmount').value = item.fee_amount;
  document.getElementById('resDesc').value = item.description || '';
  document.getElementById('resAvail').checked = (item.is_available == 1);
  
  new bootstrap.Modal(modalEl).show();
}

// 6. Reschedule Booking Modal Pre-filler
function rescheduleBooking(b) {
  document.getElementById('rescheduleId').value = b.id;
  document.getElementById('rescheduleCode').textContent = b.booking_code;
  document.getElementById('rescheduleEvent').value = b.event_name;
  document.getElementById('rescheduleLocation').value = b.event_location;
  document.getElementById('rescheduleStartDate').value = b.start_date;
  document.getElementById('rescheduleEndDate').value = b.end_date;

  new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
}

// 7. Update Payment / Deposit Modal Pre-filler
function editPayment(b) {
  document.getElementById('paymentId').value = b.id;
  document.getElementById('paymentCode').textContent = b.booking_code;
  document.getElementById('paymentStatusSelect').value = b.payment_status;
  document.getElementById('paymentAmountInput').value = b.payment_amount;
  document.getElementById('paymentDetailsInput').value = b.payment_details || '';

  new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

// 8. Edit Client Modal Pre-filler
function editClient(c) {
  document.getElementById('clientModalTitle').textContent = 'Edit Client: ' + c.full_name;
  document.getElementById('cliId').value = c.id;
  document.getElementById('cliStudentId').value = c.student_id;
  document.getElementById('cliFullName').value = c.full_name;
  document.getElementById('cliEmail').value = c.email;
  document.getElementById('cliContact').value = c.contact_number;
  document.getElementById('cliOrg').value = c.organization_name;
  document.getElementById('cliRole').value = c.role;
  document.getElementById('cliStatus').value = c.status;

  new bootstrap.Modal(document.getElementById('clientModal')).show();
}

// 9. Edit Staff Modal Pre-filler (Admin)
function editStaff(s) {
  document.getElementById('staffModalTitle').textContent = 'Edit Staff: ' + s.username;
  document.getElementById('stfId').value = s.id;
  document.getElementById('stfUsername').value = s.username;
  document.getElementById('stfFullName').value = s.full_name;
  document.getElementById('stfEmail').value = s.email;
  document.getElementById('stfContact').value = s.contact_number;
  document.getElementById('stfRole').value = s.role;
  document.getElementById('stfStatus').value = s.status;

  new bootstrap.Modal(document.getElementById('staffModal')).show();
}

// 10. Restore active tab on page load
document.addEventListener('DOMContentLoaded', () => {
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    showTab(hash);
  }
});
