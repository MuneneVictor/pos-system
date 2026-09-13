-- ============================================================
-- Wambo wa CARPETS POS
-- Foundational Database Schema
-- Target: MySQL 8.0+
--
-- IMPORTANT:
-- This file intentionally does NOT create or select a database.
-- Create/select the database named in your root .env DB_NAME,
-- then import this schema into that database.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS cash_movements;
DROP TABLE IF EXISTS cash_sessions;
DROP TABLE IF EXISTS cash_registers;
DROP TABLE IF EXISTS return_items;
DROP TABLE IF EXISTS returns;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS customer_ledger;
DROP TABLE IF EXISTS customer_payment_allocations;
DROP TABLE IF EXISTS customer_payments;
DROP TABLE IF EXISTS sale_payments;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_payments;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS document_sequences;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS branches;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. ORGANIZATION / BRANCHES
-- ============================================================

CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_branches_active (is_active)
) ENGINE=InnoDB;

-- ============================================================
-- 2. ACCESS CONTROL
-- ============================================================

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permissions_module (module)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NULL UNIQUE,
    phone VARCHAR(50) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    failed_login_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    password_changed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_users_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_users_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_users_role (role_id),
    INDEX idx_users_branch (branch_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE user_preferences (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    theme ENUM('light','dark') NOT NULL DEFAULT 'light',
    sidebar_collapsed TINYINT(1) NOT NULL DEFAULT 0,
    table_page_size SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_preferences_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_password_reset_expiry (expires_at)
) ENGINE=InnoDB;

-- ============================================================
-- 3. MASTER DATA
-- ============================================================

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_categories_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_categories_status (status),
    INDEX idx_categories_parent (parent_id)
) ENGINE=InnoDB;

CREATE TABLE units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    short_name VARCHAR(20) NOT NULL UNIQUE,
    allows_decimal TINYINT(1) NOT NULL DEFAULT 0,
    decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    alternate_phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    tax_number VARCHAR(80) NULL,
    notes TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_suppliers_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_suppliers_name (name),
    INDEX idx_suppliers_status (status)
) ENGINE=InnoDB;

CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    method_type ENUM('cash','mpesa','bank','card','credit','other') NOT NULL,
    requires_reference TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_methods_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    unit_id BIGINT UNSIGNED NOT NULL,
    default_supplier_id BIGINT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    sku VARCHAR(80) NOT NULL UNIQUE,
    barcode VARCHAR(120) NULL UNIQUE,
    description TEXT NULL,
    buying_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    average_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    minimum_stock DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    current_stock DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    image_path VARCHAR(255) NULL,
    track_stock TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_products_unit
        FOREIGN KEY (unit_id) REFERENCES units(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_products_supplier
        FOREIGN KEY (default_supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_products_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_products_name (name),
    INDEX idx_products_category (category_id),
    INDEX idx_products_supplier (default_supplier_id),
    INDEX idx_products_status (status),
    INDEX idx_products_stock (current_stock, minimum_stock)
) ENGINE=InnoDB;

-- ============================================================
-- 4. PURCHASES
-- ============================================================

CREATE TABLE purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_no VARCHAR(60) NOT NULL UNIQUE,
    invoice_reference VARCHAR(120) NULL,
    purchase_date DATETIME NOT NULL,
    status ENUM('draft','ordered','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    received_by BIGINT UNSIGNED NULL,
    received_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchases_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchases_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchases_received_by
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_purchases_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_purchases_date (purchase_date),
    INDEX idx_purchases_supplier (supplier_id),
    INDEX idx_purchases_status (status),
    INDEX idx_purchases_payment_status (payment_status)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    quantity_received DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    unit_cost DECIMAL(15,4) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_items_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_purchase_items_purchase (purchase_id),
    INDEX idx_purchase_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE purchase_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_no VARCHAR(120) NULL,
    paid_at DATETIME NOT NULL,
    notes VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_payments_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_payments_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchase_payments_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_purchase_payments_purchase (purchase_id),
    INDEX idx_purchase_payments_date (paid_at)
) ENGINE=InnoDB;

-- ============================================================
-- 5. CUSTOMERS / SALES / PAYMENTS / CREDIT
-- ============================================================

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    notes TEXT NULL,
    credit_limit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    account_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_customers_name (name),
    INDEX idx_customers_phone (phone),
    INDEX idx_customers_status (status),
    INDEX idx_customers_balance (account_balance)
) ENGINE=InnoDB;

CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    sale_no VARCHAR(60) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NULL,
    sale_date DATETIME NOT NULL,
    status ENUM('held','completed','voided') NOT NULL DEFAULT 'held',
    payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    cogs_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    gross_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    change_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    voided_by BIGINT UNSIGNED NULL,
    voided_at DATETIME NULL,
    void_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sales_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sales_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sales_voided_by
        FOREIGN KEY (voided_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_sales_date (sale_date),
    INDEX idx_sales_customer (customer_id),
    INDEX idx_sales_user (created_by),
    INDEX idx_sales_status (status),
    INDEX idx_sales_payment_status (payment_status)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name_snapshot VARCHAR(180) NOT NULL,
    sku_snapshot VARCHAR(80) NOT NULL,
    unit_snapshot VARCHAR(20) NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    cost_price DECIMAL(15,4) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    cogs_amount DECIMAL(15,2) NOT NULL,
    profit_amount DECIMAL(15,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sale_items_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sale_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_sale_items_sale (sale_id),
    INDEX idx_sale_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_no VARCHAR(120) NULL,
    paid_at DATETIME NOT NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sale_payments_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sale_payments_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sale_payments_user
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_sale_payments_sale (sale_id),
    INDEX idx_sale_payments_date (paid_at),
    INDEX idx_sale_payments_method (payment_method_id)
) ENGINE=InnoDB;

CREATE TABLE customer_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    payment_no VARCHAR(60) NOT NULL UNIQUE,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_no VARCHAR(120) NULL,
    payment_date DATETIME NOT NULL,
    notes VARCHAR(255) NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customer_payments_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_payments_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_payments_user
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_customer_payments_customer (customer_id),
    INDEX idx_customer_payments_date (payment_date)
) ENGINE=InnoDB;

CREATE TABLE customer_payment_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_payment_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customer_payment_alloc_payment
        FOREIGN KEY (customer_payment_id) REFERENCES customer_payments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_payment_alloc_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_customer_payment_sale (customer_payment_id, sale_id),
    INDEX idx_customer_payment_alloc_sale (sale_id)
) ENGINE=InnoDB;

CREATE TABLE customer_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    entry_date DATETIME NOT NULL,
    entry_type ENUM(
        'opening_balance',
        'credit_sale',
        'payment',
        'sale_return',
        'adjustment_debit',
        'adjustment_credit'
    ) NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    customer_payment_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(120) NULL,
    description VARCHAR(255) NULL,
    debit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance_after DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customer_ledger_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_ledger_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_ledger_payment
        FOREIGN KEY (customer_payment_id) REFERENCES customer_payments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_customer_ledger_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_customer_ledger_customer_date (customer_id, entry_date),
    INDEX idx_customer_ledger_sale (sale_id)
) ENGINE=InnoDB;

-- ============================================================
-- 6. EXPENSES
-- ============================================================

