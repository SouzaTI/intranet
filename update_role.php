<?php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: user.php"); exit(); }
$conn = new mysqli("localhost", "root", "", "intranet");
if (isset($_POST['user_id'], $_POST['role'])) {
    $user_id = intval($_POST['user_id']);
    $role = $conn->real_escape_string($_POST['role']);
    $conn->query("UPDATE users SET role='$role' WHERE id=$user_id");
}
header("Location: user.php");
exit();
?>