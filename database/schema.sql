-- ============================================================
-- Al-Riaz Associates — Database Schema
-- MySQL 8.x | utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS alriaz_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE alriaz_db;

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
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_id)   REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_city       (city),
    INDEX idx_category   (category),
    INDEX idx_purpose    (purpose),
    INDEX idx_published  (is_published),
    INDEX idx_featured   (is_featured),
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