CREATE TABLE expense_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    expense_category_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    expense_no VARCHAR(60) NOT NULL UNIQUE,
    expense_date DATETIME NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_no VARCHAR(120) NULL,
    receipt_path VARCHAR(255) NULL,
    status ENUM('active','voided') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    voided_by BIGINT UNSIGNED NULL,
    voided_at DATETIME NULL,
    void_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_category
        FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_payment_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_voided_by
        FOREIGN KEY (voided_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_expenses_date (expense_date),
    INDEX idx_expenses_category (expense_category_id),
    INDEX idx_expenses_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- 7. RETURNS
-- ============================================================

CREATE TABLE returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    return_no VARCHAR(60) NOT NULL UNIQUE,
    return_type ENUM('customer','supplier') NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    purchase_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    return_date DATETIME NOT NULL,
    status ENUM('draft','completed','voided') NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    refund_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    voided_by BIGINT UNSIGNED NULL,
    voided_at DATETIME NULL,
    void_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_returns_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_returns_voided_by
        FOREIGN KEY (voided_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_returns_date (return_date),
    INDEX idx_returns_type (return_type),
    INDEX idx_returns_sale (sale_id),
    INDEX idx_returns_purchase (purchase_id)
) ENGINE=InnoDB;

CREATE TABLE return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    sale_item_id BIGINT UNSIGNED NULL,
    purchase_item_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    cost_price DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    line_total DECIMAL(15,2) NOT NULL,
    stock_action ENUM('restock','writeoff','none') NOT NULL DEFAULT 'restock',
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_return_items_return
        FOREIGN KEY (return_id) REFERENCES returns(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_return_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_return_items_sale_item
        FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_return_items_purchase_item
        FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_return_items_return (return_id),
    INDEX idx_return_items_product (product_id)
) ENGINE=InnoDB;

-- ============================================================
-- 8. TRANSACTION-BASED INVENTORY
-- ============================================================

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM(
        'opening_stock',
        'purchase',
        'sale',
        'customer_return',
        'supplier_return',
        'damage',
        'loss',
        'adjustment_in',
        'adjustment_out',
        'void_reversal',
        'transfer_in',
        'transfer_out'
    ) NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(120) NULL,
    quantity_change DECIMAL(15,3) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    stock_before DECIMAL(15,3) NOT NULL,
    stock_after DECIMAL(15,3) NOT NULL,
    reason VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movements_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movements_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_stock_movements_product_date (product_id, created_at),
    INDEX idx_stock_movements_branch_date (branch_id, created_at),
    INDEX idx_stock_movements_type (movement_type),
    INDEX idx_stock_movements_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- ============================================================
-- 9. CASH MANAGEMENT
-- ============================================================

CREATE TABLE cash_registers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_registers_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_cash_register_branch_name (branch_id, name)
) ENGINE=InnoDB;

CREATE TABLE cash_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_register_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    opened_at DATETIME NOT NULL,
    opening_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    closed_by BIGINT UNSIGNED NULL,
    closed_at DATETIME NULL,
    expected_closing_amount DECIMAL(15,2) NULL,
    counted_closing_amount DECIMAL(15,2) NULL,
    variance_amount DECIMAL(15,2) NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_sessions_register
        FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cash_sessions_opened_by
        FOREIGN KEY (opened_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cash_sessions_closed_by
        FOREIGN KEY (closed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_cash_sessions_status (status),
    INDEX idx_cash_sessions_opened_at (opened_at)
) ENGINE=InnoDB;

CREATE TABLE cash_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_session_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM(
        'opening',
        'sale',
        'customer_payment',
        'expense',
        'cash_in',
        'cash_out',
        'adjustment',
        'closing'
    ) NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id BIGINT UNSIGNED NULL,
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_movements_session
        FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cash_movements_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cash_movements_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_cash_movements_session (cash_session_id),
    INDEX idx_cash_movements_branch_date (branch_id, created_at),
    INDEX idx_cash_movements_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- ============================================================
-- 10. AUDIT LOG
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(80) NOT NULL,
    record_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_user_date (user_id, created_at),
    INDEX idx_audit_module_date (module, created_at),
    INDEX idx_audit_record (module, record_id)
) ENGINE=InnoDB;

-- ============================================================
-- 11. SETTINGS / DOCUMENT NUMBERING
-- ============================================================

CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE document_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_key VARCHAR(80) NOT NULL UNIQUE,
    prefix VARCHAR(20) NOT NULL,
    current_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
    padding TINYINT UNSIGNED NOT NULL DEFAULT 6,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 12. DEFAULT SEED DATA
-- ============================================================

INSERT INTO branches (name, code, is_active)
VALUES ('Main Shop', 'MAIN', 1);

INSERT INTO roles (name, slug, description, is_system, is_active) VALUES
('Administrator / Owner', 'owner', 'Full system access and business oversight.', 1, 1),
('Manager', 'manager', 'Operational management access without full system administration.', 1, 1),
('Cashier', 'cashier', 'POS, sales and permitted customer operations.', 1, 1),
('Stock Manager', 'stock-manager', 'Inventory, products, suppliers and purchasing operations.', 1, 1);

