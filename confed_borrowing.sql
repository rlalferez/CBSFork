-- ==========================================================
-- CONFEDERATES STUDENT COUNCIL (CSC) - BORROWING SYSTEM
-- phpMyAdmin MySQL Database Setup Script
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

-- Temporarily disable foreign key constraints for clean table setup
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. USERS TABLE (Council Administrators & Staff Members)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `contact_number` VARCHAR(25) NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. RESOURCES TABLE (Council Inventory: Speakers, Sports Gear, etc.)
-- Clean & Empty: Admin will input all equipment items via the system
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `model` VARCHAR(100) NULL,
  `category` VARCHAR(50) NOT NULL,
  `total_qty` INT NOT NULL DEFAULT 1,
  `available_qty` INT NOT NULL DEFAULT 1,
  `condition_status` VARCHAR(50) NOT NULL DEFAULT 'Good Condition',
  `location` VARCHAR(100) NOT NULL DEFAULT 'Council Office Room 204',
  `fee_type` VARCHAR(50) NOT NULL DEFAULT 'Free',
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `description` TEXT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 3. CLIENTS TABLE (Borrowers: Students & Student Organizations)
-- Clean & Empty: Admin & Staff will record client profiles
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `contact_number` VARCHAR(25) NOT NULL,
  `organization_name` VARCHAR(150) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Student',
  `status` ENUM('Active', 'Suspended') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 4. BOOKINGS TABLE (Equipment Loan Transactions & Schedules)
-- Clean & Empty: Admin & Staff will list down who borrowed what
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(50) NOT NULL UNIQUE,
  `client_id` INT NOT NULL,
  `event_name` VARCHAR(150) NOT NULL,
  `event_location` VARCHAR(150) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `actual_return_date` DATE NULL,
  `purpose` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Released', 'Returned', 'Cancelled', 'Overdue') NOT NULL DEFAULT 'Approved',
  `payment_status` ENUM('Free / Waived', 'Pending Deposit', 'Deposit Paid', 'Paid', 'Refunded') NOT NULL DEFAULT 'Free / Waived',
  `payment_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_details` TEXT NULL,
  `cancellation_reason` TEXT NULL,
  `created_by_user_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 5. BOOKING ITEMS TABLE (Specific Equipment Borrowed per Transaction)
-- Clean & Empty
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `booking_items`;
CREATE TABLE `booking_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `resource_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_booking_items_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_items_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- INITIAL ADMINISTRATOR ACCOUNT (Zero equipment/loan seed data)
-- Use this account to log in as Administrator and start inputting resources!
-- Username: admin
-- Password: admin123
-- ==========================================================
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `contact_number`, `role`, `status`) 
VALUES (
  'admin',
  '$2y$10$fhXdAJw791ZaaJAFJ52lReP4mAjmzfXNtnJ7sgN0Q6sMCsrYs2AzK',
  'Council Administrator',
  'admin@csc.edu.ph',
  '09123456789',
  'admin',
  'active'
);
