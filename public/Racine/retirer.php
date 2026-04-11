<?php
session_start();

$id = $_GET['id'] ?? null;

if ($id && isset($_SESSION['panier'][$id])) {
    unset($_SESSION['panier'][$id]);
    $count = array_sum(array_column($_SESSION['panier'], 'quantity'));
    echo json_encode(['success' => true, 'count' => $count]);
} else {
    echo json_encode(['success' => false]);
}
?>
