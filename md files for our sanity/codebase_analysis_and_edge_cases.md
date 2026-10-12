# Confederates Student Council (CSC) &mdash; Resource Management System
# Exhaustive Codebase Analysis, Version Differences, Error Assessment & Edge Case Matrix

**Document File:** `md files for our sanity/codebase_analysis_and_edge_cases.md`  
**Target Repository:** `C:\xampp\htdocs\CBSFork\`  
**Assessment Date:** 2026-10-12  
**Analyzed Commits:**
- **Most Recent Commit:** `ff51b37` (*commit and push* &mdash; Mon Oct 12 23:03:50 2026 +0800)
- **Previous Commit:** `0c21f36` (*commit and push* &mdash; Mon Oct 12 23:03:35 2026 +0800)
- **Preceding Milestone Commit:** `047b7c9` (*Commit and push* &mdash; Mon Oct 12 22:14:15 2026 +0800)

---

## Executive Summary & Foundational Context

The **Confederates Student Council (CSC) Resource Management System** is an internal, clerk-operated web terminal designed specifically for student council property desk officers. It operates under a **Clerk Terminal Paradigm** &mdash; students and organizations arrive at the physical property desk, and only authorized council officers (**Council Administrators** and **Committee Staff Members**) log in to manage equipment inventory, execute checkouts, verify student IDs, process returns, schedule future equipment reservations, and print receipts and official gate passes.

The system is constructed with a modular, procedural architecture:
1. **Master Assembler & Router (`index.php`):** Authenticates sessions, hydrates queries for inventory, active borrows, returns, purchases, and user accounts, and stitches views together into a single-page application (SPA) layout with Bootstrap 5 tabs.
2. **Configuration & Security (`config/`):**
   - `db.php`: PDO MySQL connection with prepared statements, error exception handling, and installation guide fallback.
   - `session.php`: Session lifecycle, role extraction (`$currentUser`, `$isAdmin`), flash alerts, and unauthenticated redirects.
   - `helpers.php`: Core utility library featuring natural length auto-incrementing ID generation (`generate_id`), HTML escaping (`e`), redirect alerts (`redirect_with_alert`), input regex sanitization, and the multi-interval reservation availability check engine (`check_item_availability_for_dates`).
3. **Backend Action Controllers (`actions/`):** Dedicated POST/AJAX endpoints (`auth_action.php`, `item_action.php`, `borrower_action.php`, `txn_action.php`, `user_action.php`, `profile_action.php`) executing database mutations with transaction rollbacks (`beginTransaction`, `commit`, `rollBack`).
4. **Presentation Views & Modals (`views/`):** Modular templates separated into `layout/` (topbar, sidebar, header, footer), `pages/` (transactions, inventory, borrowers, purchases, reports, users, profile), and `modals/` (`modal_txn.php`, `modal_item.php`, `modal_borrower.php`, `modal_purchase.php`, `modal_category.php`, `modal_user.php`).
5. **Interactive Client Engine (`assets/`):** `style.css` (custom design system, responsive media queries, print formatting) and `app.js` (live AJAX borrower searches, tab memory, double-submission prevention, cross-tab relational row highlighting, and modal event triggers).

---

## SECTION 1: DIFFERENCES (Git Diff Analysis)

This section documents the git diff comparing the most recent commit (`ff51b37`) with the previous version (`0c21f36`), including the UI and architectural context established in `0c21f36` relative to `047b7c9`.

```text
Commit Hash Comparison: 0c21f36f79995847d172d262dc4da1e036842942 -> ff51b3710f04903b6087dd2684f536cd9c27d2b6
Files Changed: 9 files | +121 lines | -37 lines
```

### Granular File-by-File Diff Breakdown:

#### 1. `actions/item_action.php` (+23, -3)
* **Lines Changed:** Lines 28&ndash;53
* **What Changed:**
  - In `action === 'save_item'` under the edit item block (`!empty($id) && $id !== 'NEW'`), the previous code only updated `itemDesc`, `itemCategory`, and `itemRate`, leaving `itemTotalQty` uneditable.
  - The new version queries the current stock (`itemTotalQty`, `itemAvailableQty`) and calculates active unreturned units:
    ```php
    $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.itemID = ? AND b.brwTransStatus = 'Released'");
    ```
  - Computes `$currentlyCheckedOut = max($unreturned, max(0, $currItem['itemTotalQty'] - $currItem['itemAvailableQty']))`.
  - Implements a guard: if the new quantity `$qty < $currentlyCheckedOut`, execution halts with `Quantity cannot be reduced below $currentlyCheckedOut unit(s) currently checked out.`.
  - Computes `$newAvailableQty = $qty - $currentlyCheckedOut` and updates `itemTotalQty` and `itemAvailableQty` alongside `itemDesc`, `itemCategory`, and `itemRate`.
* **Description & Purpose:** Allows administrators to correct equipment stock directly in the "Edit Item" modal without forcing them to enter purchase records, while safeguarding data integrity by preventing administrators from setting the total quantity below the number of units currently in borrowers' hands.

#### 2. `actions/txn_action.php` (+4, -3)
* **Lines Changed:** Lines 96&ndash;104
* **What Changed:**
  - In `action === 'purchase_item'`, when searching if an item being purchased already exists in inventory, the previous query checked:
    ```sql
    SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ? AND is_archived = 0 LIMIT 1
    ```
  - The revised query dropped `itemCategory = ?`:
    ```sql
    SELECT itemID, itemCategory FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND is_archived = 0 LIMIT 1
    ```
  - If a matching description is found, it retrieves the existing `itemCategory` from the database (`$cat = $existing['itemCategory'];`) and increments `itemTotalQty` and `itemAvailableQty`.
* **Description & Purpose:** Eliminates accidental item duplication when restocking. If a user logs a purchase for an existing item but mistakenly selects a different category, the system matches by item description and preserves the original item category rather than spawning a duplicate item row with the same name under a different category.

#### 3. `views/modals/modal_item.php` (+1, -1)
* **Lines Changed:** Line 20
* **What Changed:** Changed the input label from `<label class="form-label fw-bold">Starting Qty</label>` to `<label class="form-label fw-bold">Quantity</label>`.
* **Description & Purpose:** Since item stock is now editable post-creation, the label "Starting Qty" was misleading. Renaming it to "Quantity" accurately reflects that this field represents current total equipment stock.

#### 4. `views/modals/modal_purchase.php` (+27, -9)
* **Lines Changed:** Lines 26&ndash;29, 60&ndash;88
* **What Changed:**
  - Added a visual category lock indicator beneath the category dropdown:
    ```html
    <div class="category-lock-msg text-muted small mt-1" style="display:none; font-size:11px;">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>Category locked to item
    </div>
    ```
  - Upgraded the JavaScript function `autoFillCategory(inputElement)`:
    - Case-insensitive string matching against the datalist options.
    - If the item matches an existing item in the catalog, it auto-selects that item's category, sets `pointerEvents = 'none'`, sets `backgroundColor = '#e9ecef'`, sets `tabindex = '-1'`, and unhides `.category-lock-msg`.
    - If the item is new/unmatched, it restores `pointerEvents = ''`, resets background color, restores tab navigation, and hides the lock indicator.
* **Description & Purpose:** Provides immediate, clear visual feedback to the officer that an item's category is locked to its existing catalog classification, preventing mismatched categories during restocks while preserving native form submission (using `pointerEvents` instead of `disabled` ensures `<select>` values are not dropped by the browser during HTTP POST).

#### 5. `views/pages/inventory.php` (+4, -4)
* **Lines Changed:** Lines 18, 25&ndash;27
* **What Changed:** Added `class="col-number"` to table headers (`<th>`) and table data cells (`<td>`) for `Total Qty`, `Available`, and `Rate`.
* **Description & Purpose:** Standardizes numeric alignment in the inventory table, ensuring quantities and monetary values are right-aligned with tabular figures for enhanced financial/inventory readability.

#### 6. `views/pages/purchases.php` (+2, -2)
* **Lines Changed:** Lines 26, 33
* **What Changed:** Added `class="col-number"` to `<th>Qty</th>` and `<td class="col-number"><?= (int)$p['purQty'] ?></td>`.
* **Description & Purpose:** Aligns purchase transaction quantities to the right margin, maintaining consistency with the application-wide numeric styling.

#### 7. `views/pages/transactions.php` (+14, -14)
* **Lines Changed:** Lines 40, 61, 67, 106, 118, 122, 151, 164, 168, 184, 194, 209, 221, 225
* **What Changed:** Added `class="col-number"` across all transaction subtab tables (`txnAllTable`, `txnReservedTable`, `txnReleasedTable`, `txnReturnTable`, `txnCancelledTable`) for `Qty Borrowed`, `Qty Returned`, `Qty`, and `Fee`.
* **Description & Purpose:** Applies consistent right-aligned tabular formatting to all quantities and rental fees across all transaction views.

#### 8. `assets/css/style.css` (+18, -0)
* **Lines Changed:** Lines 1389&ndash;1407
* **What Changed:** Added Section 13:
  ```css
  /* 13. NUMERIC INPUTS & CONTAINER ALIGNMENT */
  input[type="number"],
  .input-number-align {
    direction: rtl !important;
    text-align: right !important;
    unicode-bidi: plaintext !important;
  }
  .col-number,
  .table-custom th.col-number,
  .table-custom td.col-number {
    text-align: right !important;
  }
  ```
* **Description & Purpose:** Enforces right-aligned text for table columns marked with `.col-number`, and configures numeric input fields with right-alignment.

#### 9. `assets/js/app.js` (+28, -2)
* **Lines Changed:** Lines 110&ndash;118, 137&ndash;144, 367, 381
* **What Changed:**
  - In `editItem()`: Changed `itemTotalQty.readOnly = true` to `itemTotalQty.readOnly = false`.
  - In modal reset event listener (`hidden.bs.modal` for `#modalItem`): Ensured `readOnly = false` on reset.
  - In purchase row cloning (`#add-pur-item-btn`): Added reset logic for `pointerEvents`, background color, `tabindex`, and `.category-lock-msg`.
  - In modal purchase reset (`hidden.bs.modal` for `#modalPurchase`): Resets the form, removes dynamically added rows beyond row 1, restores the category dropdown state, and hides the lock message.
