# 🏛️ Confederates Student Council (CSC) &mdash; Property Desk & Resource Management System

A clean, modern, student-friendly **Internal Clerk & Property Custodian Web Application** built specifically for the **Confederates Student Council (CSC)**.

> **Clerk / Desk Terminal Paradigm (No Public Access):**  
> This system is strictly an **internal council office tool**. Students and campus organizations come directly to the council property desk in person to borrow equipment. The **Council Desk Officer (Staff)** or **System Administrator (Admin)** is the only person who logs into this terminal to process loans, search schedules, issue gate passes, and manage inventory.

---

## 📁 Simple Project Structure (Only 5 Essential Files)

Everything is kept clean and straightforward in the root directory:

```
Confed Borrowing System/
│
├── index.php         # MASTER APP: Desk Officer Login + Internal Workspace + Bookings + Inventory + Reports
├── db.php            # DATABASE CONNECTOR: 1-file SQLite auto-setup & switchable MySQL configuration
├── receipt.php       # PRINTABLE GATE PASS: Official printable equipment slip with signatures
├── style.css         # STYLESHEET: Modern responsive styling complementing Bootstrap 5
├── script.js         # JAVASCRIPT: Search filtering, tab navigation, and modal pre-fill helpers
│
├── database.sqlite   # Auto-generated database file (created automatically on first launch)
└── README.md         # This defense & presentation guide
```

*(All old, fragmented files from initial drafting have been cleanly archived in `_legacy_archive/` so your working directory remains 100% clean).*

---

## 🎯 Course Rubric & Specification Checklist

| Project Requirement from Guidelines | Status | How It Is Implemented (Clerk Workflow) | Where to Show in Code |
| :--- | :---: | :--- | :--- |
| **Two (2) Types of Users with Login** | ✅ **Passed** | `Admin` (full rights) and `Staff` (operational rights) with secure password hashing (`password_verify`). **Zero public view** &mdash; only authorized officers can access. | Top of `index.php` (Auth controller) |
| **All Functionalities have CRUD** | ✅ **Passed** | Complete **C**reate, **R**ead, **U**pdate, and **D**elete for Resources, Bookings, Clients, and Staff accounts. | Action handlers in `index.php` |
| **Resource Management (Speakers, Sports, etc.)** | ✅ **Passed** | Catalogs JBL/Yamaha speakers, sports equipment (balls, nets), event gear (tents, tables), cables, etc., with ID, Name, Model, Availability, Fees. | Resources Tab in `index.php` |
| **Search & Book Resource by Schedule** | ✅ **Passed** | Clerk searches items and checks live availability schedules via **"+ Desk Checkout"** modal when a student arrives. | `index.php` Modal `#newBookingModal` |
| **Change Resource or Booking Date** | ✅ **Passed** | 1-Click **Reschedule Modal** to alter dates, venue, or event details anytime. | Modal `#rescheduleModal` |
| **Cancellation Options** | ✅ **Passed** | 1-Click **Cancel Booking Modal** with mandatory cancellation reason logging and stock auto-restoration. | Modal `#cancelModal` |
| **Set Payment Status & Details** | ✅ **Passed** | 1-Click **Payment Modal** (`Free`, `Deposit Paid`, `Paid`, `Refunded`, etc.) with amount and receipt tracking. | Modal `#paymentModal` |
| **Manage Client Records (CRUD)** | ✅ **Passed** | Add, edit, view, and delete student borrowers and campus organizations. | Clients Tab in `index.php` |
| **Manage Staff Records (CRUD)** | ✅ **Passed** | Admin-exclusive tab to add new staff, change roles, edit details, or remove staff. | Staff Accounts Tab in `index.php` |
| **Manage Personal Accounts** | ✅ **Passed** | Online self-registration for staff and profile update modal (name, email, password). | Profile Tab / Modal in `index.php` |
| **Generate Booking Reports** | ✅ **Passed** | Three report modes: **Periodic** (by date range), **Per Resource**, and **Per Client** with instant print. | Reports Tab in `index.php` |
| **Bootstrap Integration (Bonus Points)** | ✅ **Passed** | Uses **Bootstrap 5.3.3** for clean modals, badges, cards, and responsive navigation. | Header of `index.php` & `receipt.php` |

---

## 🔑 Login Accounts for Demo

| Role | Username | Password | Features Accessible |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | **Full access**: All CRUD (including Adding/Editing/Deleting Equipment Resources), Staff Management, Reports, Rescheduling, Cancellations, Payments. |
| **Staff Member** | `staff1` | `staff123` | **Operational desk access**: Desk Checkouts, View Inventory (Read-only; Staff is **not** allowed to add, edit, or delete equipment), Clients CRUD, Reschedule, Cancel, Set Payments, Reports, Personal Profile. |

