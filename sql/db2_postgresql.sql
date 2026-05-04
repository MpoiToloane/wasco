-- ============================================================
-- WASCO Water Billing System - Database 2 (PostgreSQL - Analytics)
-- ============================================================

CREATE DATABASE wasco_analytics;
\c wasco_analytics;

-- Usage analytics (replicated/aggregated from DB1)
CREATE TABLE usage_analytics (
    analytics_id SERIAL PRIMARY KEY,
    account_number VARCHAR(20) NOT NULL,
    district VARCHAR(50) NOT NULL,
    customer_type VARCHAR(20) NOT NULL,
    period DATE NOT NULL,
    total_units NUMERIC(10,2) DEFAULT 0,
    total_billed NUMERIC(10,2) DEFAULT 0,
    payment_status VARCHAR(20),
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- District summaries for branch manager dashboard
CREATE TABLE district_summaries (
    summary_id SERIAL PRIMARY KEY,
    district VARCHAR(50) NOT NULL,
    period_type VARCHAR(20) NOT NULL CHECK (period_type IN ('daily','weekly','monthly','quarterly','yearly')),
    period_start DATE NOT NULL,
    total_customers INT DEFAULT 0,
    total_consumption NUMERIC(10,2) DEFAULT 0,
    total_revenue NUMERIC(10,2) DEFAULT 0,
    outstanding_balance NUMERIC(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System audit log
CREATE TABLE audit_log (
    log_id SERIAL PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_affected VARCHAR(50),
    record_id VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- VIEWS
-- ============================================================

CREATE VIEW v_district_performance AS
SELECT district,
       SUM(total_units) AS total_units,
       SUM(total_billed) AS total_billed,
       COUNT(DISTINCT account_number) AS customers,
       ROUND(SUM(total_billed) / NULLIF(COUNT(DISTINCT account_number),0), 2) AS avg_bill_per_customer
FROM usage_analytics
GROUP BY district
ORDER BY total_billed DESC;

CREATE VIEW v_yearly_trend AS
SELECT EXTRACT(YEAR FROM period) AS year,
       EXTRACT(MONTH FROM period) AS month,
       SUM(total_units) AS total_units,
       SUM(total_billed) AS total_revenue
FROM usage_analytics
GROUP BY year, month
ORDER BY year, month;

-- ============================================================
-- SAMPLE ANALYTICS DATA
-- ============================================================

INSERT INTO usage_analytics (account_number, district, customer_type, period, total_units, total_billed, payment_status) VALUES
('WAS-001','Maseru','residential','2024-11-01',12.00,77.50,'paid'),
('WAS-001','Maseru','residential','2024-12-01',15.00,92.50,'paid'),
('WAS-001','Maseru','residential','2025-01-01',13.00,82.50,'unpaid'),
('WAS-002','Maseru','residential','2024-11-01',15.00,92.50,'partial'),
('WAS-002','Maseru','residential','2024-12-01',17.00,102.50,'unpaid'),
('WAS-003','Leribe','commercial','2024-11-01',40.00,340.00,'paid'),
('WAS-004','Berea','industrial','2024-11-01',85.00,1020.00,'unpaid');

INSERT INTO district_summaries (district, period_type, period_start, total_customers, total_consumption, total_revenue, outstanding_balance) VALUES
('Maseru','monthly','2024-11-01',2,27.00,170.00,92.50),
('Maseru','monthly','2024-12-01',2,32.00,195.00,102.50),
('Leribe','monthly','2024-11-01',1,40.00,340.00,0.00),
('Berea','monthly','2024-11-01',1,85.00,1020.00,1020.00),
('Maseru','yearly','2024-01-01',2,59.00,365.00,195.00);
