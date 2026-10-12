# Confederates Student Council (CSC) &mdash; Council Operations Architectural Guide

**Target File:** `md files for our sanity/council_operations_guide.md`  
**System Name:** Confederates Student Council (CSC) Resource Management & Borrowing System  
**Document Classification:** Operations, Code Architecture, and Inter-File Interaction Blueprint  
**Audience:** Council Administrators, Developers, System Auditors, and Committee Officers  

---

## Table of Contents

1. [Architectural Overview & Clerk Terminal Paradigm](#1-architectural-overview--clerk-terminal-paradigm)
2. [Council Administrator Role & Privileges](#2-council-administrator-role--privileges)
3. [Master Operations Directory](#3-master-operations-directory)
4. [Operation 1: Transactions (`#tab-transactions`)](#4-operation-1-transactions-tab-transactions)
   - 4.1. Process Borrow (Immediate Checkout)
   - 4.2. Process Reservation (Future Booking)
   - 4.3. Release Reservation
   - 4.4. Cancel Reservation
   - 4.5. Process Return (Full & Partial Returns)
   - 4.6. Print Receipt / Gate Pass
   - 4.7. Sub-Tabs & Instant Search Filtering
5. [Operation 2: Purchases (`#tab-purchases`)](#5-operation-2-purchases-tab-purchases)
   - 5.1. Log Purchase & Restock
   - 5.2. Category Auto-Fill & Locked Classification
   - 5.3. Dynamic Category Auto-Registration
   - 5.4. Purchase Archiving & Deduct Stock Guard
6. [Operation 3: Items / Inventory (`#tab-inventory`)](#6-operation-3-items--inventory-tab-inventory)
   - 6.1. Add New Equipment Item
   - 6.2. Edit Item & In-Flight Checked-Out Stock Protection
   - 6.3. Cross-Category Description Uniqueness
   - 6.4. Manage Categories
   - 6.5. Archive Item Guard
7. [Operation 4: Borrower Management (`#tab-borrowers`)](#7-operation-4-borrower-management-tab-borrowers)
   - 7.1. Add Borrower Profile & Validation Guards
   - 7.2. Edit Borrower Profile
   - 7.3. Live Autocomplete & Student ID Duplicate Checks
   - 7.4. Archive Borrower Guard (Unreturned Equipment Protection)
8. [Operation 5: User Management (`#tab-users`) [Council Exclusive]](#8-operation-5-user-management-tab-users-council-exclusive)
   - 8.1. Create Officer Account & Password Hash Enforcement
   - 8.2. Edit Officer & Role Escalation / Demotion
   - 8.3. Self-Archive Prevention Guard
   - 8.4. Role Pill Filter & One-Click Gmail Composition
9. [Operation 6: Reports (`#tab-reports`)](#9-operation-6-reports-tab-reports)
   - 9.1. Temporal Period Filtering (Day, Week, Month, Year, Custom)
   - 9.2. Unified vs. Sub-Report Views (Borrows, Returns, Purchases, Items)
   - 9.3. Printable Layout & Official CSC Letterhead Rendering
10. [Operation 7: Profile Management (`#tab-profile`)](#10-operation-7-profile-management-tab-profile)
    - 10.1. Self-Service Account Details Update
    - 10.2. Optional Password Rotation
    - 10.3. Live Session Memory Synchronization
11. [Operation 8: Session Teardown / Sign Out](#11-operation-8-session-teardown--sign-out)
12. [Master Operations Matrix: Code, Actions, Modals & Database Tables](#12-master-operations-matrix-code-actions-modals--database-tables)

---

## 1. Architectural Overview & Clerk Terminal Paradigm

The **Confederates Student Council (CSC) Resource Management System** is built on a **Clerk Terminal Architecture**. Rather than students registering individual online accounts, physical equipment lending occurs at the **CSC Property Desk**. 

```
+-----------------------------------------------------------------------------------------+
|                                    CLERK TERMINAL                                       |
|                                                                                         |
|   [Borrower / Student]  ----(Presents ID / Requests Gear)---->  [Council Administrator]  |
|                                                                           |             |
|                                                                 (Operates Browser UI)   |
|                                                                           |             |
|                                                                           v             |
|                                                                    [index.php Router]   |
+---------------------------------------------------------------------------+-------------+
                                                                            |
                   +--------------------------------------------------------+
                   |
     +-------------v-------------+    +-----------------------+    +----------------------+
     | Master Layout & Views     |    | Actions Controllers   |    | Core Helpers & DB    |
     |                           |    |                       |    |                      |
     | - sidebar.php             |    | - txn_action.php      |    | - db.php (PDO)       |
     | - topbar.php              |    | - item_action.php     |    | - session.php (Auth) |
     | - header.php / footer.php |    | - borrower_action.php |    | - helpers.php        |
     | - views/pages/*.php       |    | - user_action.php     |    |                      |
     | - views/modals/*.php      |    | - profile_action.php  |    |                      |
     +---------------------------+    +-----------------------+    +----------------------+
```

### Key Architectural Pillars:
1. **Single-Page Application (SPA) feel with Tabbed Hydration (`index.php`):**
   All 7 operation views are loaded simultaneously inside `views/pages/` within Bootstrap 5 `.tab-pane` containers.
2. **Session Persistence & Server Override:**
   Whenever an action processes on the backend, it redirects with `redirect_with_alert($tab, $type, $message)`. The target `$tab` (e.g. `'tab-transactions'`) is placed in `$_SESSION['active_tab']`, injected as `data-server-tab` into `<main class="app-main">`, and restored by `assets/js/app.js` so the user never loses their place.
3. **Prepared Statements & Relational Data Integrity:**
   All mutations pass through `actions/*_action.php` using PDO prepared statements (`$db->prepare(...)`), wrapping complex operations in database transactions (`beginTransaction()`, `commit()`, `rollBack()`).

---

## 2. Council Administrator Role & Privileges

The system has two roles defined in the `user` table:
- **`Council`** (Administrator) &mdash; Has full rights over all operations, including system user management, purchasing, inventory adjustments, and category configurations.
- **`Committee`** (Staff) &mdash; Can checkout, process returns, view reports, and register borrowers, but cannot modify users or archive core inventory.

### How Role Checking is Enforced:
1. In `config/session.php`:
   ```php
   $currentUser = isset($_SESSION['userID']) ? $_SESSION : null;
   $isAdmin = ($currentUser && $currentUser['role'] === 'Council');
   ```
2. In UI files (`views/layout/sidebar.php`, `views/pages/*.php`):
   Protected navigation items (like **User Management**) and administrative buttons (like **Log Purchase**, **Add Item**, **Archive Item**) are wrapped in:
   ```php
   <?php if ($isAdmin): ?> ... <?php endif; ?>
   ```
3. In Controller files (`actions/user_action.php`, `actions/item_action.php`, `actions/txn_action.php`):
   ```php
   if (!$isAdmin) {
       redirect_with_alert('tab-transactions', 'danger', 'Access denied. Administrator privileges required.');
   }
   ```

---

## 3. Master Operations Directory

As rendered on the sidebar under **Council Administrator (COUNCIL)**:

| # | Council Operation | Sidebar Target | Associated Page View | Associated Modal(s) | Primary Backend Action |
|---|-------------------|----------------|----------------------|---------------------|------------------------|
| **1** | **Transactions** | `#tab-transactions` | [`views/pages/transactions.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/transactions.php) | [`modal_txn.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_txn.php) | [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php) |
| **2** | **Purchases** | `#tab-purchases` | [`views/pages/purchases.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/purchases.php) | [`modal_purchase.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_purchase.php) | [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php) |
| **3** | **Items** | `#tab-inventory` | [`views/pages/inventory.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/inventory.php) | [`modal_item.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_item.php), [`modal_category.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_category.php) | [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php) |
| **4** | **Borrower Management** | `#tab-borrowers` | [`views/pages/borrowers.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/borrowers.php) | [`modal_borrower.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_borrower.php) | [`actions/borrower_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/borrower_action.php) |
| **5** | **User Management** | `#tab-users` | [`views/pages/users.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/users.php) | [`modal_user.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_user.php) | [`actions/user_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/user_action.php) |
| **6** | **Reports** | `#tab-reports` | [`views/pages/reports.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/reports.php) | *None (Direct Print & Filter)* | [`index.php`](file:///c:/xampp/htdocs/CBSFork/index.php) |
| **7** | **Profile Management** | `#tab-profile` | [`views/pages/profile.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/profile.php) | *Inline Form* | [`actions/profile_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/profile_action.php) |
| **8** | **Sign Out** | *Form Submit* | *None* | *None* | [`actions/auth_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/auth_action.php) |

---

## 4. Operation 1: Transactions (`#tab-transactions`)

Transactions represent the core operational purpose of the Council: checking out equipment, managing future reservations, receiving returned items, and tracking status across time.

```
                            TRANSACTION LIFECYCLE
                            
           +---------------------------------------------+
           | User initiates Borrow in #modalTransaction  |
           +----------------------+----------------------+
                                  |
                   Is Borrow Date > Today?
                     /                 \
                  YES                   NO
                  /                       \
        +--------v---------+      +--------v---------+
        | Status: Reserved |      | Status: Released |
        | Stock unchanged  |      | Stock decremented|
        +--------+---------+      +--------+---------+
                 |                         |
       +---------+---------+               |
       |                   |               |
       v                   v               v
  [Release Now]      [Cancel Txn]    [Borrower returns items]
       |                   |               |
       v                   v               v
  Status: Released   Status: Cancelled  [create_return]
  Stock decremented  Stock untouched       |
       |                                   v
       +----------------------------> Status: Returned
                                      Stock incremented
```

### 4.1. Process Borrow (Immediate Checkout)
* **What it does:** Allows the administrator to check out one or multiple items to an existing or newly registered borrower. If the `brwTransBorrowOnDate` is today's date (or earlier), the item is considered physically taken.
* **How it works:**
  1. Officer clicks **New Transaction** button, opening `#modalTransaction`.
  2. In the **Process Borrow** subtab, officer searches an existing borrower by Student ID or clicks **+ Add New Borrower** to enter them directly into the form.
  3. Officer selects items from dynamic rows, enters quantities, and specifies borrow and return dates.
  4. Real-time AJAX checks availability for requested date ranges (`check_item_availability_for_dates`).
  5. On submit, `actions/txn_action.php` validates the payload, creates an auto-incrementing `TXN-###` ID using `generate_id()`, inserts the record into `borrow_transaction`, decrements `itemAvailableQty` in the `item` table, and sets `$_SESSION['print_receipt']` to trigger the receipt pop-up.
* **Key Code Responsible:**
  - `check_item_availability_for_dates()` in [`config/helpers.php`](file:///c:/xampp/htdocs/CBSFork/config/helpers.php#L92-L221)
  - `action === 'create_borrow'` in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L121-L235)
  - JavaScript functions `checkRowAvailability()`, `handleBorrowSubmit()`, and `setupBorrowerSearch()` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js) and [`views/modals/modal_txn.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_txn.php).
* **Files at Play:**
  - [`views/pages/transactions.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/transactions.php) (UI display)
  - [`views/modals/modal_txn.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_txn.php) (Modal form & dynamic rows)
  - [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php) (Backend transaction processing)
  - [`config/helpers.php`](file:///c:/xampp/htdocs/CBSFork/config/helpers.php) (Date conflict & ID generator logic)
  - [`print_receipt.php`](file:///c:/xampp/htdocs/CBSFork/print_receipt.php) (Receipt rendering)

### 4.2. Process Reservation (Future Booking)
* **What it does:** Schedules an equipment reservation for future dates without immediately reducing on-hand shelf stock.
* **How it works:**
  - If `borrowOnDate > date('Y-m-d')`, `txn_action.php` automatically tags the transaction with `brwTransStatus = 'Reserved'`.
  - Shelf stock (`itemAvailableQty`) is **not** decremented immediately because the items are still physically on the shelf today.
  - However, future overlapping requests run through `check_item_availability_for_dates()`, which sums all active `Released` and `Reserved` transactions overlapping the dates to ensure the council never overbooks equipment.
* **Key Code Responsible:**
  - Line 190 in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L190-L192):
    ```php
    $isReservation = ($borrowOnDate > $today);
    $status = $isReservation ? 'Reserved' : 'Released';
    ```
  - Milestone-date conflict evaluation in [`config/helpers.php`](file:///c:/xampp/htdocs/CBSFork/config/helpers.php#L179-L195).

### 4.3. Release Reservation
* **What it does:** Converts a `Reserved` status transaction into `Released` when the borrower arrives on the scheduled day to pick up the gear.
* **How it works:**
  - The administrator locates the reserved transaction in the **Reserved** tab and clicks **Release**.
  - A POST request is sent to `actions/txn_action.php` with `action=release_reservation`.
  - The backend verifies the reservation, changes status to `Released`, and executes:
    ```sql
    UPDATE item SET itemAvailableQty = GREATEST(0, itemAvailableQty - ?) WHERE itemID = ?
    ```
* **Key Code Responsible:**
  - Lines 43&ndash;62 in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L43-L62).

### 4.4. Cancel Reservation
* **What it does:** Cancels a future reservation if the requesting student or student organization cancels their event.
* **How it works:**
  - In the **Reserved** tab, administrator clicks **Cancel**.
  - Form posts to `action=cancel_reservation`.
  - Backend updates `borrow_transaction` status to `'Cancelled'`. No inventory adjustment is needed because the reserved items were never physically taken off the shelf.
* **Key Code Responsible:**
  - Lines 63&ndash;67 in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L63-L67).

### 4.5. Process Return (Full & Partial Returns)
* **What it does:** Records items brought back to the council property desk. Supports partial returns (e.g., returning 2 out of 5 borrowed chairs).
* **How it works:**
  1. Administrator opens `#modalTransaction` and switches to the **Process Return** tab.
  2. Administrator searches for the borrower by Student ID.
  3. An AJAX request calls `actions/txn_action.php` with `action=search_active_borrows`, which calculates remaining unreturned quantities:
     ```sql
     (b.brwTransItemQty - COALESCE(SUM(r.brwTransQty), 0))
     ```
  4. Dynamic dropdown is populated with the borrower's active items.
  5. When administrator selects an item, the borrow date is locked and the maximum returnable quantity is enforced.
  6. On submit, `actions/txn_action.php` creates a new `RET-###` record in `return_transaction`, increments `itemAvailableQty` in the `item` table, and if total returned equals the borrowed quantity, marks the borrow transaction as `'Returned'`.
* **Key Code Responsible:**
  - `action === 'search_active_borrows'` in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L9-L23)
  - `action === 'create_return'` in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L236-L297)
  - Client-side handler `selectRetBorrower()` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L248-L274).

### 4.6. Print Receipt / Gate Pass
* **What it does:** Renders a clean, formatted receipt / gate pass for security checkpoint clearance at campus gates.
* **How it works:**
  - After a checkout or release, `$_SESSION['print_receipt']` stores transaction IDs.
  - An inline script at the bottom of [`index.php`](file:///c:/xampp/htdocs/CBSFork/index.php#L239-L247) automatically triggers `window.open('print_receipt.php?ids=...', 'ReceiptWindow')`.
  - Alternatively, each transaction row has a manual **Receipt** button.
  - [`print_receipt.php`](file:///c:/xampp/htdocs/CBSFork/print_receipt.php) loads borrower details, item descriptions, rental fees, and staff officer name, and runs `window.print()` automatically upon load.

### 4.7. Sub-Tabs & Instant Search Filtering
* **Sub-Tabs available:**
  - **All:** Combined view (`$unifiedTransactions`) joining borrows and returns.
  - **Reserved:** Displays bookings awaiting pickup.
  - **Released:** Displays equipment currently with borrowers.
  - **Returned:** Historical log of return transactions.
  - **Cancelled:** Voided reservations.
* **Sidebar Sub-menu Linking:** Clicking "Reserved", "Released", "Returns", or "Cancelled" in the left sidebar calls `openSubtab('#tab-transactions', '#txn-...')` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L289-L301), activating both the main tab and the nested tab smoothly.

---

## 5. Operation 2: Purchases (`#tab-purchases`)

The **Purchases** operation acts as the council's procurement ledger and inventory restocking hub.

```
                              PURCHASE / RESTOCK FLOW
                              
                 +-----------------------------------------------+
                 |  Council Admin logs restock in #modalPurchase |
                 +-----------------------+-----------------------+
                                         |
                       Does Item Name exist in Inventory?
                                 /               \
                              YES                 NO
                              /                     \
                   +---------v----------+     +------v------------+
                   | Category locked to |     | Admin selects or  |
                   | existing item cat  |     | inputs category   |
                   +---------+----------+     +------+------------+
                             |                       |
                             v                       v
                   +-----------------------------------------------+
                   | INSERT INTO purchase_transaction (PUR-###)    |
                   | UPDATE item: +itemTotalQty, +itemAvailableQty |
                   | INSERT IGNORE INTO category                   |
                   +-----------------------------------------------+
```

### 5.1. Log Purchase & Restock
* **What it does:** Allows administrators to enter newly purchased inventory items along with the Official Receipt (OR) Number.
* **How it works:**
  1. Admin opens `#modalPurchase`.
  2. Admin enters `purORNo` and purchase date.
  3. Dynamic purchase rows allow adding multiple items simultaneously via `#add-pur-item-btn`.
  4. On submit, `actions/txn_action.php` executes `action === 'purchase_item'`.
  5. The backend checks if the item description already exists in `item` (case-insensitive):
     - **If exists:** Updates existing item quantities (`itemTotalQty += qty`, `itemAvailableQty += qty`).
     - **If new:** Generates a new `ITM-###` ID and creates the item record.
  6. A `PUR-###` record is inserted into `purchase_transaction` linked to the current user's ID.
* **Key Code Responsible:**
  - Lines 68&ndash;120 in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L68-L120).

### 5.2. Category Auto-Fill & Locked Classification
* **What it does:** Prevents catalog fragmentation by ensuring that restocking an existing item cannot accidentally assign it to a conflicting category.
* **How it works:**
  - In `#modalPurchase`, the item description input is linked to a `<datalist id="purchaseItemsDatalist">`.
  - When the administrator types an item name matching an existing item, `autoFillCategory(this)` fires.
  - The category dropdown automatically selects the existing item's category, greys out (`#e9ecef`), disables pointer events, and shows a badge: *"Category locked to item"*.
* **Key Code Responsible:**
  - Lines 59&ndash;89 in [`views/modals/modal_purchase.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_purchase.php#L59-L89).

### 5.3. Dynamic Category Auto-Registration
* **What it does:** Ensures any new category entered during a purchase is instantly saved in the `category` lookup table.
* **How it works:**
  - Before inserting an item, `txn_action.php` runs:
    ```sql
    INSERT IGNORE INTO category (categoryName) VALUES (?)
    ```
  - This prevents SQL constraint crashes while expanding category choices.

### 5.4. Purchase Archiving & Deduct Stock Guard
* **What it does:** Allows Council Admins to delete a mistaken purchase entry, automatically deducting the stocked units from inventory.
* **Protection Guard:**
  - Before deducting stock, `actions/txn_action.php` checks if the items have already been borrowed by students.
  - If `(itemAvailableQty - purQty) < 0`, the operation aborts with an alert:
    *"Cannot reverse this purchase. The items have already been checked out. You must return them before archiving this purchase."*
* **Key Code Responsible:**
  - Lines 298&ndash;337 in [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php#L298-L337).

---

## 6. Operation 3: Items / Inventory (`#tab-inventory`)

The **Items** operation manages equipment catalog descriptions, rates, quantities, categories, and inventory archiving.

### 6.1. Add New Equipment Item
* **What it does:** Registers a new asset type (e.g., HDMI Cables, Projectors, Megaphones) directly into inventory.
* **How it works:**
  - Clicking **Add Item** opens `#modalItem` with hidden `itemID = "NEW"`.
  - Admin inputs description, category, total quantity, and rental rate.
  - Submits to `actions/item_action.php` (`action === 'save_item'`).
  - Generates `ITM-###` ID, initializes both `itemTotalQty` and `itemAvailableQty` to the entered quantity.
* **Key Code Responsible:**
  - Lines 57&ndash;68 in [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php#L57-L68).

### 6.2. Edit Item & In-Flight Checked-Out Stock Protection
* **What it does:** Allows administrators to correct typos, change rental rates, reclassify categories, or adjust stock counts.
* **Data Integrity Guard:**
  - If an administrator tries to lower the total quantity of an item, the system queries active unreturned checkouts:
    ```sql
    SELECT SUM(brwTransItemQty - COALESCE(SUM(r.brwTransQty), 0)) as unreturned
    FROM borrow_transaction b
    WHERE b.itemID = ? AND b.brwTransStatus = 'Released'
    ```
  - If the new quantity is lower than units currently in students' hands (`$qty < $currentlyCheckedOut`), the update is rejected:
    *"Quantity cannot be reduced below X unit(s) currently checked out."*
* **Key Code Responsible:**
  - Lines 26&ndash;56 in [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php#L26-L56).

### 6.3. Cross-Category Description Uniqueness
* **What it does:** Prevents duplicate inventory entries regardless of category.
* **How it works:**
  - A query verifies whether another active item has the exact same name:
    ```sql
    SELECT itemID FROM item WHERE LOWER(TRIM(itemDesc)) = LOWER(?) AND itemID != ? AND is_archived = 0
    ```
* **Key Code Responsible:**
  - Lines 27&ndash;31 & 58&ndash;62 in [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php).

### 6.4. Manage Categories
* **What it does:** Allows administrators to add new categories or remove unused categories.
* **How it works:**
  - Admin clicks **Manage Categories**, opening `#modalCategory`.
  - Adding submits `action=add_category`.
  - Deleting submits `action=delete_category`. Existing items with the deleted category are preserved.
* **Key Code Responsible:**
  - Lines 70&ndash;89 in [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php#L70-L89)
  - [`views/modals/modal_category.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_category.php).

### 6.5. Archive Item Guard
* **What it does:** Soft-deletes an item by setting `is_archived = 1`.
* **Protection Guard:**
  - The item **cannot** be archived if it has active units either physically checked out (`Released`) or booked for future dates (`Reserved`).
  - Query checks:
    ```sql
    SELECT SUM(...) FROM borrow_transaction WHERE itemID = ? AND brwTransStatus IN ('Released', 'Reserved')
    ```
  - Aborts with: *"Cannot archive item: X unit(s) are currently checked out or reserved."*
* **Key Code Responsible:**
  - Lines 90&ndash;104 in [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php#L90-L104).

---

## 7. Operation 4: Borrower Management (`#tab-borrowers`)

Maintains the registry of students and student organizations borrowing council assets.

### 7.1. Add Borrower Profile & Validation Guards
* **What it does:** Creates a verified borrower record in the `borrower` table.
* **Strict Validation Guards:**
  1. **Student ID Format:** Must match regex `^\d{2}-\d-\d{5}$` (e.g., `23-1-12345`). Validated via `validate_student_id()`.
  2. **Philippine Mobile Number:** Must be a valid 11-digit mobile number starting with `09` (e.g. `09123456789`). The helper `validate_ph_contact()` automatically cleans spaces, dashes, and normalizes international prefixes `+63` or `63` to `0`.
  3. **Duplicate Check:** Enforces unique student IDs across the active registry.
* **Key Code Responsible:**
  - Helpers `validate_student_id()` and `validate_ph_contact()` in [`config/helpers.php`](file:///c:/xampp/htdocs/CBSFork/config/helpers.php#L47-L76).
  - Lines 34&ndash;76 in [`actions/borrower_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/borrower_action.php#L34-L76).

### 7.2. Edit Borrower Profile
* **What it does:** Updates contact number, college, organization, or student ID for an existing borrower.
* **How it works:**
  - Clicking **Edit** on a borrower row invokes `editBorrower()` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L387-L403), populating `#modalBorrower` with existing attributes.
  - On submit, backend updates the record in `borrower`.

### 7.3. Live Autocomplete & Student ID Duplicate Checks
* **What it does:** Provides instant search suggestions as the clerk types in the checkout or return modal.
* **How it works:**
  - `actions/borrower_action.php` responds to `action === 'search_borrower'` by searching `brwStudentID`, `brwFName`, and `brwLName` via SQL `LIKE` queries.
  - Responds to `action === 'check_student_id'` by returning real-time existence and archive status to prevent duplicate registrations.

### 7.4. Archive Borrower Guard (Unreturned Equipment Protection)
* **What it does:** Soft-deletes a borrower profile (`is_archived = 1`).
* **Protection Guard:**
  - A borrower **cannot** be archived if they are holding unreturned equipment (`brwTransStatus = 'Released'`).
  - Query calculates:
    ```sql
    SELECT SUM(brwTransItemQty - COALESCE(SUM(r.brwTransQty), 0)) as unreturned
    FROM borrow_transaction b
    WHERE b.brwID = ? AND b.brwTransStatus = 'Released'
    ```
  - Halts with: *"Cannot archive borrower: they have X unreturned item(s)."*
* **Key Code Responsible:**
  - Lines 77&ndash;91 in [`actions/borrower_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/borrower_action.php#L77-L91).

---

## 8. Operation 5: User Management (`#tab-users`) [Council Exclusive]

Exclusive to **Council Administrators**, this operation controls role-based access control (RBAC), officer creation, and account status.

### 8.1. Create Officer Account & Password Hash Enforcement
* **What it does:** Creates new Council or Committee officer accounts.
* **Security Guard:**
  - Passwords are required for new accounts.
  - Enforces Bcrypt hashing via `password_hash($pass, PASSWORD_DEFAULT)` before database insertion.
  - Validates email formatting (`validate_email_format`) and PH contact number formatting (`validate_ph_contact`).
  - Enforces email address uniqueness across all non-archived user accounts.
* **Key Code Responsible:**
  - Lines 13&ndash;64 in [`actions/user_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/user_action.php#L13-L64).

### 8.2. Edit Officer & Role Escalation / Demotion
* **What it does:** Allows Council Admins to edit staff details or toggle their role between `Committee` and `Council`.
* **Password Handling:**
  - The password field is optional on edit. If left blank, the existing Bcrypt hash is untouched.
* **Key Code Responsible:**
  - Function `editUser()` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L329-L347) and [`views/modals/modal_user.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_user.php).

### 8.3. Self-Archive Prevention Guard
* **What it does:** Protects the administrator from accidentally locking themselves out of the system.
* **Security Guard:**
  ```php
  if ($id === $currentUser['userID']) {
      redirect_with_alert('tab-users', 'danger', 'Cannot archive yourself.');
  }
  ```
* **Key Code Responsible:**
  - Lines 65&ndash;73 in [`actions/user_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/user_action.php#L65-L73).

### 8.4. Role Pill Filter & One-Click Gmail Composition
* **Role Pill Filter:** Administrator can click segmented pill buttons (`All`, `Council`, `Committee`) to instantly filter the users table via `filterTableByPill('#usersTable', 'Council', 2)` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L277-L286).
* **One-Click Gmail Link:** Clicking an officer's email opens a pre-addressed Gmail compose window:
  ```html
  <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($u['userEmail']) ?>" target="_blank">
  ```

---

## 9. Operation 6: Reports (`#tab-reports`)

The **Reports** operation produces date-bounded operational audits and printable equipment activity reports.

### 9.1. Temporal Period Filtering
* **Filter Modes:**
  1. **Day:** Evaluates activities for today or a chosen specific day (`$_GET['day']`).
  2. **Week:** Calculates Monday to Sunday for the current week or a selected ISO week (`$_GET['week']`).
  3. **Month:** Evaluates the 1st day to the last day of the chosen month (`$_GET['month']`).
  4. **Year:** Evaluates January 1 to December 31 for the chosen year (`$_GET['year']`).
* **How it works:**
  - Selecting a filter triggers URL redirection: `index.php?period=month&month=2026-10`.
  - [`index.php`](file:///c:/xampp/htdocs/CBSFork/index.php#L58-L178) calculates `$repStartDate` and `$repEndDate`, generates `$formattedPeriodLabel`, and creates SQL parameters:
    ```sql
    b.brwTransDate BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'
    ```
* **Key Code Responsible:**
  - Period calculation logic in [`index.php`](file:///c:/xampp/htdocs/CBSFork/index.php#L58-L178).
  - Client-side handlers `applyPeriod()`, `applySpecificDay()`, `applySpecificWeek()`, `applySpecificMonth()`, `applySpecificYear()` in [`assets/js/app.js`](file:///c:/xampp/htdocs/CBSFork/assets/js/app.js#L418-L459).

### 9.2. Unified vs. Sub-Report Views
* **Unified View (`#rep-unified`):** Displays combined borrows and returns, showing which borrower checked out an item, who processed it, whether it was returned, and who accepted the return.
* **Borrows (`#rep-borrow`):** Filtered borrow transactions within the time period.
* **Returns (`#rep-return`):** Filtered return transactions within the time period.
* **Purchases (`#rep-purchase`):** Restock transactions logged in the time period.
* **Items (`#rep-inventory`):** Current inventory status snapshot.

### 9.3. Printable Layout & Official CSC Letterhead Rendering
* **What it does:** Provides a professional physical printout suitable for official records and faculty advisors.
* **Print Styling:**
  - Clicking **Print Report** runs `window.print()`.
  - CSS in [`assets/css/style.css`](file:///c:/xampp/htdocs/CBSFork/assets/css/style.css) utilizes `@media print`:
    - Hides non-printable navigation bars, sidebars, buttons, and filters (`.no-print`).
    - Unhides `.report-print-header`, rendering the CSC Confederates logo, institution header, reporting period, date generated, and generating officer name.

---

## 10. Operation 7: Profile Management (`#tab-profile`)

Allows logged-in Council Administrators and Committee members to maintain their own personal credentials.

### 10.1. Self-Service Account Details Update
* **What it does:** Updates first name, last name, email address, and Philippine mobile number.
* **Security Guard:**
  - A user can only update their own profile unless they have Admin privileges:
    ```php
    if ($uid !== $currentUser['userID'] && !$isAdmin) {
        redirect_with_alert('tab-profile', 'danger', 'Permission Denied: You can only update your own profile.');
    }
    ```
* **Key Code Responsible:**
  - Lines 9&ndash;20 in [`actions/profile_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/profile_action.php#L9-L20).

### 10.2. Optional Password Rotation
* **How it works:**
  - If the "New Password" field is left blank, the existing password hash is preserved.
  - If filled, it is hashed with `password_hash($pass, PASSWORD_DEFAULT)` and updated in the database.

### 10.3. Live Session Memory Synchronization
* **How it works:**
  - When the profile update succeeds, `actions/profile_action.php` updates the live PHP session variables immediately:
    ```php
    $_SESSION['full_name'] = $fName . ' ' . $lName;
    $_SESSION['email'] = $email;
    $_SESSION['contact_number'] = $contact;
    ```
  - This ensures the sidebar header and topbar immediately display the updated name without forcing the user to log out and log back in.

---

## 11. Operation 8: Session Teardown / Sign Out

Terminates the officer's authenticated session.

* **Trigger:** Located at the bottom of the sidebar navigation.
* **How it works:**
  - A form submits a POST request to [`actions/auth_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/auth_action.php#L29-L41) with `action=logout`.
  - Empties `$_SESSION = []`.
  - Expires and invalidates the session cookie (`setcookie(session_name(), '', time() - 42000, ...)`).
  - Calls `session_destroy()`.
  - Redirects the browser back to `login.php`.

---

## 12. Master Operations Matrix: Code, Actions, Modals & Database Tables

The following matrix provides a complete reference of all 7 Council Operations, their backend controllers, frontend templates, event triggers, and database touchpoints:

| Operation Name | UI Page File | Modal View File | Backend Action File | Key Backend Action(s) | Primary JS Handlers (`app.js`) | Database Tables Affected | Primary Guard / Security Mechanism |
|---|---|---|---|---|---|---|---|
| **1. Transactions** | [`views/pages/transactions.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/transactions.php) | [`views/modals/modal_txn.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_txn.php) | [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php) | `create_borrow`<br>`create_return`<br>`release_reservation`<br>`cancel_reservation`<br>`search_active_borrows`<br>`check_availability` | `checkRowAvailability()`<br>`handleBorrowSubmit()`<br>`selectBorrower()`<br>`selectRetBorrower()`<br>`updateReturnRow()` | `borrow_transaction`<br>`return_transaction`<br>`item`<br>`borrower` | - Overbooking date collision engine<br>- Return date $\ge$ borrow date<br>- Cannot return more than borrowed<br>- Auto-receipt generation |
| **2. Purchases** | [`views/pages/purchases.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/purchases.php) | [`views/modals/modal_purchase.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_purchase.php) | [`actions/txn_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/txn_action.php) | `purchase_item`<br>`archive_purchase` | `autoFillCategory()`<br>`#add-pur-item-btn` click | `purchase_transaction`<br>`item`<br>`category` | - Admin role required (`$isAdmin`)<br>- Existing item category lock<br>- Cannot reverse purchase if items checked out |
| **3. Items (Inventory)** | [`views/pages/inventory.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/inventory.php) | [`views/modals/modal_item.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_item.php)<br>[`views/modals/modal_category.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_category.php) | [`actions/item_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/item_action.php) | `save_item`<br>`add_category`<br>`delete_category`<br>`archive_item` | `editItem()`<br>Table search filter | `item`<br>`category` | - Admin role required<br>- Description uniqueness<br>- Cannot reduce stock below checked-out units<br>- Cannot archive item with active borrows |
| **4. Borrower Management** | [`views/pages/borrowers.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/borrowers.php) | [`views/modals/modal_borrower.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_borrower.php) | [`actions/borrower_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/borrower_action.php) | `save_borrower`<br>`archive_borrower`<br>`search_borrower`<br>`check_student_id` | `editBorrower()`<br>`setupBorrowerSearch()` | `borrower` | - Student ID regex (`##-#-#####`)<br>- PH contact normalization (`09...`)<br>- Cannot archive borrower with unreturned equipment |
| **5. User Management** | [`views/pages/users.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/users.php) | [`views/modals/modal_user.php`](file:///c:/xampp/htdocs/CBSFork/views/modals/modal_user.php) | [`actions/user_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/user_action.php) | `save_user`<br>`archive_user` | `editUser()`<br>`filterTableByPill()` | `user` | - Council Admin only (`$isAdmin`)<br>- Bcrypt password hashing<br>- Unique email validation<br>- Cannot archive self |
| **6. Reports** | [`views/pages/reports.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/reports.php) | *None* | [`index.php`](file:///c:/xampp/htdocs/CBSFork/index.php) | Direct SQL queries based on URL parameters | `applyPeriod()`<br>`applySpecificDay()`<br>`applySpecificWeek()`<br>`applySpecificMonth()`<br>`applySpecificYear()`<br>`window.print()` | `borrow_transaction`<br>`return_transaction`<br>`purchase_transaction`<br>`item`<br>`user` | - Parameter sanitization via PDO quotes<br>- Dynamic date range calculations<br>- Print CSS isolation (`@media print`) |
| **7. Profile Management** | [`views/pages/profile.php`](file:///c:/xampp/htdocs/CBSFork/views/pages/profile.php) | *Inline Form* | [`actions/profile_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/profile_action.php) | `update_profile` | Standard HTML5 form validation | `user` | - Cannot edit others unless Admin<br>- Password optional on edit<br>- Instant session memory sync |
| **8. Sign Out** | [`views/layout/sidebar.php`](file:///c:/xampp/htdocs/CBSFork/views/layout/sidebar.php) | *None* | [`actions/auth_action.php`](file:///c:/xampp/htdocs/CBSFork/actions/auth_action.php) | `logout` | Form submit event | *None* | - Cookie expiration<br>- Full session destruction |

---

*Document compiled and verified against repository source code in `C:\xampp\htdocs\CBSFork\`.*
