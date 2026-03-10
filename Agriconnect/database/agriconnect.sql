-- =====================================================
-- AgriConnect – MySQL Database Schema
-- =====================================================
-- Database: agriconnect
-- Version:  1.0.0
-- Charset:  utf8mb4 (supports Kannada, Hindi, Devanagari)
--
-- Run this file in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < agriconnect.sql
--
-- Tables: users, crops, resources, orders, crop_images
-- =====================================================

-- Create and use the database
CREATE DATABASE IF NOT EXISTS `agriconnect`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `agriconnect`;

-- =====================================================
-- TABLE: users
-- Stores farmer account information
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`    VARCHAR(120) NOT NULL,
  `mobile`       VARCHAR(15)  NOT NULL UNIQUE,
  `email`        VARCHAR(150)          DEFAULT NULL,
  `password_hash`VARCHAR(255) NOT NULL,
  `village`      VARCHAR(100)          DEFAULT NULL,
  `district`     VARCHAR(100)          DEFAULT NULL,
  `state`        VARCHAR(100)          DEFAULT NULL,
  `pincode`      VARCHAR(10)           DEFAULT NULL,
  `land_acres`   DECIMAL(8,2)          DEFAULT 0.00,
  `language_pref`ENUM('en','kn','hi')  DEFAULT 'en',
  `is_active`    TINYINT(1)            DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME              DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_mobile` (`mobile`),
  INDEX `idx_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- TABLE: crops
-- Stores crop recommendation queries and results
-- =====================================================
CREATE TABLE IF NOT EXISTS `crops` (
  `id`                  INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`             INT(11)              DEFAULT NULL,   -- NULL if guest query
  `soil_type`           ENUM('clay','loamy','sandy','black','red') NOT NULL,
  `season`              ENUM('kharif','rabi','zaid')              NOT NULL,
  `water_availability`  ENUM('high','medium','low')               NOT NULL,
  `region`              VARCHAR(100)         DEFAULT 'General',
  `land_size`           DECIMAL(8,2)         DEFAULT 1.00,
  `recommended_crops`   TEXT        NOT NULL,               -- Comma-separated crop list
  `notes`               TEXT                 DEFAULT NULL,
  `created_at`          DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_soil`   (`soil_type`),
  INDEX `idx_season` (`season`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- TABLE: resources
-- Stores farmer resource usage records (water, fertilizer, labour)
-- =====================================================
CREATE TABLE IF NOT EXISTS `resources` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`           INT(11)                DEFAULT NULL,
  `record_date`       DATE          NOT NULL,
  `field_name`        VARCHAR(100)  NOT NULL,
  `crop_name`         VARCHAR(100)  NOT NULL,
  `water_usage`       DECIMAL(10,2) NOT NULL DEFAULT 0.00  COMMENT 'In Litres',
  `fertilizer_usage`  DECIMAL(10,2) NOT NULL DEFAULT 0.00  COMMENT 'In kg',
  `fertilizer_type`   ENUM('NPK','Urea','DAP','MOP','Organic','Other') DEFAULT 'NPK',
  `labour_count`      INT(5)        NOT NULL DEFAULT 0,
  `total_cost`        DECIMAL(12,2)          DEFAULT 0.00  COMMENT 'In INR (₹)',
  `notes`             TEXT                   DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_record_date` (`record_date`),
  INDEX `idx_field`       (`field_name`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- TABLE: orders
-- Stores product purchase orders from farmers
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`       VARCHAR(30)   NOT NULL UNIQUE  COMMENT 'e.g. AGR-20240615-4829',
  `user_id`        INT(11)                DEFAULT NULL,
  `customer_name`  VARCHAR(120)  NOT NULL,
  `mobile`         VARCHAR(15)   NOT NULL,
  `product_name`   VARCHAR(200)  NOT NULL,
  `quantity`       INT(6)        NOT NULL DEFAULT 1,
  `unit_price`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_price`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `address`        TEXT          NOT NULL,
  `payment_method` ENUM('cod','upi','bank','card') DEFAULT 'cod',
  `payment_status` ENUM('pending','paid','failed') DEFAULT 'pending',
  `status`         ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME               DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  INDEX `idx_mobile`  (`mobile`),
  INDEX `idx_status`  (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- TABLE: crop_images
-- Stores crop leaf images uploaded for disease detection
-- =====================================================
CREATE TABLE IF NOT EXISTS `crop_images` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)               DEFAULT NULL,
  `farmer_name`      VARCHAR(120)          DEFAULT 'Anonymous',
  `crop_type`        VARCHAR(100) NOT NULL,
  `filename`         VARCHAR(255) NOT NULL  COMMENT 'Stored in /uploads/crop_images/',
  `file_size_kb`     DECIMAL(10,2)         DEFAULT NULL,
  `mime_type`        VARCHAR(50)           DEFAULT 'image/jpeg',
  `symptom_desc`     TEXT                  DEFAULT NULL,
  `analysis_result`  JSON                  DEFAULT NULL  COMMENT 'JSON from ML API response',
  `analysis_status`  ENUM('pending','completed','failed') DEFAULT 'pending',
  `upload_date`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_crop_type`     (`crop_type`),
  INDEX `idx_upload_date`   (`upload_date`),
  INDEX `idx_analysis_status` (`analysis_status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- SAMPLE DATA: Insert demo records
-- =====================================================

-- Sample users
INSERT INTO `users` (`full_name`, `mobile`, `email`, `password_hash`, `village`, `district`, `state`, `land_acres`, `language_pref`) VALUES
('Ramesh Kumar',    '9876543210', 'ramesh@email.com',  SHA2('password123', 256), 'Doddaballapur', 'Bangalore Rural', 'Karnataka', 5.50, 'kn'),
('Sunita Devi',     '8765432109', 'sunita@email.com',  SHA2('password456', 256), 'Muzaffarpur',   'Muzaffarpur',     'Bihar',    12.00, 'hi'),
('Vijay Patil',     '7654321098', 'vijay@email.com',   SHA2('password789', 256), 'Kolhapur',      'Kolhapur',        'Maharashtra', 8.00, 'en'),
('Lakshmi Bai',     '6543210987', 'lakshmi@email.com', SHA2('password000', 256), 'Tirupati',      'Chittoor',        'Andhra Pradesh', 3.50, 'en'),
('Mohammad Farooq', '9123456780', 'farooq@email.com',  SHA2('agri2024',    256), 'Ludhiana',      'Ludhiana',        'Punjab',   20.00, 'hi');

-- Sample crop recommendations
INSERT INTO `crops` (`user_id`, `soil_type`, `season`, `water_availability`, `region`, `land_size`, `recommended_crops`) VALUES
(1, 'red',   'kharif', 'medium', 'Karnataka',   5.5,  'Ragi, Maize, Pearl Millet, Groundnut'),
(2, 'loamy', 'rabi',   'high',   'Bihar',        12.0, 'Wheat, Potato, Mustard, Onion'),
(3, 'black', 'kharif', 'medium', 'Maharashtra',  8.0,  'Cotton, Soybean, Sorghum, Pigeonpea'),
(4, 'sandy', 'kharif', 'low',    'Andhra Pradesh',3.5, 'Bajra, Cluster Bean, Moth Bean, Sesame'),
(5, 'loamy', 'rabi',   'high',   'Punjab',       20.0, 'Wheat, Potato, Sugarcane, Onion');

-- Sample resource records
INSERT INTO `resources` (`user_id`, `record_date`, `field_name`, `crop_name`, `water_usage`, `fertilizer_usage`, `fertilizer_type`, `labour_count`, `total_cost`, `notes`) VALUES
(1, '2024-06-01', 'North Field A', 'Ragi',    200.00, 5.00,  'NPK',     3, 1500.00, 'First irrigation after transplanting'),
(1, '2024-06-08', 'North Field A', 'Ragi',    150.00, 3.00,  'Urea',    2, 900.00,  'Top dressing at tillering stage'),
(2, '2024-11-15', 'South Field',   'Wheat',   300.00, 8.00,  'DAP',     5, 2200.00, 'Basal dose at sowing'),
(3, '2024-07-10', 'Cotton Farm',   'Cotton',  100.00, 4.00,  'NPK',     4, 1800.00, 'Drip irrigation – efficient usage'),
(5, '2024-11-20', 'Main Field 1',  'Wheat',   500.00, 12.00, 'Urea',    8, 4500.00, 'Crown root initiation irrigation');

-- Sample orders
INSERT INTO `orders` (`order_id`, `user_id`, `customer_name`, `mobile`, `product_name`, `quantity`, `unit_price`, `total_price`, `address`, `payment_method`, `status`) VALUES
('AGR-20240601-1001', 1, 'Ramesh Kumar',    '9876543210', 'Paddy IR-64 Seeds 25kg',    2, 850.00,  1700.00, 'Doddaballapur, Bangalore Rural, Karnataka 561101', 'cod',  'delivered'),
('AGR-20240610-1002', 2, 'Sunita Devi',     '8765432109', 'Urea 45kg bag',              3, 266.00,   798.00, 'Muzaffarpur, Bihar 842001',                       'upi',  'delivered'),
('AGR-20240615-1003', 3, 'Vijay Patil',     '7654321098', 'Imidacloprid 500ml',         5, 450.00,  2250.00, 'Kolhapur, Maharashtra 416001',                    'bank', 'shipped'),
('AGR-20240620-1004', 4, 'Lakshmi Bai',     '6543210987', 'Hybrid Maize NK-6240 4kg',  1, 1200.00, 1200.00, 'Tirupati, Chittoor, Andhra Pradesh 517501',       'cod',  'confirmed'),
('AGR-20240625-1005', 5, 'Mohammad Farooq', '9123456780', 'DAP 50kg bag',               4, 1350.00, 5400.00, 'Ludhiana, Punjab 141001',                         'upi',  'pending');

-- Sample crop image records
INSERT INTO `crop_images` (`user_id`, `farmer_name`, `crop_type`, `filename`, `file_size_kb`, `symptom_desc`, `analysis_status`) VALUES
(1, 'Ramesh Kumar', 'rice',    'crop_20240601_120001_abc123.jpg', 245.50, 'Yellow patches on lower leaves', 'completed'),
(2, 'Sunita Devi',  'wheat',   'crop_20240612_093015_def456.jpg', 312.80, 'White powdery coating on leaves', 'completed'),
(3, 'Vijay Patil',  'cotton',  'crop_20240618_154230_ghi789.jpg', 189.20, 'Curling and yellowing of leaves', 'completed'),
(5, 'Mohammad Farooq', 'wheat','crop_20240622_081500_jkl012.jpg', 421.00, 'Orange rust spots on leaf surface', 'pending');


-- =====================================================
-- VIEWS: Useful query shortcuts
-- =====================================================

-- View: Monthly resource summary
CREATE OR REPLACE VIEW `v_monthly_resource_summary` AS
SELECT
    DATE_FORMAT(record_date, '%Y-%m') AS month,
    SUM(water_usage)      AS total_water_litres,
    SUM(fertilizer_usage) AS total_fertilizer_kg,
    SUM(labour_count)     AS total_labour_days,
    SUM(total_cost)       AS total_cost_inr,
    COUNT(*)              AS entries
FROM resources
GROUP BY DATE_FORMAT(record_date, '%Y-%m')
ORDER BY month DESC;

-- View: Order summary with customer info
CREATE OR REPLACE VIEW `v_order_summary` AS
SELECT
    o.order_id,
    o.customer_name,
    o.mobile,
    o.product_name,
    o.quantity,
    o.total_price,
    o.payment_method,
    o.status,
    o.created_at
FROM orders o
ORDER BY o.created_at DESC;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
