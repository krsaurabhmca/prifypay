<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/index.php");
} else {
    header("Location: login.php");
}
exit();
?>