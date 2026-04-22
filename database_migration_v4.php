<?php
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE kyc_details ADD COLUMN aadhar_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER bank_name",
    "ALTER TABLE kyc_details ADD COLUMN pan_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER aadhar_status",
    "ALTER TABLE kyc_details ADD COLUMN ekyc_txid VARCHAR(100) AFTER pan_status"
];

foreach ($queries as $q) {
    mysqli_query($conn, $q);
}
echo "Migration Complete";
?>
