<?php
require_once 'includes/db.php';
$res = mysqli_query($conn, "SELECT * FROM settings WHERE setting_key IN ('payout_gateway_id', 'api_mode')");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['setting_key'] . ": " . $row['setting_value'] . "\n";
}
