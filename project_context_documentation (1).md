# Confederates Student Council (CSC) - Resource Management System
## Product Requirements Document (PRD) & Codebase Architecture

### 1. Project Overview
The CSC Resource Management System is a centralized, internal web application designed for the Student Council's property desk. It operates on a **Clerk Terminal Paradigm**, meaning there is no public-facing catalog. Only authorized council officers (Administrators) and committee members (Staff) log into the system to manage inventory, process equipment checkouts for students, track overdue returns, and generate usage reports.

The system utilizes a modular Single Page Application (SPA) style architecture built entirely on native PHP, MySQL/SQLite, and frontend technologies (HTML, CSS, JavaScript, and Bootstrap 5). 

---

### 2. Codebase Modular Architecture & File Directory
To ensure maintainability, the application separates backend logic, database configurations, and frontend views into dedicated files.

*   **`index.php` (Master Assembler & Router):** The main entry point. It verifies the user's session, fetches necessary data from the database, and pieces together the HTML interface by dynamically including layout and page fragments.
*   **`login.php` (Authentication Portal):** An isolated, public-facing page containing the login form. It explicitly blocks authenticated users and redirects them to the dashboard.
*   **`print_receipt.php`:** An isolated script that generates a formatted, printable HTML gate pass for approved transactions.
*   **`config/` (Core Configurations):**
    *   `db.php`: Establishes the secure PDO (PHP Data Object) connection to the MySQL or SQLite database.
    *   `session.php`: Initializes server sessions (`session_start()`), checks if a user is logged in, and manages role-based access.
    *   `helpers.php`: Contains utility functions, such as ID generation (`generate_id`).
*   **`actions/` (Backend Processors):** Invisible PHP scripts that receive form submissions (POST requests) or AJAX calls. They execute database updates, set session alerts, and redirect the user back to the UI.
    *   `auth_action.php`, `item_action.php`, `borrower_action.php`, `user_action.php`, `profile_action.php`, `txn_action.php`.
*   **`views/` (Frontend Components):** HTML fragments that `index.php` stitches together.
    *   `layout/`: `header.php`, `topbar.php`, `sidebar.php`, `footer.php`.
    *   `pages/`: The content for each tab (`transactions.php`, `inventory.php`, `reports.php`, etc.).
    *   `modals/`: Hidden popup forms (`modal_txn.php`, `modal_item.php`, etc.).
*   **`assets/` (Static Files):**
    *   `css/style.css`: Custom styling complementing Bootstrap.
    *   `js/app.js`: Client-side JavaScript handling live searches, tab switching, and modal triggers.

---

### 3. Database Schema Overview
*   **`user`:** Stores council officers. Contains hashed passwords, roles (`Council` or `Committee`), and contact details.
*   **`item`:** The equipment inventory (projectors, speakers, etc.). Tracks total stock, currently available stock, categories, and rental rates.
*   **`borrower`:** Profiles of students and organizations who rent equipment.
*   **`borrow_transaction`:** Ledger of all checkouts. Uses a composite key to link multiple items to a single transaction ID.
*   **`return_transaction`:** Ledger of returned items, linking back to the original borrow transaction.
*   **`purchase_transaction`:** Restock ledger logging when the council buys new equipment.

---

### 4. Unifying Scenario & Exhaustive Process Mapping

To understand the exact flow of data, memory management, and file interactions, we trace a complete end-to-end scenario: **A staff member attempting to access the system, failing a login, succeeding, and checking out a piece of equipment.**

#### Step 1: The Initial Request & Unauthenticated Redirection
**Scenario:** A staff member navigates to the application's base URL (e.g., `http://localhost/CBSFork/`).
1.  **File Called (`index.php`):** The Apache server automatically routes root directory requests to `index.php`, the system's "landing page" and front controller. 
2.  **Configuration Loading (Lines 6-8):** `index.php` initiates backend assembly via:
    *   `require_once 'config/db.php';`
    *   `require_once 'config/session.php';`
    *   `require_once 'config/helpers.php';`
    *   *Function Call Guideline:* `require_once` instructs PHP to import the contents of these files. The "once" suffix ensures PHP physically checks if the file was already loaded in the current execution thread. If it was, the call is ignored, preventing fatal "function already declared" errors or infinite loops.
3.  **Session Initialization (`config/db.php` Line 7):** Inside `db.php`, the code calls `if (session_status() === PHP_SESSION_NONE) { session_start(); }`.
    *   *Function Call Guideline:* HTTP is inherently stateless; the server forgets the user immediately after the page loads. `session_start()` resolves this by creating a temporary memory file on the server and issuing a unique Session ID cookie to the browser. The conditional `session_status()` check ensures PHP does not throw a fatal error by attempting to start a session that is already active.
