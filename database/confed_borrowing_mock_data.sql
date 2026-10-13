CREATE DATABASE IF NOT EXISTS `confed_borrowing` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `confed_borrowing`;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `return_transaction`;
DELETE FROM `borrow_transaction`;
DELETE FROM `purchase_transaction`;
DELETE FROM `item`;
DELETE FROM `borrower`;
DELETE FROM `category`;
DELETE FROM `user`;

INSERT INTO `category` (`categoryID`, `categoryName`) VALUES
(1, 'Electronics'),
(2, 'Audio & Visual'),
(3, 'Furniture'),
(4, 'Sports'),
(5, 'Event Materials'),
(6, 'Office Supplies');

INSERT INTO `user` (`userID`, `userFName`, `userLName`, `userEmail`, `userContactNo`, `userRole`, `userPassword`, `is_archived`) VALUES
('USR-001', 'Council', 'Administrator', 'admin@csc.edu.ph', '09123456789', 'Council', '$2y$10$fhXdAJw791ZaaJAFJ52lReP4mAjmzfXNtnJ7sgN0Q6sMCsrYs2AzK', 0),
('USR-002', 'Committee', 'Officer', 'committee@csc.edu.ph', '09987654321', 'Committee', '$2y$10$sUSc1V9yUcuZU6ST2h1KTu7Raf/k63wXoPFQiMi3diz1VAT09jRsO', 0),
('USR-003', 'Desk', 'Staff', 'staff@csc.edu.ph', '09171122334', 'Committee', '$2y$10$t2hGPp9xSjKmJT8p1/r.Wu01F4pC9UqpsvIciYv1xgU57jVjGA/Mm', 0);

INSERT INTO `borrower` (`brwID`, `brwStudentID`, `brwFName`, `brwLName`, `brwCollege`, `brwOrg`, `brwContactNo`, `is_archived`) VALUES
('BRW-001', '23-1-01451', 'Juan', 'Dela Cruz', 'CCS', 'CSS', '09171234567', 0),
('BRW-002', '23-1-02384', 'Maria', 'Santos', 'CBA', 'JMA', '09182345678', 0),
('BRW-003', '24-1-00912', 'Carlo', 'Reyes', 'CAS', 'Biology Society', '09203456789', 0),
('BRW-004', '22-2-03150', 'Angelica', 'Flores', 'CED', 'UAPSA', '09214567890', 0),
('BRW-005', '23-1-04521', 'Gabriel', 'Ramos', 'COE', 'Pink Panthers', '09225678901', 0),
('BRW-006', '24-1-01188', 'Patricia', 'Lim', 'CON', 'Red Cross Youth', '09236789012', 0),
('BRW-007', '22-1-05432', 'Mark', 'Bautista', 'CCS', 'CSS', '09247890123', 0),
('BRW-008', '23-2-02199', 'Alyssa', 'Valdez', 'CAS', 'University Athletic Council', '09258901234', 0);

INSERT INTO `item` (`itemID`, `itemDesc`, `itemCategory`, `itemTotalQty`, `itemAvailableQty`, `itemRate`, `is_archived`) VALUES
('ITM-001', 'Projector EPSON EB-X49', 'Electronics', 5, 4, 150.00, 0),
('ITM-002', 'Extension Cord Heavy Duty (10m)', 'Electronics', 10, 8, 20.00, 0),
('ITM-003', 'HDMI to USB-C / VGA Display Adapter', 'Electronics', 6, 6, 15.00, 0),
('ITM-004', 'Wireless Presentation Laser Pointer', 'Electronics', 4, 4, 25.00, 0),
('ITM-005', 'Portable PA Sound System with Bluetooth', 'Audio & Visual', 3, 2, 250.00, 0),
('ITM-006', 'Dual Wireless UHF Microphones', 'Audio & Visual', 4, 3, 50.00, 0),
('ITM-007', 'Professional Rechargeable Megaphone', 'Audio & Visual', 4, 4, 35.00, 0),
('ITM-008', 'Monoblock Chairs (Stackable White)', 'Furniture', 50, 40, 5.00, 0),
('ITM-009', 'Foldable Seminar Table (6ft Heavy Duty)', 'Furniture', 8, 8, 50.00, 0),
('ITM-010', 'Official Molten Basketball', 'Sports', 6, 4, 20.00, 0),
('ITM-011', 'Official Mikasa Volleyball', 'Sports', 6, 6, 20.00, 0),
('ITM-012', 'Pickleball Paddle & Ball Set', 'Sports', 8, 8, 25.00, 0),
('ITM-013', 'Badminton Racket Pair with Shuttlecocks', 'Sports', 6, 6, 20.00, 0),
('ITM-014', 'Red Carpet Ceremonial Runner (10m)', 'Event Materials', 2, 2, 100.00, 0),
('ITM-015', 'Chrome Stanchion Posts with Velvet Rope', 'Event Materials', 8, 8, 40.00, 0),
('ITM-016', 'Heavy Duty 2-Hole Desktop Paper Puncher', 'Office Supplies', 5, 5, 0.00, 0),
('ITM-017', 'Heavy Duty Plier Stapler with Staples', 'Office Supplies', 6, 6, 0.00, 0),
('ITM-018', 'Dry-Erase Whiteboard Marker Set (4 Colors)', 'Office Supplies', 15, 15, 0.00, 0);

