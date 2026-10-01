-- ==========================================================
-- Confederates Student Council - Resource Management System
-- Database Schema & Seed Data (Admin & Staff Roles, Full CRUD)
-- ==========================================================

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'box',
    description TEXT
);

CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    model VARCHAR(100) DEFAULT 'Standard',
    description TEXT,
    total_qty INTEGER NOT NULL DEFAULT 1,
    available_qty INTEGER NOT NULL DEFAULT 1,
    is_available INTEGER NOT NULL DEFAULT 1, -- 1 = Available for booking, 0 = Inactive / Out of Service
    location VARCHAR(100) DEFAULT 'Council Office - Room 204',
    condition_status VARCHAR(50) DEFAULT 'Good', -- Excellent, Good, Fair, Under Maintenance
    fee_type VARCHAR(50) DEFAULT 'Free', -- Free, Deposit Required, Rental Fee
    fee_amount DECIMAL(10,2) DEFAULT 0.00,
    image_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS borrowers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    organization_name VARCHAR(150) NOT NULL,
    role VARCHAR(50) DEFAULT 'Student',
    status VARCHAR(50) DEFAULT 'Active', -- Active, Suspended
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    contact_number VARCHAR(50),
    role VARCHAR(50) NOT NULL DEFAULT 'staff', -- 'admin' or 'staff'
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS borrow_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tracking_code VARCHAR(50) NOT NULL UNIQUE,
    borrower_id INTEGER NOT NULL,
    created_by_user_id INTEGER NULL,
    purpose TEXT NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    event_location VARCHAR(150) NOT NULL,
    borrow_date DATE NOT NULL,
    expected_return_date DATE NOT NULL,
    actual_return_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending', -- Pending, Approved, Released, Returned, Cancelled, Overdue
    payment_status VARCHAR(50) DEFAULT 'Free / Waived', -- Free / Waived, Pending Deposit, Deposit Paid, Paid, Refunded
    payment_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_details TEXT,
    admin_notes TEXT,
    approved_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrower_id) REFERENCES borrowers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS borrow_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    borrow_request_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    return_condition VARCHAR(50) DEFAULT 'Good',
    notes TEXT,
    FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    actor VARCHAR(100) DEFAULT 'System',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Seed Categories
INSERT OR IGNORE INTO categories (id, name, icon, description) VALUES
(1, 'Audio & Visual', 'mic', 'Speakers, projectors, wireless microphones, sound systems, and PA equipment.'),
(2, 'Sports & Recreation', 'trophy', 'Basketballs, volleyballs, badminton sets, nets, whistles, and digital scoreboards.'),
(3, 'Event & Stage Logistics', 'tent', 'Tents, canopy covers, folding tables, monobloc chairs, and official rostrum.'),
(4, 'Electronics & Cables', 'cpu', 'Extension cords, high-speed HDMI cables, power strips, and display adapters.'),
(5, 'Office & Protocol Assets', 'briefcase', 'Walkie-talkies, megaphones, trauma emergency kits, and council banners.');

