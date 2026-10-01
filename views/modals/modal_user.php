  <!-- Modal: Add User -->
  <div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/user_action.php" method="POST">
          <input type="hidden" name="action" value="save_user">
          <input type="hidden" name="userID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">First Name</label><input type="text" name="userFName" class="form-control" required></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">Last Name</label><input type="text" name="userLName" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="userEmail" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Contact No.</label><input type="text" name="userContactNo" class="form-control"></div>
            <div class="mb-3">
              <label class="form-label fw-bold">Role</label>
              <select name="userRole" class="form-select">
                <option value="Committee">Committee</option>
                <option value="Council">Council (Admin)</option>
              </select>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Password</label><input type="password" name="password" class="form-control" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save User</button></div>
        </form>
      </div>
    </div>
  </div>
