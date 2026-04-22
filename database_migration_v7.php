<?php
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'distributor', 'retailer', 'dev') NOT NULL",
    "UPDATE users SET role = 'dev' WHERE email = 'admin@prifypay.com'" // Elevate default admin to dev
];

foreach ($queries as $q) {
    mysqli_query($conn, $q);
}
echo "Migration Complete: Role 'dev' added and main admin elevated.";
?>