INSERT INTO permissions (module, name, slug, description) VALUES
('dashboard', 'View dashboard', 'dashboard.view', 'View business dashboard and KPIs'),

('pos', 'Use POS', 'pos.use', 'Open and use point of sale'),
('pos', 'Apply sale discount', 'pos.discount', 'Apply permitted line or sale discount'),
('pos', 'Override selling price', 'pos.price_override', 'Change selling price during sale'),
('pos', 'Hold sale', 'pos.hold', 'Hold and resume an incomplete sale'),
('pos', 'Void sale', 'pos.void', 'Void a completed sale'),

('sales', 'View sales', 'sales.view', 'View sales history'),
('sales', 'View sale details', 'sales.view_details', 'View individual sale details'),

('products', 'View products', 'products.view', 'View products'),
('products', 'Create products', 'products.create', 'Create products'),
('products', 'Update products', 'products.update', 'Update products'),
('products', 'Deactivate products', 'products.deactivate', 'Deactivate products'),

('inventory', 'View inventory', 'inventory.view', 'View stock and movement history'),
('inventory', 'Adjust stock', 'inventory.adjust', 'Create controlled stock adjustments'),
('inventory', 'View low stock', 'inventory.low_stock', 'View low stock alerts'),

('purchases', 'View purchases', 'purchases.view', 'View purchase records'),
('purchases', 'Create purchases', 'purchases.create', 'Create purchase orders/records'),
('purchases', 'Receive purchases', 'purchases.receive', 'Receive purchased stock'),
('purchases', 'Record purchase payments', 'purchases.pay', 'Record supplier purchase payments'),

('suppliers', 'View suppliers', 'suppliers.view', 'View suppliers'),
('suppliers', 'Manage suppliers', 'suppliers.manage', 'Create and edit suppliers'),

('customers', 'View customers', 'customers.view', 'View customers'),
('customers', 'Create customers', 'customers.create', 'Create customers'),
('customers', 'Update customers', 'customers.update', 'Update customers'),

('credit', 'View debtors', 'credit.view', 'View customer balances and statements'),
('credit', 'Create credit sale', 'credit.sell', 'Allow credit sale where customer rules permit'),
('credit', 'Record customer payment', 'credit.record_payment', 'Record debt repayment'),

('expenses', 'View expenses', 'expenses.view', 'View expenses'),
('expenses', 'Create expenses', 'expenses.create', 'Record expenses'),
('expenses', 'Edit expenses', 'expenses.edit', 'Edit permitted expense records'),
('expenses', 'Void expenses', 'expenses.void', 'Void expense records'),

('reports', 'View sales reports', 'reports.sales', 'View sales reports'),
('reports', 'View profit reports', 'reports.profit', 'View gross and net profit reports'),
('reports', 'View inventory reports', 'reports.inventory', 'View inventory reports'),
('reports', 'View purchase reports', 'reports.purchases', 'View purchase reports'),
('reports', 'View expense reports', 'reports.expenses', 'View expense reports'),
('reports', 'View customer reports', 'reports.customers', 'View customer and debtor reports'),
('reports', 'View payment reports', 'reports.payments', 'View payment reports'),
('reports', 'Export reports', 'reports.export', 'Export PDF/CSV reports'),

('users', 'View users', 'users.view', 'View users'),
('users', 'Create users', 'users.create', 'Create users'),
('users', 'Update users', 'users.update', 'Update users'),
('users', 'Manage roles', 'roles.manage', 'Manage roles'),
('users', 'Manage permissions', 'permissions.manage', 'Manage permission assignments'),

('audit', 'View audit log', 'audit.view', 'View audit/activity log'),

('settings', 'Manage business settings', 'settings.business', 'Manage business details'),
('settings', 'Manage system settings', 'settings.system', 'Manage system configuration');

-- Owner gets every permission.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.slug = 'owner';

