<?php
require_once 'db.php';

$queries = [
    "ALTER TABLE users ADD COLUMN pan_no VARCHAR(20) AFTER phone",
    "ALTER TABLE users ADD COLUMN aadhaar_no VARCHAR(20) AFTER pan_no",
    "ALTER TABLE beneficiaries ADD COLUMN pan_no VARCHAR(20) AFTER bank_name",
    "ALTER TABLE beneficiaries ADD COLUMN aadhaar_no VARCHAR(20) AFTER pan_no"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Executed: $sql <br>";
    } else {
        echo "Error: " . mysqli_error($conn) . " <br>";
    }
}
?>
