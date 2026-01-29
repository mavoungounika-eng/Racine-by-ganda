# 📘 RAPPORT TECHNIQUE ÉVOLUTIF - RACINE BACKEND

**Dernière mise à jour :** 23/11/2025
**Projet :** Racine Backend (E-commerce/ERP)
**Stack :** Laravel 12, MySQL, Blade, Tailwind CSS

---

## 1. 📊 RÉSUMÉ GÉNÉRAL DU PROJET

### Statut Actuel
Le projet est en phase de développement actif du Core Admin. L'architecture de base est en place (Laravel 12), avec une authentification administrateur sécurisée et une gestion complète des utilisateurs et des rôles (RBAC).

### Modules Existants
- **Authentification Admin** : Login, Logout, Middleware de protection (`admin`), Gestion de session.
- **Gestion des Utilisateurs** : CRUD complet, Recherche, Filtres, Validation, Soft Deletes (via status).
- **Gestion des Rôles** : CRUD complet, Relation avec Users, Protection contre suppression si utilisé.
- **Dashboard** : Vue d'accueil admin basique.

### Dernières Améliorations (v3.1)
Intégration profonde de la gestion des rôles dans le module utilisateurs. Les administrateurs peuvent désormais assigner des rôles via une interface conviviale, avec une logique de repli (fallback) automatique et une validation stricte.

---

## 2. 🕒 HISTORIQUE D'ÉVOLUTION (TIMELINE)

### 🟢 v3.5 - Module Paiement (Stripe)
**Date :** 23/11/2025
**Type :** Nouvelle Fonctionnalité Critique

**Description :**
Intégration complète de la passerelle de paiement Stripe pour valider les commandes.

**Fichiers Ajoutés/Modifiés :**
-   `config/services.php` (Config Stripe)
-   `database/migrations/2025_11_23_00000[6-7]_*.php`
-   `app/Models/Payment.php`, `Order.php`
-   `app/Services/Payments/StripePaymentService.php`
-   `app/Http/Controllers/Front/PaymentController.php`
-   `routes/web.php`
-   `bootstrap/app.php` (CSRF Exclusion)
-   `resources/views/checkout/success.blade.php`, `cancel.blade.php`

**Détails Techniques :**
-   **SDK :** `stripe/stripe-php` v19.0.
-   **Service :** `StripePaymentService` gère la création de session Checkout et la validation.
-   **Webhook :** Route `/webhooks/stripe` sécurisée par signature, met à jour le statut `payment_status` de la commande et crée l'enregistrement `Payment`.
-   **UX :** Redirection fluide vers Stripe et retour sur page de succès avec état du paiement.

---

### 🟢 v3.4 - Module Panier & Commandes
**Date :** 23/11/2025
**Type :** Nouvelle Fonctionnalité Majeure

**Description :**
Implémentation d'un système de panier hybride (Session/Base de données) et gestion complète du tunnel de commande.

**Fichiers Ajoutés/Modifiés :**
-   `database/migrations/2025_11_23_00000[2-5]_create_*.php`
-   `app/Models/Cart.php`, `CartItem.php`, `Order.php`, `OrderItem.php`
-   `app/Services/Cart/SessionCartService.php`
-   `app/Services/Cart/DatabaseCartService.php`
-   `app/Services/Cart/CartMergerService.php`
-   `app/Http/Controllers/Front/CartController.php`
-   `app/Http/Controllers/Front/OrderController.php`
-   `app/Http/Controllers/Admin/AdminOrderController.php`
-   `resources/views/cart/*.blade.php`
-   `resources/views/checkout/*.blade.php`
-   `resources/views/admin/orders/*.blade.php`

**Détails Techniques :**
-   **Architecture Hybride :** Utilisation de `SessionCartService` pour les invités et `DatabaseCartService` pour les utilisateurs connectés.
-   **Fusion :** `CartMergerService` fusionne le panier session vers la BDD lors de la connexion (à implémenter dans le LoginController si nécessaire, ou via middleware).
-   **Sécurité :** Validation des stocks et des prix côté serveur lors de la commande. Transaction DB pour garantir l'intégrité (Order + OrderItems + Stock Decrement).
-   **Admin :** Interface de gestion des statuts de commande (Pending, Paid, Shipped, etc.).

