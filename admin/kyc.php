<?php
require_once '../includes/header.php';
checkRole(['admin', 'dev']);

// Handle Approval/Rejection
if (isset($_POST['action_kyc'])) {
    $kycId = (int)$_POST['kyc_id'];
    $action = $_POST['action']; // verified / rejected
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

    $kycQuery = "SELECT user_id FROM kyc_details WHERE id = $kycId";
    $kycRes = mysqli_query($conn, $kycQuery);
    if ($kycRow = mysqli_fetch_assoc($kycRes)) {
        $userId = $kycRow['user_id'];
        
        $sql = "UPDATE kyc_details SET status = '$action', rejection_reason = '$reason' WHERE id = $kycId";
        if (mysqli_query($conn, $sql)) {
            mysqli_query($conn, "UPDATE users SET kyc_status = '$action' WHERE id = $userId");
            alert('success', "KYC status updated to $action.");
        } else {
            alert('danger', 'Error updating KYC: ' . mysqli_error($conn));
        }
    }
}

$status = $_GET['status'] ?? 'pending';
$query = "SELECT k.*, u.name, u.email, u.phone FROM kyc_details k JOIN users u ON k.user_id = u.id WHERE k.status = '$status' ORDER BY k.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-id-card"></i> KYC Verification Requests</h2>
        <div class="header-actions">
            <a href="?status=pending" class="btn <?php echo $status == 'pending' ? 'btn-primary' : 'btn-light'; ?>">Pending</a>
            <a href="?status=verified" class="btn <?php echo $status == 'verified' ? 'btn-success' : 'btn-light'; ?>">Verified</a>
            <a href="?status=rejected" class="btn <?php echo $status == 'rejected' ? 'btn-danger' : 'btn-light'; ?>">Rejected</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Aadhaar</th>
                        <th>PAN</th>
                        <th>Bank Details</th>
                        <th>Documents</th>
                        <th>Date</th>
                        <?php if ($status == 'pending'): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $row['name']; ?></strong><br>
                                    <small><?php echo $row['phone']; ?></small>
                                </td>
                                <td><?php echo $row['aadhar_no']; ?></td>
                                <td><?php echo $row['pan_no']; ?></td>
                                <td>
                                    <?php echo $row['bank_name']; ?><br>
                                    <small>Acc: <?php echo $row['account_no']; ?></small><br>
                                    <small>IFSC: <?php echo $row['ifsc']; ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button type="button" onclick="previewDoc('../<?php echo $row['aadhar_front']; ?>', 'Aadhaar Front')" title="Aadhaar Front" class="btn btn-sm btn-light"><i class="fas fa-image"></i> AF</button>
                                        <button type="button" onclick="previewDoc('../<?php echo $row['aadhar_back']; ?>', 'Aadhaar Back')" title="Aadhaar Back" class="btn btn-sm btn-light"><i class="fas fa-image"></i> AB</button>
                                        <button type="button" onclick="previewDoc('../<?php echo $row['pan_card']; ?>', 'PAN Card')" title="PAN Card" class="btn btn-sm btn-light"><i class="fas fa-image"></i> PAN</button>
                                        <button type="button" onclick="previewDoc('../<?php echo $row['passbook_check']; ?>', 'Passbook')" title="Passbook" class="btn btn-sm btn-light"><i class="fas fa-image"></i> PB</button>
                                    </div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <?php if ($status == 'pending'): ?>
                                    <td>
                                        <button class="btn btn-sm btn-success" onclick="approveKYC(<?php echo $row['id']; ?>)">Approve</button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectKYC(<?php echo $row['id']; ?>)">Reject</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" align="center">No <?php echo $status; ?> requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div id="docPreviewModal" class="modal" style="display:none; position:fixed; z-index:3000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(5px);">
    <div class="modal-content" style="background:transparent; margin:2% auto; width:90%; max-width:1000px; position:relative; display:flex; flex-direction:column; align-items:center;">
        <div style="background:var(--bg-card); width:100%; padding:15px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center; border:1px solid var(--border);">
            <h3 id="previewTitle" style="margin:0;">Document Preview</h3>
            <div style="display:flex; gap:15px; align-items:center;">
                <div class="zoom-controls" style="display:flex; align-items:center; background:var(--bg-elevated); padding:5px 15px; border-radius:50px; gap:10px;">
                    <button type="button" onclick="zoomDoc(-0.2)" class="btn btn-sm btn-light" style="border-radius:50%; width:30px; height:30px; padding:0;"><i class="fas fa-minus"></i></button>
                    <span id="zoomLevel" style="font-weight:700; min-width:40px; text-align:center;">100%</span>
                    <button type="button" onclick="zoomDoc(0.2)" class="btn btn-sm btn-light" style="border-radius:50%; width:30px; height:30px; padding:0;"><i class="fas fa-plus"></i></button>
                    <button type="button" onclick="resetZoom()" class="btn btn-sm btn-primary" style="margin-left:5px;">Reset</button>
                </div>
                <button type="button" class="btn btn-danger" onclick="closeDocModal()" style="border-radius:50%; width:36px; height:36px; padding:0;"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div id="previewContainer" style="width:100%; height:75vh; background:var(--bg-card); border-radius:0 0 12px 12px; overflow:auto; display:flex; align-items:center; justify-content:center; padding:20px; border:1px solid var(--border); border-top:none; position:relative;">
            <img id="previewImage" src="" style="max-width:100%; transition:transform 0.2s ease-out; cursor:grab;" onmousedown="startDrag(event)">
        </div>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div id="kycModal" class="modal" style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--bg-card); margin:10% auto; padding:20px; border-radius:var(--radius-lg); width:400px; border:1px solid var(--border);">
        <h3 id="modalTitle">KYC Action</h3>
        <form method="POST">
            <input type="hidden" name="kyc_id" id="modalKycId">
            <input type="hidden" name="action" id="modalAction">
            
            <div id="rejectReasonBox" style="display:none; margin-top:15px;">
                <label class="form-label">Rejection Reason</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Explain why it was rejected..."></textarea>
            </div>
            
            <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-light" onclick="closeModal()">Cancel</button>
                <button type="submit" name="action_kyc" class="btn btn-primary" id="modalSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentScale = 1;