* **Description & Purpose:** Synchronizes client-side modal behavior with the backend updates, ensuring form fields properly reset when modals are dismissed.

---

### Context of Preceding Commit `0c21f36` (Compared to `047b7c9`):
1. **Split-Screen Authentication Redesign (`login.php` & `assets/css/style.css`):**
   - Transformed the centered card login into a modern split-screen layout with an institutional brand panel on the left (featuring the official seal, council typography, and department titles) and an authentication card on the right.
   - Refactored CSS variables and removed old generic Bootstrap utility classes in favor of dedicated design tokens (`.login-wrapper`, `.login-brand-panel`, `.login-form-panel`, `.login-card`).
2. **Cancelled Transactions Subtab Integration (`views/layout/sidebar.php` & `views/pages/transactions.php`):**
   - Added a dedicated "Cancelled" subtab button in `views/layout/sidebar.php` (`openSubtab('#tab-transactions', '#txn-cancelled')`).
   - Added `#txn-cancelled` tab pane in `views/pages/transactions.php` with a dedicated table (`#txnCancelledTable`) to cleanly separate cancelled reservations from active operations.

---

## SECTION 2: ERRORS (Identified Through Exhaustive Internal Testing)

Through automated test script execution, edge-case simulation, and code logic tracing across all workflows, the following **10 errors, UI placeholders, and logic flaws** were identified in the active codebase:

---

### Error 1: Cancelled Reservations Permanently Block Borrower Archiving
* **Severity:** High (Functional Logic Bug)
* **File:** `actions/borrower_action.php` (Line 64)
* **Error Summary:** When an officer attempts to archive a student or organization borrower, the system executes an unreturned item calculation query without filtering transaction status. If a borrower has even one `Cancelled` reservation, the calculation evaluates `brwTransItemQty - 0 > 0`, falsely identifying the borrower as possessing active, unreturned council equipment. The system halts with an error and permanently prevents the borrower from being archived.
* **Revision Needed:** Update the SQL query in `actions/borrower_action.php` to strictly filter by `AND b.brwTransStatus = 'Released'` so that cancelled and non-released bookings are excluded from the unreturned equipment check.

---

### Error 2: Equipment Item Archiving Ignores Active Future Reservations
* **Severity:** High (Data Integrity & Scheduling Flaw)
* **File:** `actions/item_action.php` (Line 92)
* **Error Summary:** The item archiving guard verifies whether any units are checked out by querying only `AND b.brwTransStatus = 'Released'`. If an item has active future bookings (`brwTransStatus = 'Reserved'`), the query evaluates `unreturned = 0` and permits the administrator to archive the equipment item (`is_archived = 1`). Once archived, the item disappears from the inventory list, but the future reservation remains orphaned in the transaction ledger. When the borrower arrives on the reserved date, the equipment is missing from the system catalog.
* **Revision Needed:** Update the SQL query in `actions/item_action.php` to check `WHERE b.itemID = ? AND b.brwTransStatus IN ('Released', 'Reserved')` so items with active commitments cannot be archived.

---

### Error 3: Desk Checkout Silently Reuses Existing/Archived Student ID Without Officer Confirmation or Profile Reactivation
* **Severity:** High (Data Integrity, Identity Collision & Visibility Flaw)
* **Files:** `actions/txn_action.php` (Lines 148&ndash;167), `views/modals/modal_txn.php`, `actions/borrower_action.php`, `assets/js/app.js`
* **Error Summary:** During desk checkout, if a student ID is typed under "+ Add New Borrower", `actions/txn_action.php` executes `SELECT brwID FROM borrower WHERE brwStudentID = ? LIMIT 1` without filtering `is_archived = 0` and without validating whether the entered name matches the database. This creates two critical vulnerabilities:
  1. **Silent Soft-Archive Trap:** If the student was archived, the transaction attaches to their old `brwID`, but the record remains soft-archived (`is_archived = 1`). The borrower stays invisible in Borrower Management (`views/pages/borrowers.php`) and live autocomplete searches, causing broken cross-tab row highlighting and orphaned directory listings.
  2. **Typo & Identity Collision Vulnerability:** If the desk officer makes a typo in the Student ID field (e.g., typing `21-1-12345` instead of `24-1-12345`), the system silently binds the checkout to the pre-existing borrower without any warning. If the system were to silently auto-reactivate and overwrite details, the past transaction history of the existing student would be irreversibly overwritten with the new student's name and contact information, corrupting audit trails.
* **Revision Needed:** Implement an interactive confirmation workflow. When an entered Student ID already exists in the database (whether archived or active), prompt the desk officer with a confirmation modal detailing the existing record (Name, College, Status). Provide the officer with clear choices: (a) Reactivate & Use Existing Record, (b) Update Existing Profile Details, or (c) Change/Correct Student ID (to rectify typos).

---

### Error 4: Browser Pop-Up Blocker Intercepts Automated Equipment Receipt Printing
* **Severity:** Medium (UX & Operational Workflow Issue)
* **Files:** `index.php` (Lines 230&ndash;237), `views/pages/transactions.php`
* **Error Summary:** When checkout completes, `index.php` injects a JavaScript snippet inside `DOMContentLoaded` that triggers `window.open('print_receipt.php?ids=...', 'ReceiptWindow')`. Modern web browsers (Google Chrome, Microsoft Edge, Mozilla Firefox) automatically block programmatic `window.open()` calls executed without a direct user gesture (click event), showing a "Pop-up blocked" notification in the address bar. Because `views/pages/transactions.php` lacks any manual "Print Receipt" button in the ledger table, officers who miss the browser pop-up prompt have no way to reprint the gate pass.
* **Revision Needed:** Add a manual "Print Receipt" / "Gate Pass" icon button in the action column of `views/pages/transactions.php` for all released and completed borrow transactions, and provide an alert banner with a direct click link if pop-ups are suppressed.

---

### Error 5: Availability Engine Approves Inverted Date Ranges (`startDate > endDate`)
* **Severity:** Medium (Algorithmic Defense Gap)
* **File:** `config/helpers.php` (Lines 92&ndash;210)
* **Error Summary:** The core availability helper function `check_item_availability_for_dates()` lacks an internal guard against inverted date ranges (`strtotime($endDate) < strtotime($startDate)`). If invoked directly with an inverted range (e.g., borrow on Oct 20, return on Oct 15), the milestone date sort and overlap conditions produce zero overlapping bookings, causing the function to return `success: true` ("Available from Oct 20, 2026 to Oct 15, 2026").
* **Revision Needed:** Add a preliminary date validation guard inside `check_item_availability_for_dates()` returning `success => false` with an explicit error message whenever `strtotime($endDate) < strtotime($startDate)`.

---

### Error 6: Backend Allows New Staff User Creation with Empty Passwords
* **Severity:** High (Security Flaw)
* **File:** `actions/user_action.php` (Lines 54&ndash;58)
* **Error Summary:** When an administrator adds a new council or committee staff user (`$id === 'NEW'`), `actions/user_action.php` takes `$pass = trim($_POST['password'] ?? '')` and hashes it directly via `password_hash($pass, PASSWORD_DEFAULT)` without verifying that `$pass` is non-empty. While client-side HTML contains `required`, any bypassed request creates a user account with a valid Bcrypt hash of an empty string, allowing sign-in with no password.
* **Revision Needed:** Add backend validation in `actions/user_action.php` requiring `!empty($pass)` when creating a new user, redirecting with a descriptive error message if missing.

---

### Error 7: Discrepancy in Item Description Uniqueness Between Manual Item Creation and Purchases
* **Severity:** Medium (Database Consistency Issue)
* **Files:** `actions/item_action.php` (Lines 26 & 56) vs `actions/txn_action.php` (Line 97)
* **Error Summary:** In `actions/item_action.php`, duplicate item checking checks both description and category: `WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ?`. In `actions/txn_action.php`, the purchase restocking logic matches on description alone: `WHERE LOWER(TRIM(itemDesc)) = LOWER(?)`. If an administrator manually creates "Projector EPSON" under "Electronics" and later creates "Projector EPSON" under "Office Supplies", two distinct items exist with identical names. When a purchase is logged, `LIMIT 1` arbitrarily restocks whichever record the database engine returns first.
* **Revision Needed:** Standardize item description uniqueness so that duplicate item descriptions are rejected across the entire active inventory, ensuring predictable 1:1 mapping during purchase restocks.

