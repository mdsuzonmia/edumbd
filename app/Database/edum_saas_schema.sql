-- Edum SaaS Database Schema
-- Version: 1.00

-- 1 school
CREATE TABLE edum_schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    country VARCHAR(50) NOT NULL,
    timezone VARCHAR(50) NOT NULL,
    address TEXT DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,

    params JSON DEFAULT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    UNIQUE KEY uq_school_slug (slug),
    INDEX idx_school_status (status)
);


-- 2 users (staff)
CREATE TABLE edum_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    UNIQUE KEY uq_school_email (school_id, email),
    INDEX idx_user_school (school_id),
    INDEX idx_user_status (status),

    CONSTRAINT fk_user_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE
);

-- 3 subscriptions and plans
CREATE TABLE edum_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    school_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,

    -- subscription lifecycle
    status INT(10) NOT NULL DEFAULT 0,
    -- 0 = pending
    -- 1 = trial
    -- 2 = active
    -- 3 = suspended
    -- 4 = expired
    -- 5 = cancelled

    is_trial INT(10) NOT NULL DEFAULT 0,

    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    trial_end_date DATE DEFAULT NULL,

    -- billing info
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    billing_cycle ENUM('monthly','yearly','lifetime') NOT NULL,

    -- payment gateway
    payment_gateway VARCHAR(50) DEFAULT NULL,
    gateway_subscription_id VARCHAR(150) DEFAULT NULL,
    gateway_customer_id VARCHAR(150) DEFAULT NULL,

    -- invoice / transaction
    last_payment_at TIMESTAMP NULL,
    next_billing_at TIMESTAMP NULL,

    -- metadata
    meta LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
        CHECK (json_valid(meta)),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,

    -- constraints
    CONSTRAINT fk_sub_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sub_plan
        FOREIGN KEY (plan_id) REFERENCES edum_subscription_plans(id)
        ON DELETE RESTRICT,

    UNIQUE KEY uq_school_active_sub (school_id, status)
);

-- 4 subscription plans
CREATE TABLE edum_subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,

    prices DECIMAL(10,2) DEFAULT 0.00,
    price DECIMAL(10,2) DEFAULT 0.00,
    monthly_price DECIMAL(10,2) DEFAULT 0.00,
    yearly_price DECIMAL(10,2) DEFAULT 0.00,
    lifetime_price DECIMAL(10,2) DEFAULT 0.00,
    billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',

    currency VARCHAR(10) NOT NULL DEFAULT 'USD',

    trial_days INT(10) NOT NULL DEFAULT 0,
    student_limit INT(10) DEFAULT NULL,
    teachers_limit INT(10) DEFAULT NULL,
    branch_limit INT(10) DEFAULT NULL,
    admin_limit INT(10) DEFAULT NULL,
    sms_limit INT(10) DEFAULT NULL,
    storage_limit_mb INT(10) DEFAULT NULL,

    custom_domain TINYINT(1) DEFAULT 0,
    mobile_app_access TINYINT(1) DEFAULT 0,
    api_access TINYINT(1) DEFAULT 0,

    features JSON DEFAULT NULL,
    description TEXT DEFAULT NULL,

    is_popular TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    sort_order INT(10) DEFAULT 0,
    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5 plan modules
CREATE TABLE edum_plan_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    plan_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,

    access_level ENUM('read','write','full') NOT NULL DEFAULT 'full',

    status INT(10) NOT NULL DEFAULT 1,

    UNIQUE KEY uq_plan_module (plan_id, module_id),

    CONSTRAINT fk_pm_plan
        FOREIGN KEY (plan_id) REFERENCES edum_subscription_plans(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pm_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE CASCADE
);


-- 6 roles and permissions
CREATE TABLE edum_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    slug VARCHAR(100) NOT NULL,
    is_system TINYINT(1) DEFAULT 0,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_school_role (school_id, slug),
    INDEX idx_role_school (school_id),
    INDEX idx_role_status (status),

    CONSTRAINT fk_role_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE
);

INSERT INTO edum_roles 
(id, school_id, name, slug, description, is_system, status)
VALUES
(1, NULL, 'Super Admin', 'super_admin', 'Platform owner with full access', 1, 1),
(2, NULL, 'School Admin', 'school_admin', 'School owner / principal', 1, 1);

