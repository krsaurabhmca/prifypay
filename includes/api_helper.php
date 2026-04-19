<?php

function callAPI($method, $endpoint, $data = [], $customHeaders = [])
{
    $baseUrl = API_BASE_URL;

    // Default SLPE Headers - Dynamic from config
    $defaultHeaders = [
        "api-mode: " . API_MODE,
        "api-secret: " . API_SECRET,
        "api-key: " . API_KEY,
        "access-token: " . ACCESS_TOKEN,
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
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

    if ($error) {
        return ["success" => false, "error" => $error, "http_code" => $httpCode, "raw" => $response];
    }

    return [
        "success" => ($httpCode >= 200 && $httpCode < 300),
        "http_code" => $httpCode,
        "data" => json_decode($response, true),
        "raw" => $response
    ];
}

// Balance Check Helper
function getApiBalance() {
    $res = callAPI("GET", "balance-check");
    
    // Optional: Log raw response for debugging on live server
    // file_put_contents(__DIR__ . '/../support/balance_debug_log.txt', date('[Y-m-d H:i:s] ') . $res['raw'] . PHP_EOL, FILE_APPEND);

    if($res['success']) {
        $apiData = $res['data'];
        // The API might return balance directly in root or inside a data key
        $inner = $apiData['data'] ?? $apiData;
        
        if (API_MODE === 'live') {
            return (float)($inner['wallet_balance'] ?? $apiData['wallet_balance'] ?? 0);
        } else {
            return (float)($inner['test_wallet_balance'] ?? $apiData['test_wallet_balance'] ?? 0);
        }
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

// Check Payin Status
function getPayinStatus($orderId) {
    return callAPI("GET", "order-status", ["order_id" => $orderId]);
}

// Create Payout
function createPayout($amount, $account, $ifsc, $bank_name, $name, $callback, $reference) {
    $payoutData = [
        "amount" => (int)$amount,
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

// Check Payout Status
function getPayoutStatus($referenceId) {
    return callAPI("GET", "payout-status", ["reference_id" => $referenceId]);
}
?>
