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


$payoutData = [
    "amount" => 30,
    "mode" => "IMPS", // IMPS / NEFT
    "call_back_url" => "https://prifypay.morg.in/payout.php",
    "gateway_id" => "10",
    "reference_id" => "PAYOUT_" . time(),
    "bank_account" => [
        "name" => "KUMAR SAURABH",
        "ifsc" => "UTIB0001218",
        "bank_name" => "AXIS BANK",
        "account_number" => "916010039686821"
    ]
];

$response = callAPI('POST','create-payout',$payoutData);

echo "<pre>";
print_r($response);

//PAYOUT ================================

// Success Responce 
// Array
// (
//     [status] => 1
//     [http_code] => 200
//     [data] => Array
//         (
//             [success] => 1
//             [status] => processed
//             [utr] => 610817637148
//             [message] => IDFC payout initiated successfully.
//             [payout_id] => PAYOUT_20260418170516_9FKSAO
//             [meta] => Array
//                 (
//                     [status] => SUCCESS
//                     [message] => fundTransfer successfully processed
//                     [version] => v1
//                     [time] => 2026-04-18T11:35:17.441Z
//                 )

//             [resource] => Array
//                 (
//                     [status] => ACPT
//                     [transactionID] => TXIRTVYAMV170516
//                     [transactionReferenceNo] => 610817637148
//                     [beneficiaryName] => KUMAR  SAURABH
//                 )

//         )

//     [raw] => {"success":true,"status":"processed","utr":"610817637148","message":"IDFC payout initiated successfully.","payout_id":"PAYOUT_20260418170516_9FKSAO","meta":{"status":"SUCCESS","message":"fundTransfer successfully processed","version":"v1","time":"2026-04-18T11:35:17.441Z"},"resource":{"status":"ACPT","transactionID":"TXIRTVYAMV170516","transactionReferenceNo":"610817637148","beneficiaryName":"KUMAR  SAURABH"}}
// )