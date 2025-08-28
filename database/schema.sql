-- Luxury Watch Business Intelligence Database Schema
-- Created for comprehensive analytics and reporting

SET FOREIGN_KEY_CHECKS = 0;
USE knowhowpilot_BI;

-- =============================================
-- CORE BUSINESS TABLES
-- =============================================

-- Brands Table
CREATE TABLE brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    country VARCHAR(50),
    founded_year YEAR,
    luxury_tier ENUM('Entry', 'Mid', 'High', 'Ultra') DEFAULT 'Mid',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product Categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Products Table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    brand_id INT NOT NULL,
    category_id INT NOT NULL,
    model VARCHAR(100),
    movement_type ENUM('Mechanical', 'Automatic', 'Quartz', 'Smart') NOT NULL,
    case_material VARCHAR(50),
    case_diameter DECIMAL(4,1),
    water_resistance INT,
    price DECIMAL(12,2) NOT NULL,
    cost DECIMAL(12,2),
    weight DECIMAL(6,2),
    limited_edition BOOLEAN DEFAULT FALSE,
    limited_quantity INT NULL,
    status ENUM('Active', 'Discontinued', 'Coming Soon') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_sku (sku),
    INDEX idx_brand_category (brand_id, category_id),
    INDEX idx_price (price)
);

-- Customers Table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('M', 'F', 'Other'),
    nationality VARCHAR(50),
    preferred_language VARCHAR(10) DEFAULT 'tr',
    customer_tier ENUM('Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond') DEFAULT 'Bronze',
    registration_date DATE NOT NULL,
    last_purchase_date DATE,
    total_spent DECIMAL(15,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'VIP') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_customer_code (customer_code),
    INDEX idx_tier (customer_tier),
    INDEX idx_total_spent (total_spent)
);

-- Customer Addresses
CREATE TABLE customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type ENUM('Billing', 'Shipping', 'Both') DEFAULT 'Both',
    address_line1 VARCHAR(200) NOT NULL,
    address_line2 VARCHAR(200),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(50) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Stores/Channels
CREATE TABLE stores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type ENUM('Physical', 'Online', 'Boutique', 'Authorized Dealer') NOT NULL,
    address VARCHAR(300),
    city VARCHAR(100),
    country VARCHAR(50),
    manager_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    opening_date DATE,
    status ENUM('Active', 'Inactive', 'Temporary Closed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_store_code (store_code),
    INDEX idx_type (type)
);

-- Sales Orders
CREATE TABLE sales_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    store_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    delivery_date DATETIME,
    status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled', 'Returned') DEFAULT 'Pending',
    payment_method ENUM('Cash', 'Credit Card', 'Bank Transfer', 'Installment', 'Crypto') NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Partial', 'Refunded') DEFAULT 'Pending',
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TRY',
    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
    sales_person VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    INDEX idx_order_number (order_number),
    INDEX idx_order_date (order_date),
    INDEX idx_customer_store (customer_id, store_id),
    INDEX idx_status (status),
    INDEX idx_total_amount (total_amount)
);

-- Sales Order Items
CREATE TABLE sales_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL,
    warranty_months INT DEFAULT 24,
    serial_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order_product (order_id, product_id),
    INDEX idx_serial_number (serial_number)
);

-- =============================================
-- INVENTORY MANAGEMENT
-- =============================================

-- Inventory Locations
CREATE TABLE inventory_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type ENUM('Warehouse', 'Store', 'Showroom', 'Service Center') NOT NULL,
    address VARCHAR(300),
    city VARCHAR(100),
    country VARCHAR(50),
    capacity INT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Current Inventory
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_available INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
    reorder_level INT DEFAULT 5,
    max_stock_level INT DEFAULT 100,
    last_count_date DATE,
    cost_per_unit DECIMAL(12,2),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id),
    UNIQUE KEY unique_product_location (product_id, location_id),
    INDEX idx_quantity_available (quantity_available),
    INDEX idx_reorder_level (reorder_level)
);

-- Inventory Movements
CREATE TABLE inventory_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUSTMENT', 'RETURN') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('Purchase', 'Sale', 'Transfer', 'Adjustment', 'Return', 'Damage') NOT NULL,
    reference_id INT,
    unit_cost DECIMAL(12,2),
    notes TEXT,
    movement_date DATETIME NOT NULL,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id),
    INDEX idx_product_date (product_id, movement_date),
    INDEX idx_movement_type (movement_type),
    INDEX idx_reference (reference_type, reference_id)
);

