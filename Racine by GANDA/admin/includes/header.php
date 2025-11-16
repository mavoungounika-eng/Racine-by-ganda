<?php if (!isset($_SESSION['admin'])) { header('Location: ../login.php'); exit; } ?>
<nav>
    <a href="dashboard.php">🏠 Tableau de bord</a> |
    <a href="produits.php">🛍️ Produits</a> |
    <a href="commandes.php">📦 Commandes</a> |
    <a href="logout.php">🔓 Déconnexion</a>
    <a href="../../index.php">Acceuil</a>
</nav>
<hr>
