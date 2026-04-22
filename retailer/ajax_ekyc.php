<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

session_start();
$uId = $_SESSION['user_id'];

if ($_POST['action'] == 'create_url') {
    $type = $_POST['type']; // AADHAAR or PAN
    $orderId = "KYC_" . $uId . "_" . time();
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $redirectUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/ekyc_callback.php";

    $res = createDigilockerUrl($type, $orderId, $redirectUrl);

    if ($res['status'] == 'Success') {
        // Log the TXID and order ID to database for later retrieval
        $txid = $res['txid'];
        $sql = "UPDATE kyc_details SET ekyc_txid = '$txid' WHERE user_id = $uId";
        mysqli_query($conn, $sql);
        
        echo json_encode(['success' => true, 'url' => $res['url']]);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit;
}
?>
