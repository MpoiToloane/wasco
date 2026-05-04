<?php
// ============================================================
// Database Configuration
// DB1: MySQL (Operational) | DB2: PostgreSQL (Analytics)
// ============================================================

// --- DB1: MySQL ---
define('DB1_HOST', 'localhost');
define('DB1_NAME', 'wasco_operational');
define('DB1_USER', 'root');
define('DB1_PASS', '');

// --- DB2: PostgreSQL ---
define('DB2_HOST', 'localhost');
define('DB2_NAME', 'wasco_analytics');
define('DB2_USER', 'postgres');
define('DB2_PASS', '123456');
define('DB2_PORT', '5432');

// App config
define('APP_NAME', 'WASCO Billing Portal');
define('BASE_URL', 'http://localhost/wasco');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('DEFAULT_LANGUAGE', 'st'); // Sesotho for Lesotho
define('DEFAULT_CURRENCY_SYMBOL', 'R'); // ZAR (South African Rand)

// Helper functions to get current language and currency (will use globals set in auth.php)
function getCurrentLanguage() {
    if (isset($GLOBALS['app_language'])) {
        return $GLOBALS['app_language'];
    }
    return DEFAULT_LANGUAGE;
}

function getCurrentCurrency() {
    if (isset($GLOBALS['app_currency'])) {
        return $GLOBALS['app_currency'];
    }
    return DEFAULT_CURRENCY_SYMBOL;
}

// Keep these for backwards compatibility - they check the globals
if (!defined('CURRENT_LANGUAGE')) {
    define('CURRENT_LANGUAGE', getCurrentLanguage());
}
if (!defined('CURRENT_CURRENCY_SYMBOL')) {
    define('CURRENT_CURRENCY_SYMBOL', getCurrentCurrency());
}

