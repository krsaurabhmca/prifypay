<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Log the callback
file_put_contents('../support/callback_log.txt', date('[Y-m-d H:i:s] ') . $rawBody . PHP_EOL, FILE_APPEND);

if (isset($data['order_id']) && $data['status'] == 'success') {
    $orderId = mysqli_real_escape_string($conn, $data['order_id']);
    
    // Check if already processed
    $checkQ = mysqli_query($conn, "SELECT * FROM transactions WHERE utr = '$orderId' AND status = 'success'");
    if (mysqli_num_rows($checkQ) == 0) {
        $amount = (float)$data['amount'];
        $userId = 0;
        
        // Find user by reference or something else? 
        // In payin.php we logged a pending transaction with a refId.
        // Usually the API sends back the reference_id we provided.
        $refId = mysqli_real_escape_string($conn, $data['reference_id'] ?? '');
        $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE reference_id = '$refId' AND status = 'pending'");
        $tx = mysqli_fetch_assoc($txQuery);
        
        if ($tx) {
            $uId = $tx['user_id'];
            
            // 1. Update Retailer Wallet
            updateWallet($conn, $uId, $amount, 'add');
            
            // 2. Update Transaction Status
            mysqli_query($conn, "UPDATE transactions SET status = 'success', utr = '$orderId' WHERE id = " . $tx['id']);
            
            // 3. Handle Payin Commissions (if any)
            // Get commissions
            $retComm = getCommissionValue($conn, 'retailer', 'payin');
            $distComm = getCommissionValue($conn, 'distributor', 'payin');
            
            $retailerEarn = calculateCommission($amount, $retComm);
            $distributorEarn = calculateCommission($amount, $distComm);
            
            if ($retailerEarn > 0) {
                updateWallet($conn, $uId, $retailerEarn, 'add');
                logTransaction($conn, $uId, 'commission', $retailerEarn, 0, 0, 0, $retailerEarn, 'success', 'COMM_RET_'.$orderId);
            }
            
            // Parent Distributor?
            $userRes = mysqli_query($conn, "SELECT parent_id FROM users WHERE id = $uId");
            $uRow = mysqli_fetch_assoc($userRes);
            if ($uRow['parent_id'] && $distributorEarn > 0) {
                updateWallet($conn, $uRow['parent_id'], $distributorEarn, 'add');
                logTransaction($conn, $uRow['parent_id'], 'commission', $distributorEarn, 0, 0, 0, $distributorEarn, 'success', 'COMM_DIST_'.$orderId);
            }
        }
    }
}

echo "OK";
?>
