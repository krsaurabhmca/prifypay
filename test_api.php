<?php
header('Content-Type: application/json');
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/api_helper.php';

$res = callAPI("GET", "balance-check");

echo json_encode($res, JSON_PRETTY_PRINT);
?>
