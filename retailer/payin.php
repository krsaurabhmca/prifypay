<?php
require_once '../includes/header.php';
checkRole('retailer');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payin'])) {
    $amount = (int)$_POST['amount'];
    
    if ($amount < 10) {
        alert('danger', 'Minimum amount is ₹10.');
    } else {
        $refId = "PAYIN_" . time() . "_" . $_SESSION['user_id'];
        $callback = PAYIN_CALLBACK_URL;
        $redirect = PAYIN_REDIRECT_URL;
        
        // Use $userData (from DB) which is always available via header.php
        $customer = [
            "name" => $userData['name'],
            "email" => $userData['email'] ?: 'user@prifypay.in',
            "phone" => $userData['phone']
        ];

        $response = createPayinOrder($amount, $callback, $redirect, $customer);

        if ($response['success']) {
            // payment_url can be at different levels depending on API version
            $payUrl = $response['data']['payment_url'] 
                   ?? $response['data']['data']['payment_url'] 
                   ?? null;
            
            if ($payUrl) {
                // Log as pending
                logTransaction($conn, $uId, 'payin', $amount, 0, 0, 0, 0, 'pending', $refId, '', $payUrl, $response['raw']);
                
                // Redirect to payment gateway
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
                    <input type="number" name="amount" class="form-control" placeholder="Enter amount" min="10" required style="font-size: 18px; font-weight: 700; padding: 14px;">
                    <div class="form-hint">* Minimum ₹10 allowed.</div>
                </div>

                <!-- Quick Amount Buttons -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
                    <?php foreach([500, 1000, 2000, 5000, 10000] as $amt): ?>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelector('input[name=amount]').value=<?php echo $amt; ?>">
                        ₹<?php echo number_format($amt); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                
                <div style="background: var(--bg-elevated); padding: 18px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px;">Payment Options</h4>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fas fa-mobile-screen-button"></i>
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <p class="form-hint">Credit Card, Debit Card, UPI, and Netbanking supported.</p>
                </div>

                <button type="submit" name="payin" class="btn btn-primary btn-block" style="padding: 14px;">
                    <i class="fas fa-lock"></i> Proceed to Pay Securely
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
