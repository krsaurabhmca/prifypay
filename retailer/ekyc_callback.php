<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

session_start();
$uId = $_SESSION['user_id'];

// Get parameters from DigiLocker redirect
$verificationId = $_GET['verification_id'] ?? '';
$referenceId = $_GET['reference_id'] ?? '';
$orderId = $_GET['orderid'] ?? '';
$status = $_GET['status'] ?? '';

if ($status == 'Success') {
    // Determine if this was Aadhaar or PAN based on the order ID or we can try both
    // Usually, the API response for 'get_document' will tell us.
    // For now, let's try to get the document.
    
    // We need to know if it's AADHAAR or PAN. We can check the txid in database.
    $res = getDigilockerDocument($verificationId, $referenceId, $orderId, 'AADHAAR');
    
    if ($res['status'] == 'Success') {
        // Aadhaar Verified
        $name = mysqli_real_escape_string($conn, $res['name']);
        $uid = mysqli_real_escape_string($conn, $res['uid']);
        $sql = "UPDATE kyc_details SET aadhar_status = 'verified', aadhar_no = '$uid' WHERE user_id = $uId";
        mysqli_query($conn, $sql);
        header("Location: kyc.php?msg=Aadhaar Verified Successfully");
    } else {
        // Try PAN
        $res = getDigilockerDocument($verificationId, $referenceId, $orderId, 'PAN');
        if ($res['status'] == 'Success') {
            // PAN Verified
            $pan = mysqli_real_escape_string($conn, $res['pan_number'] ?? ''); // Check actual key in success response
            $sql = "UPDATE kyc_details SET pan_status = 'verified' WHERE user_id = $uId";
            mysqli_query($conn, $sql);
            header("Location: kyc.php?msg=PAN Verified Successfully");
        } else {
            header("Location: kyc.php?error=Verification failed: " . $res['message']);
        }
    }
} else {
    header("Location: kyc.php?error=DigiLocker verification failed or cancelled.");
}
?>
