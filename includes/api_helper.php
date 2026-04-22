<?php

function callAPI($method, $endpoint, $data = [], $customHeaders = [])
{
    global $conn;
    $baseUrl = API_BASE_URL;

    // Fetch dynamic credentials from settings
    $apiMode = getSetting($conn, 'api_mode', API_MODE);
    $apiSecret = getSetting($conn, 'api_secret', API_SECRET);
    $apiKey = getSetting($conn, 'api_key', API_KEY);
    $accessToken = getSetting($conn, 'access_token', ACCESS_TOKEN);

    // Default SLPE Headers
    $defaultHeaders = [
        "api-mode: " . $apiMode,
        "api-secret: " . $apiSecret,
        "api-key: " . $apiKey,
        "access-token: " . $accessToken,
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

    $postData = json_encode($data);
    if (in_array($method, ["POST", "PUT", "PATCH"])) {
        $options[CURLOPT_POSTFIELDS] = $postData;
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    file_put_contents(__DIR__ . '/../support/api_debug_log.txt', 
        date('[Y-m-d H:i:s] ') . "URL: $url | Data: $postData | Response: $response | Error: $error" . PHP_EOL, 
        FILE_APPEND
    );

    $decoded = json_decode($response, true);
    $isSuccess = ($httpCode >= 200 && $httpCode < 300);

    // SLPE API specific success check
    if (isset($decoded['result']) && $decoded['result'] === false) {
        $isSuccess = false;
    }
    if (isset($decoded['status']) && $decoded['status'] === false) {
        $isSuccess = false;
    }

    return [
        "success" => $isSuccess,
        "http_code" => $httpCode,
        "data" => $decoded,
        "raw" => $response
    ];
}

// Balance Check Helper
function getApiBalance() {
    $res = callAPI("GET", "balance-check");
    if($res['success']) {
        $apiData = $res['data'];
        $inner = $apiData['data'] ?? $apiData;
        
        // If the API returns detailed wallet separation, return the full object
        if (isset($inner['payin_balance']) || isset($inner['payout_balance'])) {
            return $inner;
        }

        global $conn;
        $apiMode = getSetting($conn, 'api_mode', API_MODE);

        if ($apiMode === 'live') {
            return (float)($inner['wallet_balance'] ?? $inner['balance'] ?? 0);
        } else {
            return (float)($inner['test_wallet_balance'] ?? $inner['test_balance'] ?? 0);
        }
    }
    return 0;
}

function getGatewayList() {
    $res = callAPI("GET", "gateway-list");
    if($res['success']) {
        return $res['data']['data'] ?? [];
    }
    return [];
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
    global $conn;
    $gateway_id = getSetting($conn, 'payin_gateway_id', PAYIN_GATEWAY_ID);
    
    $data = [
        "amount" => (int)$amount,
        "call_back_url" => $callback,
        "redirection_url" => $redirect,
        "gateway_id" => $gateway_id,
        "payment_link_expiry" => date("Y-m-d H:i:s", strtotime("+24 hour")),
        "payment_for" => "Add Money to Wallet",
        "customer" => $customer,
        "mode" => [
            "netbanking" => false,
            "card" => false,
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
    global $conn;
    $gateway_id = getSetting($conn, 'payout_gateway_id', PAYOUT_GATEWAY_ID);

    $payoutData = [
        "amount" => (int)$amount,
        "mode" => "IMPS",
        "call_back_url" => $callback,
        "gateway_id" => $gateway_id,
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
// --- EKYCHUB API Helpers (DigiLocker & OTP) ---

function callEkycAPI($endpoint, $params = []) {
    global $conn;
    $username = getSetting($conn, 'ekyc_username');
    $token = getSetting($conn, 'ekyc_token');
    
    $params['username'] = $username;
    $params['token'] = $token;
    
    $url = "https://connect.ekychub.in/v3/" . $endpoint . "?" . http_build_query($params);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    if ($error) return ['status' => 'Failure', 'message' => $error];
    return json_decode($response, true);
}

function createDigilockerUrl($type, $orderId, $redirectUrl) {
    $endpoint = ($type == 'PAN') ? 'digilocker/create_url_pan' : 'digilocker/create_url_aadhaar';
    return callEkycAPI($endpoint, [
        'orderid' => $orderId,
        'redirect_url' => $redirectUrl
    ]);
}

function getDigilockerDocument($verificationId, $referenceId, $orderId, $docType) {
    return callEkycAPI('digilocker/get_document', [
        'verification_id' => $verificationId,
        'reference_id' => $referenceId,
        'orderid' => $orderId,
        'document_type' => $docType
    ]);
}

function sendOtpSms($number, $otp, $orderId) {
    return callEkycAPI('verification/otp_sms', [
        'number' => $number,
        'otp' => $otp,
        'orderid' => $orderId
    ]);
}

function getKYCBalance() {
    return callEkycAPI('verification/balance');
}
?>