INSERT INTO edum_roles 
(id, school_id, name, slug, description, is_system, status)
VALUES
(3, NULL, 'Teacher', 'teacher', 'Teacher with academic permissions', 1, 1),
(4, NULL, 'Student', 'student', 'Student portal access', 1, 1),
(5, NULL, 'Parent', 'parent', 'Parent access to student info', 1, 1);

INSERT INTO edum_roles 
(id, school_id, name, slug, description, is_system, status)
VALUES
(6, NULL, 'Accountant', 'accountant', 'Manages fees, salary, expenses, reports', 1, 1),

(7, NULL, 'Librarian', 'librarian', 'Manages books, issue/return, fines', 1, 1),

(8, NULL, 'Receptionist', 'receptionist', 'Front desk, admissions, inquiries, attendance', 1, 1);


-- 7 permissions
CREATE TABLE edum_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    module VARCHAR(100) NOT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_permission_slug (slug),
    INDEX idx_permission_module (module),
    INDEX idx_permission_status (status)
);

-- 8 role_permissions
CREATE TABLE edum_role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (role_id, permission_id),

    CONSTRAINT fk_rp_role
        FOREIGN KEY (role_id) REFERENCES edum_roles(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_rp_permission
        FOREIGN KEY (permission_id) REFERENCES edum_permissions(id)
        ON DELETE CASCADE
);

-- 9 user_roles
CREATE TABLE edum_user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (user_id, role_id),

    CONSTRAINT fk_ur_user
        FOREIGN KEY (user_id) REFERENCES edum_users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ur_role
        FOREIGN KEY (role_id) REFERENCES edum_roles(id)
        ON DELETE CASCADE
);




-- 10 modules
CREATE TABLE edum_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,

    version VARCHAR(20) DEFAULT '1.0',
    author VARCHAR(255) DEFAULT NULL,

    menu_icon VARCHAR(100) DEFAULT NULL,
    sort_order INT(10) DEFAULT 0,

    params JSON DEFAULT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_module_slug (slug),
    INDEX idx_module_status (status)
);

-- 11 school_modules
CREATE TABLE edum_school_modules (
    school_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    PRIMARY KEY (school_id, module_id),

    CONSTRAINT fk_sm_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sm_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE CASCADE
);

-- 12 module_permissions
CREATE TABLE edum_module_permissions (
    module_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (module_id, permission_id),

    CONSTRAINT fk_mp_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mp_permission
        FOREIGN KEY (permission_id) REFERENCES edum_permissions(id)
        ON DELETE CASCADE
);

-- 13 role_modules
CREATE TABLE edum_role_modules (
    role_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,

    show_in_sidebar INT(10) NOT NULL DEFAULT 1,
    show_in_dashboard INT(10) NOT NULL DEFAULT 0,
    status INT(10) NOT NULL DEFAULT 1,

    PRIMARY KEY (role_id, module_id),

    CONSTRAINT fk_rm_role
        FOREIGN KEY (role_id) REFERENCES edum_roles(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_rm_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE CASCADE
);

-- 14 dashboard_widgets
CREATE TABLE edum_dashboard_widgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    module_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,

    widget_key VARCHAR(100) NOT NULL,
    position INT(10) DEFAULT 0,

    status INT(10) NOT NULL DEFAULT 1,

    CONSTRAINT fk_dw_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_dw_role
        FOREIGN KEY (role_id) REFERENCES edum_roles(id)
        ON DELETE CASCADE
);

-- 15 settings
CREATE TABLE edum_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    `group` VARCHAR(100) NOT NULL,
    `key` VARCHAR(150) NOT NULL,
    `value` LONGTEXT NULL,

    type VARCHAR(50) DEFAULT 'string',
    description TEXT DEFAULT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    UNIQUE KEY uq_setting_key (`group`, `key`),
    INDEX idx_setting_group (`group`),
    INDEX idx_setting_status (`status`)
);

