<?php
require_once '../includes/header.php';
checkRole('retailer');

$selected_bene = null;
if (isset($_GET['bene_id'])) {
    $bene_id = (int)$_GET['bene_id'];
    $res = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $bene_id AND user_id = $uId AND status = 'verified'");
    $selected_bene = mysqli_fetch_assoc($res);
}

$beneficiaries = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE user_id = $uId AND status = 'verified'");
?>

<div class="max-w-600 mx-auto">
    <div class="card" id="payoutCard">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-paper-plane"></i> Transfer Money to Bank</h2>
        </div>
        <div class="card-body">
            <div id="payoutAlert"></div>
            
            <form id="payoutForm">
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

                <button type="submit" id="submitBtn" class="btn btn-primary btn-block" style="padding: 14px;">
                    <span id="btnText"><i class="fas fa-paper-plane"></i> Instant Payout Now</span>
                    <span id="btnLoader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Success Container -->
    <div id="successContainer" style="display: none;">
        <div class="card" style="text-align: center; border-color: var(--success);">
            <div class="card-body" style="padding: 40px 20px;">
                <div style="font-size: 60px; color: var(--success); margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="margin-bottom: 10px;">Transfer Successful!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 25px;">The amount has been transferred to the beneficiary's bank account.</p>
                
                <div style="background: var(--bg-elevated); padding: 20px; border-radius: var(--radius); margin-bottom: 30px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>UTR Number:</span>
                        <strong id="successUtr">-</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button onclick="location.reload()" class="btn btn-secondary btn-block">New Transfer</button>
                    <a href="reports.php" class="btn btn-primary btn-block">View Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('payoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!confirm('Do you want to proceed with this payout?')) return;

    const form = this;
    const btn = document.getElementById('submitBtn');
    const bText = document.getElementById('btnText');
    const bLoader = document.getElementById('btnLoader');
    const alertDiv = document.getElementById('payoutAlert');

    // Loading State
    btn.disabled = true;
    bText.style.display = 'none';
    bLoader.style.display = 'inline-block';
    alertDiv.innerHTML = '';

    const formData = new FormData(form);

    fetch('ajax_payout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('payoutCard').style.display = 'none';
            document.getElementById('successContainer').style.display = 'block';
            document.getElementById('successUtr').innerText = data.utr;
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
            btn.disabled = false;
            bText.style.display = 'inline-block';
            bLoader.style.display = 'none';
        }
    })
    .catch(error => {
        alertDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Connection error. Please try again.</div>`;
        btn.disabled = false;
        bText.style.display = 'inline-block';
        bLoader.style.display = 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
