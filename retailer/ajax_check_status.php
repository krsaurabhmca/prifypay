<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tx_id'])) {
    $txId = (int)$_POST['tx_id'];
    $uId = $_SESSION['user_id'];
    
    // Admin can check any, others only their own
    $where = ($_SESSION['role'] == 'admin') ? "id = $txId" : "id = $txId AND user_id = $uId";
    
    $res = mysqli_query($conn, "SELECT * FROM transactions WHERE $where LIMIT 1");
    $tx = mysqli_fetch_assoc($res);
    
    if (!$tx) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
        exit();
    }
    
    if ($tx['status'] != 'pending') {
        echo json_encode(['success' => true, 'message' => 'Transaction already ' . $tx['status'], 'status' => $tx['status']]);
        exit();
    }
    
    $apiResponse = null;
    $newStatus = 'pending';
    $apiUtr = '';

    if ($tx['type'] == 'payin') {
        $orderId = !empty($tx['utr']) ? $tx['utr'] : $tx['reference_id'];
        $apiRes = getPayinStatus($orderId);
        
        if ($apiRes['success']) {
            $data = $apiRes['data']['data'] ?? $apiRes['data']['order_data'] ?? $apiRes['data'];
            $st = strtolower($data['status'] ?? '');
            if ($st == 'paid' || $st == 'success' || $st == 'captured') {
                $newStatus = 'success';
                $apiUtr = $data['transaction_id'] ?? $data['txn_id'] ?? '';
                
                // Update Wallet if success
                updateWallet($conn, $tx['user_id'], $tx['amount'], 'add');
            } elseif ($st == 'failed' || $st == 'failure') {
                $newStatus = 'failed';
            }
        }
    } elseif ($tx['type'] == 'payout') {
        $apiRes = getPayoutStatus($tx['reference_id']);
        
        if ($apiRes['success']) {
            $st = strtolower($apiRes['data']['status'] ?? '');
            if ($st == 'processed' || $st == 'success') {
                $newStatus = 'success';
                $apiUtr = $apiRes['data']['utr'] ?? '';
            } elseif ($st == 'failed' || $st == 'reversed') {
                $newStatus = 'failed';
                // Refund for payout failure
                updateWallet($conn, $tx['user_id'], ($tx['amount'] + $tx['fee']), 'add');
            }
        }
    }
    
    if ($newStatus != 'pending') {
        $uSql = "UPDATE transactions SET status = '$newStatus', utr = '$apiUtr' WHERE id = " . $tx['id'];
        mysqli_query($conn, $uSql);
        echo json_encode(['success' => true, 'message' => 'Status updated to ' . $newStatus, 'status' => $newStatus]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Still pending at gateway.', 'status' => 'pending']);
    }
    exit();
}
?>