-- =============================================
-- SUPPLIERS AND PURCHASING
-- =============================================

-- Suppliers
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) NOT NULL UNIQUE,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(20),
    address VARCHAR(300),
    city VARCHAR(100),
    country VARCHAR(50),
    payment_terms VARCHAR(100),
    lead_time_days INT DEFAULT 30,
    quality_rating DECIMAL(3,2) DEFAULT 5.00,
    status ENUM('Active', 'Inactive', 'Blacklisted') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_status (status)
);

-- Purchase Orders
CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    status ENUM('Draft', 'Sent', 'Confirmed', 'Partial', 'Received', 'Cancelled') DEFAULT 'Draft',
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TRY',
    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
    notes TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_po_number (po_number),
    INDEX idx_order_date (order_date),
    INDEX idx_status (status)
);

-- Purchase Order Items
CREATE TABLE purchase_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_ordered INT NOT NULL,
    quantity_received INT DEFAULT 0,
    unit_cost DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL,
    received_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_po_product (po_id, product_id)
);

-- =============================================
-- ANALYTICS AND REPORTING TABLES
-- =============================================

-- Daily Sales Summary
CREATE TABLE daily_sales_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    summary_date DATE NOT NULL,
    store_id INT NOT NULL,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_cost DECIMAL(15,2) DEFAULT 0,
    total_profit DECIMAL(15,2) DEFAULT 0,
    avg_order_value DECIMAL(12,2) DEFAULT 0,
    new_customers INT DEFAULT 0,
    returning_customers INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id),
    UNIQUE KEY unique_date_store (summary_date, store_id),
    INDEX idx_summary_date (summary_date)
);

-- Monthly Product Performance
CREATE TABLE monthly_product_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year_month VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    product_id INT NOT NULL,
    units_sold INT DEFAULT 0,
    revenue DECIMAL(15,2) DEFAULT 0,
    cost DECIMAL(15,2) DEFAULT 0,
    profit DECIMAL(15,2) DEFAULT 0,
    profit_margin DECIMAL(5,2) DEFAULT 0,
    inventory_turnover DECIMAL(8,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_month_product (year_month, product_id),
    INDEX idx_year_month (year_month),
    INDEX idx_revenue (revenue)
);

-- Customer Lifetime Value
CREATE TABLE customer_lifetime_value (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    calculation_date DATE NOT NULL,
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(15,2) DEFAULT 0,
    avg_order_value DECIMAL(12,2) DEFAULT 0,
    purchase_frequency DECIMAL(8,2) DEFAULT 0,
    customer_lifespan_days INT DEFAULT 0,
    predicted_clv DECIMAL(15,2) DEFAULT 0,
    customer_segment VARCHAR(50),
    risk_score DECIMAL(3,2) DEFAULT 0, -- 0-1 scale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_customer_date (customer_id, calculation_date),
    INDEX idx_predicted_clv (predicted_clv),
    INDEX idx_customer_segment (customer_segment)
);

-- Brand Performance Analytics
CREATE TABLE brand_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    period_type ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly') NOT NULL,
    units_sold INT DEFAULT 0,
    revenue DECIMAL(15,2) DEFAULT 0,
    market_share DECIMAL(5,2) DEFAULT 0,
    avg_selling_price DECIMAL(12,2) DEFAULT 0,
    customer_satisfaction DECIMAL(3,2) DEFAULT 0,
    return_rate DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    INDEX idx_brand_period (brand_id, period_start, period_end),
    INDEX idx_revenue (revenue)
);

-- Inventory Analytics
CREATE TABLE inventory_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    analysis_date DATE NOT NULL,
    avg_inventory DECIMAL(10,2) DEFAULT 0,
    inventory_turnover DECIMAL(8,2) DEFAULT 0,
    days_of_supply INT DEFAULT 0,
    stockout_days INT DEFAULT 0,
    carrying_cost DECIMAL(12,2) DEFAULT 0,
    obsolete_inventory DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id),
    UNIQUE KEY unique_product_location_date (product_id, location_id, analysis_date),
    INDEX idx_analysis_date (analysis_date),
    INDEX idx_turnover (inventory_turnover)
);

-- =============================================
-- SYSTEM AND AUDIT TABLES
-- =============================================

-- User Activity Log
CREATE TABLE user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at)
);

-- System Configuration
CREATE TABLE system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    description TEXT,
    data_type ENUM('string', 'integer', 'decimal', 'boolean', 'json') DEFAULT 'string',
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
);

SET FOREIGN_KEY_CHECKS = 1;
