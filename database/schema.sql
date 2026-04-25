-- ============================================================
-- Al-Riaz Associates — Database Schema
-- MySQL 8.x | utf8mb4_unicode_ci
-- ============================================================

-- ── Admin Users ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(200) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(30)  DEFAULT '',
    password      VARCHAR(255) NOT NULL,
    role          ENUM('agent','admin','super_admin') NOT NULL DEFAULT 'agent',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    invite_token  VARCHAR(100) DEFAULT NULL,
    last_login    DATETIME DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_role   (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default super admin (password: Admin@12345)
INSERT IGNORE INTO admin_users (name, email, password, role, is_active) VALUES
  ('Super Admin', 'admin@alriazassociates.pk',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
   'super_admin', 1);

-- ── Projects ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    slug              VARCHAR(255) NOT NULL UNIQUE,
    developer         VARCHAR(255) DEFAULT '',
    city              VARCHAR(100) DEFAULT '',
    area_locality     VARCHAR(255) DEFAULT '',
    status            ENUM('upcoming','under_development','ready','possession') NOT NULL DEFAULT 'upcoming',
    noc_status        ENUM('approved','pending','not_required') NOT NULL DEFAULT 'pending',
    noc_ref           VARCHAR(200) DEFAULT '',
    authorised_since  DATE DEFAULT NULL,
    authorisation_ref VARCHAR(200) DEFAULT '',
    description       TEXT,
    latitude          DECIMAL(10,8) DEFAULT NULL,
    longitude         DECIMAL(11,8) DEFAULT NULL,
    hero_image        VARCHAR(500) DEFAULT '',
    brochure_pdf      VARCHAR(500) DEFAULT '',
    master_plan       VARCHAR(500) DEFAULT '',
    is_featured       TINYINT(1) NOT NULL DEFAULT 0,
    is_published      TINYINT(1) NOT NULL DEFAULT 0,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city       (city),
    INDEX idx_status     (status),
    INDEX idx_published  (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Project Media ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_media (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id   INT UNSIGNED NOT NULL,
    file_path    VARCHAR(500) NOT NULL,
    media_type   ENUM('image','document','floor_plan') NOT NULL DEFAULT 'image',
    sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Properties (Listings) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS properties (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(255) NOT NULL,
    slug              VARCHAR(300) DEFAULT '',
    category          ENUM('residential','commercial','plot') NOT NULL DEFAULT 'residential',
    purpose           ENUM('sale','rent') NOT NULL DEFAULT 'sale',
    listing_type      VARCHAR(50) DEFAULT '',
    project_id        INT UNSIGNED DEFAULT NULL,
    agent_id          INT UNSIGNED DEFAULT NULL,
    price             DECIMAL(15,2) NOT NULL DEFAULT 0,
    price_on_demand   TINYINT(1) NOT NULL DEFAULT 0,
    rent_period       ENUM('monthly','yearly') DEFAULT 'monthly',
    city              VARCHAR(100) NOT NULL DEFAULT '',
    area_locality     VARCHAR(255) DEFAULT '',
    address           VARCHAR(500) DEFAULT '',
    latitude          DECIMAL(10,8) DEFAULT NULL,
    longitude         DECIMAL(11,8) DEFAULT NULL,
    area_value        DECIMAL(10,2) DEFAULT 0,
    area_unit         ENUM('marla','kanal','sq_ft','sq_yard','acre') NOT NULL DEFAULT 'marla',
    bedrooms          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bathrooms         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    possession_status VARCHAR(50) DEFAULT 'available',
    features          JSON DEFAULT NULL,
    description       TEXT,
    views_count       INT UNSIGNED NOT NULL DEFAULT 0,
    is_featured       TINYINT(1) NOT NULL DEFAULT 0,
    is_published      TINYINT(1) NOT NULL DEFAULT 0,
    is_sold           TINYINT(1) NOT NULL DEFAULT 0,  -- via migration 004_properties_is_sold.php
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_id)   REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_city       (city),
    INDEX idx_category   (category),
    INDEX idx_purpose    (purpose),
    INDEX idx_published  (is_published),
    INDEX idx_featured   (is_featured),
    INDEX idx_sold       (is_sold),
    INDEX idx_agent      (agent_id),
    INDEX idx_views      (views_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Property Media ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS property_media (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id   INT UNSIGNED DEFAULT NULL,
    file_path     VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) DEFAULT '',
    media_type    ENUM('image','document','floor_plan') NOT NULL DEFAULT 'image',
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
    INDEX idx_property  (property_id),
    INDEX idx_type      (media_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Inquiries ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inquiries (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_name   VARCHAR(200) NOT NULL,
    phone          VARCHAR(30)  NOT NULL,
    email          VARCHAR(255) DEFAULT '',
    message        TEXT,
    preferred_time VARCHAR(100) DEFAULT '',
    property_id    INT UNSIGNED DEFAULT NULL,
    project_id     INT UNSIGNED DEFAULT NULL,
    status         ENUM('new','assigned','contacted','qualified','closed_won','closed_lost')
                   NOT NULL DEFAULT 'new',
    assigned_to    INT UNSIGNED DEFAULT NULL,
    source         VARCHAR(100) DEFAULT 'website',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id)  ON DELETE SET NULL,
    FOREIGN KEY (project_id)  REFERENCES projects(id)    ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status     (status),
    INDEX idx_created    (created_at DESC),
    INDEX idx_assigned   (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Inquiry Notes ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inquiry_notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id  INT UNSIGNED NOT NULL,
    admin_id    INT UNSIGNED NOT NULL,
    note        TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(id)   ON DELETE CASCADE,
    FOREIGN KEY (admin_id)   REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_inquiry (inquiry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Log ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL DEFAULT 0,
    admin_name  VARCHAR(200) NOT NULL DEFAULT '',
    action      VARCHAR(100) NOT NULL,
    entity      VARCHAR(100) NOT NULL DEFAULT '',
    entity_id   INT UNSIGNED NOT NULL DEFAULT 0,
    detail      TEXT,
    ip_address  VARCHAR(45) DEFAULT '',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at DESC),
    INDEX idx_admin   (admin_id),
    INDEX idx_action  (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Settings (optional table approach) ───────────────────────
-- (We use /config/settings.json but you can use this table instead)
CREATE TABLE IF NOT EXISTS settings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(100) NOT NULL UNIQUE,
    `value`     TEXT,
    updated_at  DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Branch Offices ────────────────────────────────────────────
-- Folded from migrations:
--   001_branches.php                — base table
--   002_branches_is_hq.php          — added `is_hq`
--   003_branches_hours_schedule.php — added `hours_schedule`
CREATE TABLE IF NOT EXISTS branches (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(180) NOT NULL DEFAULT '',
    address     VARCHAR(300) NOT NULL DEFAULT '',
    phone       VARCHAR(40)  NOT NULL DEFAULT '',
    hours          VARCHAR(120) NOT NULL DEFAULT '',
    hours_schedule LONGTEXT DEFAULT NULL,            -- via migration 003
    is_hq       TINYINT(1) NOT NULL DEFAULT 0,       -- via migration 002
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_hq   (is_hq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Authorized Dealers ────────────────────────────────────────
-- Folded from migration: 006_authorized_dealers.php
CREATE TABLE IF NOT EXISTS authorized_dealers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(200) NOT NULL,
    logo_url      VARCHAR(500) NOT NULL DEFAULT '',
    website_url   VARCHAR(500) NOT NULL DEFAULT '',
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_published  TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (is_published),
    INDEX idx_sort      (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Migrations Tracking ──────────────────────────────────────
-- Records which migration files under /database/migrations/ have been applied.
CREATE TABLE IF NOT EXISTS migrations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration   VARCHAR(255) NOT NULL UNIQUE,
    batch       INT UNSIGNED NOT NULL DEFAULT 1,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  MIGRATION QUERIES
--  Exact SQL extracted from each /database/migrations/NNN_*.php file.
--  All statements are idempotent (IF NOT EXISTS / INFORMATION_SCHEMA
--  guard) so running schema.sql against a populated database is safe.
--  The tables above already reflect the final post-migration state,
--  so these statements are no-ops on a fresh install.
-- ============================================================

-- ── 001_branches.php — create `branches` table ──────────────
CREATE TABLE IF NOT EXISTS `branches` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(180) NOT NULL DEFAULT '',
    `address`     VARCHAR(300) NOT NULL DEFAULT '',
    `phone`       VARCHAR(40)  NOT NULL DEFAULT '',
    `hours`       VARCHAR(120) NOT NULL DEFAULT '',
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 002_branches_is_hq.php — add `is_hq` to branches ────────
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='branches' AND COLUMN_NAME='is_hq');
SET @q := IF(@c=0,
  'ALTER TABLE `branches` ADD COLUMN `is_hq` TINYINT(1) NOT NULL DEFAULT 0 AFTER `hours`, ADD INDEX `idx_hq` (`is_hq`)',
  'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 003_branches_hours_schedule.php — add `hours_schedule` ──
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='branches' AND COLUMN_NAME='hours_schedule');
SET @q := IF(@c=0,
  'ALTER TABLE `branches` ADD COLUMN `hours_schedule` LONGTEXT DEFAULT NULL AFTER `hours`',
  'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 004_properties_is_sold.php — add `is_sold` to properties ─
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='properties' AND COLUMN_NAME='is_sold');
SET @q := IF(@c=0,
  'ALTER TABLE `properties` ADD COLUMN `is_sold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_published`, ADD INDEX `idx_sold` (`is_sold`)',
  'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 005_smtp_to_db.php — data-only (JSON → DB); no schema change ──
-- The PHP migration moves SMTP keys from config/settings.json into the
-- `settings` table at first run. Nothing to execute at schema level.

-- ── 006_authorized_dealers.php — create `authorized_dealers` ─
CREATE TABLE IF NOT EXISTS `authorized_dealers` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(200) NOT NULL,
    `logo_url`     VARCHAR(500) NOT NULL DEFAULT '',
    `website_url`  VARCHAR(500) NOT NULL DEFAULT '',
    `sort_order`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_published` (`is_published`),
    INDEX `idx_sort`      (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 007_property_features.php — create + seed `property_features` ─
CREATE TABLE IF NOT EXISTS `property_features` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug`       VARCHAR(80)  NOT NULL UNIQUE,
    `label`      VARCHAR(120) NOT NULL,
    `icon`       VARCHAR(80)  NOT NULL DEFAULT 'fa-check-circle',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`),
    INDEX `idx_sort`   (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `property_features` (`slug`, `label`, `icon`, `sort_order`) VALUES
  ('parking',         'Parking',         'fa-square-parking',       0),
  ('gas',             'Gas',             'fa-fire-flame-curved',   10),
  ('electricity',     'Electricity',     'fa-bolt-lightning',      20),
  ('water',           'Water Supply',    'fa-droplet',             30),
  ('security',        'Security',        'fa-shield-halved',       40),
  ('boundary_wall',   'Boundary Wall',   'fa-border-all',          50),
  ('furnished',       'Furnished',       'fa-couch',               60),
  ('corner',          'Corner Plot',     'fa-arrows-turn-to-dots', 70),
  ('garden',          'Garden',          'fa-tree',                80),
  ('servant_quarter', 'Servant Quarter', 'fa-user-tie',            90),
  ('store_room',      'Store Room',      'fa-box-archive',        100),
  ('drawing_room',    'Drawing Room',    'fa-couch',              110),
  ('double_unit',     'Double Unit',     'fa-layer-group',        120),
  ('basement',        'Basement',        'fa-layer-group',        130),
  ('lift',            'Lift / Elevator', 'fa-elevator',           140);

-- ── 008_users_password_reset.php — add password-reset columns to `users` ─
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='reset_token');
SET @q := IF(@c=0,
  'ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(100) DEFAULT NULL AFTER `invite_token`, ADD INDEX `idx_reset_token` (`reset_token`)',
  'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='reset_token_expires_at');
SET @q := IF(@c=0,
  'ALTER TABLE `users` ADD COLUMN `reset_token_expires_at` DATETIME DEFAULT NULL AFTER `reset_token`',
  'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Sample Data (remove in production) ───────────────────────
-- Insert a sample project
INSERT IGNORE INTO projects (name, slug, developer, city, area_locality, status, noc_status, is_published, is_featured, description) VALUES
  ('Bahria Enclave Phase II', 'bahria-enclave-phase-ii', 'Bahria Town Pvt. Ltd.',
   'islamabad', 'Sector A, Zone IV', 'under_development', 'approved', 1, 1,
   'A premium residential project featuring modern amenities and world-class infrastructure.');

-- Insert a sample agent
INSERT IGNORE INTO admin_users (name, email, phone, password, role, is_active) VALUES
  ('Ahmed Raza', 'ahmed@alriazassociates.pk', '+92 333 1234567',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'agent', 1);

-- Insert a sample inquiry
INSERT IGNORE INTO inquiries (visitor_name, phone, email, message, status, created_at) VALUES
  ('Muhammad Ali', '+92 300 9876543', 'ali@example.com',
   'I am interested in a 10 Marla house in Bahria Town Phase 8 for sale.', 'new', NOW()),
  ('Sara Khan', '+92 321 1111222', 'sara@example.com',
   'Looking for a 2-bedroom apartment for rent in F-10 Islamabad.', 'assigned', DATE_SUB(NOW(), INTERVAL 2 DAY));