-- Seed Resource Equipment (Featuring Speakers, Sports Equipment, and Event Assets)
INSERT OR IGNORE INTO items (id, category_id, item_code, name, model, description, total_qty, available_qty, is_available, location, condition_status, fee_type, fee_amount, image_url) VALUES
(1, 1, 'CSC-SPK-01', 'JBL EON One Portable PA Column Speaker', 'EON One Pro', 'Rechargeable 250W linear-array speaker with 7-channel mixer and Bluetooth.', 3, 2, 1, 'Audio Rack A1', 'Excellent', 'Deposit Required', 500.00, 'speaker'),
(2, 1, 'CSC-SPK-02', 'Yamaha StagePas 400BT Portable Sound System', 'StagePas 400BT', '400W powered dual speakers with 8-channel mixer and 2 heavy speaker stands.', 2, 1, 1, 'Audio Rack A2', 'Good', 'Deposit Required', 1000.00, 'speaker'),
(3, 1, 'CSC-AV-01', 'Epson 1080p High-Lumen Projector', 'EB-FH52 Full HD', '4000 lumens 3LCD projector with HDMI/VGA/Wireless inputs and carry case.', 4, 3, 1, 'AV Cabinet 1', 'Excellent', 'Deposit Required', 300.00, 'projector'),
(4, 1, 'CSC-AV-02', 'Shure Dual Wireless Handheld Microphones', 'BLX288/PG58', 'Dual-channel UHF wireless microphone kit with receiver and batteries.', 5, 4, 1, 'AV Cabinet 2', 'Excellent', 'Free', 0.00, 'mic'),
(5, 1, 'CSC-AV-03', 'Logitech Wireless Presentation Clicker', 'R800 Green Laser', 'Wireless presenter with green laser pointer and LCD timer display.', 6, 5, 1, 'Drawers Unit 2', 'Good', 'Free', 0.00, 'clicker'),
(6, 2, 'CSC-SP-01', 'Molten BG5000 Official Competition Basketball', 'B7G5000 Size 7', 'FIBA-approved genuine leather tournament indoor/outdoor basketball.', 8, 6, 1, 'Sports Locker 1', 'Excellent', 'Free', 0.00, 'basketball'),
(7, 2, 'CSC-SP-02', 'Mikasa V200W Official Tournament Volleyball', 'V200W Size 5', 'FIVB official game ball with aerodynamic 18-panel dimple design.', 8, 7, 1, 'Sports Locker 2', 'Excellent', 'Free', 0.00, 'volleyball'),
(8, 2, 'CSC-SP-03', 'Yonex Nanoray Badminton Racket & Net Set', 'Nanoray 10F Kit', 'Includes 4 tournament rackets, 2 tubes shuttles, and portable regulation net.', 4, 3, 1, 'Sports Locker 3', 'Good', 'Free', 0.00, 'trophy'),
(9, 2, 'CSC-SP-04', 'Digital Multi-Sport Tabletop Scoreboard', 'Pro-Scoreboard D2', 'LED scoreboard with horn, timer, period indicator, and remote control.', 2, 2, 1, 'Sports Locker 4', 'Excellent', 'Deposit Required', 200.00, 'trophy'),
(10, 3, 'CSC-ST-01', 'Heavy-Duty 3x3m Pop-Up Canopy Tent', 'ShelterMax Pro 3x3', 'Waterproof outdoor pop-up tent with steel frame and weight sandbags.', 6, 4, 1, 'Storage Bay 3', 'Good', 'Deposit Required', 250.00, 'tent'),
(11, 3, 'CSC-ST-02', 'Lifetime 6ft Foldable Banquet Tables', 'Lifetime 80160', 'Heavy-duty granite white plastic folding table with lock mechanism.', 20, 15, 1, 'Storage Bay 1', 'Good', 'Free', 0.00, 'table'),
(12, 3, 'CSC-ST-03', 'White Monobloc Event Chairs (Set of 10)', 'Uratex Olympia', 'Durable high-impact virgin plastic stackable chairs.', 15, 12, 1, 'Storage Bay 2', 'Good', 'Free', 0.00, 'chair'),
(13, 3, 'CSC-ST-04', 'Official Mahogany Podium / Rostrum', 'Council Rostrum 01', 'Executive wooden speaking podium featuring student council crest mount.', 2, 2, 1, 'Council Room Front', 'Excellent', 'Free', 0.00, 'podium'),
(14, 4, 'CSC-EL-01', 'Heavy-Duty 20m Extension Cable Reel', 'Omni Pro Reel 20M', 'Surge-protected 4-gang grounded industrial extension cable reel.', 10, 7, 1, 'Electronics Shelf', 'Good', 'Free', 0.00, 'cable'),
(15, 4, 'CSC-EL-02', 'High-Speed Braided 4K HDMI Cable (10m)', 'UGreen 10M 4K', 'Gold-plated braided high-speed display cable for stage and auditoriums.', 8, 7, 1, 'Electronics Shelf', 'Excellent', 'Free', 0.00, 'hdmi'),
(16, 5, 'CSC-OF-01', 'Motorola Long-Range Walkie-Talkies (Set of 4)', 'Talkabout T82', 'IPx4 weatherproof two-way radios with 16 channels and desktop chargers.', 4, 3, 1, 'Safety Locker A', 'Excellent', 'Deposit Required', 300.00, 'radio'),
(17, 5, 'CSC-OF-02', 'Heavy-Duty Megaphone with Siren & Recording', 'Pyle 50W Pro', 'High-power 50-watt voice megaphone with siren and detachable mic.', 4, 3, 1, 'Safety Locker B', 'Good', 'Free', 0.00, 'megaphone'),
(18, 5, 'CSC-OF-03', 'Emergency Response Trauma First Aid Kit', 'MedKit Pro-50', 'Comprehensive first aid medical kit in emergency waterproof bag.', 3, 3, 1, 'Medical Cabinet', 'Excellent', 'Free', 0.00, 'firstaid');

