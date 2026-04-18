<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function callAPI($method, $endpoint, $data = [], $customHeaders = [])
{
    $baseUrl = "https://api.slpe.in/api/v2/";

    // Default SLPE Headers
    $defaultHeaders = [
        "api-mode: live",
        "api-secret: secret_oNkXroVDS0WY8aVt7E4YU3ynkX4CPHH5",
        "api-key: key_bSO8j6bs3IA0W6gJYuPiNiCks1XVJler",
        "access-token: access_token_M1mkYsmvSpG9uXSABzbIQ27BomyuL/uQClKFComaWlhwa6S0Y1jZYE8llQXwWzHr4qGUw6RaHTP82sHfPStvYA==",
        "Content-Type: application/json"
    ];

    // Merge headers
    $headers = array_merge($defaultHeaders, $customHeaders);

    $url = $baseUrl . $endpoint;

    $curl = curl_init();

    // Handle GET params
    if ($method == "GET" && !empty($data)) {
        $url .= "?" . http_build_query($data);
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ];

    // Handle POST/PUT
    if (in_array($method, ["POST", "PUT", "PATCH"])) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    // Error handling
    if ($error) {
        return [
            "status" => false,
            "error" => $error
        ];
    }

    return [
        "status" => ($httpCode >= 200 && $httpCode < 300),
        "http_code" => $httpCode,
        "data" => json_decode($response, true),
        "raw" => $response
    ];
}

//PAYOUT ================================


// $payoutData = [
//     "amount" => 10,
//     "mode" => "IMPS", // IMPS / NEFT
//     "call_back_url" => "https://prifypay.morg.in/payout.php",
//     "gateway_id" => "10",
//     "reference_id" => "PAYOUT_" . time(),
//     "bank_account" => [
//         "name" => "AATIF KHAN",
//         "ifsc" => "IDFB0043832",
//         "bank_name" => "IDFC FIRST BANK",
//         "account_number" => "10144105187"
//     ]
// ];

// $response = callAPI('POST','create-payout',$payoutData);

// echo "<pre>";
// print_r($response);

//PAYOUT ================================

// $data = [
//     "amount" => 110,
//     "call_back_url" => "https://prifypay.morg.in/pay_status.php",
//     "redirection_url" => "https://prifypay.morg.in/return.php", // ✅ ADD
//     "gateway_id" => "8",
//     "payment_link_expiry" => date("Y-m-d H:i:s", strtotime("+1 hour")),
//     "payment_for" => "Utility Payment",
//     "customer" => [
//         "name" => "Kumar Saurabh",
//         "email" => "myofferplant@gmail.com",
//         "phone" => "9431426600"
//     ],
//     "mode" => [
//         "netbanking" => true,
//         "card" => true,
//         "upi" => true,
//         "wallet" => false
//     ]
// ];

// $response = callAPI("POST", "create-order", $data);

// echo "<pre>";
// print_r($response);


// $data = [
//     "account_number" => "286801000016554",
//     "ifsc_code" => "IOBA0002868",
//     "name" => "Gowtham",
//     "phone" => "9840697693"
// ];


// $res = callAPI("GET", "bbps/categories-list?category_name&id=22068", $data);
// print_r($res);

// curl --location 'https://api.slpe.in/api/v2/account-validation' \
// --header 'access-token: access_token_+DKCLZ45Qj/NMc4Z4+jps02MGmAIJDpEwXe4CUDs93OOYWd6rsAIIhbVY+UWbqzUvVd0jn+6fIM44ErC6cuNRg==' \
// --header 'api-key: key_KLgVZng7yeSQRRPXhwNseoPiXLR4PMXu' \
// --header 'api-secret: secret_L2I6jz05wJHGAIe8WGCpbi7SQ8XnCth0' \
// --header 'api-mode: test' \
// --data '{
//     "account_number":"286801000016554",
//     "ifsc_code":"IOBA0002868",
//     "name":"Gowtham",
//     "phone":"9840697693"
// }'

$data = [
    "account_number" => "916010039686821",
    "ifsc_code" => "UTIB0001218",
    "name" => "KUMAR SAURABH",
    "phone" => "9431426600"
];

$res = callAPI("POST", "account-validation", $data);

print_r($res);




//$res = callAPI("GET","order-status?order_id=order_Sea8zwOrI9bnnD");

//print_r($res);

//$res = callAPI("GET", "balance-check");

// print_r($res);