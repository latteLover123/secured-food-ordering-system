-- schema.sql - Database Schema for Secured Online Food Ordering System
-- Target Database: secured_system_db

CREATE DATABASE IF NOT EXISTS `secured_system_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `secured_system_db`;

-- Users Table (Handles Person 1 Auth/MFA & Person 4 RBAC)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(254) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `mfa_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `mfa_code` VARCHAR(64) NULL DEFAULT NULL,
    `mfa_expiry` DATETIME NULL DEFAULT NULL,
    `role` ENUM('user', 'admin', 'rider', 'merchant') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data (For Testing and Demonstration)
-- Default test user: tester_sec | Password: Password@CCSB5113
INSERT INTO `users` (`username`, `email`, `password_hash`, `mfa_enabled`, `role`)
VALUES (
    'tester_sec',
    'tester@securedfood.com',
    '$argon2id$v=19$m=65536,t=4,p=2$VEVTVFNBTFRGSUxFMDEyMw$wW1xW/p1kY2T3m4F5g6h7i8j9k0l1m2n3o4p5q6r7s8',
    1,
    'user'
) ON DUPLICATE KEY UPDATE `id`=`id`;

-- ==========================================
-- PERSON 2 MODULE: CORE FUNCTIONALITY TABLES
-- ==========================================

-- 1. Table Menu Makanan (FR2 & FR6)
CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `restaurant_name` VARCHAR(100) NOT NULL,
    `item_name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL CHECK (`price` >= 0),
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table Pesanan (FR3)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL CHECK (`total_amount` >= 0),
    `delivery_address` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'accepted', 'delivering', 'delivered', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table Item Dalam Pesanan (FR3)
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `menu_id` INT NOT NULL,
    `quantity` INT NOT NULL CHECK (`quantity` > 0 AND `quantity` <= 50), -- Semantic constraint
    `price_at_order` DECIMAL(10,2) NOT NULL, -- Snapshot harga masa order
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table Rate Limiting (Brute Force Defense)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) DEFAULT 0,
    INDEX `idx_user_time` (`username`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample menu data untuk testing (Supaya senang nak test order)
INSERT IGNORE INTO `menus` (`restaurant_name`, `item_name`, `price`) VALUES 
('Nasi Kandar Beratur', 'Nasi Kandar Ayam', 12.50),
('Nasi Kandar Beratur', 'Teh Tarik', 3.00),
('Burger King', 'Whopper', 15.00);