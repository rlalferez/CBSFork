          <div class="tab-pane fade" id="tab-borrowers">
            <div class="content-card">
              <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Borrower Directory</h5>
                  <p class="text-muted small mb-0">Manage student and organization profiles.</p>
                </div>
                <button class="btn btn-primary-action btn-sm" data-bs-toggle="modal" data-bs-target="#modalBorrower">Add Borrower</button>
              </div>
              <div class="table-responsive">
                <table class="table-custom">
                  <thead><tr><th>ID</th><th>Student ID</th><th>Name</th><th>College</th><th>Org</th><th>Contact</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach($borrowers as $b): ?>
                      <tr id="row-brw-<?= $b['brwID'] ?>">
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= $b['brwID'] ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwStudentID']) ?></td>
                        <td>
                          <a href="?period=all&brw_val=<?= urlencode($b['brwID']) ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($b['brwFName'].' '.$b['brwLName']) ?></a>
                        </td>
                        <td><?= htmlspecialchars($b['brwCollege']) ?></td><td><?= htmlspecialchars($b['brwOrg']) ?></td><td><?= htmlspecialchars($b['brwContactNo']) ?></td>
                        <td>
                          <form action="actions/borrower_action.php" method="POST" class="d-inline" onsubmit="return confirm('Archive?');">
                            <input type="hidden" name="action" value="archive_borrower"><input type="hidden" name="brwID" value="<?= $b['brwID'] ?>">
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
