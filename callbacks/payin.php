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
$refId = $orderData['merchant_ref_id'] ?? $data['merchant_ref_id'] ?? $data['reference_id'] ?? $data['data']['merchant_ref_id'] ?? '';

$successStatuses = ['success', 'completed', 'captured', 'paid'];

if (!empty($orderId) && in_array($status, $successStatuses)) {
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
            
            // 4. Calculate Commissions & Net Credit
            $retComm = getCommissionValue($conn, 'retailer', 'payin');
            $distComm = getCommissionValue($conn, 'distributor', 'payin');
            $adminComm = getCommissionValue($conn, 'admin', 'payin');
            
            $retailerEarn = calculateCommission($creditAmount, $retComm);
            $distributorEarn = calculateCommission($creditAmount, $distComm);
            $adminFee = calculateCommission($creditAmount, $adminComm);
            
            $totalDeductions = $adminFee + $distributorEarn + $retailerEarn;
            $netCredit = $creditAmount - $totalDeductions;

            // 1. Credit Main Wallet (Net Amount)
            updateWallet($conn, $uId, $netCredit, 'add');
            
            // 2. Update transaction status
            $utrValue = !empty($txnId) ? $txnId : $orderId;
            $rawBodyEsc = mysqli_real_escape_string($conn, $rawBody);
            mysqli_query($conn, "UPDATE transactions SET 
                status = 'success', 
                utr = '$utrValue', 
                fee = $adminFee,
                commission_retailer = $retailerEarn,
                commission_distributor = $distributorEarn,
                api_response = '$rawBodyEsc' 
                WHERE id = " . $tx['id']);
            
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
                    
                    $payoutRetailerFee = calculateCommission($payoutAmount, $payoutComm);
                    $payoutDistributorPart = calculateCommission($payoutAmount, $distComm);
                    $totalPayoutDeduction = $payoutAmount + $payoutRetailerFee;
                    
                    if ($uData['wallet_balance'] >= $totalPayoutDeduction) {
                        // PRE-CHECK Gateway Balance
                        $apiBalance = getApiBalance();
                        if (is_array($apiBalance)) {
                            $mode = getSetting($conn, 'api_mode', API_MODE);
                            $currentLimit = ($mode == 'live') ? ($apiBalance['payout_balance'] ?? $apiBalance['wallet_balance'] ?? 0) : ($apiBalance['test_wallet_balance'] ?? 0);
                        } else {
                            $currentLimit = $apiBalance;
                        }

                        if ($currentLimit < $payoutAmount) {
                            $payoutRef = "AUTO_FAIL_" . time() . "_" . $uId;
                            logTransaction($conn, $uId, 'payout', $payoutAmount, $payoutRetailerFee, $payoutDistributorPart, ($payoutRetailerFee - $payoutDistributorPart), $payoutRetailerFee, 'failed', $payoutRef, '', 'Gateway balance insufficient: ' . $currentLimit);
                        } else {
                            $payoutRef = "AUTO_" . time() . "_" . $uId;
                            $pRes = createPayout($payoutAmount, $bene['account_number'], $bene['ifsc'], $bene['bank_name'], $bene['name'], PAYOUT_CALLBACK_URL, $payoutRef);
                            
                            if ($pRes['success']) {
                                updateWallet($conn, $uId, $totalPayoutDeduction, 'sub');
                                if ($uData['parent_id']) {
                                    updateEarningsWallet($conn, $uData['parent_id'], $payoutDistributorPart, 'add');
                                    logTransaction($conn, $uData['parent_id'], 'commission', $payoutDistributorPart, 0, 0, 0, 0, 'success', 'COMM_'.$payoutRef);
                                }
                                
                                $apiStatus = strtolower($pRes['data']['status'] ?? '');
                                $payoutUtr = $pRes['data']['utr'] ?? $pRes['data']['transaction_id'] ?? '';
                                $pStatus = ($apiStatus == 'processed' || $apiStatus == 'success') ? 'success' : 'pending';
                                
                                logTransaction($conn, $uId, 'payout', $payoutAmount, $payoutRetailerFee, $payoutDistributorPart, ($payoutRetailerFee - $payoutDistributorPart), $payoutRetailerFee, $pStatus, $payoutRef, $payoutUtr, '', $pRes['raw']);
                            } else {
                                $errMsg = $pRes['data']['message'] ?? $pRes['error'] ?? 'Auto-Payout Failed';
                                logTransaction($conn, $uId, 'payout', $payoutAmount, $payoutRetailerFee, $payoutDistributorPart, ($payoutRetailerFee - $payoutDistributorPart), $payoutRetailerFee, 'failed', $payoutRef, '', $errMsg, $pRes['raw']);
                            }
                        }
                    }
                }
            }

            // 4. Update Earnings Wallets
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
