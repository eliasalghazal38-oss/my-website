<?php
$host = "sql205.byetcluster.com";
$user = "if0_42709045";
$pass = "Elias20061410"; 
$db   = "if0_42709045_hakee";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES utf8mb4");
?>