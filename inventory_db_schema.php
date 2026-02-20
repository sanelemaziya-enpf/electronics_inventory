-- Electronics Inventory Management System Database Schema
-- Run this in phpMyAdmin after creating a database named 'electronics_inventory'

CREATE DATABASE IF NOT EXISTS electronics_inventory;
USE electronics_inventory;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff',
    department VARCHAR(100),
    floor VARCHAR(50),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Suppliers Table
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    website VARCHAR(150),
    notes TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Assets Table
CREATE TABLE assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    model VARCHAR(100),
    serial_number VARCHAR(100),
    supplier_id INT,
    purchase_date DATE,
    purchase_cost DECIMAL(10, 2),
    warranty_expiry DATE,
    status ENUM('available', 'assigned', 'maintenance', 'retired') DEFAULT 'available',
    condition_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    description TEXT,
    specifications TEXT,
    location VARCHAR(100),
    floor VARCHAR(50),
    department VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Asset Assignments Table
CREATE TABLE asset_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT,
    assigned_date DATE NOT NULL,
    return_date DATE,
    status ENUM('active', 'returned') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    employee_id VARCHAR(50),
    position VARCHAR(100),
    department VARCHAR(100),
    floor VARCHAR(50),
    photo VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    date_joined DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_department (department),
    INDEX idx_floor (floor),
    INDEX idx_status (status)
);


-- Insert default Super Admin (password: admin123)
INSERT INTO users (username, email, password, full_name, role, department, floor) 
VALUES ('superadmin', 'superadmin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin', 'IT', 'Ground Floor');

-- Insert sample categories
INSERT INTO categories (name, description, created_by) VALUES 
('Laptops', 'Portable computers', 1),
('Desktops', 'Desktop computers and workstations', 1),
('Monitors', 'Display screens', 1),
('Printers', 'Printing devices', 1),
('Networking', 'Network equipment and devices', 1),
('Accessories', 'Computer accessories and peripherals', 1);

-- Insert sample supplier
INSERT INTO suppliers (name, contact_person, email, phone, address, created_by) VALUES 
('TechSupply Co.', 'John Doe', 'john@techsupply.com', '+1234567890', '123 Tech Street, City', 1);

-- Insert sample staff members
INSERT INTO staff (staff_id, full_name, email, phone, employee_id, position, department, floor, date_joined, created_by) VALUES
('STF-2024-001', 'John Smith', 'john.smith@company.com', '+268 7612 3456', 'EMP-001', 'IT Officer', 'IT Department', '3rd Floor', '2024-01-15', 1),
('STF-2024-002', 'Sarah Johnson', 'sarah.johnson@company.com', '+268 7623 4567', 'EMP-002', 'Finance Manager', 'Finance Department', '2nd Floor', '2024-02-01', 1),
('STF-2024-003', 'Michael Brown', 'michael.brown@company.com', '+268 7634 5678', 'EMP-003', 'HR Officer', 'HR Department', '1st Floor', '2024-03-10', 1);


/*-- Update asset_assignments table to support both users and staff
-- First, backup existing data
CREATE TABLE asset_assignments_backup AS SELECT * FROM asset_assignments;

-- Drop the existing foreign key constraint
ALTER TABLE asset_assignments DROP FOREIGN KEY asset_assignments_ibfk_2;

-- Add new columns to track whether assignment is to user or staff
ALTER TABLE asset_assignments 
ADD COLUMN assigned_to_type ENUM('user', 'staff') DEFAULT 'user' AFTER assigned_to,
ADD COLUMN staff_id INT NULL AFTER assigned_to_type;

-- Update existing records to mark them as 'user' type
UPDATE asset_assignments SET assigned_to_type = 'user' WHERE assigned_to IS NOT NULL;

-- Add foreign key for staff assignments
ALTER TABLE asset_assignments 
ADD FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE;*/
-- Note: The above block is commented out to prevent execution errors if run multiple times.