<?php
/**
 * PrifyPay - Database Migration v2
 * This script adds KYC and Wallet enhancements.
 */

require_once __DIR__ . '/includes/db.php';

echo "<h1>PrifyPay Database Migration v2</h1>";
echo "<pre>";

function executeQuery($conn, $sql, $description) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ $description: Success\n";
    } else {
        echo "❌ $description: Error - " . mysqli_error($conn) . "\n";
    }
}

// 1. Update users table
$sql = "ALTER TABLE users 
        ADD COLUMN earnings_balance DECIMAL(15,2) DEFAULT 0.00 AFTER wallet_balance,
        ADD COLUMN kyc_status ENUM('pending', 'verified', 'rejected', 'not_submitted') DEFAULT 'not_submitted' AFTER status,
        ADD COLUMN business_name VARCHAR(255) DEFAULT NULL AFTER name,
        ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER password";
executeQuery($conn, $sql, "Updating users table with new columns");

// 2. Create kyc_details table
$sql = "CREATE TABLE IF NOT EXISTS kyc_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aadhar_no VARCHAR(20) NOT NULL,
    pan_no VARCHAR(20) NOT NULL,
    account_no VARCHAR(30) NOT NULL,
    ifsc VARCHAR(15) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    aadhar_front VARCHAR(255) DEFAULT NULL,
    aadhar_back VARCHAR(255) DEFAULT NULL,
    pan_card VARCHAR(255) DEFAULT NULL,
    passbook_check VARCHAR(255) DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
executeQuery($conn, $sql, "Creating kyc_details table");

echo "\nMigration completed.\n";
echo "</pre>";
?>