4.  **Authorization Check (`config/session.php`):** This file checks for the existence of an authenticated user via `if (!$currentUser)`.
    *   *Data Evaluated:* Because the user just arrived and has not logged in, the `$_SESSION` global variable contains no authentication data. Therefore, `$currentUser` evaluates to `null` (or false).
    *   *Execution:* Recognizing an unauthenticated state, the script executes `header('Location: login.php'); exit;`.
    *   *Function Call Guideline:* The `header()` function sends raw HTTP routing instructions directly to the browser, commanding it to immediately redirect to `login.php`. The `exit;` function instantly kills the server's execution of `index.php`, preventing any secure dashboard HTML or database queries from leaking to the unauthorized browser.

#### Step 2: The Login Page Load & Failed Form Submission
**Scenario:** The browser redirects to `login.php`. The staff member inputs an incorrect password and clicks "Sign In".
1.  **Rendering the Form (`login.php`):** 
    *   The file calls `require_once` for `db.php` and `session.php` to ensure the session is active. It checks `if ($currentUser) { header('Location: index.php'); exit; }`. Because the session is still null, this evaluates to false, bypassing the redirect.
    *   HTML is rendered to the browser, displaying a form mapped to backend logic: `<form method="POST" action="actions/auth_action.php">`.
    *   *Data Passed:* When submitted, the browser packages the inputs into a secure HTTP POST payload: `['email' => 'staff@csc.edu.ph', 'password' => 'wrongpass', 'action' => 'login']`. The hidden input `<input type="hidden" name="action" value="login">` acts as an instruction flag so the backend knows exactly what logic to execute.
2.  **Backend Processing (`actions/auth_action.php`):**
    *   **Data Capture:** The script extracts the POST payload using `trim($_POST['email'] ?? '')`.
    *   **Database Query Prep:** It executes `$stmt = $db->prepare("SELECT * FROM user WHERE userEmail = ? AND is_archived = 0 LIMIT 1");`. 
    *   *Function Call Guideline:* `prepare()` compiles the SQL structure separately from the user's input (the `?` placeholder). This is a critical security measure that renders SQL injection mathematically impossible.
    *   **Execution:** `$stmt->execute([$email]);` binds the email string payload to the placeholder and runs the query. 
    *   **Data Returned:** `$user = $stmt->fetch();` retrieves the matching database row as an associative PHP array (e.g., `['userID' => 'USR-002', 'userPassword' => '$2y$10$...']`).
3.  **The Invalid Path (Wrong Password):**
    *   **Verification:** The script runs `if ($user && password_verify($password, $user['userPassword']))`. 
    *   *Function Call Guideline:* `password_verify()` is a native PHP cryptography function. It securely compares the user's plain-text POST payload (`$password`) against the Bcrypt hashed string retrieved from the database (`$user['userPassword']`).
    *   **Execution:** Since the password is wrong, the function returns the boolean `false`.
    *   **Flash Data Storage:** The `else` block executes: `$_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email or password.'];`. This stores the error in the server's global session memory.
    *   **Redirect:** The script calls `header('Location: ../login.php'); exit;`, commanding the browser to reload the login page.
    *   **UI Update:** Upon reloading, `login.php` detects the `$_SESSION['alert']`, prints the red error box into the HTML, and immediately deletes the session variable using `unset()` so the alert does not persist upon subsequent manual refreshes.

#### Step 3: Successful Authentication & Single-Page Assembly
**Scenario:** The user types the correct password and submits.
1.  **State Mutation (`actions/auth_action.php`):**
    *   This time, `password_verify()` returns the boolean `true`.
    *   The backend assigns the user's database identifiers to the global session: `$_SESSION['userID'] = $user['userID'];`, `$_SESSION['role'] = $user['userRole'];`, etc. 
    *   *Data Context:* This acts as a persistent VIP wristband. Conceptually similar to a static class instance in object-oriented programming, assigning data to `$_SESSION` creates a globally accessible state for that specific user's active connection.
    *   The script calls `header('Location: ../index.php'); exit;` to redirect to the dashboard.
