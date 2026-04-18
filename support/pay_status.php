<?php

// STEP 1: Raw Data Read karo
$input = file_get_contents("php://input");

// STEP 2: Log for debugging
file_put_contents("callback_log.txt", date("Y-m-d H:i:s") . "\n" . $input . "\n\n", FILE_APPEND);

// STEP 3: JSON decode
$data = json_decode($input, true);

// Agar JSON nahi mila (fallback GET)
if (!$data) {
    $data = $_REQUEST;
}

// STEP 4: Important fields
$order_id   = $data['order_id'] ?? '';
$status     = $data['status'] ?? '';
$amount     = $data['amount'] ?? '';
$txn_id     = $data['transaction_id'] ?? '';

// STEP 5: Database update logic
// include "db.php";

// if ($order_id != '') {

//     if ($status == "SUCCESS") {

//         // Payment Success
//         mysqli_query($conn, "UPDATE payments 
//             SET status='success', txn_id='$txn_id' 
//             WHERE order_id='$order_id'");

//     } else {

//         // Payment Failed / Pending
//         mysqli_query($conn, "UPDATE payments 
//             SET status='failed' 
//             WHERE order_id='$order_id'");
//     }
// }

// // STEP 6: Always return response
// echo json_encode([
//     "status" => "received"
// ]);