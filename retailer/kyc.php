<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect("../login.php");
}

$uId = $_SESSION['user_id'];

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

require_once '../includes/header.php';
checkRole('retailer');

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
                        <i class="fas fa-check-circle"></i> Your KYC is verified. You can now use all features of the portal.
                    </div>
                <?php else: ?>
                    <div class="row mb-30">
                        <div class="col-md-6">
                            <div class="kyc-method-card <?php echo ($kycData['aadhar_status'] ?? '') == 'verified' ? 'verified' : ''; ?>">
                                <div class="method-icon"><i class="fas fa-id-card"></i></div>
                                <div class="method-info">
                                    <h4>Aadhaar Verification</h4>
                                    <p>Verify instantly via DigiLocker</p>
                                    <?php if (($kycData['aadhar_status'] ?? '') == 'verified'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Verified</span>
                                    <?php else: ?>
                                        <button onclick="startEkyc('AADHAAR')" class="btn btn-primary btn-sm mt-10">Verify Aadhaar</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="kyc-method-card <?php echo ($kycData['pan_status'] ?? '') == 'verified' ? 'verified' : ''; ?>">
                                <div class="method-icon"><i class="fas fa-id-badge"></i></div>
                                <div class="method-info">
                                    <h4>PAN Verification</h4>
                                    <p>Verify instantly via DigiLocker</p>
                                    <?php if (($kycData['pan_status'] ?? '') == 'verified'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Verified</span>
                                    <?php else: ?>
                                        <button onclick="startEkyc('PAN')" class="btn btn-primary btn-sm mt-10">Verify PAN</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($userData['kyc_status'] == 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> Your KYC is pending approval. You can update your details if needed.
                    </div>
                <?php elseif ($userData['kyc_status'] == 'rejected'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> Your KYC was rejected. Reason: <strong><?php echo $kycData['rejection_reason']; ?></strong>. Please re-submit correct details.
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
                        
                        <!-- Aadhaar Front -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Aadhaar Front</label>
                                <div class="file-preview-container" id="preview_aadhar_front">
                                    <?php if (isset($kycData['aadhar_front'])): ?>
                                        <img src="../<?php echo $kycData['aadhar_front']; ?>" alt="Aadhaar Front">
                                    <?php else: ?>
                                        <div class="no-preview"><i class="fas fa-image"></i><br>No Preview</div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="aadhar_front" class="form-control" onchange="previewFile(this, 'preview_aadhar_front')" <?php echo !isset($kycData['aadhar_front']) ? 'required' : ''; ?> accept="image/*">
                            </div>
                        </div>

                        <!-- Aadhaar Back -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Aadhaar Back</label>
                                <div class="file-preview-container" id="preview_aadhar_back">
                                    <?php if (isset($kycData['aadhar_back'])): ?>
                                        <img src="../<?php echo $kycData['aadhar_back']; ?>" alt="Aadhaar Back">
                                    <?php else: ?>
                                        <div class="no-preview"><i class="fas fa-image"></i><br>No Preview</div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="aadhar_back" class="form-control" onchange="previewFile(this, 'preview_aadhar_back')" <?php echo !isset($kycData['aadhar_back']) ? 'required' : ''; ?> accept="image/*">
                            </div>
                        </div>

                        <!-- PAN Card -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">PAN Card</label>
                                <div class="file-preview-container" id="preview_pan_card">
                                    <?php if (isset($kycData['pan_card'])): ?>
                                        <img src="../<?php echo $kycData['pan_card']; ?>" alt="PAN Card">
                                    <?php else: ?>
                                        <div class="no-preview"><i class="fas fa-image"></i><br>No Preview</div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="pan_card" class="form-control" onchange="previewFile(this, 'preview_pan_card')" <?php echo !isset($kycData['pan_card']) ? 'required' : ''; ?> accept="image/*">
                            </div>
                        </div>

                        <!-- Passbook -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Cancel Cheque / Passbook</label>
                                <div class="file-preview-container" id="preview_passbook_check">
                                    <?php if (isset($kycData['passbook_check'])): ?>
                                        <img src="../<?php echo $kycData['passbook_check']; ?>" alt="Passbook">
                                    <?php else: ?>
                                        <div class="no-preview"><i class="fas fa-image"></i><br>No Preview</div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="passbook_check" class="form-control" onchange="previewFile(this, 'preview_passbook_check')" <?php echo !isset($kycData['passbook_check']) ? 'required' : ''; ?> accept="image/*">
                            </div>
                        </div>
                    </div>

                    <style>
                        .kyc-method-card {
                            background: var(--bg-elevated);
                            border: 1px solid var(--border);
                            border-radius: 16px;
                            padding: 24px;
                            display: flex;
                            align-items: center;
                            gap: 20px;
                            transition: var(--transition);
                            position: relative;
                            overflow: hidden;
                        }
                        .kyc-method-card:hover { transform: translateY(-3px); border-color: var(--primary); }
                        .kyc-method-card.verified { border-color: var(--success); background: rgba(16, 185, 129, 0.05); }
                        
                        .method-icon { width: 56px; height: 56px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--primary); }
                        .verified .method-icon { color: var(--success); }
                        
                        .method-info h4 { font-size: 16px; font-weight: 700; margin: 0 0 4px; }
                        .method-info p { font-size: 13px; color: var(--text-muted); margin: 0; }

                        .file-preview-container {
                            width: 100%;
                            height: 150px;
                            border: 2px dashed var(--border);
                            border-radius: var(--radius);
                            margin-bottom: 10px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            overflow: hidden;
                            background: var(--bg-elevated);
                        }
                        .file-preview-container img {
                            max-width: 100%;
                            max-height: 100%;
                            object-fit: contain;
                        }
                        .no-preview {
                            text-align: center;
                            color: var(--text-muted);
                            font-size: 12px;
                        }
                        .no-preview i {
                            font-size: 24px;
                            margin-bottom: 5px;
                        }
                    </style>

                    <script>
                        function previewFile(input, previewId) {
                            const container = document.getElementById(previewId);
                            const file = input.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    container.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                                }
                                reader.readAsDataURL(file);
                            }
                        }

                        function startEkyc(type) {
                            const btn = event.currentTarget;
                            const originalText = btn.innerHTML;
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing...';

                            fetch('ajax_ekyc.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=create_url&type=${type}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = data.url;
                                } else {
                                    alert(data.message || 'Failed to initialize verification.');
                                    btn.disabled = false;
                                    btn.innerHTML = originalText;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                            });
                        }
                    </script>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="submit_kyc" class="btn btn-primary btn-block">
                            <i class="fas fa-upload"></i> Submit for Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
