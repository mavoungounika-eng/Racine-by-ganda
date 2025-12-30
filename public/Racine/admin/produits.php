
<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config.php';
include 'includes/header.php';

$result = $conn->query("SELECT * FROM produits ORDER BY id DESC");
$produits = [];
while ($row = $result->fetch_assoc()) {
    $produits[] = $row;
}
?>
<h2>Liste des Produits</h2>
<a href="ajouter_produit.php">+ Ajouter un produit</a>
<table class="table-style">

<style>
.table-style {
    width: 100%;
    border-collapse: collapse;
}
.table-style th, .table-style td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}
.table-style th {
    background-color: #f2f2f2;
}
</style>

    <tr><th>ID</th><th>Nom</th><th>Prix</th><th>Catégorie</th><th>collection</th><th>Image</th><th>Actions</th></tr>
    <?php foreach ($produits as $p): ?>
    <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['nom']) ?></td>
        <td><?= number_format($p['prix'], 0, ',', ' ') ?> F CFA</td>
        <td><?= $p['categorie'] ?></td>
        <td><?= $p['collection'] ?></td>
        <td><img src="../images/<?= $p['image'] ?>" width="50"></td>
        <td>
            <a href="modifier_produit.php?id=<?= $p['id'] ?>">Modifier</a> |
            <a href="supprimer_produit.php?id=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
