<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Log the callback for debugging
file_put_contents('payout_callback_log.txt', date('[Y-m-d H:i:s] ') . $rawBody . PHP_EOL, FILE_APPEND);

if (isset($data['reference_id'])) {
    $refId = mysqli_real_escape_string($conn, $data['reference_id']);
    $status = ($data['status'] == 'processed' || $data['status'] == 'success') ? 'success' : 'failed';
    $utr = mysqli_real_escape_string($conn, $data['utr'] ?? '');

    // Update transaction status
    $sql = "UPDATE transactions SET status = '$status', utr = '$utr' WHERE reference_id = '$refId'";
    mysqli_query($conn, $sql);
    
    // If failed, refund the amount to Retailer
    if ($status == 'failed') {
        $txQuery = mysqli_query($conn, "SELECT * FROM transactions WHERE reference_id = '$refId'");
        $tx = mysqli_fetch_assoc($txQuery);
        if ($tx && $tx['status'] != 'refunded') {
            $refundAmount = $tx['amount'] + $tx['fee'];
            updateWallet($conn, $tx['user_id'], $refundAmount, 'add');
            // Log refund
            logTransaction($conn, $tx['user_id'], 'payout', $refundAmount, 0, 0, 0, 0, 'success', 'REFUND_'.$refId, '', '', 'Refunded due to failure');
            mysqli_query($conn, "UPDATE transactions SET status = 'refunded' WHERE id = " . $tx['id']);
        }
    }
}

echo "OK";
?>