INSERT INTO `purchase_transaction` (`purTransID`, `itemID`, `userID`, `purORNo`, `purQty`, `purDate`) VALUES
('PUR-001', 'ITM-001', 'USR-001', 'OR-2024-00101', 5, '2024-01-15'),
('PUR-002', 'ITM-002', 'USR-001', 'OR-2024-00102', 10, '2024-01-15'),
('PUR-003', 'ITM-003', 'USR-001', 'OR-2024-00103', 6, '2024-01-16'),
('PUR-004', 'ITM-004', 'USR-001', 'OR-2024-00104', 4, '2024-01-18'),
('PUR-005', 'ITM-005', 'USR-001', 'OR-2024-00201', 3, '2024-02-05'),
('PUR-006', 'ITM-006', 'USR-001', 'OR-2024-00202', 4, '2024-02-05'),
('PUR-007', 'ITM-007', 'USR-001', 'OR-2024-00203', 4, '2024-02-08'),
('PUR-008', 'ITM-008', 'USR-001', 'OR-2024-00301', 50, '2024-03-01'),
('PUR-009', 'ITM-009', 'USR-001', 'OR-2024-00302', 8, '2024-03-01'),
('PUR-010', 'ITM-010', 'USR-001', 'OR-2024-00401', 6, '2024-04-10'),
('PUR-011', 'ITM-011', 'USR-001', 'OR-2024-00402', 6, '2024-04-10'),
('PUR-012', 'ITM-012', 'USR-001', 'OR-2024-00403', 8, '2024-04-12'),
('PUR-013', 'ITM-013', 'USR-001', 'OR-2024-00404', 6, '2024-04-12'),
('PUR-014', 'ITM-014', 'USR-001', 'OR-2024-00501', 2, '2024-05-02'),
('PUR-015', 'ITM-015', 'USR-001', 'OR-2024-00502', 8, '2024-05-02'),
('PUR-016', 'ITM-016', 'USR-001', 'OR-2024-00601', 5, '2024-06-10'),
('PUR-017', 'ITM-017', 'USR-001', 'OR-2024-00602', 6, '2024-06-10'),
('PUR-018', 'ITM-018', 'USR-001', 'OR-2024-00603', 15, '2024-06-12');

INSERT INTO `borrow_transaction` (`brwTransID`, `itemID`, `userID`, `brwID`, `brwTransItemQty`, `itemRate`, `brwTransDate`, `brwTransBorrowOnDate`, `brwTransReturnByDate`, `brwTransPayStat`, `brwTransTotal`, `brwTransStatus`) VALUES
('TXN-001', 'ITM-001', 'USR-002', 'BRW-001', 1, 150.00, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Paid', 150.00, 'Released'),
('TXN-002', 'ITM-002', 'USR-002', 'BRW-002', 2, 20.00, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Paid', 40.00, 'Released'),
('TXN-002', 'ITM-006', 'USR-002', 'BRW-002', 1, 50.00, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Paid', 50.00, 'Released'),
('TXN-003', 'ITM-005', 'USR-001', 'BRW-003', 1, 250.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Paid', 250.00, 'Released'),
('TXN-004', 'ITM-008', 'USR-002', 'BRW-004', 10, 5.00, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Paid', 50.00, 'Released'),
('TXN-005', 'ITM-010', 'USR-002', 'BRW-008', 2, 20.00, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Paid', 40.00, 'Released'),
('TXN-006', 'ITM-009', 'USR-002', 'BRW-005', 2, 50.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'Paid', 100.00, 'Reserved'),
('TXN-007', 'ITM-001', 'USR-001', 'BRW-007', 1, 150.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Paid', 150.00, 'Reserved'),
('TXN-008', 'ITM-007', 'USR-001', 'BRW-006', 1, 35.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'Free', 0.00, 'Cancelled'),
('TXN-009', 'ITM-004', 'USR-002', 'BRW-001', 1, 25.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Paid', 25.00, 'Returned'),
('TXN-010', 'ITM-012', 'USR-002', 'BRW-008', 2, 25.00, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Paid', 50.00, 'Returned');

INSERT INTO `return_transaction` (`retTransID`, `brwTransID`, `itemID`, `userID`, `brwID`, `brwTransQty`, `retReturnedOnDate`) VALUES
('RET-001', 'TXN-009', 'ITM-004', 'USR-002', 'BRW-001', 1, DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
('RET-002', 'TXN-010', 'ITM-012', 'USR-002', 'BRW-008', 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

SET FOREIGN_KEY_CHECKS = 1;
