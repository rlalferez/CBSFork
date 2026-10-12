  <div class="modal fade" id="modalBorrower" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="actions/borrower_action.php" method="POST">
          <input type="hidden" name="action" value="save_borrower">
          <input type="hidden" name="brwID" value="NEW">
          <div class="modal-header"><h5 class="modal-title fw-bold" id="borrowerModalTitle">Add Borrower Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label fw-bold">Student ID</label><input type="text" name="brwStudentID" class="form-control" pattern="[0-9]{2}-[0-9]-[0-9]{5}" maxlength="10" placeholder="##-#-#####" title="Format: ##-#-##### (e.g. 23-1-12345)" required></div>
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">First Name</label><input type="text" name="brwFName" class="form-control" required></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-bold">Last Name</label><input type="text" name="brwLName" class="form-control" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">College</label><input type="text" name="brwCollege" class="form-control" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Organization</label><input type="text" name="brwOrg" class="form-control"></div>
            <div class="mb-3"><label class="form-label fw-bold">Contact No.</label><input type="text" name="brwContactNo" class="form-control" pattern="09[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX" title="11-digit Philippine mobile number starting with 09 (e.g. 09123456789)" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary-action py-2 px-4" id="borrowerModalBtn">Save Borrower</button></div>
        </form>
      </div>
    </div>
  </div>
