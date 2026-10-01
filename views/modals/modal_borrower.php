  <!-- Modal: Add Borrower -->
  <div class="modal fade" id="modalBorrower" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/borrower_action.php" method="POST">
          <input type="hidden" name="action" value="save_borrower">
          <input type="hidden" name="brwID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold">Add Borrower Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Student ID</label><input type="text" name="brwStudentID" class="form-control" required></div>
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">First Name</label><input type="text" name="brwFName" class="form-control" required></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">Last Name</label><input type="text" name="brwLName" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">College</label><input type="text" name="brwCollege" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Organization</label><input type="text" name="brwOrg" class="form-control"></div>
            <div class="mb-3"><label class="form-label fw-bold">Contact No.</label><input type="text" name="brwContactNo" class="form-control"></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4">Save Borrower</button></div>
        </form>
      </div>
    </div>
  </div>
