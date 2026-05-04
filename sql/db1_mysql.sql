-- ============================================================
-- WASCO Water Billing System - Database 1 (MySQL - Operational)
-- ============================================================

CREATE DATABASE IF NOT EXISTS wasco_operational;
USE wasco_operational;

-- Customers table
CREATE TABLE customers (
    account_number VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    district ENUM('Maseru','Leribe','Berea','Mafeteng','Mohales Hoek',
                  'Quthing','Qacha Nek','Mokhotlong','Thaba-Tseka','Butha-Buthe') NOT NULL,
    customer_type ENUM('residential','commercial','industrial') DEFAULT 'residential',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User accounts (login)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin','branch_manager') DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_number) REFERENCES customers(account_number) ON DELETE SET NULL
);

-- Billing rates (tiered pricing)
CREATE TABLE billing_rates (
    rate_id INT PRIMARY KEY AUTO_INCREMENT,
    rate_tier VARCHAR(50) NOT NULL,
    usage_min DECIMAL(10,2) NOT NULL,
    usage_max DECIMAL(10,2),
    cost_per_unit DECIMAL(10,2) NOT NULL,
    customer_type ENUM('residential','commercial','industrial') DEFAULT 'residential',
    effective_date DATE NOT NULL
);

-- Water usage meter readings
CREATE TABLE water_usage (
    usage_id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20) NOT NULL,
    reading_month DATE NOT NULL,
    previous_reading DECIMAL(10,2) DEFAULT 0,
    current_reading DECIMAL(10,2) NOT NULL,
    units_consumed DECIMAL(10,2) GENERATED ALWAYS AS (current_reading - previous_reading) STORED,
    recorded_by INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_number) REFERENCES customers(account_number),
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

-- Bills
CREATE TABLE bills (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20) NOT NULL,
    billing_month DATE NOT NULL,
    units_consumed DECIMAL(10,2) NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    payment_status ENUM('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_number) REFERENCES customers(account_number)
);

-- Payments
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mpesa','ecocash','bank','online') NOT NULL,
    transaction_ref VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    FOREIGN KEY (bill_id) REFERENCES bills(bill_id),
    FOREIGN KEY (account_number) REFERENCES customers(account_number)
);

-- Notifications
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20) NOT NULL,
    bill_id INT,
    message TEXT NOT NULL,
    channel ENUM('email','sms') DEFAULT 'email',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent','failed','pending') DEFAULT 'pending',
    FOREIGN KEY (account_number) REFERENCES customers(account_number)
);

-- Leakage reports
CREATE TABLE leakage_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    location TEXT,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open','in_progress','resolved') DEFAULT 'open',
    FOREIGN KEY (account_number) REFERENCES customers(account_number)
);

-- ============================================================
-- VIEWS
-- ============================================================

CREATE VIEW v_customer_outstanding AS
SELECT c.account_number, c.full_name, c.district,
       SUM(b.amount_due) AS total_billed,
       COALESCE(SUM(p.amount_paid),0) AS total_paid,
       SUM(b.amount_due) - COALESCE(SUM(p.amount_paid),0) AS outstanding_balance
FROM customers c
JOIN bills b ON c.account_number = b.account_number
LEFT JOIN payments p ON b.bill_id = p.bill_id
GROUP BY c.account_number, c.full_name, c.district;

CREATE VIEW v_monthly_usage_report AS
SELECT c.district, c.customer_type,
       DATE_FORMAT(w.reading_month,'%Y-%m') AS month,
       COUNT(DISTINCT w.account_number) AS customer_count,
       SUM(w.units_consumed) AS total_units,
       SUM(b.amount_due) AS total_billed
FROM water_usage w
JOIN customers c ON w.account_number = c.account_number
JOIN bills b ON w.account_number = b.account_number
         AND DATE_FORMAT(w.reading_month,'%Y-%m') = DATE_FORMAT(b.billing_month,'%Y-%m')
GROUP BY c.district, c.customer_type, month;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO customers VALUES
('WAS-001','Thabiso Mokoena','thabiso@example.com','22312345','123 Kingsway, Maseru','Maseru','residential',NOW()),
('WAS-002','Mpho Letsie','mpho@example.com','22398765','45 Hilton Road, Maseru','Maseru','residential',NOW()),
('WAS-003','Lineo Sello','lineo@example.com','22456789','Plot 12, Leribe','Leribe','commercial',NOW()),
('WAS-004','Retselisitsoe Tau','retse@example.com','22567890','Block C, Berea','Berea','industrial',NOW()),
('WAS-005','Mamorena Ntsane','mamorena@example.com','22678901','Main St, Mafeteng','Mafeteng','residential',NOW());

-- Admin user (password: admin123)
INSERT INTO users (account_number, username, password_hash, role) VALUES
(NULL, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(NULL, 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_manager');

-- Customer users (password: password)
INSERT INTO users (account_number, username, password_hash, role) VALUES
('WAS-001','thabiso','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer'),
('WAS-002','mpho','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer'),
('WAS-003','lineo','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer');

INSERT INTO billing_rates (rate_tier, usage_min, usage_max, cost_per_unit, customer_type, effective_date) VALUES
('Tier 1 - Basic', 0, 6, 2.50, 'residential', '2024-01-01'),
('Tier 2 - Standard', 6.01, 20, 5.00, 'residential', '2024-01-01'),
('Tier 3 - High Usage', 20.01, NULL, 8.00, 'residential', '2024-01-01'),
('Commercial Basic', 0, 30, 6.00, 'commercial', '2024-01-01'),
('Commercial High', 30.01, NULL, 10.00, 'commercial', '2024-01-01'),
('Industrial', 0, NULL, 12.00, 'industrial', '2024-01-01');

INSERT INTO water_usage (account_number, reading_month, previous_reading, current_reading, recorded_by) VALUES
('WAS-001','2024-11-01',120.00,132.00,1),
('WAS-001','2024-12-01',132.00,147.00,1),
('WAS-001','2025-01-01',147.00,160.00,1),
('WAS-002','2024-11-01',80.00,95.00,1),
('WAS-002','2024-12-01',95.00,112.00,1),
('WAS-003','2024-11-01',500.00,540.00,1),
('WAS-004','2024-11-01',1000.00,1085.00,1);

INSERT INTO bills (account_number, billing_month, units_consumed, amount_due, due_date, payment_status) VALUES
('WAS-001','2024-11-01',12.00,77.50,'2024-11-30','paid'),
('WAS-001','2024-12-01',15.00,92.50,'2024-12-31','paid'),
('WAS-001','2025-01-01',13.00,82.50,'2025-01-31','unpaid'),
('WAS-002','2024-11-01',15.00,92.50,'2024-11-30','partial'),
('WAS-002','2024-12-01',17.00,102.50,'2024-12-31','unpaid'),
('WAS-003','2024-11-01',40.00,340.00,'2024-11-30','paid'),
('WAS-004','2024-11-01',85.00,1020.00,'2024-11-30','unpaid');

INSERT INTO payments (bill_id, account_number, amount_paid, payment_method, transaction_ref) VALUES
(1,'WAS-001',77.50,'mpesa','MPE20241101001'),
(2,'WAS-001',92.50,'ecocash','ECO20241201001'),
(4,'WAS-002',50.00,'cash','CASH20241101001'),
(6,'WAS-003',340.00,'bank','BNK20241101001');
