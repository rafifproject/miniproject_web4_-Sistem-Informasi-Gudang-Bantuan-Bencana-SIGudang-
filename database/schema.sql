-- ============================================================
-- Sistem Informasi Gudang Bantuan Bencana
-- Schema Database v1.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `gudang_bencana`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gudang_bencana`;

CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `full_name`     VARCHAR(150) NOT NULL DEFAULT '',
  `email`         VARCHAR(150)          DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT                  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`   INT UNSIGNED NOT NULL,
  `sku`           VARCHAR(50)  NOT NULL,
  `name`          VARCHAR(200) NOT NULL,
  `unit`          VARCHAR(30)  NOT NULL DEFAULT 'pcs',
  `price`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stock`         INT          NOT NULL DEFAULT 0,
  `minimum_stock` INT          NOT NULL DEFAULT 0,
  `description`   TEXT                  DEFAULT NULL,
  `is_public`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sku` (`sku`),
  KEY `fk_prod_cat` (`category_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `type`        VARCHAR(100) NOT NULL DEFAULT 'Lembaga',
  `contact`     VARCHAR(150)          DEFAULT NULL,
  `email`       VARCHAR(150)          DEFAULT NULL,
  `address`     TEXT                  DEFAULT NULL,
  `description` TEXT                  DEFAULT NULL,
  `is_public`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_suppliers` (
  `product_id`     INT UNSIGNED  NOT NULL,
  `supplier_id`    INT UNSIGNED  NOT NULL,
  `supplier_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `supplier_id`),
  KEY `fk_ps_supplier` (`supplier_id`),
  CONSTRAINT `fk_ps_product`  FOREIGN KEY (`product_id`)
    REFERENCES `products`  (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ps_supplier` FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `distribution_targets` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `location`    VARCHAR(200)          DEFAULT NULL,
  `description` TEXT                  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `product_id`             INT UNSIGNED  NOT NULL,
  `user_id`                INT UNSIGNED  NOT NULL,
  `supplier_id`            INT UNSIGNED           DEFAULT NULL,
  `distribution_target_id` INT UNSIGNED           DEFAULT NULL,
  `type`                   ENUM('IN','OUT')    NOT NULL,
  `quantity`               INT           NOT NULL,
  `date`                   DATE          NOT NULL,
  `reference`              VARCHAR(100)           DEFAULT NULL,
  `notes`                  TEXT                   DEFAULT NULL,
  `is_public`              TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sm_product`  (`product_id`),
  KEY `fk_sm_user`     (`user_id`),
  KEY `fk_sm_supplier` (`supplier_id`),
  KEY `fk_sm_dist`     (`distribution_target_id`),
  KEY `idx_sm_date`    (`date`),
  KEY `idx_sm_type`    (`type`),
  CONSTRAINT `fk_sm_product`   FOREIGN KEY (`product_id`)
    REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_sm_user`      FOREIGN KEY (`user_id`)
    REFERENCES `users`    (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_sm_supplier`  FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_sm_dist`      FOREIGN KEY (`distribution_target_id`)
    REFERENCES `distribution_targets` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
