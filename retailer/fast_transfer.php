<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_helper.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
<div class="max-w-800 mx-auto">
    <div class="fast-transfer-card animate-in">
        <div class="ft-header">
            <div class="ft-logo-glow"></div>
            <div class="ft-header-content">
                <h1 class="ft-title">Fast Transfer</h1>
                <p class="ft-subtitle">Instant Bank Settlement via Credit Card</p>
            </div>
            <div class="ft-badge">
                <i class="fas fa-bolt"></i> Instant
            </div>
        </div>
        
        <div class="ft-body">
            <form method="POST" id="fastTransferForm">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="ft-form-section">
                            <div class="form-group mb-25">
                                <label class="ft-label">Recipient Account</label>
                                <div class="ft-input-wrapper">
                                    <i class="fas fa-university ft-icon"></i>
                                    <select name="bene_id" id="beneSelect" class="ft-control" required onchange="if(this.value=='add') window.location.href='beneficiaries.php'">
                                        <option value="">Choose Verified Beneficiary</option>
                                        <?php while($b = mysqli_fetch_assoc($beneficiaries)): ?>
                                        <option value="<?php echo $b['id']; ?>">
                                            <?php echo $b['name']; ?> (<?php echo substr($b['account_number'], -4); ?>)
                                        </option>
                                        <?php endwhile; ?>
                                        <option value="add" class="add-bene-option">+ Add New Beneficiary</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="ft-label">Transfer Amount</label>
                                <div class="ft-input-wrapper amount-wrapper">
                                    <span class="ft-currency">₹</span>
                                    <input type="number" name="amount" id="transferAmount" class="ft-control ft-amount-input" placeholder="0.00" required step="1" min="100">
                                </div>
                                <div class="ft-amount-hint">Min: ₹100 | Max: ₹2,00,000</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="ft-summary">
                            <h4 class="summary-title"><i class="fas fa-receipt"></i> Transaction Summary</h4>
                            
                            <div class="summary-item">
                                <span class="label">Settlement Amount</span>
                                <span class="value" id="dispTransfer">₹0.00</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Processing Fee</span>
                                <span class="value fee" id="dispFee">₹0.00</span>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <div class="summary-item total">
                                <span class="label">Net Payable</span>
                                <div class="total-value-wrapper">
                                    <span class="value" id="dispTotal">₹0.00</span>
                                    <small class="tax-info">Incl. all taxes</small>
                                </div>
                            </div>

                            <div class="ft-trust-badge">
                                <i class="fas fa-shield-check"></i>
                                <span>Secured by 256-bit SSL</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ft-footer">
                    <button type="submit" name="initiate_transfer" class="ft-btn-primary">
                        <span>Initiate Secure Transfer</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <p class="ft-legal">By clicking, you agree to our terms and processing fees.</p>
                </div>
            </form>
        </div>
    </div>

    <!-- Feature Grid -->
    <div class="ft-features">
        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-clock"></i></div>
            <div class="feature-text">
                <h6>Real-time</h6>
                <p>IMPS Settlement</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon success"><i class="fas fa-check-double"></i></div>
            <div class="feature-text">
                <h6>Verified</h6>
                <p>Direct to Bank</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon warning"><i class="fas fa-headset"></i></div>
            <div class="feature-text">
                <h6>Support</h6>
                <p>24/7 Assistance</p>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --ft-primary: #6366f1;
    --ft-secondary: #818cf8;
    --ft-bg: #ffffff;
    --ft-card-bg: rgba(255, 255, 255, 0.7);
}

[data-theme="dark"] :root {
    --ft-bg: #0f172a;
    --ft-card-bg: rgba(30, 41, 59, 0.7);
}

.fast-transfer-card {
    background: var(--ft-card-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.ft-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    padding: 40px;
    position: relative;
    overflow: hidden;
    color: white;
}

.ft-logo-glow {
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    filter: blur(40px);
}

.ft-title { font-size: 32px; font-weight: 800; margin-bottom: 5px; letter-spacing: -1px; }
.ft-subtitle { font-size: 15px; opacity: 0.9; font-weight: 500; }

.ft-badge {
    position: absolute;
    top: 30px;
    right: 30px;
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
}

.ft-body { padding: 40px; }

.ft-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; }

.ft-input-wrapper {
    position: relative;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 14px;
    transition: var(--transition-fast);
}

.ft-input-wrapper:focus-within {
    border-color: var(--ft-primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.ft-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 18px; }

.ft-control {
    width: 100%;
    background: transparent;
    border: none;
    padding: 16px 16px 16px 50px;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    outline: none;
}

.amount-wrapper .ft-currency {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    font-weight: 700;
    color: var(--ft-primary);
}

.ft-amount-input { font-size: 28px; font-weight: 800; padding-left: 50px; height: 70px; }

.ft-amount-hint { font-size: 11px; color: var(--text-muted); margin-top: 8px; font-weight: 500; }

.ft-summary {
    background: var(--bg-elevated);
    border-radius: 20px;
    padding: 30px;
    height: 100%;
    border: 1px solid var(--border);
}

.summary-title { font-size: 14px; font-weight: 700; color: var(--ft-primary); margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

.summary-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.summary-item .label { font-size: 13px; color: var(--text-secondary); font-weight: 500; }
.summary-item .value { font-size: 15px; font-weight: 700; color: var(--text-primary); }
.summary-item .value.fee { color: var(--danger); }

.summary-divider { height: 1px; background: var(--border); margin: 20px 0; border-bottom: 1px dashed var(--border-light); }

.summary-item.total .label { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.total-value-wrapper { text-align: right; }
.summary-item.total .value { font-size: 22px; font-weight: 800; color: var(--ft-primary); display: block; line-height: 1; }
.tax-info { font-size: 10px; color: var(--text-muted); font-weight: 500; }

.ft-trust-badge {
    margin-top: 25px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 600;
    color: var(--success);
    background: var(--success-light);
    padding: 8px 12px;
    border-radius: 10px;
}

.ft-footer { margin-top: 40px; text-align: center; }

.ft-btn-primary {
    width: 100%;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border: none;
    padding: 20px;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
}

.ft-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4); }

.ft-legal { font-size: 11px; color: var(--text-muted); margin-top: 15px; }

.ft-features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 40px; }

.feature-item { display: flex; align-items: center; gap: 15px; }
.feature-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--bg-elevated); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--ft-primary); }
.feature-icon.success { color: var(--success); }
.feature-icon.warning { color: var(--warning); }
.feature-text h6 { font-size: 13px; font-weight: 700; margin: 0; color: var(--text-primary); }
.feature-text p { font-size: 11px; color: var(--text-muted); margin: 0; }

@media (max-width: 991px) {
    .ft-summary { margin-top: 30px; }
    .ft-features { grid-template-columns: 1fr; }
}
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
