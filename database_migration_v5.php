<?php
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE users ADD COLUMN mobile_verified TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN otp_expiry DATETIME DEFAULT NULL"
];

foreach ($queries as $q) {
    mysqli_query($conn, $q);
}
echo "Migration Complete";
?>
