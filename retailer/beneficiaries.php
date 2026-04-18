<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

checkRole('retailer');
$uId = $_SESSION['user_id'];

// Add Beneficiary logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_bene'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $account = mysqli_real_escape_string($conn, $_POST['account']);
    $ifsc = mysqli_real_escape_string($conn, $_POST['ifsc']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $bank = mysqli_real_escape_string($conn, $_POST['bank']);
    $pan_no = strtoupper(mysqli_real_escape_string($conn, $_POST['pan_no']));
    $aadhaar_no = str_replace(' ', '', mysqli_real_escape_string($conn, $_POST['aadhaar_no']));

    if(!validateMobile($phone)) {
        alert('danger', 'Invalid Mobile Number. Must be 10 digits.');
    } elseif($pan_no != "" && !validatePAN($pan_no)) {
        alert('danger', 'Invalid PAN Number format.');
    } elseif($aadhaar_no != "" && !validateAadhaar($aadhaar_no)) {
        alert('danger', 'Invalid Aadhaar Number. Must be 12 digits.');
    } else {
        $res = validateAccount($account, $ifsc, $name, $phone);

        $status = 'unverified';
        $respJson = mysqli_real_escape_string($conn, $res['raw'] ?? '');

        if ($res['success']) {
            // Match the specific API structure provided by the user:
            // $res['data']['data']['beneValidationResp']['resourceData']
            $apiData = $res['data']['data'] ?? null;
            $resData = $apiData['beneValidationResp']['resourceData'] ?? null;
            $responseCode = $resData['responseCode'] ?? '';
            
            if ($responseCode === 'A' || (isset($res['data']['success']) && $res['data']['success'] == 1)) {
                $status = 'verified';
                $verifiedName = trim($resData['creditorName'] ?? $name);
                alert('success', 'Beneficiary verified! Bank name: ' . $verifiedName);
            } else {
                $status = 'failed';
                $failMsg = $res['data']['message'] ?? ($apiData['beneValidationResp']['metaData']['message'] ?? 'Verification unsuccessful');
                alert('danger', 'Verification issue: ' . $failMsg);
            }
        } else {
            $status = 'failed';
            $errMsg = $res['data']['message'] ?? ($res['error'] ?? 'API Error - Account could not be verified');
            alert('danger', 'Verification failed: ' . $errMsg);
        }

        $sql = "INSERT INTO beneficiaries (user_id, name, account_number, ifsc, bank_name, pan_no, aadhaar_no, phone, status, verification_response) 
                VALUES ($uId, '$name', '$account', '$ifsc', '$bank', '$pan_no', '$aadhaar_no', '$phone', '$status', '$respJson')";
        if (mysqli_query($conn, $sql)) {
            redirect('beneficiaries.php');
        }
    }
}

// Delete Beneficiary
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM beneficiaries WHERE id = $delete_id AND user_id = $uId";
    if (mysqli_query($conn, $sql)) {
        alert('success', 'Beneficiary deleted successfully.');
        redirect('beneficiaries.php');
    }
}

require_once '../includes/header.php';


$beneficiaries = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE user_id = $uId ORDER BY id DESC");
$bene_count = mysqli_num_rows($beneficiaries);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-address-book"></i> Saved Beneficiaries <span class="badge badge-info" style="margin-left: 8px;"><?php echo $bene_count; ?></span></h2>
        <button class="btn btn-primary btn-sm" onclick="openModal('addBeneModal')">
            <i class="fas fa-user-plus"></i> Add Beneficiary
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Account</th>
                    <th>Bank</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($bene = mysqli_fetch_assoc($beneficiaries)): ?>
                <tr>
                    <td>
                        <strong style="color: var(--text-primary);"><?php echo $bene['name']; ?></strong><br>
                        <small class="text-muted">P: <?php echo $bene['pan_no'] ?: 'N/A'; ?> | A: <?php echo $bene['aadhaar_no'] ?: 'N/A'; ?></small>
                        <?php if($bene['status'] == 'failed'): ?>
                            <div style="color: var(--danger); font-size: 10px; margin-top: 5px;">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <?php 
                                    $resp = json_decode($bene['verification_response'], true); 
                                    echo $resp['data']['message'] ?? $resp['message'] ?? 'API Validation Failed'; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="color: var(--text-primary);"><?php echo $bene['account_number']; ?></span><br>
                        <small class="text-muted"><?php echo $bene['ifsc']; ?></small>
                    </td>
                    <td><?php echo $bene['bank_name']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo ($bene['status'] == 'verified') ? 'success' : 'danger'; ?>">
                            <?php echo strtoupper($bene['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <?php if ($bene['status'] == 'verified'): ?>
                            <a href="payout.php?bene_id=<?php echo $bene['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane"></i> Payout
                            </a>
                            <?php endif; ?>
                            <a href="?delete_id=<?php echo $bene['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this beneficiary?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($bene_count == 0): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <p>No beneficiaries added yet. Click "Add Beneficiary" to get started.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Beneficiary Modal -->
<div class="modal-overlay" id="addBeneModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-shield-halved"></i> Add & Verify Beneficiary</h2>
            <button class="modal-close" onclick="closeModal('addBeneModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name (as per Bank)</label>
                    <input type="text" name="name" class="form-control" placeholder="Account holder name" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account" class="form-control" placeholder="Account number" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" name="ifsc" class="form-control" placeholder="e.g. SBIN0001234" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank" class="form-control" placeholder="e.g. State Bank of India" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Beneficiary PAN</label>
                        <input type="text" name="pan_no" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" title="Invalid PAN Format" placeholder="ABCDE1234F" style="text-transform: uppercase;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Aadhaar <span class="text-muted">(Opt)</span></label>
                        <input type="text" name="aadhaar_no" class="form-control" pattern="[0-9 ]{12,}" title="Must be 12 digits" placeholder="1234 5678 9012">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="10 digit number" pattern="[0-9]{10}" title="Must be 10 digits" required>
                </div>
                <div class="modal-footer" style="padding: 16px 0 0; border-top: none;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addBeneModal')">Cancel</button>
                    <button type="submit" name="add_bene" class="btn btn-primary">
                        <i class="fas fa-shield-halved"></i> Verify & Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
