<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Read callback data - support both JSON body and form/GET params
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Fallback to GET/POST params if no JSON
if (!$data) {
    $data = $_REQUEST;
    $rawBody = json_encode($_REQUEST);
}

// Log the callback for debugging
file_put_contents(__DIR__ . '/../support/callback_log.txt', date('[Y-m-d H:i:s] ') . $rawBody . PHP_EOL, FILE_APPEND);

// Extract fields
$orderId = $data['order_id'] ?? '';
$status = strtolower($data['status'] ?? '');
$amount = (float)($data['amount'] ?? 0);
$txnId = $data['transaction_id'] ?? $data['txn_id'] ?? '';
$refId = $data['reference_id'] ?? '';

if (!empty($orderId) && $status == 'success') {
    $orderId = mysqli_real_escape_string($conn, $orderId);
    $txnId = mysqli_real_escape_string($conn, $txnId);
    $refId = mysqli_real_escape_string($conn, $refId);
    
    // Check if already processed (avoid double credit)
    $checkQ = mysqli_query($conn, "SELECT * FROM transactions WHERE utr = '$orderId' AND status = 'success'");
    if (mysqli_num_rows($checkQ) == 0) {
        
        // Find the pending transaction - try reference_id first, then match by order_id in payment_url
        $tx = null;
        
        if (!empty($refId)) {
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE reference_id = '$refId' AND status = 'pending'");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        // Fallback: try matching by order_id in the payment_url or api_response
        if (!$tx && !empty($orderId)) {
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE (payment_url LIKE '%$orderId%' OR api_response LIKE '%$orderId%') AND status = 'pending' AND type = 'payin' ORDER BY id DESC LIMIT 1");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        // Fallback: match by amount for recent pending payin (last 2 hours)
        if (!$tx && $amount > 0) {
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE type = 'payin' AND status = 'pending' AND amount = $amount AND created_at >= NOW() - INTERVAL 2 HOUR ORDER BY id DESC LIMIT 1");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        if ($tx) {
            $uId = $tx['user_id'];
            $creditAmount = $tx['amount']; // Use the logged amount
            
            // 1. Credit wallet
            updateWallet($conn, $uId, $creditAmount, 'add');
            
            // 2. Update transaction status
            $utrValue = !empty($txnId) ? $txnId : $orderId;
            mysqli_query($conn, "UPDATE transactions SET status = 'success', utr = '$utrValue' WHERE id = " . $tx['id']);
            
            // 3. Handle Payin Commissions (if configured)
            $retComm = getCommissionValue($conn, 'retailer', 'payin');
            $distComm = getCommissionValue($conn, 'distributor', 'payin');
            
            $retailerEarn = calculateCommission($creditAmount, $retComm);
            $distributorEarn = calculateCommission($creditAmount, $distComm);
            
            if ($retailerEarn > 0) {
                updateWallet($conn, $uId, $retailerEarn, 'add');
                logTransaction($conn, $uId, 'commission', $retailerEarn, 0, 0, 0, $retailerEarn, 'success', 'COMM_RET_'.$utrValue);
            }
            
            // Parent Distributor commission
            $userRes = mysqli_query($conn, "SELECT parent_id FROM users WHERE id = $uId");
            $uRow = mysqli_fetch_assoc($userRes);
            if ($uRow['parent_id'] && $distributorEarn > 0) {
                updateWallet($conn, $uRow['parent_id'], $distributorEarn, 'add');
                logTransaction($conn, $uRow['parent_id'], 'commission', $distributorEarn, 0, 0, 0, $distributorEarn, 'success', 'COMM_DIST_'.$utrValue);
            }
        }
    }
} elseif (!empty($orderId) && in_array($status, ['failed', 'failure'])) {
    // Mark transaction as failed
    $orderId = mysqli_real_escape_string($conn, $orderId);
    $refId = mysqli_real_escape_string($conn, $refId);
    
    if (!empty($refId)) {
        mysqli_query($conn, "UPDATE transactions SET status = 'failed', utr = '$orderId' WHERE reference_id = '$refId' AND status = 'pending'");
    } else {
        mysqli_query($conn, "UPDATE transactions SET status = 'failed', utr = '$orderId' WHERE (payment_url LIKE '%$orderId%' OR api_response LIKE '%$orderId%') AND status = 'pending' AND type = 'payin'");
    }
}

echo json_encode(["status" => "received"]);
?>