---

### Error 8: Right-to-Left CSS (`direction: rtl`) Distorts Decimal Rate Inputs and Numeric Editing
* **Severity:** Low (Visual & Interaction Glitch)
* **File:** `assets/css/style.css` (Lines 1395&ndash;1400)
* **Error Summary:** In commit `ff51b37`, `direction: rtl !important;` was applied to all `input[type="number"]`. While designed to place spinner arrows on the left, RTL formatting reverses cursor navigation, inverts spin arrows in Firefox, and disrupts character entry for floating-point decimal inputs (such as the rental fee field `itemRate`, e.g. `step="0.01"`), causing the decimal point to visually displace numbers while typing.
* **Revision Needed:** Remove `direction: rtl !important;` from `input[type="number"]` in `assets/css/style.css`, retaining clean right-alignment using `text-align: right;` and `font-variant-numeric: tabular-nums;`.

---

### Error 9: Unused and Hardcoded `brwTransPayStat` in Desk Checkout Workflow
* **Severity:** Low (Design & Data Completeness Issue)
* **File:** `actions/txn_action.php` (Line 177)
* **Error Summary:** In `actions/txn_action.php`, checkout logic attempts to extract `$payStat = trim($_POST['brwTransPayStat'] ?? 'Free')`. However, `views/modals/modal_txn.php` contains no input or selector for payment status. Consequently, all transactions are recorded in the database with `brwTransPayStat = 'Free'`, even when equipment has an assigned rate (`itemRate > 0`) and total rental fees are computed.
* **Revision Needed:** Either include an explicit "Payment Status" toggle (`Free` vs `Paid`) in `views/modals/modal_txn.php` when item fees exceed zero, or auto-assign `'Paid'` when fees are applicable.

---

### Error 10: Placeholder Text Used for College Seal on Sign-In Screen
* **Severity:** Low (UI / Brand Presentation Issue)
* **File:** `login.php` (Lines 29&ndash;32)
* **Error Summary:** On the sign-in portal (`login.php`), the left institutional branding panel displays a circular badge containing raw text: `<span class="login-logo-text">SU CCS<br>LOGO</span>`. This placeholder was left during initial prototyping despite the official high-resolution college emblem asset (`assets/images/imgCCS.png`) already being present in the repository.
* **Revision Needed:** Replace the text placeholder badge in `login.php` with `<img src="assets/images/imgCCS.png" alt="SU CCS Logo" class="login-logo-image">`.

---

## SECTION 3: PENDING REVISIONS (Batch 13 & 14)

*(These targeted revisions must be made to address the errors identified above. In accordance with project instructions, no revisions are implemented in this turn &mdash; this section details the exact specifications, line ranges, and behavioral changes required).*

---

### 45. Cancelled Reservation Borrower Archive Guard Fix
* **File to Revise:** `actions/borrower_action.php`
* **Target Lines:** Lines 63&ndash;74
* **What Needs to be Revised:** The SQL query checking for unreturned borrower items must explicitly filter by active released transactions.
* **Targeted Revision Steps:**
  1. Locate the check query in `action === 'archive_borrower'`:
     ```php
     // OLD:
     $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.brwID = ?");
     ```
  2. Append `AND b.brwTransStatus = 'Released'`:
     ```php
     // NEW:
     $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.brwID = ? AND b.brwTransStatus = 'Released'");
     ```
* **System Behavior Before Revision:** If a borrower has a Cancelled reservation, `(brwTransItemQty - 0)` evaluates to greater than zero, permanently locking the borrower profile and preventing archiving with an error: *"Cannot archive borrower: they have X unreturned item(s)."*
* **System Behavior After Revision:** The system accurately ignores cancelled reservations, allowing the officer to cleanly archive the borrower while still preventing archiving if the borrower holds physically released, unreturned items.

---

### 46. Item Archive Guard Protection for Active Reservations
* **File to Revise:** `actions/item_action.php`
* **Target Lines:** Lines 91&ndash;102
* **What Needs to be Revised:** The item archive check must protect both currently released equipment and future equipment reservations.
* **Targeted Revision Steps:**
  1. Locate the check query in `action === 'archive_item'`:
     ```php
     // OLD:
     $check = $db->prepare("SELECT SUM(brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) as unreturned FROM borrow_transaction b WHERE b.itemID = ? AND b.brwTransStatus = 'Released'");
     ```
  2. Change `b.brwTransStatus = 'Released'` to check both active statuses:
     ```php
     // NEW:
     $check = $db->prepare("SELECT SUM(CASE WHEN b.brwTransStatus = 'Released' THEN (brwTransItemQty - COALESCE((SELECT SUM(brwTransQty) FROM return_transaction r WHERE r.brwTransID = b.brwTransID), 0)) ELSE brwTransItemQty END) as active_count FROM borrow_transaction b WHERE b.itemID = ? AND b.brwTransStatus IN ('Released', 'Reserved')");
     ```
  3. Update alert message to explain if items are checked out or reserved:
     ```php
     if ($activeCount > 0) {
         redirect_with_alert('tab-inventory', 'danger', "Cannot archive item: $activeCount unit(s) are currently checked out or reserved. Complete or cancel them first.");
     }
     ```
* **System Behavior Before Revision:** Administrators could archive equipment items that had future reservations, removing the items from the active inventory catalog and stranding the borrower's booking.
* **System Behavior After Revision:** The system halts any attempt to archive items committed to active checkouts or future reservations until those commitments are resolved.

---

### 47. Existing / Archived Borrower Student ID Conflict Handling (Outright Rejection with Alert & Input Reset)
* **Files to Revise:** `actions/borrower_action.php`, `views/modals/modal_txn.php`, `actions/txn_action.php`
* **Target Lines:** 
  - `actions/borrower_action.php`: Lines 17&ndash;33
  - `views/modals/modal_txn.php`: Lines 614&ndash;656
  - `actions/txn_action.php`: Lines 147&ndash;165
* **Architectural Rationale:**
  When a desk officer enters a Student ID under "+ Add New Borrower" that already exists in the database (either active or archived), nested confirmation popups introduce significant code length (~130 extra lines) and complex modal-on-modal backdrop state handling to `modal_txn.php`. To keep `modal_txn.php` clean, concise, and university-portfolio appropriate, we implemented an **Outright Rejection Architecture**:
  - The system checks conflicting Student IDs against both active and archived records.
  - If a conflict is detected, the system immediately rejects the conflicting input, displays an alert message informing the user which student is already registered with that ID, and resets/focuses the input field.
  - No nested confirmation dialogs, no hidden state flags, and zero risk of accidental identity overwriting or duplicate key corruption.

#### Targeted Implementation Specifications:

##### Step 1: Lightweight Real-Time Verification Endpoint (`actions/borrower_action.php`)
Check whether the Student ID exists in either active or archived status:
```php
} elseif ($action === 'check_student_id') {
    header('Content-Type: application/json');
    $studentId = trim($_POST['studentId'] ?? '');
    $stmt = $db->prepare("SELECT brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo, is_archived FROM borrower WHERE brwStudentID = ? LIMIT 1");
    $stmt->execute([$studentId]);
    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($borrower) {
        echo json_encode([
            'exists' => true,
            'is_archived' => (int)$borrower['is_archived'],
            'borrower' => $borrower
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}
```

##### Step 2: Streamlined Client-Side Rejection & Alert (`views/modals/modal_txn.php`)
Hook onto the `#bStudentID` input blur event. If the entered Student ID already exists in the system (active or archived), alert the officer and clear/refocus the input field:
```javascript
function checkBorrowerStudentID(studentId) {
    if (!studentId || !/^[0-9]{2}-[0-9]-[0-9]{5}$/.test(studentId)) {
        return;
    }
    const selectedId = document.getElementById('selectedBrwID')?.value;
    if (selectedId) return;

    const params = new URLSearchParams();
    params.append('action', 'check_student_id');
    params.append('studentId', studentId);

    fetch('actions/borrower_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(res => res.json())
    .then(res => {
        if (res && res.exists) {
            const status = (parseInt(res.is_archived) === 1) ? 'Archived' : 'Active';
            alert(`Cannot use Student ID ${res.borrower.brwStudentID}: It is already registered to an ${status} borrower (${res.borrower.brwFName} ${res.borrower.brwLName}).\n\nPlease select them from the search bar above or enter a different Student ID.`);
            const studentInput = document.getElementById('bStudentID');
            if (studentInput) {
                studentInput.value = '';
                studentInput.focus();
            }
        }
    })
    .catch(err => console.error(err));
}
```

