<?php
require_once 'includes/db.php';

// Check if admin payin already exists
$check = mysqli_query($conn, "SELECT id FROM commissions WHERE role = 'admin' AND transaction_type = 'payin'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO commissions (role, transaction_type, method, value) VALUES ('admin', 'payin', 'percentage', 1.50)");
    echo "Admin Payin Fee added.\n";
} else {
    echo "Admin Payin Fee already exists.\n";
}

// Update Distributor Payin to 0.25% as per user example
mysqli_query($conn, "UPDATE commissions SET value = 0.25 WHERE role = 'distributor' AND transaction_type = 'payin'");

// Update Retailer Payin to 0.50% as per user example
mysqli_query($conn, "UPDATE commissions SET value = 0.50 WHERE role = 'retailer' AND transaction_type = 'payin'");

echo "Commissions updated to match user example logic.\n";
