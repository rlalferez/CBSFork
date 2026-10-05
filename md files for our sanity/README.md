# 🏛️ Confederates Student Council (CSC) &mdash; Property Desk & Resource Management System

A clean, modern, student-friendly **Internal Clerk & Property Custodian Web Application** built specifically for the **Confederates Student Council (CSC)**.

> **Clerk / Desk Terminal Paradigm (No Public Access):**  
> This system is strictly an **internal council office tool**. Students and campus organizations come directly to the council property desk in person to borrow equipment. The **Council Desk Officer (Staff)** or **System Administrator (Admin)** is the only person who logs into this terminal to process loans, search schedules, issue gate passes, and manage inventory.

---

## 📁 Project Structure (Modular Architecture)

The codebase has been refactored into a clean, maintainable modular structure separating logic (actions), presentation (views), and configuration:

```
Confed Borrowing System/
│
├── index.php         # MASTER APP: Main router combining layouts, views, and modals
├── login.php         # LOGIN PORTAL: Independent auth portal restricting public access
├── receipt.php       # PRINTABLE GATE PASS: Official printable equipment slip with signatures
├── database.sqlite   # Auto-generated database file (created automatically on first launch)
├── README.md         # This defense & presentation guide
│
├── actions/          # BACKEND LOGIC CONTROLLERS: Form handlers and SQL operations
│   ├── auth_action.php       # Handles login/logout logic and session checks
│   ├── borrower_action.php   # Handles adding, editing, and searching student/org borrowers
│   ├── item_action.php       # Handles adding, editing, and archiving equipment items
│   ├── profile_action.php    # Handles personal account and password updates
│   ├── txn_action.php        # Handles checking out, returning, and tracking item statuses
│   └── user_action.php       # Admin-exclusive user creation and role management
│
├── config/           # CONFIGURATION: Global utilities and connections
│   ├── db.php                # Database connector (PDO) supporting both SQLite and MySQL
│   ├── helpers.php           # Helper functions (e.g., ID generation)
│   └── session.php           # Global session initialization, validation, and role extraction
│
├── views/            # UI PRESENTATION TEMPLATES: Reusable HTML components
│   ├── layout/               # Shell layout components (header.php, sidebar.php, footer.php)
│   ├── modals/               # Popup modals for forms (modal_txn.php, modal_item.php, etc.)
│   └── pages/                # Individual dashboard tabs (transactions, borrowers, inventory, etc.)
│
├── assets/           # STATIC ASSETS
│   ├── css/style.css         # Modern responsive styling and print constraints
│   └── js/app.js             # Client-side interactivity (AJAX search, highlighting, modal prep)
│
└── database/         # SQL SEEDS
    ├── confed_borrowing.sql            # Empty schema for fresh setup
    └── confed_borrowing_mock_data.sql  # Optional test data
```

*(All old, fragmented files from initial drafting have been cleanly archived in `_legacy_archive/` so your working directory remains 100% clean).*

---

## 🎯 Course Rubric & Specification Checklist

| Project Requirement from Guidelines | Status | How It Is Implemented (Clerk Workflow) | Where to Show in Code |
| :--- | :---: | :--- | :--- |
| **Two (2) Types of Users with Login** | ✅ **Passed** | `Admin` (full rights) and `Committee` (operational rights) with secure password hashing (`password_verify`). **Zero public view** &mdash; only authorized officers can access. | `actions/auth_action.php` |
| **All Functionalities have CRUD** | ✅ **Passed** | Complete **C**reate, **R**ead, **U**pdate, and **D**elete (via soft-archiving) for Items, Transactions, Borrowers, and Users. | Action handlers in `actions/` |
| **Resource Management (Speakers, Sports, etc.)** | ✅ **Passed** | Catalogs JBL/Yamaha speakers, sports equipment, event gear, etc., with ID, Name, Category, Availability, and Fees. | **Items Tab** (`views/pages/inventory.php`) |
| **Search & Book Resource by Schedule** | ✅ **Passed** | Clerk searches items and checks live availability via **"Process Borrowing"** modal when a student arrives. | Modal `#modalTxn` |
| **Status Tracking & Returns** | ✅ **Passed** | 1-Click action buttons to update a transaction from `Released` to `Returned` (with partial/full quantity processing). | **Transactions Tab** (`views/pages/transactions.php`) |
| **Manage Client Records (CRUD)** | ✅ **Passed** | Add, edit, view, and search student borrowers and campus organizations. | **Borrower Directory** (`views/pages/borrowers.php`) |
| **Manage Staff Records (CRUD)** | ✅ **Passed** | Admin-exclusive tab to add new council/committee members, change roles, and edit access. | **User Management** (`views/pages/users.php`) |
| **Manage Personal Accounts** | ✅ **Passed** | Profile update tab (name, email, password) for the currently logged-in user. | **Profile Tab** (`views/pages/profile.php`) |
| **Generate Booking Reports** | ✅ **Passed** | Five report modes: **Unified View**, **Borrows**, **Returns**, **Purchases**, and **Items**, with dynamic date filtering and instant print. | **Reports Tab** (`views/pages/reports.php`) |
| **Bootstrap Integration (Bonus Points)** | ✅ **Passed** | Uses **Bootstrap 5.3.3** for clean modals, badges, cards, tabs, and responsive navigation. | Layouts in `views/layout/` |