##### Step 3: Server-Side Outright Rejection Guard (`actions/txn_action.php`)
In `action === 'create_borrow'`, verify if the Student ID belongs to any existing record (active or archived) and reject immediately:
```php
// 47. Check if student ID already exists (both active and archived)
$checkStmt = $db->prepare("SELECT brwID, brwStudentID, brwFName, brwLName, is_archived FROM borrower WHERE brwStudentID = ? LIMIT 1");
$checkStmt->execute([$studentId]);
$existingBorrower = $checkStmt->fetch();

if ($existingBorrower) {
    $status = ((int)$existingBorrower['is_archived'] === 1) ? 'archived' : 'active';
    redirect_with_alert('tab-transactions', 'danger', "Cannot register borrower: Student ID {$studentId} is already registered to an {$status} borrower ({$existingBorrower['brwFName']} {$existingBorrower['brwLName']}). Please select them from the search bar or verify the ID.");
} else {
    $brwID = generate_id($db, 'borrower', 'brwID', 'BRW-');
    $stmt = $db->prepare("INSERT INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwContactNo, brwCollege, brwOrg) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$brwID, $studentId, $fName, $lName, $contactNo, $college, $org]);
}
```

* **System Behavior Before Revision:** Entering an existing or archived Student ID could silently attach transactions without surfacing the profile or could risk data collision.
* **System Behavior After Revision:** Entering a conflicting Student ID triggers an immediate alert modal/dialog informing the desk officer that the ID is already taken by an active or archived student, immediately clearing the field. The server strictly enforces this rule, maintaining high data integrity without inflating modal script size.

---

### 48. Transaction Ledger Manual Receipt Reprint Button & Pop-Up Blocker Fallback
* **Files to Revise:** `views/pages/transactions.php`, `index.php`
* **Target Lines:** `views/pages/transactions.php` (Lines 76&ndash;93, 169&ndash;170), `index.php` (Lines 189&ndash;197)
* **What Needs to be Revised:** Add an action icon button to print receipts directly from the transaction row, and provide a direct link in the success flash banner if pop-ups are suppressed.
* **Targeted Revision Steps:**
  1. In `views/pages/transactions.php`, in the `Action` column for released and returned transactions:
     ```html
     <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Print Receipt" onclick="window.open('print_receipt.php?ids=<?= urlencode(json_encode([$t['brwTransID']])) ?>', 'ReceiptWindow', 'width=800,height=600')">
       <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Receipt
     </button>
     ```
  2. In `index.php`, when `$printReceipt` is populated, display a direct link inside the alert notification:
     ```php
     $receiptUrl = 'print_receipt.php?ids=' . urlencode(json_encode($printReceipt));
     ```
* **System Behavior Before Revision:** If the browser blocked the unprompted `window.open()` pop-up on page reload, the receipt could never be accessed or printed again.
* **System Behavior After Revision:** Officers can print receipts on demand from the transaction ledger and click the direct link in the confirmation banner.

---

### 49. Internal Temporal Validation Guard in `check_item_availability_for_dates`
* **File to Revise:** `config/helpers.php`
* **Target Lines:** Lines 92&ndash;95
* **What Needs to be Revised:** Prevent availability checks on inverted date ranges from returning false positive availability.
* **Targeted Revision Steps:**
  1. Inside `check_item_availability_for_dates()` before executing database queries:
     ```php
     if (strtotime($endDate) < strtotime($startDate)) {
         return [
             'success' => false,
             'message' => 'Return date cannot be earlier than borrow date.',
             'available' => 0,
             'total' => 0,
             'item' => null
         ];
     }
     ```
* **System Behavior Before Revision:** Calling the helper with inverted dates returned `success: true` ("Available from Oct 20 to Oct 15").
* **System Behavior After Revision:** The helper intrinsically rejects inverted date ranges, enforcing temporal integrity across all endpoints.

---

### 50. Backend Password Enforcement for New User Accounts
* **File to Revise:** `actions/user_action.php`
* **Target Lines:** Lines 20&ndash;21, 54&ndash;58
* **What Needs to be Revised:** Validate that password is non-empty when creating a new user account.
* **Targeted Revision Steps:**
  1. In `actions/user_action.php` under `action === 'save_user'`:
     ```php
     if (empty($id) || $id === 'NEW') {
         if (empty($pass)) {
             redirect_with_alert('tab-users', 'danger', 'Error: Password is required when creating a new user account.');
         }
     }
     ```
* **System Behavior Before Revision:** Omitting the password field during user creation allowed blank passwords to be stored in the database.
* **System Behavior After Revision:** The server enforces non-empty passwords for new user creation, redirecting with a clear error message if omitted.

---

### 51. Cross-Category Item Description Uniqueness Alignment
* **File to Revise:** `actions/item_action.php`
* **Target Lines:** Lines 26&ndash;30, 56&ndash;60
* **What Needs to be Revised:** Align manual item creation uniqueness with the purchase restocking logic by checking item description uniqueness across all active items regardless of category.
* **Targeted Revision Steps:**
  1. In `actions/item_action.php`:
     ```php
     // During Edit:
     $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemID != ? AND is_archived = 0");
     $stmt->execute([$desc, $id]);
     if ($stmt->fetch()) {
         redirect_with_alert('tab-inventory', 'danger', "An item with this description already exists in the inventory.");
     }
     
     // During Add:
     $stmt = $db->prepare("SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND is_archived = 0");
     $stmt->execute([$desc]);
     if ($stmt->fetch()) {
         redirect_with_alert('tab-inventory', 'danger', "Cannot create: An item with this description already exists in the inventory.");
     }
     ```
* **System Behavior Before Revision:** Duplicate item descriptions were permitted across different categories, causing purchase restocks to arbitrarily update whichever record MySQL returned first.
* **System Behavior After Revision:** Item descriptions are globally unique across all active inventory items, guaranteeing seamless 1:1 restocking.

---

### 52. Removal of Glitched `direction: rtl` on Number Inputs
* **File to Revise:** `assets/css/style.css`
* **Target Lines:** Lines 1395&ndash;1400
* **What Needs to be Revised:** Replace bidirectional text layout overrides with standard numeric text alignment.
* **Targeted Revision Steps:**
  1. Replace:
     ```css
     /* OLD: */
     input[type="number"],
     .input-number-align {
       direction: rtl !important;
       text-align: right !important;
       unicode-bidi: plaintext !important;
     }
     ```
  2. With:
     ```css
     /* NEW: */
     input[type="number"],
     .input-number-align {
       text-align: right !important;
       font-variant-numeric: tabular-nums;
     }
     ```
* **System Behavior Before Revision:** Typing decimal numbers in rate fields caused character jumping, inverted cursor arrow keys, and inconsistent stepper behaviors across browsers.
* **System Behavior After Revision:** Numeric inputs remain right-aligned with stable cursor movements, native decimal entry, and consistent stepper arrows.

---

### 53. Payment Status Configuration (Reverted per Targeted User Request)
* **Status:** **Reverted.**
* **Files Affected:** `views/modals/modal_txn.php`, `actions/txn_action.php`
* **Target Lines:** `views/modals/modal_txn.php` (Lines 120&ndash;130), `actions/txn_action.php` (Line 179)
* **Architectural Rationale:** Because payment status is not displayed on any transaction tables or report views, adding an extra dropdown to the checkout modal added visual clutter without operational benefit. This revision was cleanly reverted, restoring the clean 2-column layout (`col-md-6`) for borrow and return dates, while preserving safe database defaulting to `'Free'`.
* **System Behavior:** The checkout modal retains its clean 2-column layout for borrow and return dates, and transactions default safely to `'Free'` in the database without extra clerk input.

---

### 54. Replace "SU CCS LOGO" Placeholder with Institutional Emblem (`imgCCS.png`)
* **File to Revise:** `login.php`
* **Target Lines:** Lines 29&ndash;32
* **What Needs to be Revised:** Replace the text badge placeholder `<span class="login-logo-text">SU CCS<br>LOGO</span>` with the actual college emblem image tag referencing `assets/images/imgCCS.png` using the existing `.login-logo-image` CSS class.
* **Targeted Revision Steps:**
  1. Open `login.php` and locate the `SU CCS Logo Badge` markup within the `.login-logos` container:
     ```html
     <!-- OLD: -->
     <!-- SU CCS Logo Badge -->
     <div class="login-logo-badge">
        <span class="login-logo-text">SU CCS<br>LOGO</span>
     </div>
     ```
  2. Replace the placeholder `<span>` element with the actual image asset:
     ```html
     <!-- NEW: -->
     <!-- SU CCS Logo Badge -->
     <div class="login-logo-badge">
        <img src="assets/images/imgCCS.png" alt="SU CCS Logo" class="login-logo-image">
     </div>
     ```
* **System Behavior Before Revision:** The login screen left brand panel displays a circular white badge with plain text stating "SU CCS LOGO", indicating an unfinished mockup placeholder.
* **System Behavior After Revision:** The login screen left brand panel displays the authentic high-resolution college emblem (`imgCCS.png`) alongside the Confederates Student Council seal (`ccs_confederates_logo.png`), establishing polished institutional branding consistent with university standards.

---

## SECTION 4: EDGE CASES (Human & System Interaction Matrix)

This section maps all conceivable human and system interactions across every subsystem, analyzing boundary conditions, malformed inputs, concurrency, and security.

