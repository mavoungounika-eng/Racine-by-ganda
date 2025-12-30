<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panneau Admin - RACINE BY GANDA</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .admin-container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            text-align: center;
        }

        h1 {
            margin-bottom: 30px;
            font-size: 28px;
            color: #333;
            letter-spacing: 1px;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin: 20px 0;
        }

        a {
            text-decoration: none;
            color: #fff;
            background-color: #007BFF;
            padding: 14px 24px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        a:hover {
            background-color: #0056b3;
        }

        a::before {
            content: '';
            margin-right: 8px;
        }

        li:first-child a::before {
            content: "🛍️";
        }

        li:nth-child(2) a::before {
            content: "📦";
        }

        li:nth-child(3) a::before {
            content: "🔐";
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Panneau Admin - <span style="color:#007BFF;">RACINE BY GANDA</span></h1>
        <ul>
            <li><a href="produits.php">Gérer les Produits</a></li>
            <li><a href="commandes.php">Voir les Commandes</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </div>
</body>
</html>
