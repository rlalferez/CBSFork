# Codebase Refactoring Plan

Currently, your application is a **monolith**. Almost all of the backend logic, database queries, HTML views, CSS logic, and JavaScript interactions are crammed into `index.php` (which is over 1,500 lines long). While this is common during initial prototyping, it makes the code difficult to maintain, debug, and scale.

To retain the "university-level" simplicity while significantly improving readability, we should refactor the codebase into a modular procedural structure using standard PHP `require_once` and `include` statements. 

Here is the exhaustive plan for separating the codebase into appropriate individual files:

## Proposed Directory Structure

```text
CBSFork/
│
├── database/                # SQL schemas and mock data
│   ├── confed_borrowing.sql
│   └── confed_borrowing_mock_data.sql
│
├── config/
│   ├── db.php               # Database connection (Already exists)
│   ├── session.php          # Session start, auth checks, user role logic
│   └── helpers.php          # Utility functions (e.g., generate_id)
│
├── actions/                 # Backend form handlers and AJAX endpoints
│   ├── auth_action.php      # Login and Logout POST handlers
│   ├── item_action.php      # Save and Archive item logic
│   ├── borrower_action.php  # Save, Archive, and Search borrower logic
│   ├── user_action.php      # Save and Archive user logic
│   ├── profile_action.php   # Update profile logic
│   └── txn_action.php       # Borrow, Return, Purchase logic & searches
│
├── views/                   # HTML Templates and Fragments
│   ├── layout/
│   │   ├── header.php       # HTML <head>, CSS links, and opening <body>
│   │   ├── topbar.php       # The top navigation bar
│   │   ├── sidebar.php      # The left menu sidebar
│   │   └── footer.php       # Closing </body>, core JS files, and shared JS
│   │
│   ├── pages/               # The content for each tab
│   │   ├── transactions.php # Transactions table view
│   │   ├── purchases.php    # Purchases table view
│   │   ├── inventory.php    # Inventory table view
│   │   ├── borrowers.php    # Borrowers table view
│   │   ├── users.php        # User Management table view (Admin only)
│   │   ├── reports.php      # Report generation view and logic
│   │   └── profile.php      # Profile management view
│   │
│   └── modals/              # All popup modals
│       ├── modal_txn.php       # Add Transaction modal (Borrow/Return)
│       ├── modal_item.php      # Add/Edit Item modal
│       ├── modal_borrower.php  # Add/Edit Borrower modal
│       ├── modal_user.php      # Add/Edit User modal
│       └── modal_purchase.php  # Add Purchase modal
│
├── assets/
│   ├── css/
│   │   └── style.css        # Existing stylesheet
│   └── js/
│       └── app.js           # Extracted JavaScript from index.php
│
├── index.php                # Main entry point (Router & Assembler)
├── receipt.php              # Isolated receipt generation script
└── login.php                # Isolated login page (if user is not logged in)
```

---

## Breakdown of the Refactoring Process

### 1. Separation of Backend Logic (The `actions/` folder)
Right now, the top 200+ lines of `index.php` consist of a massive `if...elseif ($action === '...')` block that handles everything from logging in to updating items to returning books. 

**How to refactor:**
- We will route forms and AJAX calls to specific files inside the `actions/` folder instead of posting back to `index.php`. 
- For example, the "Add Item" form will have `<form action="actions/item_action.php?action=save" method="POST">`.
- These action files will process the data, interact with the database, set the session alert messages, **and set a session variable for the active tab (e.g., `$_SESSION['active_tab'] = 'inventory'`)**. 
- Finally, they will call `header('Location: ../index.php');` to redirect the user back to the main view, and `index.php` will use the session variable to keep the user on the correct tab instead of forcing them back to the Dashboard.

### 2. Separation of the View (The `views/` folder)
The HTML layout is deeply nested and highly repetitive. 

**How to refactor:**
- **Layout:** Extract the `<head>` into `views/layout/header.php`, the topbar into `topbar.php`, and the sidebar into `sidebar.php`.
- **Content Tabs:** Instead of having one massive `<div class="tab-content">` containing all 7 pages, we pull the HTML for the Inventory tab, Transactions tab, etc., into their own files inside `views/pages/`.
- **Modals:** Move every `<div class="modal fade">` to its own isolated file inside `views/modals/`.

### 3. The New `index.php` (The Assembler)
Once the logic and views are extracted, your `index.php` will be incredibly clean—around 40 lines of code. It will simply act as a glue that stitches the pieces together.

**Example of the new `index.php`:**
```php
<?php
require_once 'config/db.php';
require_once 'config/session.php'; // Protects page, redirects to login.php if needed
require_once 'config/helpers.php';

// Fetch initial required data for the views (e.g. active items for dropdowns)
$items = $db->query("SELECT * FROM item WHERE is_archived = 0")->fetchAll();
// ... (Fetch other necessary data) ...

require_once 'views/layout/header.php';
?>

<div class="app-shell pb-0 no-print">
    <?php require_once 'views/layout/topbar.php'; ?>
    
    <div class="app-layout">
        <?php require_once 'views/layout/sidebar.php'; ?>
        
        <main class="app-main">
            <div class="tab-content" id="v-pills-tabContent">
                <?php 
                    require_once 'views/pages/transactions.php';
                    require_once 'views/pages/purchases.php';
                    require_once 'views/pages/inventory.php';
                    require_once 'views/pages/borrowers.php';
                    require_once 'views/pages/reports.php';
                    require_once 'views/pages/profile.php';
                    
                    if ($isAdmin) {
                        require_once 'views/pages/users.php';
                    }
                ?>
            </div>
        </main>
    </div>
</div>

<?php 
// Include Modals
require_once 'views/modals/modal_txn.php';
require_once 'views/modals/modal_item.php';
require_once 'views/modals/modal_borrower.php';
if ($isAdmin) { require_once 'views/modals/modal_user.php'; }
require_once 'views/modals/modal_purchase.php';

// Include Footer & JS
require_once 'views/layout/footer.php'; 
?>
```

### 4. JavaScript Extraction
All the inline `<script>` tags at the bottom of `index.php` (which handle the particle animation, AJAX searches, UI toggling, and the new **Cross-Tab Relational Highlighting** logic) should be cut and pasted into a dedicated `assets/js/app.js` file. The footer will simply link to it: `<script src="assets/js/app.js"></script>`.

## Benefits of this Architecture
1. **Easy to Navigate**: If you want to change how the borrower search works, you go straight to `borrower_action.php`. If you want to change how the inventory table looks, you go to `views/pages/inventory.php`. 
2. **Team Collaboration**: You and your groupmates can work on different files simultaneously without constantly running into git merge conflicts on a single `index.php` file.
3. **Improved UX**: Implementing `$_SESSION['active_tab']` will fix the current issue where submitting a form unexpectedly kicks the user back to the Dashboard.
4. **Performance**: Only necessary logic runs. The codebase becomes infinitely easier to debug.
