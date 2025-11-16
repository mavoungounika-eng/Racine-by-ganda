<?php
session_start();

// Initialiser le panier si nécessaire
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Cas 1 : Appel via GET (ex: depuis un lien ou un bouton "Acheter")
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'], $_GET['name'], $_GET['price'])) {
    $id = $_GET['id'];
    $name = trim($_GET['name']);
    $price = floatval($_GET['price']);
    $image = $_GET['image'] ?? '';

    // Validation simple
    if (!$id || $name === '' || $price <= 0) {
        die("Produit invalide : données manquantes ou incorrectes.");
    }

    if (!isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id] = [
            'name' => htmlspecialchars($name),
            'price' => $price,
            'image' => $image,
            'quantity' => 1
        ];
    } else {
        $_SESSION['panier'][$id]['quantity']++;
    }

    // Rediriger vers la page panier après ajout
    header("Location: cart.php");
    exit;
}

// Cas 2 : Appel via POST JSON (ex: JavaScript fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    $id = $input['id'] ?? null;
    $name = trim($input['name'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $image = $input['image'] ?? '';

    if (!$id || $name === '' || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Produit invalide : données manquantes ou incorrectes']);
        exit;
    }

    if (!isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id] = [
            'name' => htmlspecialchars($name),
            'price' => $price,
            'image' => $image,
            'quantity' => 1
        ];
    } else {
        $_SESSION['panier'][$id]['quantity']++;
    }

    echo json_encode([
        'success' => true,
        'count' => array_sum(array_column($_SESSION['panier'], 'quantity'))
    ]);
    exit;
}
?>
