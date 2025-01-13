<?php
session_start();
require_once './helper/logger.php';

$username = $_SESSION['login']['username'] ?? 'Unknown User';

unset($_SESSION['login']);
session_destroy();

header('Location: login.php');
exit();