-- 16 school_settings
CREATE TABLE edum_school_settings (
    school_id BIGINT UNSIGNED NOT NULL,
    `group` VARCHAR(100) NOT NULL,
    `key` VARCHAR(150) NOT NULL,
    `value` LONGTEXT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    PRIMARY KEY (school_id, `group`, `key`),

    CONSTRAINT fk_ss_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE
);

-- 17 email_verifications
CREATE TABLE edum_email_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,

    expires_at DATETIME NOT NULL,
    verified_at DATETIME DEFAULT NULL,

    status INT(10) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_email_token (token),

    CONSTRAINT fk_ev_user
        FOREIGN KEY (user_id) REFERENCES edum_users(id)
        ON DELETE CASCADE
);

-- 18 menus
CREATE TABLE edum_menus (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  `title` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `route` VARCHAR(150) DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,

  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `menu_order` INT NOT NULL DEFAULT 0,

  `is_sidebar` INT(10) NOT NULL DEFAULT 1,   -- show in sidebar or not
  `is_system` INT(10) NOT NULL DEFAULT 0,    -- protected system menu (cannot delete)
  `is_saas_only` INT(10) NOT NULL DEFAULT 0, -- visible only for SaaS admin

  `status` INT(10) NOT NULL DEFAULT 1,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_menu_module
        FOREIGN KEY (module_id) REFERENCES edum_modules(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_menu_parent
        FOREIGN KEY (parent_id) REFERENCES edum_menus(id)
        ON DELETE CASCADE
);

INSERT INTO edum_menus
(title, slug, route, icon, parent_id, menu_order, is_sidebar, is_system, is_saas_only)
VALUES

-- Dashboard
('Dashboard', 'dashboard', 'saas-admin/dashboard', 'bi bi-speedometer2', 0, 1, 1, 1, 1),

-- Schools (Tenants)
('Schools', 'schools', 'saas-admin/schools', 'bi bi-building', 0, 2, 1, 1, 1),

-- Subscription Management
('Plans', 'plans', 'saas-admin/plans', 'bi bi-box', 0, 3, 1, 1, 1),
('Subscriptions', 'subscriptions', 'saas-admin/subscriptions', 'bi bi-repeat', 0, 4, 1, 1, 1),

-- Billing & Payments
('Payments', 'payments', 'saas-admin/payments', 'bi bi-credit-card', 0, 5, 1, 1, 1),
('Invoices', 'invoices', 'saas-admin/invoices', 'bi bi-receipt', 0, 6, 1, 1, 1),

-- SaaS Users (Platform Staff)
('SaaS Users', 'saas-users', 'saas-admin/users', 'bi bi-people', 0, 7, 1, 1, 1),

-- Reports & Analytics
('Reports', 'reports', 'saas-admin/reports', 'bi bi-graph-up-arrow', 0, 8, 1, 1, 1),

-- Notifications
('Notifications', 'notifications', 'saas-admin/notifications', 'bi bi-bell', 0, 9, 1, 1, 1),

-- Audit Logs
('Audit Logs', 'logs', 'saas-admin/logs', 'bi bi-shield-check', 0, 10, 1, 1, 1),

-- System Settings
('Settings', 'settings', 'saas-admin/settings', 'bi bi-gear', 0, 11, 1, 1, 1);


-- 19 menu_roles
CREATE TABLE edum_menu_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    menu_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,

    UNIQUE KEY uq_menu_role (menu_id, role_id),

    CONSTRAINT fk_mr_menu
        FOREIGN KEY (menu_id) REFERENCES edum_menus(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mr_role
        FOREIGN KEY (role_id) REFERENCES edum_roles(id)
        ON DELETE CASCADE
);

-- 20 school_menu_overrides
CREATE TABLE edum_school_menu_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    school_id BIGINT UNSIGNED NOT NULL,
    menu_id BIGINT UNSIGNED NOT NULL,

    is_hidden INT(10) NOT NULL DEFAULT 0,

    UNIQUE KEY uq_school_menu (school_id, menu_id),

    CONSTRAINT fk_sm_school
        FOREIGN KEY (school_id) REFERENCES edum_schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sm_menu
        FOREIGN KEY (menu_id) REFERENCES edum_menus(id)
        ON DELETE CASCADE
);