-- Seed Users: Admin & Staff (Passwords are hashed for 'admin123' and 'staff123')
INSERT OR IGNORE INTO users (id, username, password_hash, full_name, email, contact_number, role, status) VALUES
(1, 'admin', '$2y$10$Wp8uO30Q5lF7jJ7qjFZeveW3t8bCcmH6d4uK7YwO7GvhJ2XyT7Qye', 'Chief Council Administrator', 'admin@council.edu', '0917-000-0001', 'admin', 'active'),
(2, 'staff1', '$2y$10$Wp8uO30Q5lF7jJ7qjFZeveW3t8bCcmH6d4uK7YwO7GvhJ2XyT7Qye', 'Sarah Jenkins (Equipment Staff)', 'sarah.jenkins@council.edu', '0917-000-0002', 'staff', 'active'),
(3, 'staff2', '$2y$10$Wp8uO30Q5lF7jJ7qjFZeveW3t8bCcmH6d4uK7YwO7GvhJ2XyT7Qye', 'Kevin Rivera (Council Officer)', 'kevin.rivera@council.edu', '0917-000-0003', 'staff', 'active');

-- Seed Clients / Borrowers
INSERT OR IGNORE INTO borrowers (id, student_id, full_name, email, contact_number, organization_name, role, status, notes) VALUES
(1, '2024-00142', 'Marcus Aaron Cruz', 'marcus.cruz@university.edu', '0917-555-0192', 'College of Engineering Council', 'President', 'Active', 'Official student college council.'),
(2, '2023-01893', 'Samantha Jane Reyes', 'samantha.reyes@university.edu', '0928-555-8831', 'Debate & Forensics Society', 'President', 'Active', 'Accredited university debate team.'),
(3, '2025-04120', 'Liam Gabriel Gomez', 'liam.gomez@university.edu', '0995-555-3419', 'Council Sports Committee', 'Head Coordinator', 'Active', 'Internal council sports intramurals committee.'),
(4, '2024-09931', 'Chloe Andrea Santos', 'chloe.santos@university.edu', '0908-555-7762', 'Junior Marketing Association', 'Logistics Head', 'Active', 'Business administration student club.'),
(5, '2025-08819', 'Angelo Rafael Morales', 'angelo.morales@university.edu', '0919-555-9081', 'Society of Performing Artists', 'Stage Manager', 'Active', 'Drama, dance, and music guild.');