---

## 🔑 Login Accounts for Demo

| Role | Email | Password | Features Accessible |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@csc.edu.ph` | `admin123` | **Full access**: All CRUD (including Adding/Editing Items and Logging Purchases), User Management, Reports, and overriding transactions. |
| **Committee Member** | `staff@csc.edu.ph` | `staff123` | **Operational desk access**: Process Borrows, Process Returns, View Items (Read-only), Borrower Directory, Reports, Personal Profile. |

*Tip for Defense: On the login screen, you can click **"Admin Login"** or **"Staff Login"** for 1-click automatic credential filling!*

---

## 🐬 MySQL & phpMyAdmin Setup Instructions

1. Start **Apache** and **MySQL** in your **XAMPP Control Panel**.
2. Open your browser and go to: **[http://localhost/phpmyadmin/index.php](http://localhost/phpmyadmin/index.php)**.
3. Click the **"SQL"** tab in the top navigation bar.
4. Copy the entire contents of **`database/confed_borrowing.sql`**, paste it into the box, and click **"Go"** at the bottom right.
5. That's it! It creates the database `confed_borrowing` and all tables completely empty (zero seed data, allowing your group to input your own equipment and transactions).

---

## 🚀 How to Run the Project in XAMPP

1. Place or copy the `CBSFork` folder into your XAMPP `htdocs` directory (e.g. `C:\xampp\htdocs\CBSFork`).
2. Open your browser and navigate to:
   - **[http://localhost/CBSFork/](http://localhost/CBSFork/)**
3. Log in with the Administrator account:
   - **Email:** `admin@csc.edu.ph`
   - **Password:** `admin123`
4. Start by going to **Items** &rarr; click **"Add Item"** to input your council's equipment (speakers, sports gear, etc.).
5. When students arrive at the desk, click **"Process Borrowing"** in the Transactions tab to list down who borrowed what!

---

## 🎓 Defense Guide: How to Explain the Code to Your Professor

### 1. "Why is there no public catalog view?"
> *"Sir/Ma'am, this system is modeled as a Council Property Desk Terminal &mdash; like a clerk or bank teller workstation. In actual university operations, students come to the student council desk in person to borrow equipment. Only authorized council officers (Admin and Committee) log into this terminal to process the checkout, verify student IDs, and update return statuses."*

### 2. "How does the database connect and initialize?"
> *"In `config/db.php`, we connect to MySQL in phpMyAdmin via PHP Data Objects (PDO) inside `get_db()`. We configured it with `utf8mb4` charset and prepared statements to prevent SQL injections. As required, we kept the inventory and bookings tables completely clean without artificial seed data, allowing our Admin and Staff to manually input the council's actual resources."*

### 3. "Where does authentication and user roles happen?"
> *"In `actions/auth_action.php`, we handle `action=login` and verify passwords using PHP's native `password_verify()`. If not logged in, the system displays only the secure Officer Sign In screen (`login.php`). Once authenticated, global session logic in `config/session.php` establishes `$_SESSION['userRole']` which determines permissions: Admins get full custody control (only Admins can add/edit items, log purchases, and manage staff accounts), while Committee members are strictly restricted to operational borrowing desk duties."*

### 4. "Show me the Desk Checkout process."
> *"When an officer is logged in, clicking the **'Process Borrowing'** button opens the `#modalTxn`. The officer enters the borrower's Student ID (which dynamically searches the Borrower Directory via AJAX in `assets/js/app.js`), selects the equipment (showing live stock availability), and submits. Clicking submit routes to `actions/txn_action.php`, which automatically decrements item stock, creates the borrower profile if they are new, and creates the transaction ledger."*

### 5. "Show me Returns and Statuses."
> *"In the **Transactions tab**, each reservation has action buttons based on its state. If an item is `Released`, an officer can click **Process Return**. This opens a modal where they can specify the exact quantity returned, which then dynamically routes back to `actions/txn_action.php` to restore the `itemAvailableQty` in the `item` table and update the transaction status badge."*

### 6. "How do your Reports work?"
> *"Under the **Reports tab**, officers can view an aggregated, live feed of all operations split into multiple categories: Unified View, Borrows, Returns, Purchases, and Items. The data is fetched in `index.php` and dynamically displayed. Officers can use the dynamic **Start Date** and **End Date** filters, which automatically pass PHP `$_GET` parameters to query the exact timeframe, and then click **Print Report** for a cleanly formatted, hard-copy submission."*