```
Status Legend:
[HANDLED]   : Fully validated, defended, and graceful behavior confirmed in active code.
[PARTIAL]   : Handled in some layers (e.g. client-side only or backend only), but has edge-case vulnerabilities.
[UNHANDLED] : Flaw identified; requires targeted revision listed in Section 3.
```

---

### Subsystem 1: Authentication & Session Lifecycle

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Invalid Password** | Incorrect password string | **[HANDLED]** | `actions/auth_action.php` invokes `password_verify($password, $user['userPassword'])`. Evaluates to `false`, sets `$_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email or password.']`, and redirects to `login.php`. |
| **Unregistered Email** | `ghost@csc.edu.ph` | **[HANDLED]** | `actions/auth_action.php` executes prepared statement `SELECT * FROM user WHERE userEmail = ? AND is_archived = 0`. Returns empty, fails `if ($user && ...)` check, and triggers error redirect. |
| **Archived User Login** | Credentials of user with `is_archived = 1` | **[HANDLED]** | `actions/auth_action.php` query explicitly includes `AND is_archived = 0`. Soft-archived accounts return no row and cannot authenticate. |
| **SQL Injection in Login** | `' OR 1=1 --` | **[HANDLED]** | PDO prepared statement with parameterized binding (`$stmt->execute([$email])`) treats input as a literal string. |
| **Unauthenticated Direct Access** | Navigating to `index.php` or `actions/txn_action.php` directly | **[HANDLED]** | `config/session.php` checks `if (!$currentUser)`. Detects unauthenticated session, determines relative path (`../login.php` or `login.php`), and executes `header("Location: $redirectUrl"); exit;`. |
| **Authenticated User Accessing Login** | Navigating to `login.php` while session active | **[HANDLED]** | `login.php` evaluates `if ($currentUser) { header('Location: index.php'); exit; }`, redirecting officer directly to dashboard. |
| **Session Cookie Destruction on Sign Out** | Clicking "Sign Out" in sidebar | **[HANDLED]** | `actions/auth_action.php` (`action === 'logout'`) clears `$_SESSION = []`, expires session cookie via `setcookie()`, invokes `session_destroy()`, and redirects to `login.php`. |
| **Brand Logo Asset Rendering** | Rendering college & council seals on sign-in | **[PARTIAL]** | `login.php` displays council logo via `<img>`, but college emblem currently displays text badge `<span class="login-logo-text">SU CCS<br>LOGO</span>` instead of loading `assets/images/imgCCS.png` (Identified as **Error 10**, targeted by **Revision 54**). |

---

### Subsystem 2: Equipment Checkout & Lending Lifecycle

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Zero or Negative Quantity** | `qty = 0` or `qty = -5` | **[HANDLED]** | `actions/txn_action.php` runs `$qty = max(1, (int)($qtys[$i] ?? 1))`. Negative numbers or zeros are forced to minimum 1. |
| **Quantity Exceeding Shelf Stock** | Requesting 15 units when 10 exist | **[HANDLED]** | `check_item_availability_for_dates()` calculates `$availableForPeriod = max(0, $totalQty - $peakBooked)`. Throws Exception: *"Only X unit(s) available..."*. Modal displays conflict dialog with option to adjust quantity to available stock. |
| **Empty Items Checkout** | Submitting borrow modal with 0 rows | **[HANDLED]** | `actions/txn_action.php` evaluates `if (empty($itemIDs))`. Halts execution and redirects with *"Error: No items selected for checkout."*. |
| **Inverted Checkout Dates** | Return date earlier than Borrow date | **[HANDLED]** | Both frontend (`assets/js/app.js` sets `min` attribute and triggers warning) and backend (`actions/txn_action.php` runs `strtotime($returnByDate) < strtotime($borrowOnDate)`) intercept and reject inverted dates. |
| **Checkout for Today (Physical Handover)** | `borrowOnDate == date('Y-m-d')` | **[HANDLED]** | Evaluates `$isReservation = ($borrowOnDate > $today)` as `false`. Assigns status `'Released'`, decrements `itemAvailableQty` immediately via `UPDATE item SET itemAvailableQty = GREATEST(0, itemAvailableQty - ?)`. |
| **Checkout for Future Date (Reservation)** | `borrowOnDate > date('Y-m-d')` | **[HANDLED]** | Evaluates `$isReservation = true`. Assigns status `'Reserved'`. Does not decrement current shelf stock, but locks equipment in `check_item_availability_for_dates()` for that future window. |
| **Duplicate Item Rows in Same Cart** | Selecting same item twice in checkout | **[HANDLED]** | Intra-transaction queries in `check_item_availability_for_dates()` see uncommitted records within the active PDO transaction and calculate cumulative peak booked quantity, preventing overbooking. |
| **Checkout with Unregistered Borrower** | Typing new student details in modal | **[HANDLED]** | `actions/txn_action.php` verifies student ID format (`validate_student_id`), mobile number (`validate_ph_contact`), generates `BRW-xxx`, inserts into `borrower`, and links to the transaction. |
| **Checkout with Soft-Archived Borrower** | Typing student ID of archived student | **[PARTIAL]** | Reuses archived borrower ID without restoring `is_archived = 0` or prompting officer, risking silent identity overwrite and keeping profile hidden (Identified as **Error 3**, targeted by **Revision 47**). |
| **Student ID Typo or Existing ID Collision** | Entering existing student ID under Add New Borrower | **[UNHANDLED]** | Lacks interactive confirmation prompt to inform officer of collision, risking accidental identity overwriting or typo propagation (Identified as **Error 3**, targeted by **Revision 47**). |
| **Automatic Receipt Pop-Up** | Form submit triggers page reload | **[PARTIAL]** | `index.php` triggers `window.open()`, but modern browsers suppress unprompted pop-ups without a user gesture (Identified as **Error 4**, targeted by **Revision 48**). |

---

### Subsystem 3: Equipment Returns & Stock Restoration

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Full Return** | Returning all borrowed units | **[HANDLED]** | `actions/txn_action.php` inserts record into `return_transaction`, increments `itemAvailableQty` by return quantity, detects `($alreadyReturned + $qty) >= $originalQty`, and updates `brwTransStatus = 'Returned'`. |
| **Partial Return** | Returning 2 of 5 borrowed units | **[HANDLED]** | Inserts partial record into `return_transaction` (`qty = 2`), restores 2 units to `itemAvailableQty`, but keeps `brwTransStatus = 'Released'` so remaining 3 units remain actively tracked. |
| **Excessive Return Quantity** | Attempting to return 6 when only 5 borrowed | **[HANDLED]** | `actions/txn_action.php` queries `SUM(brwTransQty) as total_returned`. Runs `if ($qty > ($originalQty - $alreadyReturned))`. Throws Exception: *"Cannot return more items than originally borrowed."*, rolling back transaction. |
| **Return Date Before Borrow Date** | Ret Date set to date before checkout | **[HANDLED]** | `actions/txn_action.php` compares timestamps: `if (strtotime($retDate) < strtotime($txnData['brwTransBorrowOnDate']))`. Throws Exception: *"Return date cannot be earlier than the original checkout date."*. |
| **Borrower with Zero Active Borrows** | Searching student with no active loans | **[HANDLED]** | `actions/txn_action.php` (`search_active_borrows`) filters `WHERE brwTransStatus = 'Released' HAVING (brwTransItemQty - returned_qty) > 0`. Returns empty JSON array; UI shows *"No active borrowings found"*. |

---

### Subsystem 4: Inventory & Equipment Management

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Duplicate Item Description (Same Category)** | Adding "Projector" when "Projector" exists in category | **[HANDLED]** | `actions/item_action.php` checks `LOWER(TRIM(itemDesc)) = LOWER(?) AND itemCategory = ? AND is_archived = 0`. Intercepts and redirects with danger alert. |
| **Duplicate Description (Different Category)** | Adding "Projector" in "Office" when exists in "Audio" | **[PARTIAL]** | Allowed in `item_action.php`, but causes collision in `txn_action.php` purchase restocking (Identified as **Error 7**, targeted by **Revision 51**). |
| **Negative Equipment Rental Fee** | Setting Rate = `-25.00` | **[HANDLED]** | `actions/item_action.php` extracts rate via `max(0.0, (float)($_POST['itemRate'] ?? 0))`. Negative fees are automatically converted to `0.00`. |
| **Reducing Stock Below Active Checkouts** | Reducing Total Qty from 10 to 3 while 5 are checked out | **[HANDLED]** | `actions/item_action.php` queries active unreturned units (`currentlyCheckedOut = 5`). Runs `if ($qty < $currentlyCheckedOut)`. Rejects update with error: *"Quantity cannot be reduced below 5 unit(s) currently checked out."*. |
| **Archiving Item with Active Loans** | Clicking Archive on item with active loans | **[HANDLED]** | `actions/item_action.php` queries active unreturned units with `brwTransStatus = 'Released'`. If > 0, halts with error: *"Cannot archive item: X unit(s) are currently checked out. Return them first."*. |
| **Archiving Item with Future Reservations** | Clicking Archive on item with future bookings | **[UNHANDLED]** | Only checks `brwTransStatus = 'Released'`, ignoring reservations and allowing archiving of reserved equipment (Identified as **Error 2**, targeted by **Revision 46**). |
| **Deleting Category with Assigned Items** | Deleting category currently in use | **[HANDLED]** | Category is removed from `category` table dropdown, but existing items keep their string value in `itemCategory` column (`VARCHAR(50)`), avoiding data corruption. |

