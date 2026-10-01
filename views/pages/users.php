<?php if($isAdmin): ?>
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- USER MANAGEMENT TAB -->
          <?php if($isAdmin): ?>
          <div class="tab-pane fade" id="tab-users">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">User Management</h5>
                  <p class="text-muted small mb-0">Manage Council and Committee member access.</p>
                </div>
                <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser">Add User</button>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($users as $u): ?>
                      <tr>
                        <td><?= $u['userID'] ?></td><td><?= htmlspecialchars($u['userFName'].' '.$u['userLName']) ?></td>
                        <td><?= htmlspecialchars($u['userRole']) ?></td>
<?php endif; ?>
