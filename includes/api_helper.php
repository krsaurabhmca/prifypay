<?php

function callAPI($method, $endpoint, $data = [], $customHeaders = [])
{
    // Default SLPE Headers from config
    $defaultHeaders = [
        "api-mode: " . API_MODE,
        "api-secret: " . API_SECRET,
        "api-key: " . API_KEY,
        "access-token: " . ACCESS_TOKEN,
        "Content-Type: application/json"
    ];

    // Merge headers
    $headers = array_merge($defaultHeaders, $customHeaders);

    $url = API_BASE_URL . $endpoint;

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
            "success" => false,
            "error" => $error
        ];
    }

    $decodedData = json_decode($response, true);

    // SLPE API uses both 'success' and 'result' fields depending on endpoint
    $isSuccess = ($httpCode >= 200 && $httpCode < 300);
    if ($isSuccess) {
        $isSuccess = (isset($decodedData['success']) && $decodedData['success'] == true)
                  || (isset($decodedData['result']) && $decodedData['result'] == true);
    }

    return [
        "success" => $isSuccess,
        "http_code" => $httpCode,
        "data" => $decodedData,
        "raw" => $response
    ];
}

// Balance Check Helper
function getApiBalance() {
    $res = callAPI("GET", "balance-check");
    if($res['success']) {
        return $res['data']['balance'] ?? 0;
    }
    return 0;
}

// Account Validation Helper
function validateAccount($account, $ifsc, $name, $phone) {
    $data = [
        "account_number" => $account,
        "ifsc_code" => $ifsc,
        "name" => $name,
        "phone" => $phone
    ];
    return callAPI("POST", "account-validation", $data);
}

// Create Payin Order
function createPayinOrder($amount, $callback, $redirect, $customer) {
    $data = [
        "amount" => (int)$amount,
        "call_back_url" => $callback,
        "redirection_url" => $redirect,
        "gateway_id" => PAYIN_GATEWAY_ID,
        "payment_link_expiry" => date("Y-m-d H:i:s", strtotime("+24 hour")),
        "payment_for" => "Add Money to Wallet",
        "customer" => $customer,
        "mode" => [
            "netbanking" => true,
            "card" => true,
            "upi" => true,
            "wallet" => false
        ]
    ];
    return callAPI("POST", "create-order", $data);
}

// Create Payout
function createPayout($amount, $account, $ifsc, $bank_name, $name, $callback, $reference) {
    $payoutData = [
        "amount" => $amount,
        "mode" => "IMPS",
        "call_back_url" => $callback,
        "gateway_id" => PAYOUT_GATEWAY_ID,
        "reference_id" => $reference,
        "bank_account" => [
            "name" => $name,
            "ifsc" => $ifsc,
            "bank_name" => $bank_name,
            "account_number" => $account
        ]
    ];
    return callAPI('POST', 'create-payout', $payoutData);
}
?>