---

### Subsystem 5: Restocking Purchases

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Restock Existing Item** | Purchasing item existing in catalog | **[HANDLED]** | `actions/txn_action.php` matches description, increments `itemTotalQty` and `itemAvailableQty`, and logs transaction in `purchase_transaction`. |
| **Purchase Brand New Equipment Item** | Purchasing item not present in catalog | **[HANDLED]** | Generates new `ITM-xxx`, auto-registers category via `INSERT IGNORE INTO category`, sets `itemTotalQty = qty` and `itemAvailableQty = qty`, and records purchase. |
| **Archiving Purchase When Stock is Intact** | Reversing purchase of 5 items with 5 in stock | **[HANDLED]** | Verifies `itemAvailableQty - purQty >= 0`, deducts 5 from `itemTotalQty` and `itemAvailableQty`, and deletes purchase record. |
| **Archiving Purchase When Stock Checked Out** | Reversing purchase of 5 items when only 2 remain | **[HANDLED]** | Detects `itemAvailableQty - purQty < 0`. Throws Exception: *"Cannot reverse this purchase. The items have already been checked out. You must return them before archiving this purchase."*. |

---

### Subsystem 6: Borrower Directory

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Invalid Student ID Format** | `2023-0001` or `23-12-1234` | **[HANDLED]** | Enforces regex `validate_student_id` (`^\d{2}-\d-\d{5}$`). Rejects with *"Invalid Student ID format. Use ##-#-##### (e.g. 23-1-12345)."*. |
| **Malformed Contact Number** | `08123456789` or `12345` | **[HANDLED]** | `validate_ph_contact` normalizes formatting (+63, spaces, dashes) and enforces `^09\d{9}$`. Rejects invalid numbers with alert. |
| **Archiving Borrower with Active Loans** | Archiving student with unreturned equipment | **[HANDLED]** | Calculates unreturned equipment. If > 0, halts execution: *"Cannot archive borrower: they have X unreturned item(s)."*. |
| **Archiving Borrower with Cancelled Bookings** | Archiving student who had a cancelled booking | **[UNHANDLED]** | Query lacks status filter; cancelled bookings are miscounted as unreturned equipment, blocking archive (Identified as **Error 1**, targeted by **Revision 45**). |

---

### Subsystem 7: Staff User Management & Personal Profile

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Non-Admin Accessing User Management** | Committee staff accessing `tab-users` | **[HANDLED]** | `index.php` checks `$isAdmin` before rendering tab or modals. `actions/user_action.php` enforces `if (!$isAdmin)` guard and redirects immediately. |
| **Self-Archiving Prevention** | Admin attempting to archive their own account | **[HANDLED]** | `actions/user_action.php` evaluates `if ($id === $currentUser['userID'])`. Rejects with *"Cannot archive yourself."*. |
| **Duplicate Staff Email Registration** | Registering user with an existing email | **[HANDLED]** | Queries `SELECT userID FROM user WHERE userEmail = ? AND is_archived = 0`. If found for another user ID, rejects with duplicate email alert. |
| **Creating Staff User with Blank Password** | Submitting user modal with empty password | **[PARTIAL]** | HTML has `required`, but backend lacks `!empty($pass)` check on creation (Identified as **Error 6**, targeted by **Revision 50**). |
| **Updating Other User Profile via Profile Tab** | Staff altering POST `userID` to another user | **[HANDLED]** | `actions/profile_action.php` checks `if ($uid !== $currentUser['userID'] && !$isAdmin)`. Intercepts with *"Permission Denied: You can only update your own profile."*. |

---

### Subsystem 8: Reports, Filtering & Data Exports

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Reporting Period Across Boundaries** | Filtering across month/year boundaries | **[HANDLED]** | Handled via normalized `repStartDate` and `repEndDate` date strings bound via `$db->quote()`, correctly filtering SQL `BETWEEN` clauses. |
| **Zero Activity in Selected Period** | Filtering on date with zero transactions | **[HANDLED]** | Detects `count($repUnified) == 0`. Displays styled `.empty-state-box` with SVG illustration and text: *"No transactions recorded for this period"*. |
| **Print Report Action** | Clicking "Print Report" button | **[HANDLED]** | Triggers `window.print()`. CSS `@media print` cleanly hides sidebar, topbar, and filter controls (`.no-print`), and displays formal institutional letterhead (`.report-print-header.print-only`). |

---

### Subsystem 9: Printable Equipment Gate Pass (`print_receipt.php`)

| Interaction Scenario | Input / Action | System Handled? | How System Behaves & Function/File Responsible |
| :--- | :--- | :---: | :--- |
| **Unauthenticated Direct Access** | Navigating to `print_receipt.php` without login | **[HANDLED]** | Checks `if (!$currentUser) { die("Unauthorized."); }`, preventing unauthorized access to transaction receipts. |
| **Malformed `ids` URL Parameter** | `ids=invalid` or `ids=[]` | **[HANDLED]** | Evaluates `json_decode()`. If not an array or empty, script halts with `die("Invalid transaction data.");`. |
| **Non-Existent Transaction ID** | Valid array with non-existent ID `["TXN-999"]` | **[HANDLED]** | Query returns empty dataset, script halts cleanly with `die("Transactions not found.");`. |
| **Multiple Items on Single Receipt** | Single checkout containing multiple equipment items | **[HANDLED]** | Populates `$inClause` dynamically, aggregates transaction items in a table, computes `$totalAmount += $t['brwTransTotal']`, and displays combined totals. |
| **Physical Print Auto-Trigger** | Receipt window loads in browser | **[HANDLED]** | `<body onload="window.print()">` triggers system printer dialog with monospace ticket layout. |

---

## SECTION 5: IMPLEMENTED TARGETED REVISIONS & INTERNAL TEST VERIFICATION

*(All 10 targeted revisions from Batches 13 & 14 have been officially implemented and verified. This section explains what was changed in friendly, plain English so the entire project team can easily understand each fix, why it matters, and how the system behaves now).*

---

### 1. Fix for Archiving Borrowers who had Cancelled Reservations (Revision 45)
* **Why we implemented it:** Previously, if a student made an equipment reservation and later cancelled it, the system got confused. When an officer tried to archive that student at the end of the semester, the database calculation counted the cancelled item as "still missing," permanently locking the student profile and giving an error: *"Cannot archive borrower: they have 1 unreturned item(s)."*
* **What was revised:** In `actions/borrower_action.php` (Line 64), we added `AND b.brwTransStatus = 'Released'` to the unreturned equipment check.
* **Before:** Once a borrower had even a single cancelled booking, you could never archive them ever again.
* **After:** Cancelled bookings are completely ignored during archiving. You can now cleanly archive borrowers as long as they don't have physically released gear still sitting in their hands.

---

### 2. Guard to Stop Equipment Items from Being Archived with Active Future Bookings (Revision 46)
* **Why we implemented it:** If a student reserved 3 projectors for next Monday, an admin could still open the Inventory tab and click "Archive" on that projector today. The projector would disappear from active inventory, but the booking was still sitting in the ledger. When the student showed up on Monday, the equipment was basically a ghost.
* **What was revised:** In `actions/item_action.php` (Lines 91&ndash;97), we updated the check query to inspect both `Released` and `Reserved` statuses (`b.brwTransStatus IN ('Released', 'Reserved')`).
* **Before:** You could archive equipment that was promised to future borrowers, stranding their reservations.
* **After:** The system blocks you and displays an alert: *"Cannot archive item: X unit(s) are currently checked out or reserved. Complete or cancel them first."*

---

### 3. Outright Rejection and Alert for Conflicting Student ID (Revision 47)
* **Why we implemented it:** When a desk officer clicks *"+ Add New Borrower"* in the checkout modal and enters a Student ID that already belongs to another borrower (active or archived), allowing it would cause database collisions or risk hijacking historical borrowing records. A confirmation modal was initially considered, but it made the checkout modal script bloated (~130 extra lines) and complicated to maintain. Outright rejection keeps the code short, simple, and clean: if the ID already exists anywhere in the database, reject it immediately.
* **What was revised:**
  - Added a lightweight check endpoint `action === 'check_student_id'` in `actions/borrower_action.php`.
  - In `views/modals/modal_txn.php`, hooked `checkBorrowerStudentID()` to the Student ID blur event: if the ID exists (active or archived), it immediately displays a clear browser alert informing the officer that the ID is already taken, clears the input box, and refocuses it.
  - In `actions/txn_action.php`, added a server-side guard in `create_borrow`: if a new borrower is submitted with an existing active or archived Student ID, it halts immediately and redirects with an error alert advising the officer to select the borrower via the search bar.
* **Before:** You could enter duplicate or archived student IDs, risking silent record binding or script bloat from nested confirmation popups.
* **After:** Entering a conflicting Student ID immediately alerts the officer and resets the field. If submitted, the server outright rejects it with an error banner, protecting data integrity with minimal code footprint.

---

