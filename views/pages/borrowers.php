          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-borrowers' ? 'show active' : '' ?>" id="tab-borrowers">
            <div class="content-card">
              <div class="content-card-header">
                <div class="card-title-group">
                  <h5 class="card-title">Borrower Management</h5>
                  <p class="card-subtitle">Manage student and organization profiles.</p>
                </div>
                <div class="card-header-actions">
                  <input type="text" class="form-control form-control-sm table-search" data-target="#borrowersTable" placeholder="Search borrowers..." style="max-width: 250px;">
                  <button class="btn btn-primary-action btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalBorrower">Add Borrower</button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table-custom" id="borrowersTable">
                  <thead><tr><th>ID</th><th>Student ID</th><th>Name</th><th>College</th><th>Org</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($borrowers as $b): ?>
                      <tr id="row-brw-<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                          <span class="fw-bold"><?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($b['brwStudentID'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <span class="fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($b['brwCollege'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($b['brwOrg'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($b['brwContactNo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 me-1 edit-borrower-btn" style="font-size:12px;"
                            data-id="<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>"
                            data-studentid="<?= htmlspecialchars($b['brwStudentID'], ENT_QUOTES, 'UTF-8') ?>"
                            data-fname="<?= htmlspecialchars($b['brwFName'], ENT_QUOTES, 'UTF-8') ?>"
                            data-lname="<?= htmlspecialchars($b['brwLName'], ENT_QUOTES, 'UTF-8') ?>"
                            data-college="<?= htmlspecialchars($b['brwCollege'], ENT_QUOTES, 'UTF-8') ?>"
                            data-org="<?= htmlspecialchars($b['brwOrg'], ENT_QUOTES, 'UTF-8') ?>"
                            data-contact="<?= htmlspecialchars($b['brwContactNo'], ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                          <form action="actions/borrower_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive borrower?');">
                            <input type="hidden" name="action" value="archive_borrower">
                            <input type="hidden" name="brwID" value="<?= htmlspecialchars($b['brwID'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;">Archive</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($borrowers) == 0): ?>
                      <tr><td colspan="7" class="text-center text-muted py-4">No borrowers found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
