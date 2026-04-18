<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

checkRole('retailer');
$uId = $_SESSION['user_id'];

// Get user data for current request
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payin'])) {
    $amount = (int)$_POST['amount'];
    
    if ($amount < 10) {
        alert('danger', 'Minimum amount is ₹10.');
    } else {
        $refId = "PAYIN_" . time() . "_" . $uId;
        $callback = PAYIN_CALLBACK_URL;
        $redirect = PAYIN_REDIRECT_URL;
        
        $customer = [
            "name" => $userData['name'],
            "email" => $userData['email'] ?: 'user@prifypay.in',
            "phone" => $userData['phone']
        ];

        $response = createPayinOrder($amount, $callback, $redirect, $customer);

        if ($response['success']) {
            $payUrl = $response['data']['payment_url'] 
                   ?? $response['data']['data']['payment_url'] 
                   ?? null;
            
            if ($payUrl) {
                logTransaction($conn, $uId, 'payin', $amount, 0, 0, 0, 0, 'pending', $refId, '', $payUrl, $response['raw']);
                echo "<script>window.location.href='$payUrl';</script>";
                exit();
            } else {
                alert('danger', 'Payment URL not received. Raw: ' . substr($response['raw'] ?? '', 0, 200));
            }
        } else {
            $errMsg = $response['data']['message'] ?? $response['data']['error'] ?? $response['error'] ?? '';
            $rawHint = $response['raw'] ? ' | API: ' . substr($response['raw'], 0, 150) : '';
            alert('danger', 'Payment Error: ' . ($errMsg ?: 'Gateway error') . $rawHint);
        }
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-600 mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-plus-circle"></i> Add Money to Wallet</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Amount (INR)</label>
                    <input type="number" id="payinAmount" name="amount" class="form-control" placeholder="0.00" required step="1" 
                           style="font-size: 24px; font-weight: 700; padding: 16px; text-align: center;">
                    <p class="text-secondary" style="font-size: 13px; margin-top: 8px;">* Minimum ₹10 allowed.</p>
                </div>

                <div class="d-flex flex-wrap gap-10 mb-20" style="margin-bottom: 25px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(500)">₹500</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(1000)">₹1,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(2000)">₹2,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(5000)">₹5,000</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(10000)">₹10,000</button>
                </div>

                <div style="background: var(--bg-elevated); padding: 20px; border-radius: var(--radius); margin-bottom: 25px;">
                    <h4 style="font-size: 14px; margin-bottom: 12px; font-weight: 600;">Payment Options</h4>
                    <div style="display: flex; gap: 15px; font-size: 20px; color: var(--primary);">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fas fa-mobile-alt"></i>
                        <i class="fas fa-university"></i>
                    </div>
                    <p style="font-size: 12px; color: var(--text-secondary); margin-top: 10px;">
                        Credit Card, Debit Card, UPI, and Netbanking supported.
                    </p>
                </div>

                <button type="submit" name="payin" class="btn btn-primary btn-block" style="padding: 14px;">
                    <i class="fas fa-lock"></i> Proceed to Pay Securely
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

<?php require_once '../includes/footer.php'; ?>
