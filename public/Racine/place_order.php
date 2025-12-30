<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Accès direct interdit.");
}

if (empty($_SESSION['panier'])) {
    die("Votre panier est vide.");
}

$prenom = trim($_POST['firstname'] ?? '');
$nom = trim($_POST['lastname'] ?? '');
$email = trim($_POST['emailaddress'] ?? '');
$telephone = trim($_POST['phone'] ?? '');
$adresse = trim($_POST['streetaddress'] ?? '');
$mode_paiement = $_POST['payment_method'] ?? '';
$terms = $_POST['terms'] ?? '';

if (!$prenom || !$nom || !$email || !$telephone || !$adresse || !$mode_paiement || !$terms) {
    die("Veuillez remplir tous les champs obligatoires.");
}

$client = $prenom . ' ' . $nom;

$subtotal = 0;
foreach ($_SESSION['panier'] as $item) {
    $prix = floatval($item['price'] ?? 0);
    $quantite = intval($item['quantity'] ?? 1);
    $subtotal += $prix * $quantite;
}

$livraison = 2000;
$remise = 0;
$total = $subtotal + $livraison - $remise;

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO commandes (client_name, client_email, client_phone, client_address, payment_method, subtotal, delivery, discount, total, date_commande) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) throw new Exception("Erreur préparation commande : " . $conn->error);

    $stmt->bind_param("sssssdddd", $client, $email, $telephone, $adresse, $mode_paiement, $subtotal, $livraison, $remise, $total);
    if (!$stmt->execute()) throw new Exception("Erreur exécution commande : " . $stmt->error);

    $order_id = $conn->insert_id;

    $stmt_item = $conn->prepare("INSERT INTO commande_details (order_id, produit_id, nom_produit, quantite, prix, image) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt_item) throw new Exception("Erreur préparation détails commande : " . $conn->error);

    foreach ($_SESSION['panier'] as $produit_id => $item) {
        $nomProduit = $item['name'] ?? '';
        $quantite = intval($item['quantity'] ?? 0);
        $prix = floatval($item['price'] ?? 0);
        $image = $item['image'] ?? '';

        if ($nomProduit === '') {
            throw new Exception("Le champ 'nom_produit' est vide.");
        }

        // !!! CORRECTION ICI : "iissds"
        $stmt_item->bind_param("iissds", $order_id, $produit_id, $nomProduit, $quantite, $prix, $image);

        if (!$stmt_item->execute()) {
            throw new Exception("Erreur insertion produit : " . $stmt_item->error);
        }
    }

    $conn->commit();
    unset($_SESSION['panier']);
    echo "✅ Commande enregistrée avec succès ! Numéro : #$order_id";

} catch (Exception $e) {
    $conn->rollback();
    die("❌ Erreur lors de la commande : " . $e->getMessage());
}
?>
