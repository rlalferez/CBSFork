
      <aside class="app-sidebar no-print" id="sidebarMenu">
        <div class="sidebar-panel">
          
          <!-- Mobile Sidebar Header -->
          <div class="sidebar-mobile-header">
              <span class="sidebar-mobile-title">Menu</span>
              <button class="btn-close" id="sidebarClose"></button>
          </div>
          
          <!-- User Profile Card -->
          <div class="sidebar-profile">
            <div class="sidebar-avatar">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
            </div>
            <div class="sidebar-user-info">
              <div class="sidebar-user-name"><?= htmlspecialchars($currentUser['full_name'] ?? trim(($currentUser['userFName'] ?? '') . ' ' . ($currentUser['userLName'] ?? '')) ?: 'Officer') ?></div>
              <span class="sidebar-user-role <?= $isAdmin ? 'role-council' : 'role-committee' ?>">
                <?= $isAdmin ? 'Council' : 'Committee' ?>
              </span>
            </div>
          </div>

          <!-- Section Title -->
          <div class="sidebar-section-title">Council Operations</div>
          
          <!-- Main Navigation Links -->
          <div class="sidebar-nav" id="v-pills-tab" role="tablist">
            
            <!-- Transactions Menu with Submenu -->
            <div class="sidebar-dropdown">
              <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-transactions' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-transactions">
                <div class="sidebar-link-content">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <span>Transactions</span>
                </div>
                <svg class="sidebar-dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-reserved'); return false;">Reserved</a>
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-released'); return false;">Released</a>
                <a href="#" onclick="openSubtab('#tab-transactions', '#txn-return'); return false;">Returns</a>
              </div>
            </div>

            <!-- Purchases Tab -->
            <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-purchases' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-purchases">
              <div class="sidebar-link-content">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Purchases</span>
              </div>
            </button>

            <!-- Items / Inventory Tab -->
            <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-inventory' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-inventory">
              <div class="sidebar-link-content">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Items</span>
              </div>
            </button>

            <!-- Borrowers Tab -->
            <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-borrowers' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-borrowers">
              <div class="sidebar-link-content">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Borrower Management</span>
              </div>
            </button>

            <!-- User Management Tab (Admin only) -->
            <?php if ($isAdmin): ?>
              <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-users' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-users">
                <div class="sidebar-link-content">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <span>User Management</span>
                </div>
              </button>
            <?php endif; ?>

            <!-- Reports Menu with Submenu -->
            <div class="sidebar-dropdown">
              <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-reports' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-reports">
                <div class="sidebar-link-content">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  <span>Reports</span>
                </div>
                <svg class="sidebar-dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              <div class="sidebar-submenu">
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-unified'); return false;">Unified View</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-borrow'); return false;">Borrows</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-return'); return false;">Returns</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-purchase'); return false;">Purchases</a>
                <a href="#" onclick="openSubtab('#tab-reports', '#rep-inventory'); return false;">Items</a>
              </div>
            </div>

            <!-- Profile Tab -->
            <button class="sidebar-link <?= ($activeTab ?? '') === 'tab-profile' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-profile">
              <div class="sidebar-link-content">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile Management</span>
              </div>
            </button>

            <!-- Sign Out Form -->
            <form action="actions/auth_action.php" method="POST" class="sidebar-logout-form">
              <input type="hidden" name="action" value="logout">
              <button type="submit" class="sidebar-link sidebar-logout-btn">
                <div class="sidebar-link-content logout-danger">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                  <span>Sign Out</span>
                </div>
              </button>
            </form>

          </div>
        </div>
      </aside>
