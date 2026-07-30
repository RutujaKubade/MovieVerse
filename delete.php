<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit();
}
include 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $sql = "DELETE FROM library WHERE id=$id";
    $conn->query($sql);
}

header("Location:index.php");
exit();
?>