function previewDoc(url, title) {
    document.getElementById('previewImage').src = url;
    document.getElementById('previewTitle').innerText = title;
    document.getElementById('docPreviewModal').style.display = 'block';
    resetZoom();
}

function zoomDoc(delta) {
    currentScale += delta;
    if(currentScale < 0.2) currentScale = 0.2;
    if(currentScale > 5) currentScale = 5;
    applyZoom();
}

function resetZoom() {
    currentScale = 1;
    applyZoom();
}

function applyZoom() {
    const img = document.getElementById('previewImage');
    img.style.transform = `scale(${currentScale})`;
    document.getElementById('zoomLevel').innerText = Math.round(currentScale * 100) + '%';
}

function closeDocModal() {
    document.getElementById('docPreviewModal').style.display = 'none';
}

function approveKYC(id) {
    document.getElementById('modalKycId').value = id;
    document.getElementById('modalAction').value = 'verified';
    document.getElementById('modalTitle').innerText = 'Approve KYC';
    document.getElementById('rejectReasonBox').style.display = 'none';
    document.getElementById('modalSubmitBtn').className = 'btn btn-success';
    document.getElementById('modalSubmitBtn').innerText = 'Approve';
    document.getElementById('kycModal').style.display = 'block';
}

function rejectKYC(id) {
    document.getElementById('modalKycId').value = id;
    document.getElementById('modalAction').value = 'rejected';
    document.getElementById('modalTitle').innerText = 'Reject KYC';
    document.getElementById('rejectReasonBox').style.display = 'block';
    document.getElementById('modalSubmitBtn').className = 'btn btn-danger';
    document.getElementById('modalSubmitBtn').innerText = 'Reject';
    document.getElementById('kycModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('kycModal').style.display = 'none';
}

// Drag functionality for zoomed image
function startDrag(e) {
    if (currentScale <= 1) return;
    const container = document.getElementById('previewContainer');
    const img = document.getElementById('previewImage');
    
    let startX = e.clientX;
    let startY = e.clientY;
    let startScrollLeft = container.scrollLeft;
    let startScrollTop = container.scrollTop;

    img.style.cursor = 'grabbing';

    function onMouseMove(e) {
        container.scrollLeft = startScrollLeft - (e.clientX - startX);
        container.scrollTop = startScrollTop - (e.clientY - startY);
    }

    function onMouseUp() {
        img.style.cursor = 'grab';
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}
</script>

<?php require_once '../includes/footer.php'; ?>