-- Manager: operational modules and reports, but not user/role/system administration.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p
    ON p.slug IN (
        'dashboard.view',
        'pos.use','pos.discount','pos.hold','pos.void',
        'sales.view','sales.view_details',
        'products.view','products.create','products.update','products.deactivate',
        'inventory.view','inventory.adjust','inventory.low_stock',
        'purchases.view','purchases.create','purchases.receive','purchases.pay',
        'suppliers.view','suppliers.manage',
        'customers.view','customers.create','customers.update',
        'credit.view','credit.sell','credit.record_payment',
        'expenses.view','expenses.create','expenses.edit','expenses.void',
        'reports.sales','reports.profit','reports.inventory','reports.purchases',
        'reports.expenses','reports.customers','reports.payments','reports.export',
        'audit.view'
    )
WHERE r.slug = 'manager';

-- Cashier: fast POS + basic customer and debt-payment duties.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p
    ON p.slug IN (
        'dashboard.view',
        'pos.use','pos.discount','pos.hold',
        'sales.view','sales.view_details',
        'products.view',
        'customers.view','customers.create','customers.update',
        'credit.view','credit.sell','credit.record_payment'
    )
WHERE r.slug = 'cashier';

-- Stock manager: product, inventory, supplier and purchasing duties.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p
    ON p.slug IN (
        'dashboard.view',
        'products.view','products.create','products.update','products.deactivate',
        'inventory.view','inventory.adjust','inventory.low_stock',
        'purchases.view','purchases.create','purchases.receive','purchases.pay',
        'suppliers.view','suppliers.manage',
        'reports.inventory','reports.purchases'
    )
WHERE r.slug = 'stock-manager';

INSERT INTO units (name, short_name, allows_decimal, decimal_places, is_active) VALUES
('Piece', 'pc', 0, 0, 1),
('Roll', 'roll', 1, 3, 1),
('Box', 'box', 1, 3, 1),
('Metre', 'm', 1, 3, 1),
('Square Metre', 'sqm', 1, 3, 1),
('Kilogram', 'kg', 1, 3, 1),
('Other', 'other', 1, 3, 1);

INSERT INTO payment_methods (name, code, method_type, requires_reference, is_active, sort_order) VALUES
('Cash', 'CASH', 'cash', 0, 1, 10),
('M-Pesa', 'MPESA', 'mpesa', 1, 1, 20),
('Bank', 'BANK', 'bank', 1, 1, 30),
('Card', 'CARD', 'card', 1, 1, 40),
('Credit', 'CREDIT', 'credit', 0, 1, 50),
('Other', 'OTHER', 'other', 1, 1, 60);

INSERT INTO expense_categories (name, description, is_active) VALUES
('Transport', 'Transport and travel expenses', 1),
('Electricity', 'Electricity bills and related charges', 1),
('Water', 'Water bills and related charges', 1),
('Rent', 'Shop or premises rent', 1),
('Salaries', 'Employee salaries and wages', 1),
('Delivery', 'Customer or supplier delivery costs', 1),
('Internet', 'Internet and connectivity costs', 1),
('Repairs', 'Repairs and maintenance costs', 1),
('Advertising', 'Marketing and advertising costs', 1),
('Packaging', 'Packaging materials and costs', 1),
('Other', 'Other operating expenses', 1);

INSERT INTO settings (setting_key, setting_value, setting_type, is_public) VALUES
('business.name', 'Wambo wa Carpets', 'string', 1),
('business.currency', 'KES', 'string', 1),
('business.currency_symbol', 'KSh', 'string', 1),
('business.timezone', 'Africa/Nairobi', 'string', 1),
('ui.default_theme', 'light', 'string', 1),
('ui.allow_theme_toggle', '1', 'boolean', 1),
('inventory.allow_negative_stock', '0', 'boolean', 0),
('inventory.costing_method', 'weighted_average', 'string', 0),
('sales.allow_overpayment', '0', 'boolean', 0),
('security.session_timeout_minutes', '60', 'integer', 0);

INSERT INTO document_sequences (sequence_key, prefix, current_value, padding) VALUES
('sale', 'SAL-', 0, 6),
('purchase', 'PUR-', 0, 6),
('customer_payment', 'CPY-', 0, 6),
('expense', 'EXP-', 0, 6),
('return', 'RET-', 0, 6);

-- ============================================================
-- END OF FOUNDATIONAL SCHEMA
-- ============================================================
