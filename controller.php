<?php
require_once 'config.php';

function checkRole($allowed_roles) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        echo '<pre>Debug: Session not set - user_id or role missing. Redirecting to login.php</pre>';
        header("Location: login.php");
        exit;
    }
   // echo '<pre>Debug: Current role: ' . $_SESSION['role'] . '</pre>';
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        die("Bạn không có quyền truy cập trang này!");
    }
}
?>