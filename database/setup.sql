-- ============================================================
-- Mason Construction Services Inc.
-- Database Setup Script
-- Run this in your Bluehost cPanel > phpMyAdmin
-- Database: thematg5_mason_db
-- ============================================================

-- Use the correct database
USE thematg5_mason_db;

-- ============================================================
-- Table: contact_submissions
-- Stores all contact form submissions from the website
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_submissions (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name         VARCHAR(100)    NOT NULL,
    email        VARCHAR(255)    NOT NULL,
    phone        VARCHAR(30)     DEFAULT NULL,
    subject      VARCHAR(255)    NOT NULL DEFAULT 'General Inquiry',
    message      TEXT            NOT NULL,
    ip_address   VARCHAR(45)     DEFAULT NULL,
    status       ENUM('new','read','in_progress','resolved','spam')
                                 NOT NULL DEFAULT 'new',
    admin_notes  TEXT            DEFAULT NULL,
    submitted_at DATETIME        NOT NULL,
    updated_at   DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status       (status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_email        (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Table: admin_users
-- Stores admin login credentials (passwords are bcrypt-hashed)
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login    DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Default Admin User
-- IMPORTANT: Do NOT use this INSERT block in production.
-- Instead, run database/create-admin.php to generate a secure
-- password hash unique to your deployment.
--
-- If you do use this block for initial setup:
--   Username : admin
--   Password : Mason@Admin2025!
-- Change the password immediately after first login.
-- ============================================================
-- INSERT IGNORE INTO admin_users (username, password_hash, full_name, email, is_active, created_at)
-- VALUES (
--     'admin',
--     '$2b$12$IdXJ4hXoaFxqMOeHxEo9Ne3LecRpfBL/L4ZXMlA9ru2YXCwlnTHB6',
--     'Jitesh Admin',
--     'mason@themasonconstruction.com',
--     1,
--     NOW()
-- );
-- Run database/create-admin.php to create the admin user securely.

-- ============================================================
-- Verify tables were created
-- ============================================================
SHOW TABLES;
