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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {
        // Initialize Select2 dropdown inside bootstrap modals
        $('.select2-init').select2({ dropdownParent: $('.modal') });
        
        // Mobile Sidebar Toggle Logic
        $('#sidebarToggle').click(function() {
            $('#sidebarMenu').addClass('show-sidebar');
        });
        $('#sidebarClose').click(function() {
            $('#sidebarMenu').removeClass('show-sidebar');
        });
        
        // Handle Sidebar Tab switching to mimic the old custom active state logic
        $('.nav-tab-btn').click(function() {
           $('.nav-tab-btn').removeClass('active');
