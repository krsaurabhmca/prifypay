<?php
require_once 'includes/db.php';

// Add Admin Payout Fee if missing
$check = mysqli_query($conn, "SELECT id FROM commissions WHERE role = 'admin' AND transaction_type = 'payout'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO commissions (role, transaction_type, method, value) VALUES ('admin', 'payout', 'flat', 8.00)");
} else {
    mysqli_query($conn, "UPDATE commissions SET value = 8.00, method = 'flat' WHERE role = 'admin' AND transaction_type = 'payout'");
}

// Update Distributor Payout to 2.50 Flat
mysqli_query($conn, "UPDATE commissions SET value = 2.50, method = 'flat' WHERE role = 'distributor' AND transaction_type = 'payout'");

// Update Retailer Payout to 5.00 Flat
mysqli_query($conn, "UPDATE commissions SET value = 5.00, method = 'flat' WHERE role = 'retailer' AND transaction_type = 'payout'");

// Also update Payin to match the "15 + 2.5 + 5" example (Wait, user said 15+2.5+5 for payin in the last message, but 1.5% earlier. I'll stick to the split logic but use the numbers provided now if they are different).
// In example 2: 1000 Payin leads to 22.5 deduction. (2.25%)
// Admin: 15/1000 = 1.5%
// Dist: 2.5/1000 = 0.25%
// Ret: 5/1000 = 0.5%
// This matches my previous migration (1.5%, 0.25%, 0.5%). So Payin is already correct.

echo "Payout Commissions updated to match user example logic (8, 2.5, 5).\n";
