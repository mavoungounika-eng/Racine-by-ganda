<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config.php';

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM produits WHERE id = ?");
$stmt->execute([$id]);
header('Location: produits.php');
exit;
