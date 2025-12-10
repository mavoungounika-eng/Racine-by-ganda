<?php
// Informations de connexion à la base de données
$serveur = "localhost";       
$utilisateur = "root";        
$mot_de_passe = "";           
$base_de_donnees = "by_ganda";

// Connexion à la base de données
$conn = new mysqli($serveur, $utilisateur, $mot_de_passe, $base_de_donnees);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

// Pour s'assurer que l'encodage est UTF-8 (affichage des accents, etc.)
$conn->set_charset("utf8");

?>
