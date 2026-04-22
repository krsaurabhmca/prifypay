<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Fallback to REQUEST for form-data callbacks
if (!$data) {
    $data = $_REQUEST;
    $rawBody = json_encode($_REQUEST);
}

// Log the callback for debugging
file_put_contents(__DIR__ . '/../support/callback_log.txt', date('[Y-m-d H:i:s] PAYOUT: ') . $rawBody . PHP_EOL, FILE_APPEND);

// SLPE V2 often nests data under a 'data' key
$resData = $data['data'] ?? $data;

$refId = $resData['reference_id'] ?? $resData['merchant_ref_id'] ?? '';
$statusInput = strtolower($resData['status'] ?? $data['status'] ?? '');
$utr = $resData['utr'] ?? $resData['transaction_id'] ?? '';

if (!empty($refId)) {
    $refIdEsc = mysqli_real_escape_string($conn, $refId);
    $utrEsc = mysqli_real_escape_string($conn, $utr);
    $rawBodyEsc = mysqli_real_escape_string($conn, $rawBody);
    
    // Status mapping
    if (in_array($statusInput, ['processed', 'success', 'completed'])) {
        $status = 'success';
    } elseif (in_array($statusInput, ['failed', 'failure', 'rejected', 'reversed'])) {
        $status = 'failed';
    } else {
        $status = 'pending';
    }

    // Update transaction status and store raw response
    $sql = "UPDATE transactions SET status = '$status', utr = '$utrEsc', api_response = '$rawBodyEsc' WHERE reference_id = '$refIdEsc' AND status != 'success' AND status != 'refunded'";
    mysqli_query($conn, $sql);
    
    // If failed, refund the amount + fee to Retailer
    if ($status == 'failed') {
        $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE reference_id = '$refIdEsc' AND status = 'failed' AND type = 'payout'");
        $tx = mysqli_fetch_assoc($txQuery);
        
        if ($tx) {
            $uId = $tx['user_id'];
            $refundAmount = (float)$tx['amount'] + (float)$tx['fee'];
            
            // Refund to wallet
            updateWallet($conn, $uId, $refundAmount, 'add');
            
            // Log the refund transaction
            $refundRef = 'REFUND_' . $refId;
            logTransaction($conn, $uId, 'payout', $refundAmount, 0, 0, 0, 0, 'success', $refundRef, $utr, '', 'Refund for failed payout ' . $refId);
            
            // Mark original as refunded to prevent double refund
            mysqli_query($conn, "UPDATE transactions SET status = 'refunded' WHERE id = " . $tx['id']);
        }
    }
}

echo json_encode(["status" => "received"]);
?>
