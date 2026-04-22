<?php
require_once '../includes/header.php';
checkRole(['admin', 'dev']);

// Add User Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $pan_no = strtoupper(mysqli_real_escape_string($conn, $_POST['pan_no']));
    $aadhaar_no = str_replace(' ', '', mysqli_real_escape_string($conn, $_POST['aadhaar_no']));
    
    // Validation
    if(!validateMobile($phone)) {
        alert('danger', 'Invalid Mobile Number. Must be 10 digits.');
    } elseif(!validatePAN($pan_no)) {
        alert('danger', 'Invalid PAN Number format.');
    } elseif($aadhaar_no != "" && !validateAadhaar($aadhaar_no)) {
        alert('danger', 'Invalid Aadhaar Number. Must be 12 digits.');
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role_type = mysqli_real_escape_string($conn, $_POST['role_type']);
        $parent_id = $_POST['parent_id'] ? (int)$_POST['parent_id'] : 'NULL';

        $sql = "INSERT INTO users (name, email, phone, pan_no, aadhaar_no, password, role, parent_id) 
                VALUES ('$name', '$email', '$phone', '$pan_no', '$aadhaar_no', '$password', '$role_type', $parent_id)";
        
        if (mysqli_query($conn, $sql)) {
            alert('success', 'User added successfully!');
        } else {
            alert('danger', 'Error adding user: ' . mysqli_error($conn));
        }
    }
}

$users = mysqli_query($conn, "SELECT u.*, p.name as parent_name FROM users u LEFT JOIN users p ON u.parent_id = p.id WHERE u.role NOT IN ('admin', 'dev') ORDER BY u.id DESC");
$distributors = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'distributor' ORDER BY name ASC");
$user_count = mysqli_num_rows($users);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-users"></i> All Members <span class="badge badge-info" style="margin-left: 8px;"><?php echo $user_count; ?></span></h2>
        <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">
            <i class="fas fa-user-plus"></i> Add New Member
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>KYC</th>
                    <th>Role</th>
                    <th>Parent</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td>
                        <strong style="color: var(--text-primary);"><?php echo $u['name']; ?></strong><br>
                        <small class="text-muted"><?php echo $u['phone']; ?></small>
                    </td>
                    <td>
                        <?php echo getKycStatusLabel($u['kyc_status']); ?>
                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">PAN: <?php echo $u['pan_no'] ?: 'N/A'; ?></div>
                    </td>
                    <td><span class="badge badge-role capitalize"><?php echo $u['role']; ?></span></td>
                    <td><?php echo $u['parent_name'] ?? '<span class="text-muted">System</span>'; ?></td>
                    <td class="fw-700">
                        <div class="text-success" title="Main Wallet"><?php echo formatCurrency($u['wallet_balance']); ?></div>
                        <div class="text-primary" style="font-size: 11px;" title="Earnings"><?php echo formatCurrency($u['earnings_balance']); ?></div>
                    </td>
                    <td><span class="badge badge-success">ACTIVE</span></td>
                    <td>
                        <a href="login_as.php?id=<?php echo $u['id']; ?>" class="btn btn-secondary btn-sm" title="Login as this user">
                            <i class="fas fa-right-to-bracket"></i> Login As
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($user_count == 0): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No users found. Click "Add New Member" to create one.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Add New Member</h2>
            <button class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="10 digit number" pattern="[0-9]{10}" title="Must be 10 digits" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">PAN Card No.</label>
                        <input type="text" name="pan_no" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" title="Invalid PAN Format" placeholder="ABCDE1234F" style="text-transform: uppercase;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Aadhaar No. <span class="text-muted">(Opt)</span></label>
                        <input type="text" name="aadhaar_no" class="form-control" pattern="[0-9 ]{12,}" title="Must be 12 digits" placeholder="1234 5678 9012">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Set password" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role_type" class="form-control" required id="role_select">
                            <option value="retailer">Retailer</option>
                            <option value="distributor">Distributor</option>
                        </select>
                    </div>
                    <div class="form-group" id="parent_group">
                        <label class="form-label">Assign to Distributor</label>
                        <select name="parent_id" class="form-control">
                            <option value="">None (Direct)</option>
                            <?php while($d = mysqli_fetch_assoc($distributors)): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 0 0; border-top: none;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('role_select').addEventListener('change', function() {
    if (this.value === 'distributor') {
        document.getElementById('parent_group').style.display = 'none';
    } else {
        document.getElementById('parent_group').style.display = 'block';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
