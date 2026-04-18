<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_user_id'])) {
    $adminId = $_SESSION['admin_user_id'];
    
    // Fetch admin user data
    $query = "SELECT * FROM users WHERE id = $adminId AND role = 'admin'";
    $result = mysqli_query($conn, $query);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Restore Admin Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Remove temporary sessions
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_name']);

        header("Location: index.php");
        exit();
    }
}

header("Location: ../login.php");
exit();
?>
