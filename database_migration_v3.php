<?php
require_once 'includes/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT
    )",
    "INSERT INTO settings (setting_key, setting_value) VALUES ('payin_gateway_id', '1') ON DUPLICATE KEY UPDATE setting_key=setting_key",
    "INSERT INTO settings (setting_key, setting_value) VALUES ('payout_gateway_id', '1') ON DUPLICATE KEY UPDATE setting_key=setting_key"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q <br>";
    } else {
        echo "Error: " . mysqli_error($conn) . " <br>";
    }
}
?>
