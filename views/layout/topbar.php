  <nav class="navbar navbar-expand-lg app-navbar sticky-top no-print">
    <div class="container-fluid px-0">
      <div class="d-flex align-items-center">
        <!-- Hamburger button for mobile sidebar -->
        <button class="btn btn-light border d-lg-none me-3" id="sidebarToggle">
           <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <a href="index.php" class="navbar-brand d-flex align-items-center gap-3 text-decoration-none">
          <div class="brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
          </div>
          <div class="d-none d-sm-block">
            <span class="fw-bold d-block text-dark lh-1 fs-5">Confederates Student Council</span>
            <small class="text-primary fw-semibold" style="font-size: 12px; letter-spacing: 0.3px;">Item Management & Lending System</small>
          </div>
        </a>
      </div>
      <div class="d-flex align-items-center gap-3 ms-auto">
        <!-- 
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold d-none d-md-inline-flex align-items-center gap-2" style="font-size: 12px; border-radius: 9999px;">
          <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span>
          System Online
        </span>
        -->
        <div class="dropdown">
          <button class="btn btn-light border dropdown-toggle px-3 py-2 rounded-pill d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="dropdown">
            <span class="badge bg-primary text-uppercase px-2 py-1" style="font-size: 10px;"><?= htmlspecialchars($currentUser['role']) ?></span>
            <span class="fw-semibold text-dark small"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
            <li><button type="button" class="dropdown-item small py-2 d-flex align-items-center gap-2" onclick="document.querySelector('.app-sidebar [data-bs-target=\'#tab-profile\']').click()" style="border:none; background:none; width:100%; text-align:left;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Profile Management</span>
            </button></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger py-2 d-flex align-items-center gap-2" href="actions/auth_action.php?action=logout">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign Out</span>
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
