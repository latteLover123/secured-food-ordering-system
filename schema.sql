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