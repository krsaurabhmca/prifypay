<?php
require_once 'includes/db.php';
$queries = [
    "ALTER TABLE transactions ADD COLUMN payout_bene_id INT DEFAULT NULL",
    "ALTER TABLE transactions ADD COLUMN payout_amount DECIMAL(15,2) DEFAULT NULL"
];
foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Executed: $sql\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
