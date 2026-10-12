          <div class="tab-pane fade <?= ($activeTab ?? '') === 'tab-profile' ? 'show active' : '' ?>" id="tab-profile">
            <div class="content-card">
              <div class="content-card-header">
                <div class="card-title-group">
                  <h5 class="card-title">Profile Management</h5>
                  <p class="card-subtitle">Update your personal account details.</p>
                </div>
              </div>
              <div class="card-form-container">
                <form action="actions/profile_action.php" method="POST" class="profile-form">
                  <input type="hidden" name="action" value="update_profile">
                  <input type="hidden" name="userID" value="<?= htmlspecialchars($profileUser['userID']) ?>">
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold small">First Name</label>
                      <input type="text" class="form-control" name="userFName" value="<?= htmlspecialchars($profileUser['userFName']) ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-bold small">Last Name</label>
                      <input type="text" class="form-control" name="userLName" value="<?= htmlspecialchars($profileUser['userLName']) ?>" required>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-bold small">Email Address</label>
                    <input type="email" class="form-control" name="userEmail" value="<?= htmlspecialchars($profileUser['userEmail']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-bold small">Contact No</label>
                    <input type="text" class="form-control" name="userContactNo" value="<?= htmlspecialchars($profileUser['userContactNo']) ?>" pattern="09[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX" title="11-digit Philippine mobile number starting with 09 (e.g. 09123456789)" required>
                  </div>
                  <div class="mb-4">
                    <label class="form-label fw-bold small">New Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                  </div>
                  <button type="submit" class="btn btn-primary-action px-4 py-2">Save Changes</button>
                </form>
              </div>
            </div>
          </div>
