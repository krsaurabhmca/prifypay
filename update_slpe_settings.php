<?php
require_once 'includes/db.php';

$settings = [
    'api_mode' => 'live',
    'api_secret' => 'secret_oNkXroVDS0WY8aVt7E4YU3ynkX4CPHH5',
    'api_key' => 'key_bSO8j6bs3IA0W6gJYuPiNiCks1XVJler',
    'access_token' => 'access_token_M1mkYsmvSpG9uXSABzbIQ27BomyuL/uQClKFComaWlhwa6S0Y1jZYE8llQXwWzHr4qGUw6RaHTP82sHfPStvYA=='
];

foreach ($settings as $key => $val) {
    $key = mysqli_real_escape_string($conn, $key);
    $val = mysqli_real_escape_string($conn, $val);
    
    // Check if exists
    $check = mysqli_query($conn, "SELECT * FROM settings WHERE setting_key = '$key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$val' WHERE setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$val')");
    }
}

echo "Settings Updated Successfully";
?>
