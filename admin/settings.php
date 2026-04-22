<?php
require_once '../includes/header.php';
checkRole('admin');

if (isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_value = '$value'");
    }
    alert('success', 'Settings updated successfully!');
}

// Fetch all settings into an array for easy access
$settingsRes = mysqli_query($conn, "SELECT * FROM settings");
$s = [];
while($row = mysqli_fetch_assoc($settingsRes)) {
    $s[$row['setting_key']] = $row['setting_value'];
}

// Fetch dynamic gateway list
$gatewayList = getGatewayList();
?>

<div class="max-w-900 mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-tools"></i> System Configuration</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="settings-container">
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="showTab('tab-gateway')"><i class="fas fa-network-wired"></i> Gateway</button>
                    <button class="tab-btn" onclick="showTab('tab-apikeys')"><i class="fas fa-key"></i> SLPE API Keys</button>
                    <button class="tab-btn" onclick="showTab('tab-kyc')"><i class="fas fa-id-card"></i> KYC API (EKYCHUB)</button>
                    <button class="tab-btn" onclick="showTab('tab-smtp')"><i class="fas fa-envelope"></i> SMTP / OTP</button>
                </div>
                
                <form method="POST" class="settings-form">
                    <!-- Gateway Tab -->
                    <div id="tab-gateway" class="tab-content active">
                        <h4 class="section-title">Gateway Routing</h4>
                        
                        <div class="form-group mb-20">
                            <label class="form-label">Default Pay-In Gateway</label>
                            <select name="settings[payin_gateway_id]" class="form-control h-50">
                                <option value="">Select Gateway</option>
                                <?php foreach($gatewayList as $gw): ?>
                                    <option value="<?php echo $gw['id']; ?>" <?php echo ($s['payin_gateway_id'] ?? '') == $gw['id'] ? 'selected' : ''; ?>>
                                        <?php echo $gw['name']; ?> (ID: <?php echo $gw['id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label">Default Payout Gateway</label>
                            <select name="settings[payout_gateway_id]" class="form-control h-50">
                                <option value="">Select Gateway</option>
                                <?php foreach($gatewayList as $gw): ?>
                                    <option value="<?php echo $gw['id']; ?>" <?php echo ($s['payout_gateway_id'] ?? '') == $gw['id'] ? 'selected' : ''; ?>>
                                        <?php echo $gw['name']; ?> (ID: <?php echo $gw['id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (empty($gatewayList)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Unable to fetch gateway list. Please check your API credentials.
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- API Keys Tab -->
                    <div id="tab-apikeys" class="tab-content">
                        <h4 class="section-title">SLPE API Credentials</h4>
                        <div class="form-group mb-20">
                            <label class="form-label">API Mode</label>
                            <select name="settings[api_mode]" class="form-control">
                                <option value="live" <?php echo ($s['api_mode'] ?? 'live') == 'live' ? 'selected' : ''; ?>>Live</option>
                                <option value="test" <?php echo ($s['api_mode'] ?? 'live') == 'test' ? 'selected' : ''; ?>>Test</option>
                            </select>
                        </div>
                        <div class="form-group mb-20">
                            <label class="form-label">API Key</label>
                            <input type="text" name="settings[api_key]" class="form-control" value="<?php echo $s['api_key'] ?? ''; ?>" placeholder="key_...">
                        </div>
                        <div class="form-group mb-20">
                            <label class="form-label">API Secret</label>
                            <input type="text" name="settings[api_secret]" class="form-control" value="<?php echo $s['api_secret'] ?? ''; ?>" placeholder="secret_...">
                        </div>
                        <div class="form-group mb-20">
                            <label class="form-label">Access Token</label>
                            <textarea name="settings[access_token]" class="form-control" rows="3" placeholder="access_token_..."><?php echo $s['access_token'] ?? ''; ?></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Changing these keys will affect all transactions immediately.
                        </div>
                    </div>

                    <!-- KYC API Tab -->
                    <div id="tab-kyc" class="tab-content">
                        <h4 class="section-title">EKYCHUB DigiLocker Settings</h4>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">API Username</label>
                                <input type="text" name="settings[ekyc_username]" class="form-control" value="<?php echo $s['ekyc_username'] ?? ''; ?>" placeholder="9431426600">
                            </div>
                            <div class="form-group">
                                <label class="form-label">API Token</label>
                                <input type="text" name="settings[ekyc_token]" class="form-control" value="<?php echo $s['ekyc_token'] ?? ''; ?>" placeholder="10008398b8f2...">
                            </div>
                        </div>
                        <div class="form-group mt-15">
                            <label class="form-label">DigiLocker Status</label>
                            <select name="settings[ekyc_status]" class="form-control">
                                <option value="enabled" <?php echo ($s['ekyc_status'] ?? '') == 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="disabled" <?php echo ($s['ekyc_status'] ?? '') == 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>

                    <!-- SMTP Tab -->
                    <div id="tab-smtp" class="tab-content">
                        <h4 class="section-title">SMTP Configuration</h4>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="settings[smtp_host]" class="form-control" value="<?php echo $s['smtp_host'] ?? ''; ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">SMTP Port</label>
                                <input type="text" name="settings[smtp_port]" class="form-control" value="<?php echo $s['smtp_port'] ?? '587'; ?>">
                            </div>
                        </div>
                        <div class="grid-2 mt-15">
                            <div class="form-group">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" name="settings[smtp_user]" class="form-control" value="<?php echo $s['smtp_user'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" name="settings[smtp_pass]" class="form-control" value="<?php echo $s['smtp_pass'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-group mt-15">
                            <label class="form-label">From Email</label>
                            <input type="email" name="settings[smtp_from]" class="form-control" value="<?php echo $s['smtp_from'] ?? 'noreply@prifypay.in'; ?>">
                        </div>
                        
                        <h4 class="section-title mt-30">Login Security Settings</h4>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Login OTP Verification</label>
                                <select name="settings[login_otp_enabled]" class="form-control">
                                    <option value="1" <?php echo ($s['login_otp_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo ($s['login_otp_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Login Captcha</label>
                                <select name="settings[login_captcha_enabled]" class="form-control">
                                    <option value="1" <?php echo ($s['login_captcha_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo ($s['login_captcha_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            </div>
                        </div>

                        <h4 class="section-title mt-30">OTP Verification Settings</h4>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Mobile OTP Verification (Global)</label>
                                <select name="settings[otp_mobile_enabled]" class="form-control">
                                    <option value="1" <?php echo ($s['otp_mobile_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo ($s['otp_mobile_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email OTP Verification (Global)</label>
                                <select name="settings[otp_email_enabled]" class="form-control">
                                    <option value="1" <?php echo ($s['otp_email_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo ($s['otp_email_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-30 border-top" style="background: var(--bg-elevated); border-radius: 0 0 12px 12px;">
                        <button type="submit" name="update_settings" class="btn btn-primary h-50" style="padding: 0 40px;">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container { display: flex; min-height: 500px; }
.settings-tabs { width: 250px; background: var(--bg-elevated); border-right: 1px solid var(--border); padding: 20px 0; }
.tab-btn { width: 100%; text-align: left; padding: 14px 24px; border: none; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 12px; }
.tab-btn:hover { background: rgba(99, 102, 241, 0.05); color: var(--primary); }
.tab-btn.active { background: white; color: var(--primary); border-left: 4px solid var(--primary); }
[data-theme="dark"] .tab-btn.active { background: var(--bg-card); }

.settings-form { flex: 1; display: flex; flex-direction: column; }
.tab-content { padding: 30px; display: none; flex: 1; }
.tab-content.active { display: block; }

.section-title { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
.h-50 { height: 50px !important; }
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

<?php require_once '../includes/footer.php'; ?>
