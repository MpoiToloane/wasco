# WASCO - Distributed Online Water Bill Management System
## DIDB2210 Group Project — Limkokwing University

---

## PROJECT STRUCTURE

```
wasco/
├── index.php                  # Login page
├── logout.php
├── config/
│   └── database.php           # DB1 (MySQL) + DB2 (PostgreSQL) connections
├── includes/
│   ├── auth.php               # Login, session, role management
│   ├── header.php             # Navigation
│   └── footer.php
├── pages/
│   ├── dashboard.php          # Customer dashboard
│   ├── my_bills.php           # Customer bills list
│   ├── pay_bill.php           # Payment page
│   ├── usage.php              # Usage history + chart
│   ├── leakage.php            # Report water leakage
│   ├── admin/
│   │   ├── dashboard.php      # Admin overview
│   │   ├── customers.php      # Manage customers
│   │   ├── bills.php          # Generate & manage bills
│   │   ├── payments.php       # Record payments
│   │   └── rates.php          # Manage billing rates
│   └── manager/
│       ├── dashboard.php      # Branch manager overview
│       └── reports.php        # Advanced reports & analytics
├── assets/
│   ├── css/style.css
│   └── js/main.js
└── sql/
    ├── db1_mysql.sql          # DB1 schema + sample data
    └── db2_postgresql.sql     # DB2 schema + sample data
```

---

## SETUP INSTRUCTIONS

### Requirements
- PHP 8.0+
- MySQL 8.0+
- PostgreSQL 13+
- Apache/Nginx with mod_rewrite
- PHP extensions: pdo_mysql, pdo_pgsql

### Step 1 — Setup DB1 (MySQL)
```bash
mysql -u root -p < sql/db1_mysql.sql
```

### Step 2 — Setup DB2 (PostgreSQL)
```bash
psql -U postgres < sql/db2_postgresql.sql
```

### Step 3 — Configure database credentials
Edit `config/database.php`:
```php
// MySQL
define('DB1_HOST', 'localhost');
define('DB1_NAME', 'wasco_operational');
define('DB1_USER', 'root');
define('DB1_PASS', 'your_password');

// PostgreSQL
define('DB2_HOST', 'localhost');
define('DB2_NAME', 'wasco_analytics');
define('DB2_USER', 'postgres');
define('DB2_PASS', 'your_password');
```

### Step 4 — Deploy to server
- Copy the entire `wasco/` folder to your web server's root (e.g. `htdocs/wasco` for XAMPP)
- Update `BASE_URL` in `config/database.php`:
  ```php
  define('BASE_URL', 'http://localhost/wasco');
  ```
- For online hosting, set `BASE_URL` to your domain

### Step 5 — Access the app
Open: `http://localhost/wasco`

---

## DEMO LOGIN CREDENTIALS

| Role           | Username  | Password |
|----------------|-----------|----------|
| Admin          | admin     | password |
| Branch Manager | manager   | password |
| Customer       | thabiso   | password |
| Customer       | mpho      | password |
| Customer       | lineo     | password |

---

## DISTRIBUTED DATABASE DESIGN

### DB1 — MySQL (Operational Database)
Handles all real-time transactional data:
- `customers` — customer accounts
- `users` — login accounts & roles
- `billing_rates` — tiered pricing tiers
- `water_usage` — meter readings
- `bills` — generated water bills
- `payments` — payment records
- `notifications` — bill notifications
- `leakage_reports` — water leak reports

**Views:**
- `v_customer_outstanding` — outstanding balances per customer
- `v_monthly_usage_report` — usage patterns by district/month

### DB2 — PostgreSQL (Analytics Database)
Stores aggregated analytics data synced from DB1:
- `usage_analytics` — per-account per-month analytics
- `district_summaries` — district-level aggregations
- `audit_log` — system audit trail (all user actions)

**Views:**
- `v_district_performance` — district comparison
- `v_yearly_trend` — revenue trend by month/year

---

## DATABASE CONCEPTS DEMONSTRATED

| Concept | Where Used |
|---|---|
| DDL (CREATE TABLE) | sql/db1_mysql.sql, sql/db2_postgresql.sql |
| DML (INSERT/UPDATE/DELETE) | All admin pages |
| Advanced SQL (Views) | v_customer_outstanding, v_monthly_usage_report, v_district_performance, v_yearly_trend |
| Embedded SQL (PDO) | config/database.php, includes/auth.php |
| TCL (Transactions) | pay_bill.php, admin/bills.php, admin/customers.php |
| Access Control (Roles) | includes/auth.php — customer/admin/branch_manager |
| Distributed DB | DB1 MySQL + DB2 PostgreSQL |
| Heterogeneous DBMS | MySQL ≠ PostgreSQL (different DBMS types) |
| Data Replication | syncToAnalytics() in config/database.php |
| Audit Logging | auditLog() writes to DB2 |

---

## FEATURES

- 🔐 Secure login with bcrypt password hashing
- 👥 Role-based access (customer / admin / branch manager)
- 💧 Water meter reading & bill generation
- 💳 Bill payment (M-Pesa, EcoCash, Bank, Cash)
- 📊 Usage history with bar chart
- 🗺️ District analytics dashboard (from DB2 PostgreSQL)
- ⚠️ Leakage reporting system
- 🔔 Bill notifications system
- 📋 SQL Views for reports
- 🔄 Real-time sync between DB1 and DB2
- 📝 Audit log of all user actions (stored in DB2)
