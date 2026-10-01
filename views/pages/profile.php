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

          <!-- PROFILE TAB -->
          <div class="tab-pane fade" id="tab-profile">
            <div class="content-card">
              <div class="content-card-header">
                <h5 class="fw-bold mb-1 text-dark">Profile Management</h5>
                <p class="text-muted small mb-0">Update your personal account details.</p>
              </div>
              <div class="p-4 bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <form action="actions/profile_action.php" method="POST" style="max-width: 600px;">
                  <input type="hidden" name="action" value="update_profile">
                  <input type="hidden" name="userID" value="<?= htmlspecialchars($profileUser['userID']) ?>">
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold small">First Name</label>
                      <input type="text" class="form-control" name="userFName" value="<?= htmlspecialchars($profileUser['userFName']) ?>" required>
                    </div>
