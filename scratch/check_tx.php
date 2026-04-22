<?php
require_once 'includes/db.php';
$res = mysqli_query($conn, "SELECT * FROM transactions ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
