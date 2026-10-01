-- ==========================================================
-- CONFEDERATES STUDENT COUNCIL (CSC) - BORROWING SYSTEM
-- Mock Database Data
-- ==========================================================

USE confed_borrowing;

-- Users
INSERT IGNORE INTO user (userID, userFName, userLName, userEmail, userContactNo, userRole, userPassword, is_archived) VALUES
('USR-001', 'Admin', 'User', 'admin@example.com', '09123456789', 'Council', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('USR-002', 'Staff', 'Member', 'staff@example.com', '09987654321', 'Committee', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Items
INSERT IGNORE INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate, is_archived) VALUES
('ITM-001', 'Projector EPSON X39', 'Electronics', 5, 4, 150.00, 0),
('ITM-002', 'Extension Cord 10m', 'Accessories', 10, 8, 20.00, 0),
('ITM-003', 'Portable Sound System', 'Electronics', 2, 2, 300.00, 0),
('ITM-004', 'Monoblock Chair', 'Furniture', 50, 40, 5.00, 0),
('ITM-005', 'Foldable Table', 'Furniture', 10, 10, 50.00, 0);
INSERT IGNORE INTO item (itemID, itemDesc, itemCategory, itemTotalQty, itemAvailableQty, itemRate, is_archived) VALUES 
('ITM-006', 'Mock Item 6', 'Category 6', 43, 43, 10.00, 0),
('ITM-007', 'Mock Item 7', 'Category 7', 17, 17, 10.00, 0),
('ITM-008', 'Mock Item 8', 'Category 8', 9, 9, 10.00, 0),
('ITM-009', 'Mock Item 9', 'Category 9', 22, 22, 10.00, 0),
('ITM-010', 'Mock Item 10', 'Category 10', 41, 41, 10.00, 0),
('ITM-011', 'Mock Item 11', 'Category 11', 21, 21, 10.00, 0),
('ITM-012', 'Mock Item 12', 'Category 12', 25, 25, 10.00, 0),
('ITM-013', 'Mock Item 13', 'Category 13', 21, 21, 10.00, 0),
('ITM-014', 'Mock Item 14', 'Category 14', 38, 38, 10.00, 0),
('ITM-015', 'Mock Item 15', 'Category 15', 44, 44, 10.00, 0),
('ITM-016', 'Mock Item 16', 'Category 16', 38, 38, 10.00, 0),
('ITM-017', 'Mock Item 17', 'Category 17', 11, 11, 10.00, 0),
('ITM-018', 'Mock Item 18', 'Category 18', 12, 12, 10.00, 0),
('ITM-019', 'Mock Item 19', 'Category 19', 8, 8, 10.00, 0),
('ITM-020', 'Mock Item 20', 'Category 20', 14, 14, 10.00, 0),
('ITM-021', 'Mock Item 21', 'Category 21', 24, 24, 10.00, 0),
('ITM-022', 'Mock Item 22', 'Category 22', 40, 40, 10.00, 0),
('ITM-023', 'Mock Item 23', 'Category 23', 19, 19, 10.00, 0),
('ITM-024', 'Mock Item 24', 'Category 24', 37, 37, 10.00, 0),
('ITM-025', 'Mock Item 25', 'Category 25', 49, 49, 10.00, 0),
('ITM-026', 'Mock Item 26', 'Category 26', 36, 36, 10.00, 0),
('ITM-027', 'Mock Item 27', 'Category 27', 17, 17, 10.00, 0),
('ITM-028', 'Mock Item 28', 'Category 28', 47, 47, 10.00, 0),
('ITM-029', 'Mock Item 29', 'Category 29', 25, 25, 10.00, 0),
('ITM-030', 'Mock Item 30', 'Category 30', 22, 22, 10.00, 0),
('ITM-031', 'Mock Item 31', 'Category 31', 33, 33, 10.00, 0),
('ITM-032', 'Mock Item 32', 'Category 32', 31, 31, 10.00, 0),
('ITM-033', 'Mock Item 33', 'Category 33', 29, 29, 10.00, 0),
('ITM-034', 'Mock Item 34', 'Category 34', 48, 48, 10.00, 0),
('ITM-035', 'Mock Item 35', 'Category 35', 38, 38, 10.00, 0);

-- Borrowers
INSERT IGNORE INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo, is_archived) VALUES
('BRW-001', '2023-0001', 'John', 'Doe', 'CCS', 'CSG', '09111111111', 0),
('BRW-002', '2023-0002', 'Jane', 'Smith', 'CBA', 'JMA', '09222222222', 0),
('BRW-003', '2022-0105', 'Alice', 'Johnson', 'CNAHS', 'NURSING', '09333333333', 0);
INSERT IGNORE INTO borrower (brwID, brwStudentID, brwFName, brwLName, brwCollege, brwOrg, brwContactNo, is_archived) VALUES 
('BRW-004', '2023-4', 'Student4', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-005', '2023-5', 'Student5', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-006', '2023-6', 'Student6', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-007', '2023-7', 'Student7', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-008', '2023-8', 'Student8', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-009', '2023-9', 'Student9', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-010', '2023-10', 'Student10', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-011', '2023-11', 'Student11', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-012', '2023-12', 'Student12', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-013', '2023-13', 'Student13', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-014', '2023-14', 'Student14', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-015', '2023-15', 'Student15', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-016', '2023-16', 'Student16', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-017', '2023-17', 'Student17', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-018', '2023-18', 'Student18', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-019', '2023-19', 'Student19', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-020', '2023-20', 'Student20', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-021', '2023-21', 'Student21', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-022', '2023-22', 'Student22', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-023', '2023-23', 'Student23', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-024', '2023-24', 'Student24', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-025', '2023-25', 'Student25', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-026', '2023-26', 'Student26', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-027', '2023-27', 'Student27', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-028', '2023-28', 'Student28', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-029', '2023-29', 'Student29', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-030', '2023-30', 'Student30', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-031', '2023-31', 'Student31', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-032', '2023-32', 'Student32', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-033', '2023-33', 'Student33', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-034', '2023-34', 'Student34', 'Mock', 'College', 'Org', '09000000000', 0),
('BRW-035', '2023-35', 'Student35', 'Mock', 'College', 'Org', '09000000000', 0);

