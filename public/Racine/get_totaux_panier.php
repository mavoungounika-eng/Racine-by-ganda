<?php
session_start();
include './config.php';
$panier = $_SESSION['panier'] ?? [];

$sous_total = 0;
foreach ($panier as $item) {
    $sous_total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}

$livraison = 2000;
$remise = 3000;
$total = max(0, $sous_total + $livraison - $remise);

echo json_encode([
    'sous_total' => $sous_total,
    'livraison' => $livraison,
    'remise' => $remise,
    'total' => $total
]);