-- Seed Bookings with Payment Statuses & Roles
INSERT OR IGNORE INTO borrow_requests (id, tracking_code, borrower_id, created_by_user_id, purpose, event_name, event_location, borrow_date, expected_return_date, actual_return_date, status, payment_status, payment_amount, payment_details, admin_notes, approved_by, created_at) VALUES
(1, 'CSC-2026-8941', 1, 2, 'Colloquium sound system & projection setup', 'EngTech Summit 2026', 'Engineering Auditorium', '2026-09-27', '2026-09-29', NULL, 'Released', 'Deposit Paid', 500.00, 'Official Receipt #OR-9912. 500 PHP security deposit held by Council Treasurer.', 'Approved by Staff Sarah. Valid ID presented.', 'Sarah Jenkins', '2026-09-26 10:15:00'),
(2, 'CSC-2026-7732', 2, 2, 'Audio equipment for debate championship debate rooms', 'Veritas Parliamentary Cup', 'Liberal Arts Hall 301-305', '2026-09-30', '2026-10-02', NULL, 'Approved', 'Free / Waived', 0.00, 'University academic contest - fee waived by Council resolution.', 'Approved for 2-day use.', 'Sarah Jenkins', '2026-09-27 14:30:00'),
(3, 'CSC-2026-6129', 3, 3, 'Basketball tryouts and friendly games', 'Inter-College Basketball Friendly', 'Main Gymnasium Court 1', '2026-10-01', '2026-10-01', NULL, 'Pending', 'Free / Waived', 0.00, 'Student organization internal use.', 'Awaiting facility reservation signoff.', NULL, '2026-09-28 09:10:00'),
(4, 'CSC-2026-5084', 4, 1, 'Exhibitor booth tables & monobloc chairs setup', 'MarketVibe Expo 2026', 'Campus Boulevard Covered Walkway', '2026-09-22', '2026-09-25', '2026-09-25', 'Returned', 'Refunded', 250.00, 'Security deposit refunded upon clean inspection.', 'All items returned in mint condition.', 'Chief Admin', '2026-09-20 16:45:00'),
(5, 'CSC-2026-3199', 5, 2, 'Music rehearsals for annual musical gala', 'Gala Musical Rehearsals', 'Amphitheater Stage', '2026-09-24', '2026-09-26', NULL, 'Overdue', 'Deposit Paid', 1000.00, 'Deposit OR #9901.', 'Equipment not yet surrendered. Followed up with stage manager.', 'Sarah Jenkins', '2026-09-23 11:00:00');

-- Seed Booking Items
INSERT OR IGNORE INTO borrow_items (id, borrow_request_id, item_id, quantity, return_condition, notes) VALUES
(1, 1, 1, 1, 'Pending', 'JBL EON One Speaker unit'),
(2, 1, 3, 1, 'Pending', 'Epson 1080p Projector'),
(3, 1, 11, 4, 'Pending', 'Folding banquet tables'),
(4, 2, 2, 1, 'Pending', 'Yamaha StagePas 400BT Sound System with stands'),
(5, 2, 4, 2, 'Pending', 'Shure Dual Wireless Microphones'),
(6, 3, 6, 2, 'Pending', 'Molten BG5000 Basketballs'),
(7, 4, 11, 4, 'Good', '4 Tables returned in good order'),
(8, 4, 12, 2, 'Good', '20 Monobloc chairs returned complete'),
(9, 5, 2, 1, 'Pending', 'Yamaha StagePas 400BT (Overdue for return)');

-- Seed Activity Logs
INSERT OR IGNORE INTO activity_logs (action, details, actor, created_at) VALUES
('System Seeded', 'Resource Management System database initialized with speakers, sports gear, and logistics.', 'System', '2026-09-26 08:00:00'),
('Booking Released', 'Booking CSC-2026-8941 released to Marcus Aaron Cruz with 500 PHP deposit logged.', 'Sarah Jenkins', '2026-09-27 08:45:00'),
('Booking Approved', 'Booking CSC-2026-7732 approved for Debate Society.', 'Sarah Jenkins', '2026-09-27 15:00:00'),
('Booking Returned', 'Booking CSC-2026-5084 returned in good condition. Deposit refunded.', 'Chief Admin', '2026-09-25 17:30:00');