-- Borrow Transactions
INSERT IGNORE INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal) VALUES
('TXN-001', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-002', 'ITM-002', 'USR-001', 'BRW-001', 2, 20.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 40.00),
('TXN-003', 'ITM-004', 'USR-002', 'BRW-002', 10, 5.00, '2023-10-15', '2023-10-15', '2023-10-16', 'Free', 50.00);
INSERT IGNORE INTO borrow_transaction (brwTransID, itemID, userID, brwID, brwTransItemQty, itemRate, brwTransDate, brwTransBorrowOnDate, brwTransReturnByDate, brwTransPayStat, brwTransTotal) VALUES 
('TXN-004', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-005', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-006', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-007', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-008', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-009', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-010', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-011', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-012', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-013', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-014', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-015', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-016', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-017', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-018', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-019', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-020', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-021', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-022', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-023', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-024', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-025', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-026', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-027', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-028', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-029', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-030', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-031', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-032', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-033', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-034', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00),
('TXN-035', 'ITM-001', 'USR-001', 'BRW-001', 1, 150.00, '2023-10-01', '2023-10-01', '2023-10-02', 'Paid', 150.00);

-- Return Transactions
INSERT IGNORE INTO return_transaction (retTransID, brwTransID, itemID, userID, brwID, brwTransQty, retReturnedOnDate) VALUES
('RET-001', 'TXN-001', 'ITM-001', 'USR-001', 'BRW-001', 1, '2023-10-02'),
('RET-002', 'TXN-002', 'ITM-002', 'USR-001', 'BRW-001', 2, '2023-10-02');

-- Purchase Transactions
INSERT IGNORE INTO purchase_transaction (purTransID, itemID, userID, purORNo, purQty, purDate) VALUES
('PUR-001', 'ITM-001', 'USR-001', 'OR-998877', 5, '2023-01-10'),
('PUR-002', 'ITM-002', 'USR-001', 'OR-112233', 10, '2023-01-15');


