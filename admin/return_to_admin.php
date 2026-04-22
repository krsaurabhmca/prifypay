<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_user_id'])) {
    $adminId = $_SESSION['admin_user_id'];
    
    // Fetch admin user data
    $query = "SELECT * FROM users WHERE id = $adminId AND role IN ('admin', 'dev')";
    $result = mysqli_query($conn, $query);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Restore Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        $redirectPath = $_SESSION['admin_role'] . "/index.php";

        // Remove temporary sessions
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_role']);

        header("Location: ../" . $redirectPath);
        exit();
    }
}

header("Location: ../login.php");
exit();
?>