// Translations
$translations = [
    'en' => [
        'dashboard' => 'Dashboard',
        'my_bills' => 'My Bills',
        'pay_bill' => 'Pay Bill',
        'usage' => 'Usage',
        'report_leak' => 'Report Leak',
        'customers' => 'Customers',
        'bills' => 'Bills',
        'payments' => 'Payments',
        'rates' => 'Rates',
        'reports' => 'Reports',
        'districts' => 'Districts',
        'welcome' => 'Welcome',
        'account' => 'Account',
        'district' => 'District',
        'outstanding_balance' => 'Outstanding Balance',
        'total_bills' => 'Total Bills',
        'total_paid' => 'Total Paid',
        'latest_bill' => 'Latest Bill',
        'all_time' => 'All time',
        'payment_required' => 'Payment required',
        'all_paid_up' => 'All paid up!',
        'all_payments' => 'All payments',
        'water_usage_history' => 'Water Usage History',
        'recent_payments' => 'Recent Payments',
        'date' => 'Date',
        'month' => 'Month',
        'amount' => 'Amount',
        'method' => 'Method',
        'no_payments' => 'No payments yet',
        'pay_water_bill' => 'Pay Water Bill',
        'make_payment' => 'Make a payment for your outstanding bills',
        'no_outstanding_bills' => 'No outstanding bills! You\'re all caught up.',
        'outstanding_bills' => 'Outstanding Bills',
        'payment_details' => 'Payment Details',
        'select_bill' => 'Select Bill',
        'amount_to_pay' => 'Amount to Pay',
        'transaction_ref' => 'Transaction Reference',
        'payment_method' => 'Payment Method',
        'submit_payment' => 'Submit Payment',
        'bill_id' => 'Bill ID',
        'billing_month' => 'Month',
        'usage_m3' => 'Usage (m³)',
        'amount_due' => 'Amount Due',
        'amount_paid' => 'Amount Paid',
        'balance' => 'Balance',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'action' => 'Action',
        'pay' => 'Pay',
        'settled' => 'Settled',
        'generate_bill' => 'Generate Bill',
        'account_number' => 'Account Number',
        'full_name' => 'Full Name',
        'customer_type' => 'Customer Type',
        'units_consumed' => 'Units Consumed',
        'billing_rates' => 'Billing Rates',
        'cost_per_unit' => 'Cost/Unit',
        'effective_date' => 'Effective',
        'delete' => 'Delete',
        'add_rate' => 'Add Rate',
        'tier' => 'Tier',
        'min_m3' => 'Min (m³)',
        'max_m3' => 'Max (m³)',
        'admin_dashboard' => 'Admin Dashboard',
        'system_overview' => 'System overview — DB1: MySQL · DB2: PostgreSQL',
        'total_customers' => 'Total Customers',
        'registered_accounts' => 'Registered accounts',
        'total_billed' => 'Total Billed',
        'total_collected' => 'Total Collected',
        'unpaid_bills' => 'Unpaid Bills',
        'require_followup' => 'Require follow-up',
        'leakage_reports' => 'Leakage Reports',
        'branch_manager_dashboard' => 'Branch Manager Dashboard',
        'summative_insights' => 'Summative insights on water usage & billing across all districts',
        'all_districts' => 'All districts',
        'all_time_revenue' => 'All time revenue',
        'payments_received' => 'Payments received',
        'uncollected_revenue' => 'Uncollected revenue',
        'monthly_trend' => 'Monthly Trend',
        'logout' => 'Logout',
        'lang' => 'Lang',
        'currency' => 'Currency',
    ],
    'st' => [
        'dashboard' => 'Dashboard', // Keep English for technical terms
        'my_bills' => 'Lichelete tsa ka',
        'pay_bill' => 'Lefa Bill',
        'usage' => 'Tšebeliso',
        'report_leak' => 'Tlaleha Leak',
        'customers' => 'Bareki',
        'bills' => 'Lichelete',
        'payments' => 'Litefiso',
        'rates' => 'Litefello',
        'reports' => 'Litlaleho',
        'districts' => 'Litereke',
        'welcome' => 'Lumelisa',
        'account' => 'Akhaonto',
        'district' => 'Setereke',
        'outstanding_balance' => 'Chelete e setseng',
        'total_bills' => 'Lichelete tsohle',
        'total_paid' => 'Chelete e lefelletsoeng',
        'latest_bill' => 'Bill ea morao-rao',
        'all_time' => 'Nako eohle',
        'payment_required' => 'Tefiso e hlokahala',
        'all_paid_up' => 'Tsohle li lefelletsoe!',
        'all_payments' => 'Litefiso tsohle',
        'water_usage_history' => 'Histori ea Tšebeliso ea Metsi',
        'recent_payments' => 'Litefiso tsa Morao-rao',
        'date' => 'Letsatsi',
        'month' => 'Kgwedi',
        'amount' => 'Chelete',
        'method' => 'Mokhoa',
        'no_payments' => 'Ha ho litefiso',
        'pay_water_bill' => 'Lefa Bill ea Metsi',
        'make_payment' => 'Etsa tefiso bakeng sa lichelete tse setseng',
        'no_outstanding_bills' => 'Ha ho lichelete tse setseng! O ntse o lokile.',
        'outstanding_bills' => 'Lichelete Tse Setseng',
        'payment_details' => 'Lintlha tsa Tefiso',
        'select_bill' => 'Khetha Bill',
        'amount_to_pay' => 'Chelete eo u tla e lefa',
        'transaction_ref' => 'Nomoro ea Transaction',
        'payment_method' => 'Mokhoa oa Tefiso',
        'submit_payment' => 'Romela Tefiso',
        'bill_id' => 'ID ea Bill',
        'billing_month' => 'Kgwedi',
        'usage_m3' => 'Tšebeliso (m³)',
        'amount_due' => 'Chelete e lokelang ho lefuoa',
        'amount_paid' => 'Chelete e lefelletsoeng',
        'balance' => 'Balance',
        'due_date' => 'Letsatsi la ho lefa',
        'status' => 'Boemo',
        'action' => 'Ketso',
        'pay' => 'Lefa',
        'settled' => 'E felletsoe',
        'generate_bill' => 'Hlahisa Bill',
        'account_number' => 'Nomoro ea Akhaonto',
        'full_name' => 'Lebitso le Feletseng',
        'customer_type' => 'Mofuta oa Moreki',
        'units_consumed' => 'Liuniti Tse Sebelisitsoeng',
        'billing_rates' => 'Litefello tsa Billing',
        'cost_per_unit' => 'Litšenyehelo/Unit',
        'effective_date' => 'E sebetsa',
        'delete' => 'Tlosa',
        'add_rate' => 'Eketsa Rate',
        'tier' => 'Tier',
        'min_m3' => 'Min (m³)',
        'max_m3' => 'Max (m³)',
        'admin_dashboard' => 'Dashboard ea Admin',
        'system_overview' => 'Kakaretso ea Sisteme — DB1: MySQL · DB2: PostgreSQL',
        'total_customers' => 'Bareki Bohle',
        'registered_accounts' => 'Liakhaonto Tse Ngolisitsoeng',
        'total_billed' => 'Chelete eohle e Billed',
        'total_collected' => 'Chelete e Bokelletsoeng',
        'unpaid_bills' => 'Lichelete Tse sa Lefelloeng',
        'require_followup' => 'E hloka ho lateloa',
        'leakage_reports' => 'Litlaleho tsa Leakage',
        'branch_manager_dashboard' => 'Dashboard ea Mookameli oa Lekala',
        'summative_insights' => 'Lintlha tse akaretsang mabapi le tšebeliso ea metsi le billing ho litereke tsohle',
        'all_districts' => 'Litereke tsohle',
        'all_time_revenue' => 'Chelete ea nako eohle',
        'payments_received' => 'Litefiso Tse Amohetsoeng',
        'uncollected_revenue' => 'Chelete e sa bokelloang',
        'monthly_trend' => 'Tloaelo ea Khoeli le Khoeli',
        'logout' => 'Tsoa',
        'lang' => 'Puo',
        'currency' => 'Chelete',
    ],
];

