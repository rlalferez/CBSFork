
      <aside class="app-sidebar no-print" id="sidebarMenu">
        <div class="sidebar-panel">
          <div class="d-flex justify-content-between align-items-center d-lg-none border-bottom pb-2 mb-3">
              <span class="fw-bold">Menu</span>
              <button class="btn-close" id="sidebarClose"></button>
          </div>
          
          <div class="officer-badge-box">
            <div class="officer-avatar">
              <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
              <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($currentUser['full_name']) ?></div>
              <span class="badge <?= $isAdmin ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info-emphasis' ?> text-uppercase px-2 py-0" style="font-size: 10px;">
                <?= $isAdmin ? 'Council' : 'Committee' ?>
              </span>
            </div>
          </div>

          <div class="text-uppercase small text-muted fw-bold mb-2 ps-1" style="font-size: 11px; letter-spacing: 0.05em;">Council Operations</div>
          
          <div class="d-flex flex-column nav" id="v-pills-tab" role="tablist">
            <div class="sidebar-item-group">
              <button class="nav-tab-btn <?= !$isReportActive ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-transactions">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <span>Transactions</span>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-return'); return false;">Returns</a>
              </div>
            </div>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-purchases">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Purchases</span>
              </div>
            </button>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-inventory">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Items</span>
              </div>
            </button>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-borrowers">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Borrower Management</span>
              </div>
            </button>
            <?php if ($isAdmin): ?>
              <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-users">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <span>User Management</span>
                </div>
              </button>
            <?php endif; ?>
            <div class="sidebar-item-group">
              <button class="nav-tab-btn <?= $isReportActive ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-reports">
                <div class="nav-left">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  <span>Reports</span>
                </div>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-unified'); return false;">Unified View</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-return'); return false;">Returns</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-purchase'); return false;">Purchases</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-inventory'); return false;">Items</a>
              </div>
            </div>
            <button class="nav-tab-btn" data-bs-toggle="pill" data-bs-target="#tab-profile">
              <div class="nav-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Management</span>
              </div>
            </button>          </div>
        </div>
      </aside>
