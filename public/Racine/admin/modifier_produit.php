<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include 'config.php';
include 'includes/header.php';

$id = $_GET['id'];
$produit = $conn->prepare("SELECT * FROM produits WHERE id = ?");
$produit->execute([$id]);
$p = $produit->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prix = $_POST['prix'];
    $categorie = $_POST['categorie'];

    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $path = 'images/' . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $path);
    } else {
        $path = $p['image'];
    }

    $stmt = $conn->prepare("UPDATE produits SET nom = ?, prix = ?, categorie = ?, image = ? WHERE id = ?");
    $stmt->execute([$nom, $prix, $categorie, $path, $id]);
    header('Location: produits.php');
    exit;
}
?>

<h2>Modifier le produit</h2>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="nom" value="<?= htmlspecialchars($p['nom']) ?>" required><br>
    <input type="number" name="prix" value="<?= $p['prix'] ?>" required><br>
    <input type="text" name="categorie" value="<?= $p['categorie'] ?>" required><br>
    <img src="<?= $p['image'] ?>" width="100"><br>
    <input type="file" name="image" accept="image/*"><br>
    <button type="submit">Mettre à jour</button>
</form>