---

### 🟠 v3.3 - Module de Gestion des Produits
**Date :** 23/11/2025
**Type :** Nouvelle Fonctionnalité

**Description :**
Implémentation du CRUD complet pour les produits, avec gestion des images et relation avec les catégories.

**Fichiers Ajoutés/Modifiés :**
-   `database/migrations/2025_11_23_000001_create_products_table.php`
-   `app/Models/Product.php`
-   `app/Http/Controllers/Admin/AdminProductController.php`
-   `app/Http/Requests/StoreProductRequest.php`
-   `app/Http/Requests/UpdateProductRequest.php`
-   `resources/views/admin/products/*.blade.php`
-   `routes/web.php`
-   `resources/views/layouts/admin.blade.php`

**Détails Techniques :**
-   **Structure DB :** `id`, `category_id` (FK), `title`, `slug` (unique), `description`, `price`, `stock`, `is_active`, `main_image`.
-   **Logique Métier :**
    -   Upload d'images dans `storage/app/public/products`.
    -   Suppression automatique de l'image lors de la suppression du produit.
    -   Génération automatique du slug.
-   **UI :**
    -   Tableau avec miniatures d'images.
    -   Filtres par catégorie et statut.
    -   Formulaire avec upload de fichier.

---

### 🟣 v3.2 - Module de Gestion des Catégories
**Date :** 23/11/2025
**Type :** Nouvelle Fonctionnalité

**Description :**
Implémentation du CRUD complet pour les catégories de produits, avec gestion de la hiérarchie (parent/enfant).

**Fichiers Ajoutés/Modifiés :**
-   `database/migrations/2025_11_23_000000_create_categories_table.php`
-   `app/Models/Category.php`
-   `app/Http/Controllers/Admin/AdminCategoryController.php`
-   `app/Http/Requests/StoreCategoryRequest.php`
-   `app/Http/Requests/UpdateCategoryRequest.php`
-   `resources/views/admin/categories/*.blade.php`
-   `routes/web.php`
-   `resources/views/layouts/admin.blade.php`

**Détails Techniques :**
-   **Structure DB :** `id`, `name`, `slug` (unique), `description`, `is_active`, `parent_id` (FK self).
-   **Logique Métier :**
    -   Génération automatique du slug à partir du nom.
    -   Protection contre les boucles infinies (une catégorie ne peut être son propre parent).
    -   Protection contre la suppression si des sous-catégories existent.
-   **UI :**
    -   Tableau avec badges de statut.
    -   Affichage du parent.
    -   Compteur de sous-catégories.

---

### 🟢 v3.1 - Intégration des Rôles dans le Module Utilisateurs
**Date :** 23/11/2025
**Type :** Amélioration Logique & UI

**Description :**
L'objectif était de relier le CRUD Utilisateurs au CRUD Rôles. Auparavant, le `role_id` devait être saisi manuellement ou n'était pas géré. Désormais, c'est un choix dynamique.

**Fichiers Modifiés :**
- `app/Http/Controllers/Admin/AdminUserController.php`
- `app/Http/Requests/StoreAdminUserRequest.php`
- `app/Http/Requests/UpdateAdminUserRequest.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`

**Détails Techniques (Diff Summary) :**
1.  **Controller (`AdminUserController`)** :
    -   `index()` : Ajout de `User::with('role')` pour éviter le problème N+1. Ajout filtre `role_id`.
    -   `create()/edit()` : Injection de `$roles = Role::where('is_active', true)...`.
    -   `store()` : Logique de fallback : si `role_id` est vide, assignation automatique du rôle `client` (via slug).
2.  **Requests** :
    -   Remplacement de `integer|min:1` par `exists:roles,id` pour garantir l'intégrité référentielle.
3.  **Vues** :
    -   `index` : Badges de couleur selon le slug du rôle (`admin`=rouge, `client`=gris, etc.).
    -   `create/edit` : Remplacement `input[type=number]` par `select` dynamique.