### 4. Manual Receipt Reprint Button & Pop-Up Blocker Link (Revision 48)
* **Why we implemented it:** When checking out items, the system tries to automatically open `print_receipt.php` in a popup window. Modern web browsers (Chrome, Edge, Brave) frequently block unprompted popups. If the popup was blocked, desk officers had zero way to reprint the gate pass from the transaction table!
* **What was revised:**
  - Added a manual **"Receipt"** button with a printer icon on every row in the *All* and *Released* subtabs in `views/pages/transactions.php`.
  - Added a direct fallback button inside the green success notification banner in `index.php`: *"Click here to print receipt if pop-up was blocked"*.
* **Before:** If the browser blocked the checkout popup, the receipt was lost forever unless you manually constructed URLs.
* **After:** Officers can reprint receipts on demand with one click directly from the transaction ledger, or click the direct link in the success banner.

---

### 5. Catching Backwards Dates in the Availability Engine (Revision 49)
* **Why we implemented it:** If someone checked item availability with backwards dates (like returning on Oct 15 but borrowing on Oct 20), the availability engine mathematically found 0 overlapping bookings and returned `success: true` (*"Available for Oct 20 to Oct 15!"*). Even though client-side date pickers have `min` attributes, having the backend function approve inverted dates was a logical hole.
* **What was revised:** In `config/helpers.php` inside `check_item_availability_for_dates()`, we added an upfront temporal guard: if `strtotime($endDate) < strtotime($startDate)`, immediately return `success: false` with *"Return date cannot be earlier than borrow date."*
* **Before:** The helper function mathematically approved backwards dates as "Available."
* **After:** The helper function strictly rejects inverted date ranges at the root level before running database queries.

---

### 6. Requiring Passwords for New Staff Accounts (Revision 50)
* **Why we implemented it:** When an admin added a new council staff member in the Users tab, the backend hashed whatever was in the password field even if it was completely blank! That meant an account could technically be saved with a blank password.
* **What was revised:** In `actions/user_action.php` (Line 53), we added backend validation: when creating a new user (`$id === 'NEW'`), if `empty($pass)`, immediately halt and redirect with: *"Error: Password is required when creating a new user account."*
* **Before:** New accounts could accidentally or intentionally be registered without a password.
* **After:** The server strictly demands a non-empty password for new users, while still allowing you to leave it blank when editing an existing user (to keep their current password).

---

### 7. Globally Unique Item Descriptions Across All Categories (Revision 51)
* **Why we implemented it:** You could create "Epson Projector" under Electronics, and also create "Epson Projector" under Office Supplies. But when recording purchases, the restocking query only checked description: `WHERE itemDesc = ? LIMIT 1`. It would arbitrarily add new stock to whichever record MySQL felt like returning first!
* **What was revised:** In `actions/item_action.php` (Lines 26 & 56), we updated the uniqueness checks to search across all active inventory items regardless of category (`WHERE itemDesc = ? AND is_archived = 0`).
* **Before:** Two items with identical descriptions could exist in different categories, breaking 1:1 purchase restocking.
* **After:** Every active inventory item must have a unique description, guaranteeing seamless, predictable restocking.

---

### 8. Fixing the Number Input Typing Glitch (Removing `direction: rtl`) (Revision 52)
* **Why we implemented it:** A previous CSS rule applied `direction: rtl !important;` to all number inputs to put spinner arrows on the left. But RTL makes browsers format text like Arabic or Hebrew! When typing decimal numbers (like rental fee rates `50.25`), the decimal point and numbers would jump around backwards, and cursor arrow keys moved in reverse.
* **What was revised:** In `assets/css/style.css` (Lines 1395&ndash;1400), we removed `direction: rtl` and `unicode-bidi: plaintext`, using clean `text-align: right !important;` and `font-variant-numeric: tabular-nums;`.
* **Before:** Typing decimal rental rates was glitchy, reversed arrow keys, and displaced decimal points.
* **After:** Numbers type smoothly from left to right, while staying neatly right-aligned with fixed tabular figure widths.

---

### 9. Payment Status Configuration (Revision 53 &mdash; Reverted per Targeted User Request)
* **Why it was reverted:** Payment status is not displayed on any transaction tables or report views, rendering an additional dropdown redundant on the modal form. Reverting this revision restored the clean, spacious 2-column layout for Borrow Date and Return By Date in `views/modals/modal_txn.php`, keeping the checkout form uncluttered.
* **What was revised:** In `views/modals/modal_txn.php`, removed the `<select name="brwTransPayStat">` dropdown and restored the clean `col-md-6 mb-3` layout for both date inputs. In `actions/txn_action.php`, restored the default fallback `$payStat = trim($_POST['brwTransPayStat'] ?? 'Free');`.
* **Before:** The modal squeezed borrow date, return date, and payment status onto a single 3-column row, even though payment status was never surfaced in any table.
* **After:** The modal retains its clean 2-column layout for borrow and return dates, and transactions default safely to `'Free'` in the database without extra clerk friction.

---

### 10. Replacing the "SU CCS LOGO" Text Badge with the Real College Emblem (Revision 54)
* **Why we implemented it:** On the split-screen sign-in page (`login.php`), the left branding panel had an unfinished white circular badge that literally just displayed text saying "SU CCS LOGO". The real high-resolution college logo image (`imgCCS.png`) was already sitting in `assets/images/` completely unused!
* **What was revised:** In `login.php` (Lines 29&ndash;32), we replaced `<span class="login-logo-text">SU CCS<br>LOGO</span>` with `<img src="assets/images/imgCCS.png" alt="SU CCS Logo" class="login-logo-image">`.
* **Before:** The login portal showed an obvious text placeholder badge.
* **After:** The login portal renders the authentic high-resolution college emblem alongside the Confederates Council seal, delivering polished institutional branding.

---

### Exhaustive Internal Test Results Log

All 29 PHP files in the repository, all 10 implemented/reverted targeted revisions, and 82 exhaustive edge case scenarios and simulations across all subsystems were tested using automated test suites and PHP syntax linting.

