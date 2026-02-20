<?php
// config.php - Database Configuration
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'electronics_inventory');

// Application Configuration
define('APP_NAME', 'Electronics Inventory System');
define('APP_VERSION', '1.0.0');

// Test Push: marker added for verifying repository push

// Timezone
date_default_timezone_set('Africa/Mbabane');

// ERRor reporting 

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions
function sanitize($data) {
    global $conn;
    if ($data === null || $data === '') return '';
    return $conn->real_escape_string(trim($data));
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function hasPermission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $roles = ['staff' => 1, 'admin' => 2, 'super_admin' => 3];
    $user_level = $roles[$_SESSION['role']] ?? 0;
    $required_level = $roles[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

function logActivity($user_id, $action, $table_name, $record_id, $description) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

function generateAssetTag() {
    global $conn;
    $prefix = "AST";
    
    // Get the highest number across all assets
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(asset_tag, 4) AS UNSIGNED)) as max_num FROM assets WHERE asset_tag LIKE '{$prefix}%'");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $next_num = ($row['max_num'] ?? 0) + 1;
    } else {
        $next_num = 1;
    }
    
    // Format: AST000001, AST000002, etc.
    return $prefix . str_pad($next_num, 6, '0', STR_PAD_LEFT);
}

function formatCurrency($amount) {
    return 'E ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>