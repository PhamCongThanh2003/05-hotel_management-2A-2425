<?php
require_once 'config.php';

function checkRole($allowed_roles) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        die("Bạn không có quyền truy cập trang này!");
    }
}
?>