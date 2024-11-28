<?php
session_start();

if (isset($_SESSION['login'])) {
    if ($_SESSION['login']['role'] === 'admin') {
        header('Location: dashboard/admin_dashboard.php'); 
    } else {
        header('Location: dashboard/user_dashboard.php'); 
    }
} else {
    header('Location: login.php');
}

exit();
