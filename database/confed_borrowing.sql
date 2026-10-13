CREATE DATABASE IF NOT EXISTS `confed_borrowing` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `confed_borrowing`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `return_transaction`;
DROP TABLE IF EXISTS `purchase_transaction`;
DROP TABLE IF EXISTS `borrow_transaction`;
DROP TABLE IF EXISTS `item`;
DROP TABLE IF EXISTS `borrower`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `userID` VARCHAR(15) NOT NULL,
  `userFName` VARCHAR(50) NOT NULL,
  `userLName` VARCHAR(50) NOT NULL,
  `userEmail` VARCHAR(100) NOT NULL,
  `userContactNo` VARCHAR(25) NOT NULL,
  `userRole` ENUM('Council', 'Committee') NOT NULL DEFAULT 'Committee',
  `userPassword` VARCHAR(255) NOT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `category` (
  `categoryID` INT AUTO_INCREMENT PRIMARY KEY,
  `categoryName` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `category` (`categoryName`) VALUES 
('Electronics'), ('Audio & Visual'), ('Furniture'), ('Sports'), ('Event Materials'), ('Office Supplies');

CREATE TABLE `item` (
  `itemID` VARCHAR(15) NOT NULL,
  `itemDesc` VARCHAR(150) NOT NULL,
  `itemCategory` VARCHAR(50) NOT NULL,
  `itemTotalQty` INT NOT NULL DEFAULT 0,
  `itemAvailableQty` INT NOT NULL DEFAULT 0,
  `itemRate` DOUBLE NOT NULL DEFAULT 0.00,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `borrower` (
  `brwID` VARCHAR(15) NOT NULL,
  `brwStudentID` VARCHAR(20) NOT NULL,
  `brwFName` VARCHAR(50) NOT NULL,
  `brwLName` VARCHAR(50) NOT NULL,
  `brwCollege` VARCHAR(100) NOT NULL,
  `brwOrg` VARCHAR(100) NULL,
  `brwContactNo` VARCHAR(25) NOT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`brwID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `borrow_transaction` (
  `brwTransID` VARCHAR(15) NOT NULL,
  `itemID` VARCHAR(15) NOT NULL,
  `userID` VARCHAR(15) NOT NULL,
  `brwID` VARCHAR(15) NOT NULL,
  `brwTransItemQty` INT NOT NULL DEFAULT 1,
  `itemRate` DOUBLE NOT NULL, 
  `brwTransDate` DATE NOT NULL,
  `brwTransBorrowOnDate` DATE NOT NULL,
  `brwTransReturnByDate` DATE NOT NULL,
  `brwTransPayStat` VARCHAR(20) DEFAULT 'Free',
  `brwTransTotal` DOUBLE DEFAULT 0.00,
  `brwTransStatus` VARCHAR(20) NOT NULL DEFAULT 'Released',
  PRIMARY KEY (`brwTransID`, `itemID`),
  CONSTRAINT `fk_borrow_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_borrow_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `return_transaction` (
  `retTransID` VARCHAR(15) NOT NULL,
  `brwTransID` VARCHAR(15) NOT NULL,
  `itemID` VARCHAR(15) NOT NULL,
  `userID` VARCHAR(15) NOT NULL,
  `brwID` VARCHAR(15) NOT NULL,
  `brwTransQty` INT NOT NULL DEFAULT 1,
  `retReturnedOnDate` DATE NOT NULL,
  PRIMARY KEY (`retTransID`, `itemID`),
  CONSTRAINT `fk_return_brwtrans` FOREIGN KEY (`brwTransID`, `itemID`) REFERENCES `borrow_transaction` (`brwTransID`, `itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_brw` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_return_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `purchase_transaction` (
  `purTransID` VARCHAR(15) NOT NULL,
  `itemID` VARCHAR(15) NOT NULL,
  `userID` VARCHAR(15) NOT NULL,
  `purORNo` VARCHAR(50) NOT NULL,
  `purQty` INT NOT NULL,
  `purDate` DATE NOT NULL,
  PRIMARY KEY (`purTransID`),
  CONSTRAINT `fk_pur_item` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pur_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

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

INSERT INTO `user` (`userID`, `userFName`, `userLName`, `userEmail`, `userContactNo`, `userRole`, `userPassword`) 
VALUES (
  'USR-002',
  'Committee',
  'Officer',
  'committee@csc.edu.ph',
  '09987654321',
  'Committee',
  '$2y$10$sUSc1V9yUcuZU6ST2h1KTu7Raf/k63wXoPFQiMi3diz1VAT09jRsO'
);
