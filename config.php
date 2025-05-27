<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_manage";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>