```text
================================================================================
AUTOMATED TEST VERIFICATION RUN: 2026-10-13 00:45:00
Target Environment: PHP 8.2.12 | MySQL localhost:3306 | confed_borrowing
================================================================================

[LINT CHECK - 29 PHP FILES]:
  - index.php ........................................................... PASSED (0 errors)
  - login.php ........................................................... PASSED (0 errors)
  - print_receipt.php ................................................... PASSED (0 errors)
  - actions/auth_action.php ............................................. PASSED (0 errors)
  - actions/borrower_action.php ......................................... PASSED (0 errors)
  - actions/item_action.php ............................................. PASSED (0 errors)
  - actions/profile_action.php .......................................... PASSED (0 errors)
  - actions/txn_action.php .............................................. PASSED (0 errors)
  - actions/user_action.php ............................................. PASSED (0 errors)
  - config/db.php ....................................................... PASSED (0 errors)
  - config/helpers.php .................................................. PASSED (0 errors)
  - config/session.php .................................................. PASSED (0 errors)
  - views/layout/footer.php ............................................. PASSED (0 errors)
  - views/layout/header.php ............................................. PASSED (0 errors)
  - views/layout/sidebar.php ............................................ PASSED (0 errors)
  - views/layout/topbar.php ............................................. PASSED (0 errors)
  - views/modals/modal_borrower.php ..................................... PASSED (0 errors)
  - views/modals/modal_category.php ..................................... PASSED (0 errors)
  - views/modals/modal_item.php ......................................... PASSED (0 errors)
  - views/modals/modal_purchase.php ..................................... PASSED (0 errors)
  - views/modals/modal_txn.php .......................................... PASSED (0 errors)
  - views/modals/modal_user.php ......................................... PASSED (0 errors)
  - views/pages/borrowers.php ........................................... PASSED (0 errors)
  - views/pages/inventory.php ........................................... PASSED (0 errors)
  - views/pages/profile.php ............................................. PASSED (0 errors)
  - views/pages/purchases.php ........................................... PASSED (0 errors)
  - views/pages/reports.php ............................................. PASSED (0 errors)
  - views/pages/transactions.php ........................................ PASSED (0 errors)
  - views/pages/users.php ............................................... PASSED (0 errors)

[LOGIC & INTEGRATION REVISION SUITE (test_all_revisions.php) - 21 TESTS]:
  [PASS] Rev 45: Cancelled transaction does not block borrower archiving
  [PASS] Rev 46: Active Reserved transaction blocks item archive
  [PASS] Rev 47: Lookup correctly identifies archived borrower
  [PASS] Rev 47: Lookup correctly identifies active borrower
  [PASS] Rev 47: Outright rejection triggers for archived student ID
  [PASS] Rev 47: Outright rejection triggers for active student ID
  [PASS] Rev 47: modal_txn.php has no bloated modalBorrowerConflict markup
  [PASS] Rev 47: modal_txn.php contains client-side checkBorrowerStudentID alert function
  [PASS] Rev 48: transactions.php contains manual Receipt reprint buttons
  [PASS] Rev 48: index.php contains direct pop-up blocked fallback link
  [PASS] Rev 49: Inverted dates return success = false (Temporal Guard)
  [PASS] Rev 49: Valid date range executes availability check
  [PASS] Rev 50: user_action.php enforces non-empty password on user creation
  [PASS] Rev 51: item_action.php enforces description uniqueness globally across inventory
  [PASS] Rev 52: style.css does not contain direction: rtl
  [PASS] Rev 52: style.css uses font-variant-numeric: tabular-nums and right alignment
  [PASS] Rev 53: modal_txn.php does not include unused Payment Status selector (Reverted)
  [PASS] Rev 53: modal_txn.php retains clean 2-column layout for borrow and return dates
  [PASS] Rev 54: login.php placeholder text removed
  [PASS] Rev 54: login.php loads imgCCS.png with login-logo-image class
  [PASS] Rev 54: assets/images/imgCCS.png file exists on disk

[EXHAUSTIVE SUBSYSTEM EDGE CASE & INVARIANT SUITE (exhaustive_edge_case_suite.php) - 62 TESTS]:
  -- Subsystem 1: Authentication & Authorization --
  [PASS] 1.1 Empty password fails password_verify
  [PASS] 1.2 Empty email lookup returns false
  [PASS] 1.3 Non-existent email lookup returns false
  [PASS] 1.4 Correct email with wrong password fails verification
  [PASS] 1.5 Soft-archived user filtered out by AND is_archived = 0
  [PASS] 1.6 Active user credentials verify successfully
  [PASS] 1.7 session.php intercepts unauthenticated requests
  [PASS] 1.8 user_action.php enforces !isAdmin check at top of script
  [PASS] 1.9 item_action.php enforces !isAdmin check at top of script
  [PASS] 1.10 txn_action.php enforces !isAdmin on purchase_item
  [PASS] 1.11 txn_action.php enforces !isAdmin on archive_purchase
  [PASS] 1.12 profile_action.php blocks non-admin modifying others' profiles

  -- Subsystem 2: Staff User Management --
  [PASS] 2.1 user_action.php requires non-empty password on creation
  [PASS] 2.2 validate_email_format rejects 'notanemail'
  [PASS] 2.3 validate_email_format rejects 'admin@'
  [PASS] 2.4 validate_email_format accepts 'officer@csc.edu.ph'
  [PASS] 2.5 Duplicate active email detected by query
  [PASS] 2.6 user_action.php prevents self-archiving
  [PASS] 2.7 validate_ph_contact accepts '09123456789'
  [PASS] 2.8 validate_ph_contact normalizes '+639123456789'
  [PASS] 2.9 validate_ph_contact normalizes '639123456789'
  [PASS] 2.10 validate_ph_contact normalizes '0912-345-6789'
  [PASS] 2.11 validate_ph_contact normalizes '(0912) 345 6789'
  [PASS] 2.12 validate_ph_contact rejects invalid '08123456789'
  [PASS] 2.13 validate_ph_contact rejects 10-digit '0912345678'
  [PASS] 2.14 validate_ph_contact rejects 12-digit '091234567890'

  -- Subsystem 3: Borrower Directory --
  [PASS] 3.1 validate_student_id accepts '23-1-12345'
  [PASS] 3.2 validate_student_id accepts '24-2-99999'
  [PASS] 3.3 validate_student_id rejects '2023-0001'
  [PASS] 3.4 validate_student_id rejects '23-12-12345'
  [PASS] 3.5 validate_student_id rejects '23-1-1234'
  [PASS] 3.6 validate_student_id rejects empty string
  [PASS] 3.7 borrower_action.php checks for duplicate student ID on save
  [PASS] 3.8 Active unreturned items (2 units) blocks borrower archive
  [PASS] 3.9 Cancelled reservation evaluates to 0 unreturned items
  [PASS] 3.10 Returned transaction evaluates to 0 unreturned items
  [PASS] 3.11 search_borrower filters is_archived = 0

  -- Subsystem 4: Inventory & Category Operations --
  [PASS] 4.1 item_action.php checks description uniqueness globally (Add)
  [PASS] 4.2 item_action.php checks description uniqueness globally (Edit)
  [PASS] 4.3 Negative rental fee is capped with max(0.0, ...)
  [PASS] 4.4 item_action.php checks qty < currentlyCheckedOut
  [PASS] 4.5 item_action.php checks both Released and Reserved for archiving

  -- Subsystem 5: Restocking Purchases --
  [PASS] 5.1 purchase_item matches catalog items by description
  [PASS] 5.2 archive_purchase blocks reversal if available stock is insufficient

  -- Subsystem 6: Borrowing & Reservation Engine --
  [PASS] 6.1 check_item_availability_for_dates rejects inverted dates
  [PASS] 6.2 txn_action.php contains inverted date check
  [PASS] 6.3 txn_action.php evaluates isReservation = (borrowOnDate > today)
  [PASS] 6.4 Availability engine rejects request exceeding total stock
  [PASS] 6.5 Overlapping reservation blocked (5 total - 4 booked = 1 avail, req 2)
  [PASS] 6.6 Non-overlapping future reservation approved (5 available)
  [PASS] 6.7 Check query identifies active conflicting student ID
  [PASS] 6.8 Check query identifies archived conflicting student ID
  [PASS] 6.9 Payment status safely defaults to Free

  -- Subsystem 7: Returns Processing --
  [PASS] 7.1 txn_action.php prevents returning more than borrowed
  [PASS] 7.2 txn_action.php prevents return date earlier than checkout date
  [PASS] 7.3 search_active_borrows filters Released and (itemQty - returned_qty) > 0

  -- Subsystem 8: Receipts & Printing --
  [PASS] 8.1 print_receipt.php requires authenticated session
  [PASS] 8.2 print_receipt.php validates json IDs parameter
  [PASS] 8.3 print_receipt.php escapes all output with htmlspecialchars

  -- Subsystem 9: Helper Utilities & ID Generation --
  [PASS] 9.1 generate_id returns format ITM-xxx
  [PASS] 9.2 e() escapes <script>
  [PASS] 9.3 e() handles null safely

[ADVANCED MULTI-STEP SIMULATION SUITE (advanced_simulation_tests.php) - 13 TESTS]:
  -- Simulation 1: Multi-Step Partial Returns & Total Restoration --
  [PASS] Sim 1.1: Borrow 5 units reduces available stock from 10 to 5
  [PASS] Sim 1.2: Partial return of 2 units leaves status Released
  [PASS] Sim 1.3: Second partial return sums to 4 units
  [PASS] Sim 1.4: Final return marks transaction Returned and restores stock to 10

  -- Simulation 2: Purchase Restock, Checkout, & Reversal Guard --
  [PASS] Sim 2.1: Purchase increments available stock from 0 to 5
  [PASS] Sim 2.2: Cannot reverse purchase when checked-out items leave insufficient available stock (2 - 5 < 0)
  [PASS] Sim 2.3: Reversal allowed after return restores stock (5 - 5 >= 0)

  -- Simulation 3: Complex Overlapping Reservation Math --
  [PASS] Sim 3.1: Overlapping reservation B (6+5=11 > 10) is rejected
  [PASS] Sim 3.2: Overlapping reservation C (6+4=10 <= 10) is approved
  [PASS] Sim 3.3: Overlapping reservation D (4+6=10 <= 10) is approved after A ends
  [PASS] Sim 3.4: Overlapping reservation E (4+7=11 > 10) is rejected

  -- Simulation 4: Natural ID Auto-Increment Rollover --
  [PASS] Sim 4.1: Natural rollover from ITM-009 produces ITM-010
  [PASS] Sim 4.2: Natural rollover from ITM-099 produces ITM-100

================================================================================
CONSOLIDATED SUMMARY: 96 OF 96 TESTS PASSED | 0 FAILURES | ZERO REGRESSIONS
================================================================================
```

---

## SECTION 6: ARCHITECTURAL INTEGRITY & RUBRIC COMPLIANCE VERIFICATION

| Course Rubric / Technical Specification | Verification Result | Implementation Details in Current Active Code |
| :--- | :---: | :--- |
| **Two (2) Types of Users with Login** | **PASSED** | Council (Admin) and Committee (Staff) roles enforced via Bcrypt password verification in `actions/auth_action.php` and role guards in `config/session.php`. Zero public catalog access. |
| **Complete CRUD Across All Entities** | **PASSED** | Full Create, Read, Update, and Soft-Delete operations implemented for Items, Borrowers, Transactions, and Users with referential integrity protection. |
| **Resource Scheduling & Date Overlap Defense** | **PASSED** | Algorithmic peak booked calculation in `check_item_availability_for_dates()` prevents overbooking across overlapping reservation windows. |
| **Relational Cross-Tab Highlighting** | **PASSED** | Deep-link helper functions (`switchTabAndHighlight`, `openSubtabAndHighlight`) allow officers to trace transactions to borrower profiles and inventory items with animated row focus. |
| **Code Complexity Appropriate for University Level** | **PASSED** | Structured procedural PHP utilizing native PDO, prepared statements, and clear procedural functions without excessive object-oriented abstractions or unnecessary external frameworks. |
| **Documentation & Educational Maintainability** | **PASSED** | Descriptive comments, clean separation of concerns, and full architectural documentation in `md files for our sanity/`. |
