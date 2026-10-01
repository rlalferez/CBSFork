-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2026 at 04:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csc_item_borrowing`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrower`
--

CREATE TABLE `borrower` (
  `brwID` char(8) NOT NULL,
  `brwStudentID` varchar(10) NOT NULL,
  `brwFName` varchar(20) NOT NULL,
  `brwLName` varchar(20) NOT NULL,
  `brwCollege` varchar(20) NOT NULL,
  `brwContactNo` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transaction`
--

CREATE TABLE `borrow_transaction` (
  `brwTransID` char(8) NOT NULL,
  `userID` char(8) NOT NULL,
  `brwID` char(8) NOT NULL,
  `itemID` char(8) NOT NULL,
  `itemDesc` varchar(20) NOT NULL,
  `brwTransItemQty` int(11) NOT NULL,
  `itemRate` double NOT NULL,
  `brwTransDate` date NOT NULL,
  `brwTransBorrowOnDate` date NOT NULL,
  `brwTransReturnByDate` date NOT NULL,
  `brwTransPayStat` varchar(7) DEFAULT NULL,
  `brwTransTotal` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `itemID` char(8) NOT NULL,
  `itemDesc` varchar(20) NOT NULL,
  `itemCategory` varchar(20) NOT NULL,
  `itemTotalQty` int(11) NOT NULL,
  `itemAvailableQty` int(11) NOT NULL,
  `itemRate` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_transaction`
--

CREATE TABLE `purchase_transaction` (
  `purTransID` char(8) NOT NULL,
  `purORNo` varchar(12) NOT NULL,
  `userID` char(8) NOT NULL,
  `itemID` char(8) NOT NULL,
  `itemDesc` varchar(20) NOT NULL,
  `purQty` int(11) NOT NULL,
  `purDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_transaction`
--

CREATE TABLE `return_transaction` (
  `retTransID` char(8) NOT NULL,
  `brwTransID` char(9) NOT NULL,
  `userID` char(8) NOT NULL,
  `brwID` char(8) NOT NULL,
  `itemID` char(8) NOT NULL,
  `itemDesc` varchar(20) NOT NULL,
  `brwTransQty` int(11) NOT NULL,
  `itemRate` double NOT NULL,
  `brwTransDate` date NOT NULL,
  `retReturnedOnDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` char(8) NOT NULL,
  `userFName` varchar(20) NOT NULL,
  `userLName` varchar(20) NOT NULL,
  `userRole` varchar(9) NOT NULL,
  `userContactNo` varchar(11) NOT NULL,
  `userPassword` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrower`
--
ALTER TABLE `borrower`
  ADD PRIMARY KEY (`brwID`);

--
-- Indexes for table `borrow_transaction`
--
ALTER TABLE `borrow_transaction`
  ADD PRIMARY KEY (`brwTransID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `brwID` (`brwID`),
  ADD KEY `itemID` (`itemID`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`itemID`);

--
-- Indexes for table `purchase_transaction`
--
ALTER TABLE `purchase_transaction`
  ADD PRIMARY KEY (`purTransID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `return_transaction`
--
ALTER TABLE `return_transaction`
  ADD PRIMARY KEY (`retTransID`),
  ADD KEY `brwID` (`brwID`),
  ADD KEY `brwTransID` (`brwTransID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_transaction`
--
ALTER TABLE `borrow_transaction`
  ADD CONSTRAINT `borrow_transaction_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `borrow_transaction_ibfk_2` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`),
  ADD CONSTRAINT `borrow_transaction_ibfk_3` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`);

--
-- Constraints for table `purchase_transaction`
--
ALTER TABLE `purchase_transaction`
  ADD CONSTRAINT `purchase_transaction_ibfk_1` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`),
  ADD CONSTRAINT `purchase_transaction_ibfk_3` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `return_transaction`
--
ALTER TABLE `return_transaction`
  ADD CONSTRAINT `return_transaction_ibfk_1` FOREIGN KEY (`brwID`) REFERENCES `borrower` (`brwID`),
  ADD CONSTRAINT `return_transaction_ibfk_2` FOREIGN KEY (`brwTransID`) REFERENCES `borrow_transaction` (`brwTransID`),
  ADD CONSTRAINT `return_transaction_ibfk_3` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`),
  ADD CONSTRAINT `return_transaction_ibfk_5` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
