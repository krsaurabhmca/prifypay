<?php include_once('header.php');

$payoutData = [
    "amount" => 90,
    "mode" => "IMPS", // IMPS / NEFT
    "call_back_url" => "https://prifypay.morg.in/payout.php",
    "gateway_id" => "10",
    "reference_id" => "PAYOUT_" . time(),
    "bank_account" => [
        "name" => "AATIF KHAN",
        "ifsc" => "IDFB0043832",
        "bank_name" => "IDFC FIRST BANK",
        "account_number" => "10144105187"
    ]
];

    // bank_account" => [
    //     "name" => "Kumar Saurabh",
    //     "ifsc" => "UTIB0001218",
    //     "bank_name" => "AXIS BANK",
    //     "account_number" => "916010039686821"
    // ]
    
        // "name" => "AATIF KHAN",
        // "ifsc" => "IDFB0043832",
        // "bank_name" => "IDFC FIRST BANK",
        // "account_number" => "10144105187"

$response = callAPI('POST','create-payout',$payoutData);

echo "<pre>";
print_r($response);

file_put_contents("callback_log.txt", "Payout :". date("Y-m-d H:i:s") . "\n" . json_encode($response) . "\n\n", FILE_APPEND);