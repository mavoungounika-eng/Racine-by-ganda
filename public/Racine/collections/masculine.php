<?php
include '../config.php'; // Connexion à la base de données
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Collection Masculine - RACINE BY GANDA</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 30px;
        }
        h1 {
            text-align: center;
            color: #222;
            margin-bottom: 40px;
        }
        .produits {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .produit {
            width: 230px;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .produit:hover {
            transform: scale(1.03);
        }
        .produit img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .produit .info {
            padding: 15px;
        }
        .produit h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #333;
        }
        .produit p {
            margin: 0;
            font-weight: bold;
            color: #007BFF;
        }
    </style>
</head>
<body>

<h1>Collection Masculine</h1>

<div class="produits">
    <?php
    $sql = "SELECT * FROM produits WHERE collection = 'masculine' ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
    ?>
        <div class="produit">
            <img src="../images/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['nom']) ?>">
            <div class="info">
                <h3><?= htmlspecialchars($row['nom']) ?></h3>
                <p><?= number_format($row['prix'], 0, ',', ' ') ?> F CFA</p>
            </div>
        </div>
    <?php
        endwhile;
    else:
        echo "<p>Aucun produit dans cette collection.</p>";
    endif;
    ?>
</div>

</body>
</html>
