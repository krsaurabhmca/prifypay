<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "--- PRIPAY DIAGNOSTICS ---\n";

// 1. Check DB Connection
require_once 'includes/db.php';
if ($conn) {
    echo "DB Connection: SUCCESS\n";
    $q = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    if ($q) {
        $row = mysqli_fetch_assoc($q);
        echo "User Count: " . $row['count'] . "\n";
    } else {
        echo "DB Query Failed: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "DB Connection: FAILED\n";
}

// 2. Check API Config
require_once 'includes/config.php';
echo "API Mode: " . API_MODE . "\n";
echo "API Base: " . API_BASE_URL . "\n";

// 3. Test API Call (Balance)
require_once 'includes/api_helper.php';

echo "Testing Balance Check API...\n";
$res = callAPI("GET", "balance-check");

if ($res['success']) {
    echo "API Connection: SUCCESS\n";
    echo "Raw Response: " . $res['raw'] . "\n";
    $bal = getApiBalance();
    echo "Parsed Balance: " . $bal . "\n";
} else {
    echo "API Connection: FAILED\n";
    echo "Error: " . ($res['error'] ?? 'Unknown Error') . "\n";
    echo "HTTP Code: " . $res['http_code'] . "\n";
    echo "Raw Response: " . ($res['raw'] ?? 'NONE') . "\n";
}

echo "--- END DIAGNOSTICS ---\n";
?>
