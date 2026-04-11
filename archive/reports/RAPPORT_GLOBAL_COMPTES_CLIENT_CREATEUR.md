# 📊 RAPPORT GLOBAL COMPTES CLIENT & CRÉATEUR
## RACINE BY GANDA - Vue Complète des Espaces Utilisateurs

**Date :** 2025  
**Projet :** RACINE BY GANDA  
**Objectif :** Documentation complète de tous les espaces, fonctionnalités, designs et processus

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'Ensemble](#vue-densemble)
2. [Compte Client - Dashboard](#compte-client-dashboard)
3. [Compte Créateur - Dashboard](#compte-créateur-dashboard)
4. [Pages de Profil Communes](#pages-de-profil-communes)
5. [Design & Apparence](#design--apparence)
6. [Fonctionnalités Disponibles](#fonctionnalités-disponibles)
7. [Processus & Flux](#processus--flux)
8. [Navigation & Accès](#navigation--accès)

---

## 🎯 VUE D'ENSEMBLE

### Types de Comptes

| Type | Route | URL | Layout | Description |
|------|-------|-----|--------|-------------|
| **Client** | `account.dashboard` | `/compte` | `layouts.frontend` | Espace client pour achats et suivi |
| **Créateur** | `creator.dashboard` | `/atelier-creator` | `layouts.creator-master` | Espace créateur pour gestion produits |

### Redirections Automatiques

Après connexion, redirection selon le rôle :
- `client` → `/compte`
- `createur` / `creator` → `/atelier-creator`
- `staff` → `/staff/dashboard`
- `admin` / `super_admin` → `/admin/dashboard`

---

## 👤 COMPTE CLIENT - DASHBOARD

### 📍 Accès
- **URL :** `/compte`
- **Route :** `account.dashboard`
- **Middleware :** `auth`
- **Layout :** `layouts.frontend`

### 🎨 Apparence & Design

#### Structure Visuelle
- **Layout :** Frontend standard avec navbar et footer
- **Style :** Design premium RACINE (dark, orange, yellow)
- **Responsive :** Oui, adapté mobile/tablette/desktop

#### État Actuel
⚠️ **EN COURS DE DÉVELOPPEMENT**

La page actuelle (`resources/views/account/dashboard.blade.php`) affiche :
- Message "Bienvenue dans votre espace client"
- Message "Cette page est en cours de développement"
- Carte avec :
  - Nom de l'utilisateur
  - Email
  - Badge du rôle
  - Bouton de déconnexion

### 🔄 Version Alternative (Module Frontend)

Il existe également une version alternative dans le module Frontend :
- **Fichier :** `modules/Frontend/Resources/views/dashboards/client.blade.php`
- **Contrôleur :** `modules/Frontend/Http/Controllers/DashboardController@client`

#### Fonctionnalités de cette Version

**1. Sidebar Navigation**
- Profil utilisateur (avatar, nom, email)
- Menu latéral avec :
  - 🏠 Tableau de bord
  - 🛍️ Mes commandes
  - ❤️ Mes favoris
  - 📍 Adresses
  - ⚙️ Mon profil
  - 🔔 Notifications
- Bouton déconnexion

**2. Statistiques**
- Total commandes
- Commandes en attente
- Commandes complétées
- Montant total dépensé

**3. Commandes Récentes**
- Liste des 5 dernières commandes
- Détails : numéro, date, statut, montant
- Lien vers détails

**4. Actions Rapides**
- Boutique (découvrir collections)
- Mon panier
- Mon profil

### 📊 Données Affichées

```php
$stats = [
    'my_orders_total' => Order::where('user_id', $user->id)->count(),
    'my_orders_pending' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
    'my_orders_completed' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
    'total_spent' => Order::where('user_id', $user->id)
        ->where('payment_status', 'paid')
        ->sum('total_amount'),
];

$my_orders = Order::where('user_id', $user->id)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();
```

---

## 🎨 COMPTE CRÉATEUR - DASHBOARD

### 📍 Accès
- **URL :** `/atelier-creator`
- **Route :** `creator.dashboard`
- **Middleware :** `auth`, `creator`
- **Layout :** `layouts.creator-master`

### 🎨 Apparence & Design

#### Layout Créateur (`layouts.creator-master`)

**Structure :**
- **Sidebar gauche** (rétractable) :
  - Logo "Mon Atelier" avec icône palette
  - Navigation principale
  - Informations utilisateur en bas
- **Header supérieur** :
  - Titre de page
  - Sous-titre
  - Actions rapides
  - Notifications
  - Menu utilisateur
- **Zone de contenu** principale

**Design :**
- **Background :** Dark (`#111111`, `#1f1412`)
- **Couleurs :** Orange (`#ED5F1E`), Yellow (`#FFB800`), Black (`#160D0C`)
- **Fonts :** Inter (sans-serif), Playfair Display (display)
- **Style :** Premium, moderne, glassmorphism

#### Sidebar Navigation

**Sections :**
1. **Tableau de bord** (actif par défaut)
2. **Créations :**
   - 📦 Mes Produits
   - ➕ Nouveau Produit
   - 🖼️ Galerie
3. **Ventes :**
   - 🛍️ Commandes
   - 📊 Statistiques
   - 💰 Revenus
4. **Compte :**
   - 👤 Mon Profil
   - ⚙️ Paramètres

### 📊 Dashboard Contenu

#### Statistiques (4 Cartes)

**1. Mes Produits**
- Nombre total : `$stats['products_count']`
- Produits actifs : `$stats['active_products_count']`
- Évolution : "+3 ce mois"
- Icône : 📦

**2. Ventes**
- Total : `$stats['total_sales']`
- Ventes mensuelles : `$stats['monthly_sales']`
- Évolution : "+12% ce mois"
- Icône : 🛍️

**3. Revenus**
- Montant : `$stats['total_sales']` (formaté)
- Évolution : "+18% ce mois"
- Icône : 💰

**4. En Attente**
- Commandes en attente : `$stats['pending_orders']`
- Icône : ⏰

#### Ventes Récentes

- Liste des 5 dernières ventes
- Informations :
  - Numéro de commande
  - Nom du client
  - Montant
  - Heure de la commande
- Lien "Voir tout"

#### Performance

**Indicateurs :**
- Taux de vente : 78% (barre de progression)
- Satisfaction client : 92% (barre verte)
- Stock disponible : 65% (barre bleue)

#### Actions Rapides

- **Nouveau Produit** (bouton accent)
- **Voir Statistiques** (bouton outline)
- **Paramètres** (bouton outline)

### 📊 Données Calculées

```php
$stats = [
    'products_count' => Product::where('user_id', $user->id)->count(),
    'active_products_count' => Product::where('user_id', $user->id)
        ->where('is_active', true)
        ->count(),
    'collections_count' => Collection::where('user_id', $user->id)->count(),
    'total_sales' => $this->calculateTotalSales($user->id),
    'monthly_sales' => $this->calculateMonthlySales($user->id),
    'pending_orders' => $this->getPendingOrdersCount($user->id),
];

$recentProducts = Product::where('user_id', $user->id)
    ->latest()
    ->take(5)
    ->get();

$topProducts = $this->getTopSellingProducts($user->id);
$salesData = $this->getSalesChartData($user->id); // 12 derniers mois
```

### 🔧 Méthodes de Calcul

**Total des Ventes :**
```php
OrderItem::whereHas('product', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})
->whereHas('order', function ($query) {
    $query->where('status', 'paid');
})
->sum(DB::raw('price * quantity'));
```

**Ventes Mensuelles :**
```php
OrderItem::whereHas('product', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})
->whereHas('order', function ($query) {
    $query->where('status', 'paid')
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year);
})
->sum(DB::raw('price * quantity'));
```

**Commandes en Attente :**
```php
OrderItem::whereHas('product', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})
->whereHas('order', function ($query) {
    $query->where('status', 'pending');
})
->distinct('order_id')
->count('order_id');
```

---

## 👤 PAGES DE PROFIL COMMUNES

### 📍 Accès
- **URL :** `/profil`
- **Route :** `profile.index`
- **Middleware :** `auth`
- **Layout :** `layouts.internal`

### 🎨 Apparence & Design

**Layout :** `layouts.internal`
- Design interne avec sidebar
- Couleurs RACINE (violet, gold, orange)
- Typographie : Playfair Display pour titres

### 📄 Pages Disponibles

#### 1. Mon Profil (`/profil`)

**Contenu :**

**Carte Profil (Gauche) :**
- Avatar circulaire avec initiale
- Nom complet
- Email
- Badge de rôle (coloré selon rôle) :
  - 👑 Super Admin (rouge)
  - ⚙️ Administrateur (gold)
  - 🛠️ Staff (bleu)
  - 🎨 Créateur (vert)
  - 👤 Client (violet)
- Informations :
  - 📱 Téléphone
  - 📅 Membre depuis (date)
  - 🕐 Dernière activité (diffForHumans)

**Formulaires (Droite) :**

**A. Informations Personnelles**
- Nom complet
- Adresse email
- Téléphone
- Bouton "Enregistrer les modifications"

**B. Sécurité**
- Mot de passe actuel
- Nouveau mot de passe
- Confirmation mot de passe
- Conseils de sécurité
- Bouton "Modifier le mot de passe"

#### 2. Mes Commandes (`/profil/commandes`)

**Contenu :**

**Tableau des Commandes :**
- Colonnes :
  - N° Commande
  - Date
  - Articles (nombre + premier produit)
  - Montant
  - Statut (badge coloré) :
    - En attente (warning)
    - Payée (info)
    - Expédiée (primary)
    - Livrée (success)
    - Annulée (danger)
  - Paiement (badge) :
    - Payé (success)
    - En attente (warning)
    - Échoué (danger)
  - Actions (bouton "Voir")

**Pagination :** 15 commandes par page

**État Vide :**
- Icône shopping bag
- Message "Aucune commande"
- Lien vers boutique

#### 3. Mes Adresses (`/profil/adresses`)

**Contenu :**

**Adresses Enregistrées :**
- Liste des adresses
- Chaque adresse affiche :
  - Badge "Par défaut" si applicable
  - Nom complet
  - Adresse complète
  - Téléphone
  - Bouton supprimer

**Formulaire Ajout :**
- Prénom *
- Nom *
- Téléphone
- Adresse ligne 1 *
- Adresse ligne 2
- Ville *
- Code postal
- Pays * (défaut : "Congo")
- Checkbox "Définir comme adresse par défaut"
- Bouton "Enregistrer l'adresse"

**Fonctionnalités :**
- Si `is_default = true`, toutes les autres deviennent `false`
- Suppression avec confirmation

#### 4. Points de Fidélité (`/profil/fidelite`)

**Contenu :**

**Carte Points (Gauche) :**
- Nombre de points disponibles (grand format)
- Badge niveau (Bronze / Silver / Gold)
- Total gagné
- Total dépensé

**Comment Gagner :**
- 1% du montant de chaque commande payée
- Points convertibles en réductions
- Niveaux : Bronze → Silver → Gold

**Historique (Droite) :**
- Tableau des transactions
- Colonnes :
  - Date
  - Description
  - Points (+ ou -)
  - Type (badge) :
    - Gagné (success)
    - Dépensé (warning)
    - Expiré (secondary)
- Pagination : 20 transactions par page

**État Vide :**
- Icône étoile
- Message "Aucun point"
- Message "Commencez à acheter pour gagner des points !"

### 🔧 Contrôleur Profil

**Fichier :** `app/Http/Controllers/ProfileController.php`

**Méthodes :**
- `index()` - Affiche le profil avec commandes et adresses
- `orders()` - Liste des commandes (pagination 15)
- `addresses()` - Liste des adresses
- `storeAddress()` - Créer une adresse
- `deleteAddress()` - Supprimer une adresse
- `update()` - Mettre à jour le profil
- `updatePassword()` - Changer le mot de passe
- `loyalty()` - Afficher les points de fidélité

---

## 🎨 DESIGN & APPARENCE

### 🎨 Layout Frontend (`layouts.frontend`)

**Utilisé par :** Dashboard client, pages publiques

**Caractéristiques :**
- **Header :** Navbar premium fixe avec :
  - Logo RACINE BY GANDA
  - Menu navigation (Accueil, Atelier, Boutique, Showroom)
  - Dropdowns (Boutique, Informations)
  - Icône panier avec badge
  - Bouton connexion
  - Menu mobile (burger)
- **Footer :** Premium avec :
  - Newsletter CTA (orange gradient)
  - 4 colonnes (Brand, Boutique, Informations, Contact)
  - Réseaux sociaux
  - Liens légaux
  - Méthodes de paiement
  - Copyright + crédit développeur

**Couleurs :**
- Background : `#1c1412`, `#261915`
- Accent : `#ED5F1E` (orange), `#FFB800` (yellow)
- Text : White, rgba(255,255,255,0.6-0.9)

**Fonts :**
- Aileron (body)
- Playfair Display (headings)

### 🎨 Layout Creator Master (`layouts.creator-master`)

**Utilisé par :** Dashboard créateur uniquement

**Caractéristiques :**
- **Sidebar :** Rétractable (64px ou 20px)
  - Background : `#1f1412`
  - Border : `rgba(255,255,255,0.1)`
  - Navigation avec icônes
  - Profil utilisateur en bas
- **Header :** Top bar fixe
  - Background : `#1f1412`
  - Titre + sous-titre
  - Actions rapides
  - Notifications
  - Menu utilisateur (dropdown)
- **Content :** Zone principale avec padding

**Couleurs :**
- Background : `#111111`, `#1f1412`
- Accent : `#ED5F1E`, `#FFB800`
- Cards : White avec ombres

**Fonts :**
- Inter (sans-serif)
- Playfair Display (display)

**Technologies :**
- Tailwind CSS (CDN)
- Alpine.js (interactivité)
- Font Awesome (icônes)

### 🎨 Layout Internal (`layouts.internal`)

**Utilisé par :** Pages de profil

**Caractéristiques :**
- Design interne avec sidebar
- Couleurs RACINE (violet, gold, orange)
- Typographie : Playfair Display

### 🎨 Système de Personnalisation

#### Module Appearance (`/appearance/settings`)

**Fichier :** `resources/views/appearance/settings.blade.php`

**Options Disponibles :**

**1. Mode d'Affichage**
- ☀️ Clair
- 🌙 Sombre
- ⏰ Auto (selon système)

**2. Palette d'Accent**
- 🟠 Orange (`#ED5F1E`)
- 🟡 Jaune (`#FFB800`)
- 🟨 Or (`#D4AF37`)
- 🔴 Rouge (`#DC2626`)

**3. Style Visuel**
- 💖 Femme
- 💼 Homme
- ⚪ Neutre

**4. Intensité des Animations**
- Aucune
- Douce
- Standard
- Luxe

**5. Niveau de Contraste**
- Normal
- Lumineux
- Sombre

**6. Filtre Golden Light**
- Boolean (on/off)

**Stockage :**
- Table : `user_settings`
- Modèle : `UserSetting`
- Relation : `User` → `HasOne` `UserSetting`

**API :**
- `GET /appearance/current` - Paramètres actuels
- `POST /appearance/update` - Mettre à jour
- `POST /appearance/update-single` - Mettre à jour une option
- `POST /appearance/preview` - Prévisualiser
- `POST /appearance/reset` - Réinitialiser

---

## ⚙️ FONCTIONNALITÉS DISPONIBLES

### 👤 Client

#### Dashboard
- ✅ Vue d'ensemble (en développement)
- ✅ Statistiques commandes
- ✅ Commandes récentes
- ✅ Actions rapides

#### Profil
- ✅ Informations personnelles
- ✅ Changement mot de passe
- ✅ Gestion adresses
- ✅ Historique commandes
- ✅ Points de fidélité

#### Autres
- ✅ Panier
- ✅ Checkout
- ✅ Favoris (mentionné dans sidebar)
- ✅ Notifications (mentionné dans sidebar)

### 🎨 Créateur

#### Dashboard
- ✅ Statistiques produits
- ✅ Statistiques ventes
- ✅ Revenus
- ✅ Commandes en attente
- ✅ Ventes récentes
- ✅ Performance (taux de vente, satisfaction, stock)
- ✅ Produits récents
- ✅ Top produits vendus
- ✅ Graphiques ventes (12 mois)

#### Navigation
- ✅ Tableau de bord
- ✅ Mes Produits
- ✅ Nouveau Produit
- ✅ Galerie
- ✅ Commandes
- ✅ Statistiques
- ✅ Revenus
- ✅ Mon Profil
- ✅ Paramètres

#### Profil
- ✅ Même accès que client (pages communes)

### 🔔 Notifications

**Routes :**
- `GET /notifications` - Liste
- `GET /notifications/count` - Compteur non lues
- `POST /notifications/{id}/read` - Marquer lue
- `POST /notifications/read-all` - Tout marquer lu
- `DELETE /notifications/{id}` - Supprimer
- `DELETE /notifications/clear/read` - Supprimer lues

**Widget :** `components/notification-widget.blade.php`

---

## 🔄 PROCESSUS & FLUX

### 🔐 Connexion

**1. Hub d'Authentification (`/auth`)**
- Choix : Espace Boutique / Espace Équipe
- Design premium (dark, glassmorphism, gold/bronze/orange)

**2. Page de Login (`/login`)**
- Contexte : `boutique` ou `equipe`
- Badge contextuel
- Titre et sous-titre adaptés
- Bouton retour vers `/auth`
- Bouton "Continuer avec Google"

**3. Redirection Post-Login**
- Client → `/compte`
- Créateur → `/atelier-creator`
- Staff → `/staff/dashboard`
- Admin → `/admin/dashboard`

### 🛒 Processus d'Achat (Client)

**1. Navigation Boutique**
- `/boutique` - Catalogue produits
- `/produit/{id}` - Fiche produit
- `/cart` - Panier
- `/checkout` - Paiement

**2. Après Commande**
- Confirmation
- Email de confirmation
- Points de fidélité ajoutés (1% du montant)
- Notification
- Commande visible dans `/profil/commandes`

### 🎨 Processus Créateur

**1. Gestion Produits**
- Création produit
- Upload images
- Gestion stock
- Activation/désactivation

**2. Suivi Ventes**
- Dashboard avec statistiques
- Liste commandes
- Calcul revenus
- Graphiques performance

**3. Revenus**
- Calcul automatique depuis `OrderItem`
- Filtrage par statut paiement
- Agrégation mensuelle

---

## 🧭 NAVIGATION & ACCÈS

### 📍 Routes Principales

#### Client
```
/compte                    → Dashboard client
/profil                    → Mon profil
/profil/commandes          → Mes commandes
/profil/adresses           → Mes adresses
/profil/fidelite           → Points de fidélité
/appearance/settings       → Réglages apparence
/notifications             → Notifications
```

#### Créateur
```
/atelier-creator           → Dashboard créateur
/profil                    → Mon profil (commun)
/profil/commandes          → Mes commandes (commun)
/profil/adresses           → Mes adresses (commun)
/profil/fidelite           → Points de fidélité (commun)
/appearance/settings       → Réglages apparence (commun)
/notifications             → Notifications (commun)
```

### 🔗 Liens Rapides

**Depuis Dashboard Client :**
- Boutique
- Mon panier
- Mon profil

**Depuis Dashboard Créateur :**
- Nouveau Produit
- Voir Statistiques
- Paramètres

### 📱 Responsive

**Tous les layouts sont responsive :**
- Mobile : Menu burger, colonnes empilées
- Tablette : Adaptation grille
- Desktop : Layout complet avec sidebar

---

## 📊 RÉSUMÉ DES DONNÉES

### 👤 Client

**Statistiques :**
- Total commandes
- Commandes en attente
- Commandes complétées
- Montant total dépensé

**Données :**
- 5 dernières commandes
- Adresses enregistrées
- Points de fidélité
- Transactions fidélité

### 🎨 Créateur

**Statistiques :**
- Nombre produits
- Produits actifs
- Collections
- Total ventes
- Ventes mensuelles
- Commandes en attente

**Données :**
- 5 produits récents
- Top produits vendus
- Graphique ventes (12 mois)
- Performance (taux, satisfaction, stock)

---

## 🎯 POINTS IMPORTANTS

### ⚠️ État Actuel

**Dashboard Client :**
- ⚠️ En cours de développement
- Version basique affichée
- Version complète disponible dans module Frontend

**Dashboard Créateur :**
- ✅ Complètement fonctionnel
- Statistiques calculées en temps réel
- Interface premium et moderne

### ✅ Fonctionnalités Complètes

**Profil :**
- ✅ Toutes les pages fonctionnelles
- ✅ Gestion complète des données
- ✅ Validation et sécurité

**Apparence :**
- ✅ Système de personnalisation complet
- ✅ Stockage des préférences
- ✅ API pour intégration

### 🔄 Améliorations Futures

**Dashboard Client :**
- Implémenter la version complète du module Frontend
- Ajouter graphiques et visualisations
- Intégrer favoris et wishlist

**Dashboard Créateur :**
- Ajouter gestion collections
- Améliorer graphiques ventes
- Ajouter export données

---

**Fin du Rapport Global**

*Ce rapport documente tous les aspects des comptes client et créateur, incluant l'apparence, le design, les fonctionnalités, les processus et la navigation.*