**Problèmes Résolus :**
-   Risque d'erreur humaine lors de la saisie d'un ID de rôle.
-   Manque de visibilité sur le rôle d'un utilisateur dans la liste.
-   Absence de rôle par défaut lors de la création.

---

### 🔵 v3.0 - Module de Gestion des Rôles
**Date :** 23/11/2025 (Estimé)
**Type :** Nouvelle Fonctionnalité

**Description :**
Création de l'entité `Role` et de son interface de gestion.

**Composants :**
-   **Migration :** Table `roles` (`name`, `slug`, `description`, `is_active`).
-   **Modèle :** `Role` avec relation `hasMany(User::class)`.
-   **Controller :** `AdminRoleController` (Resource).
-   **Vues :** `admin/roles/*`.
-   **Seeder :** `RolesTableSeeder` (Admin, Client, Manager).

---

### 🔵 v2.0 - Module de Gestion des Utilisateurs
**Date :** Antérieur
**Type :** Nouvelle Fonctionnalité

**Description :**
Mise en place du CRUD utilisateurs standard.

**Composants :**
-   **Controller :** `AdminUserController`.
-   **Vues :** `admin/users/*` avec Tailwind CSS.
-   **Requests :** Validation stricte (email unique, password confirmed).
-   **Fonctionnalités :** Recherche par nom/email, filtres par statut.

---

### 🔵 v1.0 - Initialisation & Authentification
**Date :** Antérieur
**Type :** Setup Projet

**Description :**
Installation de Laravel 12 et sécurisation de l'accès admin.

**Composants :**
-   **Auth :** `AdminAuthController`.
-   **Middleware :** `AdminOnly` (vérifie `is_admin` ou `role_id`).
-   **Modèle User :** Ajout méthode `isAdmin()` et scope `admins()`.
-   **Routes :** Groupe `admin.*` avec préfixe `/admin`.

---

## 3. 🧩 DÉTAILS DES MODULES

### 🔐 Authentification & Sécurité
-   **Logique Hybride :** Le système supporte à la fois le flag booléen `is_admin` (Legacy) et le système de rôles complet (`role_id`).
-   **Middleware :** `App\Http\Middleware\AdminOnly` est le gardien de l'espace admin. Il vérifie `User::isAdmin()`.
-   **Compatibilité :** La méthode `isAdmin()` retourne `true` si :
    1.  `is_admin` == true
    2.  `role_id` == 1
    3.  `role->slug` est 'admin' ou 'super_admin'.

### 👥 Gestion des Utilisateurs
-   **Architecture :** MVC classique.
-   **Validation :** FormRequests séparées pour `Store` et `Update`.
-   **UX :** Feedback visuel (Toasts via session flash), Modales de confirmation pour suppression.

### 🎭 Gestion des Rôles
-   **Structure :** Table simple mais extensible.
-   **Sécurité :** Empêche la suppression d'un rôle s'il est assigné à des utilisateurs (`count() > 0`).

---

## 4. 🛣️ ROADMAP FUTURE

### 🚀 Prochaines Étapes (Court Terme)
- [ ] **Catalogue Produit (Priorité Haute)**
    - [ ] Migration `categories` (Nested sets ou Parent ID).
    - [ ] Migration `products` (SKU, Prix, Stock, Description).
    - [ ] Gestion des images (Media Library ou simple upload).
    - [ ] CRUD Catégories & Produits.

### 🔮 Moyen Terme
- [ ] **Gestion des Commandes**
    - [ ] Tables `orders`, `order_items`.
    - [ ] Statuts de commande (State Machine).
- [ ] **Clients**
    - [ ] Espace client (Front-end).
    - [ ] Profil et adresses.

### 🛠️ Améliorations Techniques Envisagées
- [ ] **Tests Automatisés :** Ajouter des tests Feature pour `AdminUserController` et `AdminRoleController`.
- [ ] **Composants Blade :** Extraire les éléments UI (Badges, Boutons, Inputs) vers `resources/views/components`.
