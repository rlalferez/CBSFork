-- ==========================================================
-- CONFEDERATES STUDENT COUNCIL (CSC) - BORROWING SYSTEM
-- Database Schema (Refactored)
-- Database: confed_borrowing
-- ==========================================================
-- Instructions:
-- 1. Open http://localhost/phpmyadmin/index.php
-- 2. Click the "SQL" tab at the top
-- 3. Paste this entire script into the box and click "Go"
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `confed_borrowing` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `confed_borrowing`;

-- Temporarily disable foreign key constraints so we can drop tables cleanly
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. USER TABLE (Council Administrators & Committee Members)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `userID` VARCHAR(15) NOT NULL, -- e.g., USR-001
  `userFName` VARCHAR(50) NOT NULL,
  `userLName` VARCHAR(50) NOT NULL,
  `userEmail` VARCHAR(100) NOT NULL, -- Needed to open Gmail
  `userContactNo` VARCHAR(25) NOT NULL,
  `userRole` ENUM('Council', 'Committee') NOT NULL DEFAULT 'Committee',
  `userPassword` VARCHAR(255) NOT NULL, -- 255 for Bcrypt hashes
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete instead of hard delete
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. ITEM TABLE (Equipment Inventory)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `itemID` VARCHAR(15) NOT NULL, -- e.g., ITM-001
  `itemDesc` VARCHAR(150) NOT NULL, -- Name / Description of item
  `itemCategory` VARCHAR(50) NOT NULL,
  `itemTotalQty` INT NOT NULL DEFAULT 0, -- Will be incremented via purchases
  `itemAvailableQty` INT NOT NULL DEFAULT 0,
  `itemRate` DOUBLE NOT NULL DEFAULT 0.00, -- Rental Fee
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete flag
  PRIMARY KEY (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 3. BORROWER TABLE (Students and Organizations)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `borrower`;
CREATE TABLE `borrower` (
  `brwID` VARCHAR(15) NOT NULL, -- e.g., BRW-001
  `brwStudentID` VARCHAR(20) NOT NULL,
  `brwFName` VARCHAR(50) NOT NULL,
  `brwLName` VARCHAR(50) NOT NULL,
  `brwCollege` VARCHAR(100) NOT NULL,
  `brwOrg` VARCHAR(100) NULL, -- Added back for non-academic orgs
  `brwContactNo` VARCHAR(25) NOT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0, -- Soft delete flag
  PRIMARY KEY (`brwID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 4. BORROW TRANSACTION (Composite Key for multiple items)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `borrow_transaction`;
CREATE TABLE `borrow_transaction` (
  `brwTransID` VARCHAR(15) NOT NULL, -- e.g., TXN-001
  `itemID` VARCHAR(15) NOT NULL,     -- The item borrowed
  `userID` VARCHAR(15) NOT NULL,     -- Council/Committee member who processed this
  `brwID` VARCHAR(15) NOT NULL,      -- The borrower
  
  -- Snapshot data (saves info at the time of transaction)
  `brwTransItemQty` INT NOT NULL DEFAULT 1,
  `itemRate` DOUBLE NOT NULL, 
  
  -- Dates and Status
  `brwTransDate` DATE NOT NULL, -- Date the transaction was recorded
  `brwTransBorrowOnDate` DATE NOT NULL, -- When they took the item
  `brwTransReturnByDate` DATE NOT NULL, -- Deadline to return
  `brwTransPayStat` VARCHAR(20) DEFAULT 'Free',
  `brwTransTotal` DOUBLE DEFAULT 0.00,
  
  -- COMPOSITE PRIMARY KEY: Allows same transaction ID to have multiple different items
  PRIMARY KEY (`brwTransID`, `itemID`),
  CONSTRAINT `fk_borrow_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 5. RETURN TRANSACTION (Composite Key)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `return_transaction`;
CREATE TABLE `return_transaction` (
  `retTransID` VARCHAR(15) NOT NULL, -- e.g., RET-001
  `brwTransID` VARCHAR(15) NOT NULL, -- Links back to the borrow transaction
  `itemID` VARCHAR(15) NOT NULL,     -- The item returned
  `userID` VARCHAR(15) NOT NULL,     -- Staff who processed the return
  `brwID` VARCHAR(15) NOT NULL,      -- Borrower who returned it
  
  `brwTransQty` INT NOT NULL DEFAULT 1, -- Quantity returned
  `retReturnedOnDate` DATE NOT NULL,    -- Date returned
  
  -- COMPOSITE PRIMARY KEY
  PRIMARY KEY (`retTransID`, `itemID`),
  CONSTRAINT `fk_return_brwtrans` FOREIGN KEY (`brwTransID`, `itemID`) REFERENCES `borrow_transaction` (`brwTransID`, `itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 6. PURCHASE TRANSACTION (Restock Ledger)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `purchase_transaction`;
CREATE TABLE `purchase_transaction` (
  `purTransID` VARCHAR(15) NOT NULL, -- e.g., PUR-001
  `itemID` VARCHAR(15) NOT NULL,     -- The item being restocked (must exist in item table)
  `userID` VARCHAR(15) NOT NULL,     -- Council member who logged the purchase
  `purORNo` VARCHAR(50) NOT NULL,    -- Official Receipt Number
  `purQty` INT NOT NULL,             -- Quantity purchased
  `purDate` DATE NOT NULL,           -- Date of purchase
  
  PRIMARY KEY (`purTransID`),
  CONSTRAINT `fk_pur_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pur_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- DEFAULT INITIAL DATA
-- ==========================================================
-- Insert a default Administrator (Council) account
-- Password is 'admin123' hashed with bcrypt
INSERT INTO `user` (`userID`, `userFName`, `userLName`, `userEmail`, `userContactNo`, `userRole`, `userPassword`) 
VALUES (
  'USR-001',
  'Council',
  'Administrator',
  'admin@csc.edu.ph',
  '09123456789',
  'Council',
  '$2y$10$fhXdAJw791ZaaJAFJ52lReP4mAjmzfXNtnJ7sgN0Q6sMCsrYs2AzK'
);
