<?php
session_start();
include '../config.php'; // Ajuste le chemin si besoin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
        die("ID de commande manquant ou invalide.");
    }

    $order_id = intval($_POST['order_id']);

    // Suppression de la commande et de ses détails (commande_details)
    // Supposons que tu veux aussi supprimer les lignes associées dans commande_details

    // Préparer la suppression dans la table commande_details
    $stmt1 = $conn->prepare("DELETE FROM commande_details WHERE order_id = ?");
    $stmt1->bind_param("i", $order_id);
    $stmt1->execute();

    // Préparer la suppression dans la table commandes
    $stmt2 = $conn->prepare("DELETE FROM commandes WHERE id = ?");
    $stmt2->bind_param("i", $order_id);
    $stmt2->execute();

    // Vérifier si la suppression a marché
    if ($stmt2->affected_rows > 0) {
        header("Location: commandes.php?msg=Commande supprimée avec succès");
        exit;
    } else {
        die("Erreur lors de la suppression : commande introuvable.");
    }
} else {
    die("Accès non autorisé.");
}
?>
