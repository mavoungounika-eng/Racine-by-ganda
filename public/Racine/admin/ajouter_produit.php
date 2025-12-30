<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prix = $_POST['prix'];
    $categorie = $_POST['categorie'];
    $collection = $_POST['collection'] ?? 'boutique';
    
    // Ajout description vide si non fournie (pour garder la structure)
    $description = ''; // ou tu peux ajouter un champ description dans le formulaire si besoin

    // Upload de l'image
    $image = $_FILES['image']['name'];
    $path = '../images/' . basename($image);
    move_uploaded_file($_FILES['image']['tmp_name'], $path);

    // Requête préparée
    $stmt = $conn->prepare("INSERT INTO produits (nom, prix, description, image, collection) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsss", $nom, $prix, $description, $image, $collection);
    $stmt->execute(); // n'oublie pas d'exécuter la requête

    header('Location: produits.php');
    exit;
}
?>

<h2>Ajouter un produit</h2>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="nom" placeholder="Nom" required><br>
    <input type="number" name="prix" placeholder="Prix" required><br>
    <input type="text" name="categorie" placeholder="Catégorie" required><br>
    
    <label for="collection">Collection :</label>
    <select name="collection" id="collection" required>
        <option value="feminine">Féminine</option>
        <option value="masculine">Masculine</option>
        <option value="accessoires">Accessoires</option>
        <option value="atelier">Atelier</option>
        <option value="edition_limitee">Édition Limitée</option>
    </select><br>

    <input type="file" name="image" accept="image/*" required><br>
    <button type="submit">Ajouter</button>
</form>