2.  **Dashboard Assembly (`index.php`):**
    *   `session.php` runs. Because `$_SESSION['userID']` is populated, `$currentUser` evaluates to true. The redirect is bypassed.
    *   **Data Hydration:** The script executes multiple SQL queries (e.g., `$items = $db->query("SELECT * FROM item...")->fetchAll();`) to pull all active inventory, borrower profiles, and transaction ledgers into local PHP arrays.
    *   **Frontend Stitching:** Utilizing its modular architecture, `index.php` uses `require_once` to pull in `views/layout/header.php`, `topbar.php`, and `sidebar.php`.
    *   **The SPA Container:** The script outputs `<div class="tab-content" id="v-pills-tabContent">`. 
    *   *Function Call Guideline:* Inside this single `div`, the script utilizes `require_once` to load *all* page components (`transactions.php`, `inventory.php`, etc.) into the Document Object Model (DOM) simultaneously. The Bootstrap class `tab-content` and the ID `v-pills-tabContent` hook into frontend JavaScript. While all HTML is physically present in the browser's memory, the JS and CSS ensure only one child `tab-pane` is visible at a time. This allows instantaneous tab switching without further server requests.
    *   **Modals:** At the bottom of `index.php`, all modals (`modal_txn.php`, `modal_user.php`) are loaded via `require_once`. They sit idle in the DOM, strictly hidden by CSS, until a specific button click or JS command targets their ID.

#### Step 4: The Checkout Process (Interactivity & Backend Logic)
**Scenario:** The staff clicks "Add Transaction", searches for a borrower, and checks out an item.
1.  **Frontend Interactivity (`assets/js/app.js`):**
    *   The user clicks the "Add Transaction" button. Bootstrap JavaScript intercepts the `data-bs-target="#modalTransaction"` attribute and overrides default browser behavior to unhide the HTML modal pre-loaded in Step 3.
    *   **Live AJAX Search:** As the user types "John" into the borrower search bar, an event listener captures the keystrokes. It triggers `$.post('actions/txn_action.php', { action: 'search_borrower', query: 'John' })`.
    *   **Data Exchange:** The backend executes `$stmt = $db->prepare("SELECT * FROM borrower WHERE brwFName LIKE ?...");`, returning a JSON string of matching profiles. The JavaScript receives this JSON and injects clickable dropdown results into the DOM without reloading the webpage.
2.  **Form Submission (`actions/txn_action.php`):**
    *   The user clicks "Process Checkout". The form sends a POST request payload containing `action=create_borrow`, `itemID`, `brwID`, and `qty`.
    *   **Validation Check:** The script queries the item to retrieve `itemRate` and `itemAvailableQty`. It runs `if ($item['itemAvailableQty'] < $qty)`.
    *   *Invalid Path:* If true, it assigns `$_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not enough stock'];`, calls `header('Location: ../index.php');`, and stops execution.
    *   **Valid Path (Data Mutations):** If false, the script calls `generate_id($db, 'borrow_transaction', ...)` to generate a unique key (e.g., `TXN-005`).
    *   It runs an `INSERT INTO borrow_transaction ...` statement to create the immutable ledger record.
    *   It runs an `UPDATE item SET itemAvailableQty = itemAvailableQty - ? WHERE itemID = ?` statement. This immediately deducts the checked-out quantity from the live inventory, ensuring data integrity.
    *   **Trigger Generation:** The script assigns `$_SESSION['print_receipt'] = [$transID];` and `$_SESSION['active_tab'] = 'tab-transactions';`, then redirects the browser back to `index.php`.

#### Step 5: Final UI Update & Automated Gate Pass Generation
**Scenario:** The dashboard reloads to reflect the checkout and issues the official slip.
1.  **Data Refresh (`index.php`):** 
    *   Upon reload, `index.php` reads `$_SESSION['active_tab']` and injects the `show active` class into the Transactions `tab-pane`.
    *   The `SELECT` queries pull fresh data from the database. The inventory table instantly reflects the decremented stock, and the transactions table displays the new `TXN-005` record.
2.  **Receipt Script Injection:**
    *   At the bottom of `index.php`, the script evaluates `if ($printReceipt)`. Because it was populated in Step 4, it evaluates to true.
    *   **Output:** PHP dynamically writes a `<script>` block into the HTML payload, passing the `TXN-005` array into JavaScript via `json_encode($printReceipt)`.
    *   **Execution:** The browser parses the incoming HTML, executes the injected JavaScript, and triggers `window.open('print_receipt.php?ids=["TXN-005"]', ...);`.
3.  **Gate Pass Assembly (`print_receipt.php`):**
    *   The new popup window initiates an independent GET request to `print_receipt.php`.
    *   The script captures the `ids` parameter from the URL `$_GET` array.
    *   It executes a complex SQL `JOIN` query to aggregate transaction metadata, equipment descriptions, the borrower's profile, and the processing staff's profile.
    *   It renders a clean HTML view structured explicitly for physical printing, utilizing an `<body onload="window.print()">` tag to automatically trigger the system's printer dialog for the staff member.

## Tutorial Links
- https://www.w3schools.com/bootstrap/bootstrap_ref_all_classes.asp
- https://www.w3schools.com/php/keyword_require_once.asp
- https://www.w3schools.com/html/html5_canvas.asp
- https://github.com/zDR34M/Particle-Network-Animation/blob/main/index.html
- https://codepen.io/JulianLaval/pen/KpLXOO/