<?php
include '../config.php';

$sql = "SELECT * FROM commandes ORDER BY date_commande DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commandes - Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            padding: 30px;
        }
        .commande {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .commande h3 {
            margin-top: 0;
            color: #007BFF;
        }
        .produits {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .produit {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            width: 200px;
            background-color: #fafafa;
        }
        .produit img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <h1>Commandes Clients</h1>
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orderId = $row['id'];
            echo "<div class='commande'>";
            echo "<h3>Commande n°$orderId - " . date('d/m/Y H:i', strtotime($row['date_commande'])) . "</h3>";
            echo "<p><strong>Client :</strong> " . htmlspecialchars($row['client_name']) . "</p>";
            echo "<p><strong>Email :</strong> " . htmlspecialchars($row['client_email']) . "</p>";
            echo "<p><strong>Tél :</strong> " . htmlspecialchars($row['client_phone']) . "</p>";
            echo "<p><strong>Adresse :</strong> " . htmlspecialchars($row['client_address']) . "</p>";
            echo "<p><strong>Mode de paiement :</strong> " . htmlspecialchars($row['payment_method']) . "</p>";
            echo "<p><strong>Total :</strong> " . number_format($row['total'], 0, ',', ' ') . " F CFA</p>";

            $prodQuery = "SELECT nom_produit, quantite, image FROM commande_details WHERE order_id = $orderId";
            $prodResult = $conn->query($prodQuery);

            echo "<div class='produits'>";
            if ($prodResult && $prodResult->num_rows > 0) {
                while ($prod = $prodResult->fetch_assoc()) {
                    echo "<div class='produit'>";
                    echo '<img src="../images/' . htmlspecialchars($prod['image']) . '" alt="Produit">';
                    echo '<div><strong>' . htmlspecialchars($prod['nom_produit']) . '</strong></div>';
                    echo '<div>Quantité : ' . intval($prod['quantite']) . '</div>';
                    echo "</div>";
                }
            } else {
                echo "Aucun produit.";
            }
            echo "</div>";

            echo "<form method='POST' action='supprimer_commande.php' onsubmit='return confirm(\"Supprimer cette commande ?\");'>";
            echo "<input type='hidden' name='order_id' value='$orderId'>";
            echo "<button type='submit' class='delete-btn'>Supprimer</button>";
            echo "</form>";

            echo "</div>";
        }
    } else {
        echo "<p>Aucune commande trouvée.</p>";
    }
    ?>
</body>
</html>
