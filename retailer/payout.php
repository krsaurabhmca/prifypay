<?php
require_once '../includes/header.php';
checkRole('retailer');

$selected_bene = null;
if (isset($_GET['bene_id'])) {
    $bene_id = (int)$_GET['bene_id'];
    $res = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $bene_id AND user_id = $uId AND status = 'verified'");
    $selected_bene = mysqli_fetch_assoc($res);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payout'])) {
    $amount = (float)$_POST['amount'];
    $bene_id = (int)$_POST['bene_id'];
    
    // Get commissions
    $retComm = getCommissionValue($conn, 'retailer', 'payout');
    $distComm = getCommissionValue($conn, 'distributor', 'payout');
    
    $retailerFee = calculateCommission($amount, $retComm);
    $distributorPart = calculateCommission($amount, $distComm);
    
    $totalDeduction = $amount + $retailerFee;

    if ($userData['wallet_balance'] < $totalDeduction) {
        alert('danger', 'Insufficient wallet balance. You need ' . formatCurrency($totalDeduction));
    } else {
        $beneQuery = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $bene_id AND user_id = $uId");
        $bene = mysqli_fetch_assoc($beneQuery);

        if ($bene) {
            $refId = "PAYOUT_" . time();
            $callback = BASE_URL . "/callbacks/payout.php";
            
            $res = createPayout($amount, $bene['account_number'], $bene['ifsc'], $bene['bank_name'], $bene['name'], $callback, $refId);

            if ($res['success']) {
                // 1. Deduct from Retailer
                updateWallet($conn, $uId, $totalDeduction, 'sub');
                
                // 2. Add commission to Distributor (if exists)
                if ($userData['parent_id']) {
                    updateWallet($conn, $userData['parent_id'], $distributorPart, 'add');
                    // Log distributor commission
                    logTransaction($conn, $userData['parent_id'], 'commission', $distributorPart, 0, 0, 0, $distributorPart, 'success', 'COMM_'.$refId);
                }

                // 3. Log Retailer Payout
                logTransaction($conn, $uId, 'payout', $amount, $retailerFee, $distributorPart, ($retailerFee - $distributorPart), $retailerFee, 'success', $refId, $res['data']['utr'] ?? '', '', $res['raw']);
                
                alert('success', 'Payout initiated successfully! UTR: ' . ($res['data']['utr'] ?? 'Pending'));
                redirect('index.php');
            } else {
                alert('danger', 'Payout Failed: ' . ($res['data']['message'] ?? 'API Error'));
            }
        }
    }
}

$beneficiaries = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE user_id = $uId AND status = 'verified'");
?>

<div class="max-w-600 mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-paper-plane"></i> Transfer Money to Bank</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Select Beneficiary</label>
                    <select name="bene_id" class="form-control" required onchange="if(this.value=='add'){window.location.href='beneficiaries.php'}">
                        <option value="">-- Select Verified Beneficiary --</option>
                        <?php while($b = mysqli_fetch_assoc($beneficiaries)): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($selected_bene && $selected_bene['id'] == $b['id']) ? 'selected' : ''; ?>>
                            <?php echo $b['name']; ?> - <?php echo $b['account_number']; ?>
                        </option>
                        <?php endwhile; ?>
                        <option value="add">+ Add New Beneficiary</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Amount (INR)</label>
                    <input type="number" name="amount" class="form-control" placeholder="Enter amount" required style="font-size: 18px; font-weight: 700; padding: 14px;">
                </div>

                <div style="background: var(--bg-elevated); padding: 18px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                        <i class="fas fa-info-circle text-info"></i> Transfer Summary
                    </h4>
                    <p style="font-size: 13px; color: var(--text-secondary);">Fee will be calculated based on your current commission slab.</p>
                    <p style="font-size: 12px; font-weight: 600; color: var(--danger); margin-top: 8px;">
                        <i class="fas fa-bolt"></i> Money will be transferred instantly via IMPS.
                    </p>
                </div>

                <button type="submit" name="payout" class="btn btn-primary btn-block" style="padding: 14px;" onclick="return confirm('Do you want to proceed with this payout?')">
                    <i class="fas fa-paper-plane"></i> Instant Payout Now
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