// Translation function
function __($key) {
    global $translations;
    $lang = getCurrentLanguage();
    return $translations[$lang][$key] ?? $key;
}

/**
 * Connect to MySQL (DB1 - Operational)
 */
function getDB1(): PDO {
    static $pdo1 = null;
    if ($pdo1 === null) {
        try {
            $pdo1 = new PDO(
                "mysql:host=" . DB1_HOST . ";dbname=" . DB1_NAME . ";charset=utf8",
                DB1_USER, DB1_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die(json_encode(['error' => 'DB1 connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo1;
}

/**
 * Connect to PostgreSQL (DB2 - Analytics)
 */
function getDB2(): PDO {
    static $pdo2 = null;
    if ($pdo2 === null) {
        try {
            $dsn = "pgsql:host=" . DB2_HOST . ";port=" . DB2_PORT . ";dbname=" . DB2_NAME;
            $pdo2 = new PDO($dsn, DB2_USER, DB2_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die(json_encode(['error' => 'DB2 connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo2;
}

/**
 * Sync a record to PostgreSQL analytics after DB1 write
 */
function syncToAnalytics(string $accountNumber, string $district, string $customerType,
                          string $period, float $units, float $billed, string $status): void {
    try {
        $db2 = getDB2();
        $stmt = $db2->prepare("
            INSERT INTO usage_analytics (account_number, district, customer_type, period, total_units, total_billed, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute([$accountNumber, $district, $customerType, $period, $units, $billed, $status]);
    } catch (Exception $e) {
        // Log but don't break main flow
        error_log("Analytics sync failed: " . $e->getMessage());
    }
}

/**
 * Log action to PostgreSQL audit log
 */
function auditLog(int $userId, string $action, string $table = '', string $recordId = '', string $details = ''): void {
    try {
        $db2 = getDB2();
        $stmt = $db2->prepare("
            INSERT INTO audit_log (user_id, action, table_affected, record_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $table, $recordId, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
?>
