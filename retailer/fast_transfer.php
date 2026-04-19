<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_helper.php';

checkRole('retailer');
$uId = $_SESSION['user_id'];

// Get user data
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);

// Get commissions for simulation
$payinComm = getCommissionValue($conn, 'retailer', 'payin');
$payoutComm = getCommissionValue($conn, 'retailer', 'payout');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['initiate_transfer'])) {
    $payoutAmount = (float)$_POST['amount'];
    $beneId = (int)$_POST['bene_id'];
    
    // Calculate required Payin amount
    // Formula: Payin should cover (Payout Amount + Payout Fee)
    $payoutFee = calculateCommission($payoutAmount, $payoutComm);
    $totalRequired = $payoutAmount + $payoutFee;
    
    // We also consider any current wallet balance? 
    // For "Fast Transfer", we usually assume we want to pay exactly what's needed.
    // However, if buyer pays ₹X, they get ₹X in wallet.
    // So we need they pay ₹totalRequired.
    $payinAmount = ceil($totalRequired); 

    $refId = "FAST_" . time() . "_" . $uId;
    $callback = defined('PAYIN_CALLBACK_URL') ? PAYIN_CALLBACK_URL : (BASE_URL . '/callbacks/payin.php');
    $redirect = defined('PAYIN_REDIRECT_URL') ? PAYIN_REDIRECT_URL : (BASE_URL . '/retailer/reports.php');

    $customer = [
        "name" => $userData['name'] ?? 'User',
        "email" => ($userData['email'] ?? '') ?: 'user@prifypay.in',
        "phone" => $userData['phone'] ?? ''
    ];

    $response = createPayinOrder($payinAmount, $callback, $redirect, $customer);

    if ($response['success']) {
        $apiData = $response['data'];
        $apiOrderId = $apiData['order_id'] ?? $apiData['data']['order_id'] ?? '';
        $payUrl = $apiData['payment_url'] ?? $apiData['data']['payment_url'] ?? null;
        
        if ($payUrl) {
            // Log with payout instructions
            logTransaction($conn, $uId, 'payin', $payinAmount, 0, 0, 0, 0, 'pending', $refId, $apiOrderId, $payUrl, $response['raw'], $beneId, $payoutAmount);
            echo "<script>window.location.href='$payUrl';</script>";
            exit();
        }
    }
    alert('danger', 'Failed to initiate transfer. Please try again.');
}

require_once __DIR__ . '/../includes/header.php';

$beneficiaries = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE user_id = $uId AND status = 'verified'");
?>

