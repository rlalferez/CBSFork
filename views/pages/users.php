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
                        <td><a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($u['userEmail']) ?>" target="_blank"><?= htmlspecialchars($u['userEmail']) ?></a></td>
                        <td><?= htmlspecialchars($u['userContactNo']) ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;" onclick="editUser('<?= $u['userID'] ?>', '<?= addslashes($u['userFName']) ?>', '<?= addslashes($u['userLName']) ?>', '<?= addslashes($u['userEmail']) ?>', '<?= addslashes($u['userContactNo']) ?>', '<?= addslashes($u['userRole']) ?>')">Edit</button>
                          <form action="actions/user_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
                            <input type="hidden" name="action" value="archive_user"><input type="hidden" name="userID" value="<?= $u['userID'] ?>">
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
          <?php endif; ?>
