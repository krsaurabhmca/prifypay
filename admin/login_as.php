<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

checkRole(['admin', 'dev']);

if (isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    
    // Fetch target user data
    $query = "SELECT * FROM users WHERE id = $targetId AND role != 'admin'";
    $result = mysqli_query($conn, $query);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Store original admin session if not already stored
        if (!isset($_SESSION['admin_user_id'])) {
            $_SESSION['admin_user_id'] = $_SESSION['user_id'];
            $_SESSION['admin_name'] = $_SESSION['name'];
            $_SESSION['admin_role'] = $_SESSION['role'];
        }

        // Switch session to target user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Redirect to target dashboard
        header("Location: ../" . $user['role'] . "/index.php");
        exit();
    } else {
        alert('danger', 'User not found or cannot login as Admin.');
        redirect('users.php');
    }
} else {
    redirect('users.php');
}
?>
