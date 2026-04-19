<?php
/**
 * PrifyPay - Database Migration Script
 * This script safely updates the database schema.
 */

// Load database connection
$config_path = __DIR__ . '/includes/db.php';
if (!file_exists($config_path)) {
    die("Error: db.php not found at $config_path. Run this script from the project root.");
}
require_once $config_path;

echo "<h1>PrifyPay Database Update</h1>";
echo "<pre>";

function columnExists($conn, $table, $column) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}

$updates = [
    // Table: users
    ['table' => 'users', 'column' => 'pan_no', 'sql' => "ALTER TABLE users ADD COLUMN pan_no VARCHAR(20) AFTER phone"],
    ['table' => 'users', 'column' => 'aadhaar_no', 'sql' => "ALTER TABLE users ADD COLUMN aadhaar_no VARCHAR(20) AFTER pan_no"],
    
    // Table: beneficiaries
    ['table' => 'beneficiaries', 'column' => 'pan_no', 'sql' => "ALTER TABLE beneficiaries ADD COLUMN pan_no VARCHAR(20) AFTER bank_name"],
    ['table' => 'beneficiaries', 'column' => 'aadhaar_no', 'sql' => "ALTER TABLE beneficiaries ADD COLUMN aadhaar_no VARCHAR(20) AFTER pan_no"],
    
    // Table: transactions
    ['table' => 'transactions', 'column' => 'payout_bene_id', 'sql' => "ALTER TABLE transactions ADD COLUMN payout_bene_id INT DEFAULT NULL"],
    ['table' => 'transactions', 'column' => 'payout_amount', 'sql' => "ALTER TABLE transactions ADD COLUMN payout_amount DECIMAL(15,2) DEFAULT NULL"],
];

foreach ($updates as $update) {
    $table = $update['table'];
    $column = $update['column'];
    $sql = $update['sql'];
    
    if (!columnExists($conn, $table, $column)) {
        if (mysqli_query($conn, $sql)) {
            echo "✅ Successfully added column `$column` to table `$table`.\n";
        } else {
            echo "❌ Error adding column `$column` to table `$table`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "ℹ️ Column `$column` already exists in table `$table`. Skipping.\n";
    }
}

echo "\nMigration process completed.\n";
echo "</pre>";
?>
