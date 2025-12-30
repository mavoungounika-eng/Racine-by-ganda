<?php
include '../config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Collection Féminine - RACINE BY GANDA</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 40px;
        }
        h1 {
            text-align: center;
            color: #cc3366;
            margin-bottom: 30px;
        }
        .produits {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .produit {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 220px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .produit img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 6px;
        }
        .produit h2 {
            font-size: 18px;
            color: #333;
            margin: 10px 0 5px;
        }
        .produit p {
            font-size: 16px;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>Collection Féminine</h1>
    <div class="produits">
        <?php
        $sql = "SELECT * FROM produits WHERE collection = 'feminine'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='produit'>";
                echo "<img src='../images/" . htmlspecialchars($row['image']) . "' alt='Produit'>";
                echo "<h2>" . htmlspecialchars($row['nom']) . "</h2>";
                echo "<p>" . number_format($row['prix'], 0, ',', ' ') . " F CFA</p>";
                echo "</div>";
            }
        } else {
            echo "<p>Aucun produit disponible pour cette collection.</p>";
        }
        ?>
    </div>
</body>
</html>
