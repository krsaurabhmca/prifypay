<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

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

// Extract fields - SLPE V2 API uses a nested structure
$orderId = $data['data']['order_id'] ?? $data['order_id'] ?? '';
$orderData = $data['data']['order_data'] ?? $data; // Use nested order_data if available, else fallback to root

$status = strtolower($orderData['status'] ?? $data['status'] ?? '');
$amount = (float)($orderData['amount'] ?? $data['amount'] ?? 0);
$txnId = $orderData['transaction_id'] ?? $data['transaction_id'] ?? $orderData['txn_id'] ?? $data['txn_id'] ?? '';
$refId = $orderData['merchant_ref_id'] ?? $data['reference_id'] ?? $data['data']['merchant_ref_id'] ?? '';

if (!empty($orderId) && ($status == 'success' || $status == 'completed' || $status == 'captured')) {
    $orderIdEsc = mysqli_real_escape_string($conn, $orderId);
    $txnIdEsc = mysqli_real_escape_string($conn, $txnId);
    $refIdEsc = mysqli_real_escape_string($conn, $refId);
    
    // Check if already processed
    $checkQ = mysqli_query($conn, "SELECT * FROM transactions WHERE (utr = '$orderIdEsc' OR utr = '$txnIdEsc') AND status = 'success'");
    if (mysqli_num_rows($checkQ) == 0) {
        $tx = null;
        
        // 1. Try matching by reference_id
        if (!empty($refId)) {
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE reference_id = '$refIdEsc' AND status = 'pending'");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        // 2. Try matching by order_id or txn_id in logs
        if (!$tx && (!empty($orderId) || !empty($txnId))) {
            $search = !empty($orderId) ? $orderIdEsc : $txnIdEsc;
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE (payment_url LIKE '%$search%' OR api_response LIKE '%$search%' OR reference_id LIKE '%$search%') AND status = 'pending' AND type = 'payin' ORDER BY id DESC LIMIT 1");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        // 3. Last resort: match by amount and user (if we can find user in any field) for recent 2h
        if (!$tx && $amount > 0) {
            $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE type = 'payin' AND status = 'pending' AND amount = " . (float)$amount . " AND created_at >= NOW() - INTERVAL 2 HOUR ORDER BY id DESC LIMIT 1");
            $tx = mysqli_fetch_assoc($txQuery);
        }
        
        if ($tx) {
            $uId = $tx['user_id'];
            $creditAmount = $tx['amount'];
            
            // 1. Credit wallet
            updateWallet($conn, $uId, $creditAmount, 'add');
            
            // 2. Update transaction status
            $utrValue = !empty($txnId) ? $txnId : $orderId;
            mysqli_query($conn, "UPDATE transactions SET status = 'success', utr = '$utrValue' WHERE id = " . $tx['id']);
            
            // 3. Handle Auto-Payout (Fast Transfer)
            if (!empty($tx['payout_bene_id']) && !empty($tx['payout_amount'])) {
                $beneId = $tx['payout_bene_id'];
                $payoutAmount = (float)$tx['payout_amount'];
                
                $beneQ = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $beneId");
                $bene = mysqli_fetch_assoc($beneQ);
                
                if ($bene) {
                    $uRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
                    $uData = mysqli_fetch_assoc($uRes);
                    
                    $payoutComm = getCommissionValue($conn, 'retailer', 'payout');
                    $distComm = getCommissionValue($conn, 'distributor', 'payout');
                    
                    $retailerFee = calculateCommission($payoutAmount, $payoutComm);
                    $distributorPart = calculateCommission($payoutAmount, $distComm);
                    $totalDeduction = $payoutAmount + $retailerFee;
                    
                    if ($uData['wallet_balance'] >= $totalDeduction) {
                        $payoutRef = "AUTO_" . time() . "_" . $uId;
                        $pRes = createPayout($payoutAmount, $bene['account_number'], $bene['ifsc'], $bene['bank_name'], $bene['name'], PAYOUT_CALLBACK_URL, $payoutRef);
                        
                        if ($pRes['success']) {
                            updateWallet($conn, $uId, $totalDeduction, 'sub');
                            if ($uData['parent_id']) {
                                updateEarningsWallet($conn, $uData['parent_id'], $distributorPart, 'add');
                                logTransaction($conn, $uData['parent_id'], 'commission', $distributorPart, 0, 0, 0, 0, 'success', 'COMM_'.$payoutRef);
                            }
                            
                            $apiStatus = strtolower($pRes['data']['status'] ?? '');
                            $payoutUtr = $pRes['data']['utr'] ?? $pRes['data']['transaction_id'] ?? '';
                            $pStatus = ($apiStatus == 'processed' || $apiStatus == 'success') ? 'success' : 'pending';
                            
                            logTransaction($conn, $uId, 'payout', $payoutAmount, $retailerFee, $distributorPart, ($retailerFee - $distributorPart), $retailerFee, $pStatus, $payoutRef, $payoutUtr, '', $pRes['raw']);
                        }
                    }
                }
            }

            // 4. Handle Payin Commissions (if configured)
            $retComm = getCommissionValue($conn, 'retailer', 'payin');
            $distComm = getCommissionValue($conn, 'distributor', 'payin');
            
            $retailerEarn = calculateCommission($creditAmount, $retComm);
            $distributorEarn = calculateCommission($creditAmount, $distComm);
            
            if ($retailerEarn > 0) {
                updateEarningsWallet($conn, $uId, $retailerEarn, 'add');
                logTransaction($conn, $uId, 'commission', $retailerEarn, 0, 0, 0, $retailerEarn, 'success', 'COMM_RET_'.$utrValue);
            }
            
            $userRes = mysqli_query($conn, "SELECT parent_id FROM users WHERE id = $uId");
            $uRow = mysqli_fetch_assoc($userRes);
            if ($uRow['parent_id'] && $distributorEarn > 0) {
                updateEarningsWallet($conn, $uRow['parent_id'], $distributorEarn, 'add');
                logTransaction($conn, $uRow['parent_id'], 'commission', $distributorEarn, 0, 0, 0, $distributorEarn, 'success', 'COMM_DIST_'.$utrValue);
            }
        }
    }
} elseif (!empty($orderId) && in_array($status, ['failed', 'failure'])) {
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
