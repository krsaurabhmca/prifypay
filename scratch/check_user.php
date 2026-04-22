<?php
require_once 'includes/db.php';
$res = mysqli_query($conn, "SELECT * FROM users WHERE name LIKE '%KUMAR%'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
