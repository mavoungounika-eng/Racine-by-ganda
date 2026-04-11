-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 15 juil. 2025 à 02:26
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `by_ganda`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` text,
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

DROP TABLE IF EXISTS `avis`;
CREATE TABLE IF NOT EXISTS `avis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `produit_id` int DEFAULT NULL,
  `note` int DEFAULT NULL,
  `commentaire` text,
  `date_avis` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `produit_id` (`produit_id`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `mode_paiement` enum('mobile_money','carte_visa','paypal','paiement_livraison') NOT NULL,
  `statut` enum('en_attente','en_cours','livrée','annulée') DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `adresse_livraison` text,
  `client_name` varchar(100) DEFAULT NULL,
  `client_lastname` varchar(100) DEFAULT NULL,
  `client_email` varchar(150) DEFAULT NULL,
  `client_phone` varchar(50) DEFAULT NULL,
  `client_address` text,
  `payment_method` varchar(50) NOT NULL,
  `subtotal` double DEFAULT NULL,
  `delivery` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `user_id`, `total`, `mode_paiement`, `statut`, `date_commande`, `adresse_livraison`, `client_name`, `client_lastname`, `client_email`, `client_phone`, `client_address`, `payment_method`, `subtotal`, `delivery`, `discount`, `image`) VALUES
(10, NULL, -500.00, 'mobile_money', 'en_attente', '2025-07-14 18:13:10', NULL, 'dercy ambon', NULL, 'BLACKDERCYPEFRA@GMAIL.COM', '065468769', 'mbinda', 'check_payment', 500, 2000, 3000, ''),
(32, NULL, 8789.00, 'mobile_money', 'en_attente', '2025-07-15 04:21:03', NULL, 'dercy ambon ambon', NULL, 'BLACKDERCYPEFRA@GMAIL.COM', '065468769', 'mbinda', 'check_payment', 6789, 2000, 0, '');

-- --------------------------------------------------------

--
-- Structure de la table `commande_details`
--

DROP TABLE IF EXISTS `commande_details`;
CREATE TABLE IF NOT EXISTS `commande_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `produit_id` int NOT NULL,
  `nom_produit` varchar(255) NOT NULL,
  `quantite` int NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `nom` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `commande_details`
--

INSERT INTO `commande_details` (`id`, `order_id`, `produit_id`, `nom_produit`, `quantite`, `prix`, `image`, `nom`) VALUES
(4, 10, 0, '', 1, 500.00, '../images/2024_07_31_18_08_IMG_2252.JPG', '0'),
(8, 32, 5, 'sac', 1, 6789.00, '2024_07_31_18_08_IMG_2252.JPG', '');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `mode` enum('mobile_money','carte_visa','paypal','paiement_livraison') DEFAULT NULL,
  `statut` enum('en_attente','reussi','échoué') DEFAULT NULL,
  `date_paiement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

DROP TABLE IF EXISTS `panier`;
CREATE TABLE IF NOT EXISTS `panier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `produit_id` int DEFAULT NULL,
  `quantite` int DEFAULT '1',
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `produit_id` (`produit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `prix` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `categorie_id` int DEFAULT NULL,
  `taille` varchar(50) DEFAULT NULL,
  `couleur` varchar(50) DEFAULT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `categorie` int NOT NULL,
  `collection` varchar(100) NOT NULL DEFAULT 'boutique',
  PRIMARY KEY (`id`),
  KEY `categorie_id` (`categorie_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `description`, `prix`, `stock`, `image`, `categorie_id`, `taille`, `couleur`, `date_ajout`, `categorie`, `collection`) VALUES
(5, 'sac', '', 6789.00, 0, '2024_07_31_18_08_IMG_2252.JPG', NULL, NULL, NULL, '2025-07-14 22:36:26', 0, 'feminine'),
(6, 'bissape', '', 5678.00, 0, '2024_09_06_19_55_IMG_3002.JPG', NULL, NULL, NULL, '2025-07-14 22:43:46', 0, 'masculine'),
(8, 'montre', '', 1500.00, 0, '2024_09_09_11_44_IMG_3106.JPG', NULL, NULL, NULL, '2025-07-14 22:45:31', 0, 'accessoires'),
(9, 'TISSU', '', 5000.00, 0, '2024_09_08_19_56_IMG_3171.JPG', NULL, NULL, NULL, '2025-07-14 22:46:11', 0, 'atelier'),
(10, 'vvc', '', 3543.00, 0, '2024_09_08_19_56_IMG_3170.JPG', NULL, NULL, NULL, '2025-07-14 22:46:56', 0, 'edition_limitee');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `sexe` enum('homme','femme') DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
