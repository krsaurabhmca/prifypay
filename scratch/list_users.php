<?php
require_once 'includes/db.php';
$res = mysqli_query($conn, "SELECT id, name, wallet_balance, role FROM users");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
