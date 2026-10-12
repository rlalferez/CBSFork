          <?php if($isAdmin): ?>
          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-users' ? 'show active' : '' ?>" id="tab-users">
            <div class="content-card">
              <div class="content-card-header border-bottom-0 pb-0">
                <div class="card-title-group">
                  <h5 class="card-title">User Management</h5>
                  <p class="card-subtitle">Manage Council and Committee member access.</p>
                </div>
                <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalUser">Add User</button>
              </div>
              <div class="card-filter-bar">
                  <div class="btn-group" role="group">
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_all" autocomplete="off" checked onclick="filterTableByPill('#usersTable', 'All', 2)">
                      <label class="btn btn-outline-primary btn-sm rounded-start-pill px-3" for="btn_user_all">All</label>
                    
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_council" autocomplete="off" onclick="filterTableByPill('#usersTable', 'Council', 2)">
                      <label class="btn btn-outline-primary btn-sm px-3" for="btn_user_council">Council</label>
                    
                      <input type="radio" class="btn-check" name="btnradio_user" id="btn_user_committee" autocomplete="off" onclick="filterTableByPill('#usersTable', 'Committee', 2)">
                      <label class="btn btn-outline-primary btn-sm rounded-end-pill px-3" for="btn_user_committee">Committee</label>
                  </div>
                  <input type="text" class="form-control form-control-sm table-search ms-auto" data-target="#usersTable" placeholder="Search users..." style="max-width: 250px;">
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="usersTable">
                  <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($users as $u): ?>
                      <tr id="row-user-<?= htmlspecialchars($u['userID'], ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars($u['userID'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['userFName'].' '.$u['userLName'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['userRole'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($u['userEmail']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($u['userEmail'], ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td><?= htmlspecialchars($u['userContactNo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 edit-user-btn" style="font-size:12px;"
                            data-id="<?= htmlspecialchars($u['userID'], ENT_QUOTES, 'UTF-8') ?>"
                            data-fname="<?= htmlspecialchars($u['userFName'], ENT_QUOTES, 'UTF-8') ?>"
                            data-lname="<?= htmlspecialchars($u['userLName'], ENT_QUOTES, 'UTF-8') ?>"
                            data-email="<?= htmlspecialchars($u['userEmail'], ENT_QUOTES, 'UTF-8') ?>"
                            data-contact="<?= htmlspecialchars($u['userContactNo'], ENT_QUOTES, 'UTF-8') ?>"
                            data-role="<?= htmlspecialchars($u['userRole'], ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                          <form action="actions/user_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive user?');">
                            <input type="hidden" name="action" value="archive_user">
                            <input type="hidden" name="userID" value="<?= htmlspecialchars($u['userID'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($users) == 0): ?>
                      <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>
