/**
 * Confed Borrowing System - Public App JS
 * Handles catalog rendering, category filtering, cart management, and borrowing requests
 */

(function () {
  'use strict';

  // State Management
  const state = {
    items: [],
    categories: [],
    selectedCategory: 0,
    searchQuery: '',
    availableOnly: false,
    cart: [] // Array of { id, name, item_code, available_qty, quantity, location }
  };

  // SVG Icon Templates for Categories & Items
  const iconTemplates = {
    mic: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>`,
    tent: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20 12 4 5 20"/><path d="m14 14-2 3-2-3"/><path d="M2 20h20"/></svg>`,
    trophy: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>`,
    cpu: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/></svg>`,
    briefcase: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>`,
    box: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>`
  };

  // DOM Elements Cache
  const DOM = {
    categoryBar: document.getElementById('categoryFilterBar'),
    itemsGrid: document.getElementById('itemsGrid'),
    searchInput: document.getElementById('catalogSearchInput'),
    categorySelect: document.getElementById('heroCategorySelect'),
    availableOnlyCheckbox: document.getElementById('availableOnlyCheckbox'),
    cartBadge: document.getElementById('cartBadgeCount'),
    cartTrigger: document.getElementById('cartTriggerBtn'),
    cartOverlay: document.getElementById('cartOverlay'),
    cartDrawer: document.getElementById('cartDrawer'),
    cartCloseBtn: document.getElementById('cartCloseBtn'),
    cartItemsList: document.getElementById('cartItemsList'),
    cartEmptyState: document.getElementById('cartEmptyState'),
    checkoutBtn: document.getElementById('checkoutBtn'),
    borrowModal: document.getElementById('borrowModal'),
    borrowModalClose: document.getElementById('borrowModalClose'),
    borrowForm: document.getElementById('borrowForm'),
    modalCartSummary: document.getElementById('modalCartSummary'),
    successModal: document.getElementById('successModal'),
    toastContainer: document.getElementById('toastContainer')
  };

  // Toast Notification Helper
  function showToast(message, type = 'info') {
    if (!DOM.toastContainer) {
      const container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
      DOM.toastContainer = container;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <span>${escapeHtml(message)}</span>
    `;
    DOM.toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // Load Categories
  async function fetchCategories() {
    try {
      const res = await fetch('api/api.php?action=get_categories');
      const data = await res.json();
      if (data.success) {
        state.categories = data.data;
        renderCategoryFilters();
        if (DOM.categorySelect) {
          DOM.categorySelect.innerHTML = `<option value="0">All Equipment Categories</option>` +
            state.categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
        }
      }
    } catch (err) {
      console.error('Failed to fetch categories:', err);
    }
  }

  // Render Category Bar
  function renderCategoryFilters() {
    if (!DOM.categoryBar) return;
    
    let html = `
      <button class="category-chip ${state.selectedCategory === 0 ? 'active' : ''}" data-id="0">
        ${iconTemplates.box}
        <span>All Items</span>
      </button>
    `;

    state.categories.forEach(cat => {
      const icon = iconTemplates[cat.icon] || iconTemplates.box;
      const isActive = state.selectedCategory === parseInt(cat.id);
      html += `
        <button class="category-chip ${isActive ? 'active' : ''}" data-id="${cat.id}">
          ${icon}
          <span>${escapeHtml(cat.name)}</span>
          <small style="opacity:0.7">(${cat.item_count})</small>
        </button>
      `;
    });

    DOM.categoryBar.innerHTML = html;

    DOM.categoryBar.querySelectorAll('.category-chip').forEach(btn => {
      btn.addEventListener('click', () => {
        state.selectedCategory = parseInt(btn.dataset.id);
        renderCategoryFilters();
        fetchItems();
      });
    });
  }

  // Fetch Items
  async function fetchItems() {
    if (!DOM.itemsGrid) return;
    DOM.itemsGrid.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
        <div style="display:inline-block; width: 36px; height: 36px; border: 3px solid #cbd5e1; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; color: #64748b; font-weight: 500;">Loading confederation inventory...</p>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
      </div>
    `;

    try {
      let url = `api/api.php?action=get_items&category_id=${state.selectedCategory}&search=${encodeURIComponent(state.searchQuery)}`;
      if (state.availableOnly) {
        url += '&available_only=1';
      }

      const res = await fetch(url);
      const data = await res.json();

      if (data.success) {
        state.items = data.data;
        renderItems();
      } else {
        DOM.itemsGrid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #dc2626;">${escapeHtml(data.message)}</p>`;
      }
    } catch (err) {
      console.error('Error loading inventory:', err);
      DOM.itemsGrid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #dc2626;">Failed to load items. Check database connection.</p>`;
    }
  }

  // Render Items Grid
  function renderItems() {
    if (!DOM.itemsGrid) return;

    if (state.items.length === 0) {
      DOM.itemsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px dashed #cbd5e1;">
          <div style="font-size: 40px; margin-bottom: 12px;">📦</div>
          <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">No Equipment Found</h3>
          <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Try searching with different terms or select another category.</p>
        </div>
      `;
      return;
    }

    DOM.itemsGrid.innerHTML = state.items.map(item => {
      const avail = parseInt(item.available_qty);
      const total = parseInt(item.total_qty);
      const isAvailable = avail > 0;
      const inCart = state.cart.find(c => c.id === item.id);
      const icon = iconTemplates[item.category_icon] || iconTemplates.box;

      return `
        <div class="item-card" data-id="${item.id}">
          <div class="item-visual-banner">
            <span class="item-category-tag">${escapeHtml(item.category_name)}</span>
            <span class="item-condition-tag">${escapeHtml(item.condition_status)}</span>
            <div class="item-icon-art">
              ${icon}
            </div>
          </div>
          <div class="item-body">
            <span class="item-code">${escapeHtml(item.item_code)}</span>
            <h3 class="item-title">${escapeHtml(item.name)}</h3>
            <p class="item-description">${escapeHtml(item.description || 'Standard official confederation asset.')}</p>
            
            <div style="font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 4px; margin-bottom: 12px;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>${escapeHtml(item.location || 'Confed Main Office')}</span>
            </div>

            <div class="item-meta-row">
              <div class="stock-indicator">
                <span class="stock-count ${isAvailable ? 'available' : 'depleted'}">
                  ${avail} / ${total}
                </span>
                <span class="stock-label">${isAvailable ? 'Available Now' : 'All Borrowed'}</span>
              </div>

              ${isAvailable ? `
                <button class="btn btn-sm btn-primary add-to-cart-btn" data-id="${item.id}" ${inCart && inCart.quantity >= avail ? 'disabled' : ''}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span>${inCart ? `Add More (${inCart.quantity})` : 'Add to Request'}</span>
                </button>
              ` : `
                <button class="btn btn-sm btn-secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                  Unavailable
                </button>
              `}
            </div>
          </div>
        </div>
      `;
    }).join('');

    // Attach Add to Cart Listeners
    DOM.itemsGrid.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = parseInt(btn.dataset.id);
        addToCart(id);
      });
    });
  }

  // ==========================================
  // CART MANAGEMENT
  // ==========================================
  function addToCart(itemId) {
    const item = state.items.find(i => parseInt(i.id) === itemId);
    if (!item) return;

    const available = parseInt(item.available_qty);
    if (available <= 0) {
      showToast('This item is currently out of stock.', 'error');
      return;
    }

    const existing = state.cart.find(c => parseInt(c.id) === itemId);
    if (existing) {
      if (existing.quantity < available) {
        existing.quantity++;
        showToast(`Increased "${item.name}" quantity to ${existing.quantity}.`, 'success');
      } else {
        showToast(`Maximum available stock reached for "${item.name}".`, 'error');
        return;
      }
    } else {
      state.cart.push({
        id: item.id,
        name: item.name,
        item_code: item.item_code,
        available_qty: available,
        quantity: 1,
        location: item.location
      });
      showToast(`Added "${item.name}" to borrowing request.`, 'success');
    }

    updateCartUI();
    renderItems(); // Update button label
  }

  function updateCartItemQty(itemId, delta) {
    const itemIndex = state.cart.findIndex(c => parseInt(c.id) === itemId);
    if (itemIndex === -1) return;

    const item = state.cart[itemIndex];
    const newQty = item.quantity + delta;

    if (newQty <= 0) {
      state.cart.splice(itemIndex, 1);
      showToast(`Removed "${item.name}" from request.`, 'info');
    } else if (newQty > item.available_qty) {
      showToast(`Cannot exceed available stock (${item.available_qty}).`, 'error');
      return;
    } else {
      item.quantity = newQty;
    }

    updateCartUI();
    renderItems();
  }

  function updateCartUI() {
    const totalCount = state.cart.reduce((sum, item) => sum + item.quantity, 0);

    if (DOM.cartBadge) {
      DOM.cartBadge.textContent = totalCount;
      DOM.cartBadge.style.display = totalCount > 0 ? 'inline-block' : 'none';
    }

    if (!DOM.cartItemsList) return;

    if (state.cart.length === 0) {
      DOM.cartItemsList.innerHTML = `
        <div style="text-align: center; padding: 40px 10px; color: #64748b;">
          <div style="font-size: 32px; margin-bottom: 8px;">🛒</div>
          <p style="font-weight: 600;">Your borrowing request is empty</p>
          <small>Click "Add to Request" on equipment you wish to reserve.</small>
        </div>
      `;
      if (DOM.checkoutBtn) DOM.checkoutBtn.disabled = true;
    } else {
      DOM.cartItemsList.innerHTML = state.cart.map(item => `
        <div class="cart-item-row">
          <div class="cart-item-info">
            <h4>${escapeHtml(item.name)}</h4>
            <p>${escapeHtml(item.item_code)} &bull; ${escapeHtml(item.location)}</p>
          </div>
          <div class="cart-qty-ctrl">
            <button class="qty-btn" onclick="window.ConfedApp.updateCartQty(${item.id}, -1)">&minus;</button>
            <span style="font-weight: 700; min-width: 20px; text-align: center;">${item.quantity}</span>
            <button class="qty-btn" onclick="window.ConfedApp.updateCartQty(${item.id}, 1)">&plus;</button>
          </div>
        </div>
      `).join('');
      if (DOM.checkoutBtn) DOM.checkoutBtn.disabled = false;
    }
  }

  function toggleCart(open) {
    if (!DOM.cartOverlay) return;
    if (open) {
      DOM.cartOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else {
      DOM.cartOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  // ==========================================
  // BORROW REQUEST MODAL
  // ==========================================
  function openBorrowModal() {
    if (state.cart.length === 0) {
      showToast('Please add at least one piece of equipment to your request.', 'error');
      return;
    }

    toggleCart(false);

    if (DOM.modalCartSummary) {
      DOM.modalCartSummary.innerHTML = `
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px;">
          <thead>
            <tr style="border-bottom: 1px solid #e2e8f0; text-align: left; color: #64748b;">
              <th style="padding: 6px 0;">Item Code</th>
              <th style="padding: 6px 0;">Equipment Name</th>
              <th style="padding: 6px 0; text-align: right;">Quantity</th>
            </tr>
          </thead>
          <tbody>
            ${state.cart.map(i => `
              <tr style="border-bottom: 1px dashed #f1f5f9;">
                <td style="padding: 8px 0; font-family: monospace; color: #2563eb; font-weight: 700;">${escapeHtml(i.item_code)}</td>
                <td style="padding: 8px 0; font-weight: 600;">${escapeHtml(i.name)}</td>
                <td style="padding: 8px 0; text-align: right; font-weight: 700;">${i.quantity}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    // Set default borrow date to today
    const today = new Date().toISOString().split('T')[0];
    const borrowDateInput = document.getElementById('borrowDate');
    const returnDateInput = document.getElementById('returnDate');
    if (borrowDateInput && !borrowDateInput.value) {
      borrowDateInput.value = today;
      borrowDateInput.min = today;
    }
    if (returnDateInput && !returnDateInput.value) {
      returnDateInput.value = today;
      returnDateInput.min = today;
    }

    if (DOM.borrowModal) {
      DOM.borrowModal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeBorrowModal() {
    if (DOM.borrowModal) {
      DOM.borrowModal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  // Submit Request
  async function handleBorrowFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    const formData = new FormData(form);
    const payload = {
      student_id: formData.get('student_id'),
      full_name: formData.get('full_name'),
      email: formData.get('email'),
      contact_number: formData.get('contact_number'),
      organization_name: formData.get('organization_name'),
      role: formData.get('role'),
      purpose: formData.get('purpose'),
      event_name: formData.get('event_name'),
      event_location: formData.get('event_location'),
      borrow_date: formData.get('borrow_date'),
      expected_return_date: formData.get('expected_return_date'),
      items: state.cart.map(i => ({
        item_id: i.id,
        quantity: i.quantity,
        notes: ''
      }))
    };

    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span>Submitting Request...</span>`;

    try {
      const res = await fetch('api/api.php?action=submit_borrow_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        closeBorrowModal();
        state.cart = [];
        updateCartUI();
        renderItems();
        form.reset();

        showSuccessConfirmation(data.data);
      } else {
        showToast(data.message || 'Submission failed.', 'error');
      }
    } catch (err) {
      console.error('Submission error:', err);
      showToast('Network error while submitting request.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = `<span>Submit Official Request</span>`;
    }
  }

  function showSuccessConfirmation(info) {
    if (!DOM.successModal) {
      alert(`Borrowing request submitted!\nTracking Code: ${info.tracking_code}\nPlease proceed to the Confed Office for equipment release.`);
      window.location.href = info.receipt_url;
      return;
    }

    const modalBody = DOM.successModal.querySelector('.modal-body');
    if (modalBody) {
      modalBody.innerHTML = `
        <div style="text-align: center; padding: 20px 0;">
          <div style="width: 68px; height: 68px; background: #d1fae5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
            ✓
          </div>
          <h3 style="font-size: 22px; font-weight: 800; color: #065f46;">Request Successfully Logged!</h3>
          <p style="color: #64748b; font-size: 14px; margin-top: 6px;">Your borrowing reservation has been queued for officer review.</p>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 24px 0;">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Official Tracking Code</div>
            <div style="font-size: 26px; font-weight: 800; color: #1e40af; font-family: monospace; letter-spacing: 0.05em; margin: 4px 0;">
              ${escapeHtml(info.tracking_code)}
            </div>
            <small style="color: #94a3b8;">Save this code or present the Gate Pass at the Confed Office.</small>
          </div>

          <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="${info.receipt_url}" target="_blank" class="btn btn-primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
              <span>View & Print Gate Pass</span>
            </a>
            <a href="my-requests.php?code=${encodeURIComponent(info.tracking_code)}" class="btn btn-secondary">
              <span>Track Status</span>
            </a>
          </div>
        </div>
      `;
    }

    DOM.successModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  // ==========================================
  // EVENT LISTENERS INITIALIZATION
  // ==========================================
  function initEvents() {
    // Search input
    if (DOM.searchInput) {
      let debounceTimer;
      DOM.searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          state.searchQuery = e.target.value.trim();
          fetchItems();
        }, 300);
      });
    }

    // Hero category dropdown
    if (DOM.categorySelect) {
      DOM.categorySelect.addEventListener('change', (e) => {
        state.selectedCategory = parseInt(e.target.value);
        renderCategoryFilters();
        fetchItems();
      });
    }

    // Available only checkbox
    if (DOM.availableOnlyCheckbox) {
      DOM.availableOnlyCheckbox.addEventListener('change', (e) => {
        state.availableOnly = e.target.checked;
        fetchItems();
      });
    }

    // Cart trigger
    if (DOM.cartTrigger) {
      DOM.cartTrigger.addEventListener('click', () => toggleCart(true));
    }
    if (DOM.cartCloseBtn) {
      DOM.cartCloseBtn.addEventListener('click', () => toggleCart(false));
    }
    if (DOM.cartOverlay) {
      DOM.cartOverlay.addEventListener('click', (e) => {
        if (e.target === DOM.cartOverlay) toggleCart(false);
      });
    }

    // Checkout button
    if (DOM.checkoutBtn) {
      DOM.checkoutBtn.addEventListener('click', openBorrowModal);
    }
    if (DOM.borrowModalClose) {
      DOM.borrowModalClose.addEventListener('click', closeBorrowModal);
    }
    if (DOM.borrowModal) {
      DOM.borrowModal.addEventListener('click', (e) => {
        if (e.target === DOM.borrowModal) closeBorrowModal();
      });
    }

    // Borrow Form
    if (DOM.borrowForm) {
      DOM.borrowForm.addEventListener('submit', handleBorrowFormSubmit);
    }

    // Close success modal
    const successClose = document.getElementById('successModalClose');
    if (successClose) {
      successClose.addEventListener('click', () => {
        if (DOM.successModal) DOM.successModal.classList.remove('open');
        document.body.style.overflow = '';
      });
    }
  }

  // Expose global methods for inline handlers
  window.ConfedApp = {
    updateCartQty: updateCartItemQty,
    showToast: showToast
  };

  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    fetchCategories();
    fetchItems();
    initEvents();
  });

})();