*Tip for Defense: On the login screen, you can click **"👤 Fill Admin"** or **"👔 Fill Staff"** for 1-click automatic credential filling! You can also click **"Register Staff Account"** to demonstrate creating a new clerk account.*

---

## 🐬 MySQL & phpMyAdmin Setup Instructions

1. Start **Apache** and **MySQL** in your **XAMPP Control Panel**.
2. Open your browser and go to: **[http://localhost/phpmyadmin/index.php](http://localhost/phpmyadmin/index.php)**.
3. Click the **"SQL"** tab in the top navigation bar.
4. Copy the entire contents of **`confed_borrowing.sql`** (or copy from the SQL code block provided in this guide), paste it into the box, and click **"Go"** at the bottom right.
5. That's it! It creates the database `confed_borrowing` and all 5 tables completely empty (zero seed data for resources/bookings, so you and your group have full control to input your own equipment and transactions).

---

## 🚀 How to Run the Project in XAMPP

1. Place or copy the `Confed Borrowing System` folder into your XAMPP `htdocs` directory (e.g. `C:\xampp\htdocs\Confed Borrowing System`).
2. Open your browser and navigate to:
   - **[http://localhost/Confed%20Borrowing%20System/](http://localhost/Confed%20Borrowing%20System/)**
3. Log in with the initial Administrator account:
   - **Username:** `admin`
   - **Password:** `admin123`
4. Start by going to **Resources** &rarr; click **"+ Add Resource"** to input your council's equipment (speakers, sports gear, etc.).
5. When students arrive at the desk, click **"+ Desk Checkout"** to list down who borrowed what!

---

## 🎓 Defense Guide: How to Explain the Code to Your Professor

### 1. "Why is there no public catalog view?"
> *"Sir/Ma'am, this system is modeled as a Council Property Desk Terminal &mdash; like a clerk or bank teller workstation. In actual university operations, students come to the student council desk in person to borrow equipment. Only authorized council officers (Admin and Staff) log into this terminal to process the checkout, verify student IDs, check equipment conditions, and print the official Gate Pass."*

### 2. "How does the database connect and initialize?"
> *"In `db.php`, we connect to MySQL in phpMyAdmin via PHP Data Objects (PDO) inside `get_db()`. We configured it with `utf8mb4` charset and prepared statements to prevent SQL injections. As required, we kept the inventory and bookings tables completely clean without artificial seed data, allowing our Admin and Staff to manually input the council's actual resources and list down transactions as students arrive at the desk."*

### 3. "Where does authentication and user roles happen?"
> *"In `index.php` (around lines 25–90), we handle `action=login` and verify passwords using PHP's native `password_verify()`. If not logged in, the system displays only the secure Officer Sign In screen. Once authenticated, `$_SESSION['role']` determines permissions: `admin` gets full custody control (only Admins can add, edit, or delete equipment resources and manage staff accounts), while `staff` is strictly restricted to operational borrowing desk duties (checking out equipment, logging returns, rescheduling, and issuing gate passes)."*

### 4. "Show me the Desk Checkout process."
> *"When an officer is logged in, clicking the **'+ Desk Checkout'** button opens `#newBookingModal`. The officer selects the equipment (showing live stock availability), enters the borrower's Student ID, Name, Organization, dates, and purpose. Clicking submit automatically decrements stock, records the client, creates the booking, and generates a printable Custody Slip/Gate Pass via `receipt.php`."*

### 5. "Show me Rescheduling, Cancellations, and Payments."
> *"In the **Bookings tab**, each reservation has dedicated action buttons:
> - **Date (Reschedule):** Updates start/end dates and venue location.
> - **Cancel:** Records the reason for cancellation and restores the equipment stock back to inventory.
> - **Pay:** Sets payment/deposit status (`Free`, `Deposit Paid`, `Paid`, `Refunded`) and logs receipt details.
> - **Approve &rarr; Release &rarr; Return:** Moves the loan through its physical lifecycle."*

### 6. "How do your Booking Reports work?"
> *"Under the **Reports tab**, officers can generate the 3 reports required by the syllabus:
> 1. **Periodic Report:** Filter bookings between a Start Date and End Date.
> 2. **Per Resource Report:** Total borrowings and quantity used for each item (e.g. Speakers vs Volleyballs).
> 3. **Per Client Report:** Borrowing history per student and student organization.
> There is also a **Print Report** button for hard-copy submission."*
