# Confederates Student Council (CSC) - Borrowing System

**Project Context & Foundational Documentation (PRD Format)**

## 1. Project Overview

The CSC Borrowing System is a web-based internal inventory and transaction management application designed for the student council. It allows council officers to manage the checkout, return, and restocking of council-owned equipment (e.g., speakers, sports gear, tables) by students and organizations.

The system utilizes a modular, procedural PHP architecture mapped to a MySQL database, featuring a clean, responsive Bootstrap 5 UI, AJAX-powered search functionalities, and role-based access control.

## 2. Current Features & Capabilities

### Authentication & User Roles
* **Role-Based Access Control:** Two distinct roles exist:
  * **Council (Admin):** Full CRUD access to all features, including adding/editing items, logging purchases, and managing user accounts.
  * **Committee (Staff):** Operational access focused on processing borrows, processing returns, and viewing reports.
* **Secure Authentication:** Passwords are encrypted using `bcrypt`.

### Inventory Management
* **Item Directory:** Displays all active inventory items, categorized (Audio & Visual, Furniture, etc.).
* **Stock Tracking:** Tracks `itemTotalQty` (total owned) and `itemAvailableQty` (currently in storage).
* **Restocking (Purchases):** Admins can log purchases using OR (Official Receipt) numbers, which dynamically increments the total and available stock of an item.

### Borrower Management
* **Borrower Directory:** Maintains a list of students and organizations with their contact details, college, and department.
* **Archiving:** Soft-deletion (`is_archived` flag) prevents accidental data loss while hiding obsolete records from the active view.

### Transaction Processing (The Core Loop)
* **Desk Checkout (Borrow):** Staff can process out-going equipment. The system validates stock availability, deducts from the available quantity, and generates a composite transaction record.
* **AJAX Search:** Staff can instantly search for a borrower by Student ID or Name via live dropdowns without reloading the page.
* **Process Return:** Staff can search for an active transaction, confirm the returned quantity, and the system restores the available stock while logging the return date.
* **Gate Pass Generation:** Generates a printable receipt (`print_receipt.php`) summarizing the items, borrower details, and expected fees upon successful checkout.

### Reporting
* **Date-Filtered Reports:** Generates snapshots of transactions between specific Start and End dates.
* **Categorized Views:** Sorts data into Unified View, Borrows, Returns, Purchases, and Inventory state.
* **Printable Output:** A print-ready layout that hides sidebars and navigation for physical filing.

## 3. Codebase File Directory & Logic Map

### Root Files
* **`index.php`**: The main application controller and view assembler.
* **`login.php`**: The public-facing authentication portal.
* **`print_receipt.php`**: Generates the official printable gate pass for borrowers.
* **`receipt.php`**: (Legacy) Defunct alternate version of the receipt.

### `actions/` (Backend Logic Controllers)
* **`auth_action.php`**: Handles login/logout credential checks.
* **`borrower_action.php`**: Processes adding, updating, archiving, and AJAX searching borrowers.
* **`item_action.php`**: Processes adding, updating, and archiving items (Admin only).
* **`profile_action.php`**: Updates logged-in user details.
* **`purchase_action.php`**: Logs restocks and increments item quantities.
* **`txn_action.php`**: Core logic for checking out items, processing returns, and fetching active borrows.
* **`user_action.php`**: Processes adding, editing, and archiving staff (Admin only).

### `config/` (Configuration & Utilities)
* **`db.php`**: Establishes PDO database connection (MySQL/SQLite).
* **`helpers.php`**: Contains `generate_id()` for standard ID creation.
* **`session.php`**: Starts session and checks authentication.

### `views/` (UI Components)
* **`layout/`**: Scaffolding (`header.php`, `topbar.php`, `sidebar.php`, `footer.php`).
* **`pages/`**: Dashboard tabs (`transactions.php`, `inventory.php`, `reports.php`, etc.).
* **`modals/`**: Hidden forms (`modal_txn.php`, `modal_item.php`, etc.).

### `assets/` (Static)
* **`css/style.css`**: UI theme and styling.
* **`js/app.js`**: Frontend JS for AJAX requests, DOM manipulation, and tab toggling.

---

## 4. Unifying Scenario & Exhaustive Process Mapping

To thoroughly map the codebase, assume a unified human-interaction scenario: **A Council Officer logs in, sets up a new projector, processes a checkout for a student, prints a receipt, and handles a subsequent return.**

### Phase 1: Authentication & Initialization
*   **Action:** The Officer attempts to open the dashboard (`index.php`) without logging in.
    *   *Backend Check:* `index.php` loads `config/session.php`. Inside `session.php` (Line ~9), `!$currentUser` evaluates to true. 
    *   *System Response:* PHP executes `header('Location: login.php')` and halts.
*   **Action:** The Officer is redirected to `login.php`, enters invalid credentials, and submits.
    *   *Frontend Load:* `login.php` renders the split-screen HTML with canvas animation.
    *   *Backend Routing:* Form `POST` sends data to `actions/auth_action.php` (Line ~6).
    *   *Invalid Check:* The SQL query fails to match, or `password_verify()` returns false. 
    *   *System Response:* `auth_action.php` (Line ~17) sets `$_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email or password.']` and redirects back to `login.php`.
    *   *Frontend Display:* `login.php` reads `$_SESSION['alert']`, displays the red Bootstrap danger banner, and unsets the session alert.
