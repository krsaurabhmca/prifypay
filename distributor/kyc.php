<?php
require_once '../includes/header.php';
checkRole('distributor');

if (isset($_POST['submit_kyc'])) {
    $aadhar_no = mysqli_real_escape_string($conn, $_POST['aadhar_no']);
    $pan_no = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $account_no = mysqli_real_escape_string($conn, $_POST['account_no']);
    $ifsc = mysqli_real_escape_string($conn, $_POST['ifsc']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);

    // Handle File Uploads
    $target_dir = "../uploads/kyc/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $files = ['aadhar_front', 'aadhar_back', 'pan_card', 'passbook_check'];
    $file_paths = [];

    foreach ($files as $file) {
        if (isset($_FILES[$file]) && $_FILES[$file]['error'] == 0) {
            $ext = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
            $filename = $uId . "_" . $file . "_" . time() . "." . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES[$file]['tmp_name'], $target_file)) {
                $file_paths[$file] = "uploads/kyc/" . $filename;
            }
        }
    }

    // Check if user already has a KYC record
    $checkQuery = "SELECT id FROM kyc_details WHERE user_id = $uId";
    $checkRes = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkRes) > 0) {
        $sql = "UPDATE kyc_details SET 
                aadhar_no = '$aadhar_no', 
                pan_no = '$pan_no', 
                account_no = '$account_no', 
                ifsc = '$ifsc', 
                bank_name = '$bank_name',
                status = 'pending'";
        
        foreach ($file_paths as $key => $path) {
            $sql .= ", $key = '$path'";
        }
        $sql .= " WHERE user_id = $uId";
    } else {
        $cols = "user_id, aadhar_no, pan_no, account_no, ifsc, bank_name, status";
        $vals = "$uId, '$aadhar_no', '$pan_no', '$account_no', '$ifsc', '$bank_name', 'pending'";
        
        foreach ($file_paths as $key => $path) {
            $cols .= ", $key";
            $vals .= ", '$path'";
        }
        $sql = "INSERT INTO kyc_details ($cols) VALUES ($vals)";
    }

    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "UPDATE users SET kyc_status = 'pending' WHERE id = $uId");
        alert('success', 'KYC documents submitted successfully. Please wait for admin approval.');
        redirect('kyc.php');
    } else {
        alert('danger', 'Error submitting KYC: ' . mysqli_error($conn));
    }
}

// Get existing KYC data
$kycQuery = "SELECT * FROM kyc_details WHERE user_id = $uId";
$kycRes = mysqli_query($conn, $kycQuery);
$kycData = mysqli_fetch_assoc($kycRes);
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-id-card"></i> KYC Verification</h2>
                <?php echo getKycStatusLabel($userData['kyc_status']); ?>
            </div>
            <div class="card-body">
                <?php if ($userData['kyc_status'] == 'verified'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Your KYC is verified.
                    </div>
                <?php elseif ($userData['kyc_status'] == 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> Your KYC is pending approval.
                    </div>
                <?php elseif ($userData['kyc_status'] == 'rejected'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> Your KYC was rejected. Reason: <strong><?php echo $kycData['rejection_reason']; ?></strong>.
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Aadhaar Number</label>
                                <input type="text" name="aadhar_no" class="form-control" value="<?php echo $kycData['aadhar_no'] ?? ''; ?>" placeholder="12 Digit Aadhaar No" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="pan_no" class="form-control" value="<?php echo $kycData['pan_no'] ?? ''; ?>" placeholder="ABCDE1234F" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h4 style="margin: 20px 0 10px; color: var(--primary);">Bank Details</h4>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_no" class="form-control" value="<?php echo $kycData['account_no'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" name="ifsc" class="form-control" value="<?php echo $kycData['ifsc'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?php echo $kycData['bank_name'] ?? ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h4 style="margin: 20px 0 10px; color: var(--primary);">Upload Documents</h4>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Aadhaar Front</label>
                                <input type="file" name="aadhar_front" class="form-control" <?php echo !isset($kycData['aadhar_front']) ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Aadhaar Back</label>
                                <input type="file" name="aadhar_back" class="form-control" <?php echo !isset($kycData['aadhar_back']) ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">PAN Card</label>
                                <input type="file" name="pan_card" class="form-control" <?php echo !isset($kycData['pan_card']) ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Cancel Cheque / Passbook</label>
                                <input type="file" name="passbook_check" class="form-control" <?php echo !isset($kycData['passbook_check']) ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="submit_kyc" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Submit for Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
