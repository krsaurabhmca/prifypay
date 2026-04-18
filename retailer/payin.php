<?php
// Ensure dependencies are loaded using the same pattern as header.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_helper.php';

checkRole('retailer');
$uId = $_SESSION['user_id'];

// Get user data for current request
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payin'])) {
    $amount = (isset($_POST['amount'])) ? (int)$_POST['amount'] : 0;
    
    if ($amount < 10) {
        alert('danger', 'Minimum amount is ₹10.');
    } else {
        $refId = "PAYIN_" . time() . "_" . $uId;
        
        // Define fallback if constants missing (to prevent fatal errors)
        $callback = defined('PAYIN_CALLBACK_URL') ? PAYIN_CALLBACK_URL : (BASE_URL . '/callbacks/payin.php');
        $redirect = defined('PAYIN_REDIRECT_URL') ? PAYIN_REDIRECT_URL : (BASE_URL . '/retailer/index.php');
        
        $customer = [
            "name" => $userData['name'] ?? 'User',
            "email" => ($userData['email'] ?? '') ?: 'user@prifypay.in',
            "phone" => $userData['phone'] ?? ''
        ];

        $response = createPayinOrder($amount, $callback, $redirect, $customer);

        if ($response['success']) {
            $apiData = $response['data'];
            $payUrl = $apiData['payment_url'] 
                   ?? $apiData['data']['payment_url'] 
                   ?? null;
            
            if ($payUrl) {
                logTransaction($conn, $uId, 'payin', $amount, 0, 0, 0, 0, 'pending', $refId, '', $payUrl, $response['raw']);
                echo "<script>window.location.href='$payUrl';</script>";
                exit();
            } else {
                alert('danger', 'Payment URL not received. Raw response captured in log.');
            }
        } else {
            $errMsg = $response['data']['message'] ?? $response['data']['error'] ?? $response['error'] ?? 'Gateway Error';
            $rawHint = $response['raw'] ? ' | API: ' . substr($response['raw'], 0, 100) : '';
            alert('danger', 'Payment Error: ' . $errMsg . $rawHint);
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-600 mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title text-center"><i class="fas fa-plus-circle"></i> Add Money to Wallet</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group text-center">
                    <label class="form-label" style="display: block; margin-bottom: 15px;">Enter Amount (INR)</label>
                    <input type="number" id="payinAmount" name="amount" class="form-control" placeholder="0" required step="1" min="10"
                           style="font-size: 32px; font-weight: 800; padding: 20px; text-align: center; border-radius: 12px; border: 2px solid var(--border);">
                    <p class="text-secondary" style="font-size: 13px; margin-top: 10px;">Minimum ₹10 allowed for Pay-In.</p>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-10 mb-20" style="margin-bottom: 30px; gap: 8px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(500)">₹500</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(1000)">₹1,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(2000)">₹2,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(5000)">₹5,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(10000)">₹10,000</button>
                </div>

                <div style="background: var(--bg-elevated); padding: 20px; border-radius: var(--radius); margin-bottom: 30px;">
                    <h4 style="font-size: 14px; margin-bottom: 12px; font-weight: 600; color: var(--text-primary);">Payment Methods</h4>
                    <div style="display: flex; gap: 20px; font-size: 24px; color: var(--primary);">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fas fa-mobile-alt"></i>
                        <i class="fas fa-university"></i>
                    </div>
                    <p style="font-size: 12px; color: var(--text-secondary); margin-top: 12px; line-height: 1.4;">
                        Instant settlement via UPI, QR, Netbanking, or Cards.
                    </p>
                </div>

                <button type="submit" name="payin" class="btn btn-primary btn-block" style="padding: 16px; font-weight: 600; font-size: 16px; border-radius: 12px;">
                    <i class="fas fa-lock" style="margin-right: 8px;"></i> Proceed to Payment
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function setAmount(val) {
    document.getElementById('payinAmount').value = val;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