*   **Action:** The Officer enters valid credentials (`admin@csc.edu.ph` / `admin123`) and submits.
    *   *Valid Check:* `actions/auth_action.php` verifies the hash. Sets `$_SESSION['userID']`, `$_SESSION['role']`, etc.
    *   *System Response:* Redirects to `index.php`.
    *   *Dashboard Assembly:* `index.php` (Lines 1-45) connects to `db.php`, queries DB for `$items`, `$borrowers`, and `$unifiedTransactions`. It then stitches the DOM using `require_once` for all `views/layout/` and `views/pages/` components.

### Phase 2: Setup (Adding a New Item)
*   **Action:** The Officer clicks "Add Item" on the Inventory tab.
    *   *Frontend Response:* Bootstrap's JS intercepts `data-bs-target="#modalItem"` and unhides the modal rendered via `views/modals/modal_item.php`.
*   **Action:** The Officer fills out the projector details and clicks "Save".
    *   *Backend Routing:* `POST` request hits `actions/item_action.php` (Line ~6).
    *   *Logic Check:* Validates `$isAdmin`. True. Checks if `itemID` is 'NEW'. True.
    *   *System Response:* `helpers.php`'s `generate_id()` function assigns a new ID (e.g., `ITM-005`). An `INSERT` query logs the projector with default 0 quantities. `$_SESSION['alert']` and `$_SESSION['active_tab'] = 'tab-inventory'` are set. Redirects to `index.php`.
    *   *Frontend Refresh:* `index.php` loads, reads `$activeTab`, and sets the Inventory tab to `show active`.

### Phase 3: The Desk Checkout (Borrowing)
*   **Action:** A student arrives. The Officer clicks "New Transaction", opening `views/modals/modal_txn.php`. They type "2024-0" into `#borrowerSearch`.
    *   *Frontend Response:* `assets/js/app.js` (Line ~4) registers the `input` event. After a 300ms debounce, it executes `$.post('actions/borrower_action.php')`.
    *   *Backend Response:* `borrower_action.php` executes a `LIKE` SQL query returning matched students as JSON.
    *   *Frontend Render:* `app.js` generates a dropdown list. The Officer selects the student, triggering `selectBorrower()` which autofills the readonly inputs.
*   **Action:** The Officer adds a row, selects the Projector, types `10` for quantity (Invalid interaction: Only 4 are available), and submits.
    *   *Backend Routing:* `POST` to `actions/txn_action.php` (Line ~47).
    *   *Invalid Check:* Inside the `for` loop, SQL checks `$item['itemAvailableQty'] < $qty` (4 < 10). Condition is true.
    *   *System Response:* Throws an Exception. The `catch` block executes `$db->rollBack()`. Sets `danger` alert. Redirects to `index.php`. No data is saved.
*   **Action:** The Officer corrects the quantity to `1` and submits (Valid).
    *   *Backend Routing:* `POST` to `actions/txn_action.php`.
    *   *Valid Check:* Stock is verified. A loop iterates through requested items.
    *   *System Response:* 
        1. `generate_id()` assigns `TXN-001`.
        2. `INSERT` into `borrow_transaction` with status 'Released'.
        3. `UPDATE item SET itemAvailableQty = itemAvailableQty - 1`.
        4. Array of `$transIDs` is stored in `$_SESSION['print_receipt']`.
        5. `$db->commit()` executes. Redirects to `index.php`.
*   **Action:** Automated Receipt Printing.
    *   *Frontend Load:* `index.php` (Line ~115) detects `$printReceipt`. It injects a `<script>` block that fires `window.open('print_receipt.php?ids=[...]')`.
    *   *Print Layout:* `print_receipt.php` intercepts the request, runs a `JOIN` query to gather borrower and item names, and outputs a stripped-down, print-media CSS HTML page that automatically calls `onload="window.print()"`.

### Phase 4: Equipment Return
*   **Action:** Two days later, the student returns the projector. The Officer opens the "Process Return" sub-tab in `#modalTransaction` and searches the Student ID.
    *   *Frontend Response:* `app.js` (Line ~42) triggers `selectRetBorrower()`, which fires an AJAX POST to `actions/txn_action.php?action=search_active_borrows`.
    *   *Backend Check:* Queries `borrow_transaction` where `(brwTransItemQty - returned_qty) > 0`. Returns JSON.
    *   *Frontend Render:* Populates the "Item Borrowed" `<select>` dropdown with active TXN IDs.
*   **Action:** The Officer selects the active transaction and submits.
    *   *Backend Routing:* `POST` to `actions/txn_action.php` (Line ~96 `create_return`).
    *   *System Response:*
        1. `generate_id()` creates `RET-001`.
        2. `INSERT` into `return_transaction` capturing the returned quantity and date.
        3. `UPDATE item SET itemAvailableQty = itemAvailableQty + 1` (Restores stock).
        4. `UPDATE borrow_transaction SET brwTransStatus = 'Returned'`.
        5. Sets `success` alert and redirects to `index.php` on the `tab-transactions`.

### Phase 5: Report Generation
*   **Action:** The Officer goes to the Reports tab (`views/pages/reports.php`) and changes the Start Date input.
    *   *Frontend Response:* The HTML `onchange="this.form.submit()"` triggers a standard `GET` request.
    *   *Backend Routing:* `index.php` (Line ~48) detects `isset($_GET['start_date'])`.
    *   *System Response:* Evaluates `$isReportActive = true`. Appends `AND b.brwTransDate BETWEEN $start AND $end` to all SQL queries loading the page state. 
    *   *Frontend Display:* The page loads with `$activeTab` forced to `tab-reports` and the data tables immediately reflect the narrowed timeframe. The Officer clicks "Print Report" to output the data.