<div class="max-w-700 mx-auto">
    <div class="card overflow-hidden">
        <div class="card-header bg-gradient-primary text-white p-30 text-center">
            <h1 class="mb-10" style="font-weight: 800; letter-spacing: -0.5px;">Fast Transfer</h1>
            <p class="opacity-80">Credit Card to Bank Account in 1-Click</p>
        </div>
        
        <div class="card-body p-40">
            <form method="POST" id="fastTransferForm">
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group mb-25">
                            <label class="form-label font-600">Select Recipient</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-user-friends"></i></span>
                                <select name="bene_id" id="beneSelect" class="form-control h-60" required onchange="if(this.value=='add') window.location.href='beneficiaries.php'">
                                    <option value="">-- Choose Verified Beneficiary --</option>
                                    <?php while($b = mysqli_fetch_assoc($beneficiaries)): ?>
                                    <option value="<?php echo $b['id']; ?>">
                                        <?php echo $b['name']; ?> (<?php echo substr($b['account_number'], -4); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                    <option value="add" style="color: var(--primary); font-weight: 600;">+ Add New Beneficiary</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-25">
                            <label class="form-label font-600">Transfer Amount</label>
                            <div class="input-group">
                                <span class="input-icon" style="font-size: 24px; font-weight: 700;">₹</span>
                                <input type="number" name="amount" id="transferAmount" class="form-control h-60 font-700 text-24" placeholder="0.00" required step="1" min="100">
                            </div>
                            <small class="text-muted mt-5">Min: ₹100 | Max: ₹2,00,000</small>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="summary-card p-25 h-100 bg-elevated border-radius-16">
                            <h4 class="mb-20 text-primary"><i class="fas fa-receipt"></i> Calculation</h4>
                            
                            <div class="d-flex justify-content-between mb-12">
                                <span class="text-secondary">Transfer</span>
                                <span id="dispTransfer">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-12">
                                <span class="text-secondary">Fee</span>
                                <span id="dispFee" class="text-danger">₹0.00</span>
                            </div>
                            <hr class="my-15 border-dashed">
                            <div class="d-flex justify-content-between mb-20">
                                <span class="font-700">Total to Pay</span>
                                <span id="dispTotal" class="font-800 text-18 text-primary">₹0.00</span>
                            </div>

                            <div class="alert alert-info py-10 px-15" style="font-size: 12px; margin-bottom: 0;">
                                <i class="fas fa-shield-check"></i> Funds will be settled instantly after payment.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-40">
                    <button type="submit" name="initiate_transfer" class="btn btn-primary btn-block btn-lg h-70 border-radius-16 font-700 shadow-glow">
                        <i class="fas fa-bolt mr-10"></i> Proceed to Pay & Transfer
                    </button>
                    <div class="text-center mt-20 opacity-60">
                        <i class="fas fa-lock small mr-5"></i> Secure 256-bit Encrypted Transaction
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Instructions / Benefits -->
    <div class="row mt-40">
        <div class="col-md-4">
            <div class="text-center p-20">
                <div class="icon-circle bg-soft-primary text-primary mb-15 mx-auto"><i class="fas fa-credit-card"></i></div>
                <h5 class="font-700">CC to Bank</h5>
                <p class="small text-secondary">Pay using any Credit/Debit card or UPI.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-20">
                <div class="icon-circle bg-soft-success text-success mb-15 mx-auto"><i class="fas fa-history"></i></div>
                <h5 class="font-700">Instant Settlement</h5>
                <p class="small text-secondary">Money hits account within seconds via IMPS.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-20">
                <div class="icon-circle bg-soft-warning text-warning mb-15 mx-auto"><i class="fas fa-headset"></i></div>
                <h5 class="font-700">24/7 Support</h5>
                <p class="small text-secondary">Dedicated assistance for all transactions.</p>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
.h-60 { height: 60px !important; }
.h-70 { height: 70px !important; }
.text-24 { font-size: 24px !important; }
.font-600 { font-weight: 600; }
.font-700 { font-weight: 700; }
.font-800 { font-weight: 800; }
.border-radius-16 { border-radius: 16px; }
.summary-card { border: 1px solid var(--border); }
.input-group { position: relative; }
.input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); z-index: 5; color: var(--text-secondary); }
.input-group .form-control { padding-left: 45px; }
.shadow-glow { box-shadow: 0 10px 30px rgba(78, 115, 223, 0.3); transition: all 0.3s ease; }
.shadow-glow:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(78, 115, 223, 0.4); }
.icon-circle { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.bg-soft-primary { background: rgba(78, 115, 223, 0.1); }
.bg-soft-success { background: rgba(28, 200, 138, 0.1); }
.bg-soft-warning { background: rgba(246, 194, 62, 0.1); }
</style>

<script>
const feeMethod = '<?php echo $payoutComm['method']; ?>';
const feeValue = <?php echo (float)$payoutComm['value']; ?>;

const amountInput = document.getElementById('transferAmount');
const dispTransfer = document.getElementById('dispTransfer');
const dispFee = document.getElementById('dispFee');
const dispTotal = document.getElementById('dispTotal');

function calculate() {
    const val = parseFloat(amountInput.value) || 0;
    let fee = 0;
    if (val > 0) {
        if (feeMethod === 'percentage') {
            fee = (val * feeValue) / 100;
        } else {
            fee = feeValue;
        }
    }
    
    const total = val + fee;
    
    dispTransfer.innerText = '₹' + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
    dispFee.innerText = '₹' + fee.toLocaleString('en-IN', {minimumFractionDigits: 2});
    dispTotal.innerText = '₹' + Math.ceil(total).toLocaleString('en-IN', {minimumFractionDigits: 2});
}

amountInput.addEventListener('input', calculate);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
