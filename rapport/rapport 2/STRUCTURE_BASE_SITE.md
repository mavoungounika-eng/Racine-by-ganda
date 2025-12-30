   # 🏗️ STRUCTURE DE BASE DU SITE - RACINE BY GANDA

   **Date :** 28 novembre 2025  
   **Framework :** Laravel 12  
   **Type :** E-commerce + ERP intégré

   ---

   ## 📊 VUE D'ENSEMBLE

   **RACINE-BACKEND** est une plateforme e-commerce complète avec système ERP intégré, développée pour gérer les opérations d'une entreprise de mode avec **trois canaux de vente** :
   - 🛒 **Boutique en ligne** (E-commerce)
   - 🏪 **Showroom physique** (Scan QR Code)
   - 🎨 **Atelier de création** (Gestion créateurs)

   ---

   ## 🎯 ARCHITECTURE GLOBALE

   ### Stack Technique
   - **Backend :** Laravel 12 (PHP 8.2+)
   - **Base de données :** SQLite (dev) / MySQL/PostgreSQL (prod)
   - **Frontend :** Blade Templates + Tailwind CSS 4.0 + Bootstrap
   - **Build :** Vite 7.0
   - **Paiements :** Stripe (CB) + Infrastructure Mobile Money
   - **Sessions :** Database-driven
   - **2FA :** Google2FA + QR Code

   ### Architecture Modulaire
   Le projet utilise une **architecture modulaire intégrée** :
   - **Code principal** dans `app/` (Admin, Frontend, Auth)
   - **Modules métier** dans `modules/` (ERP, CRM, Analytics, etc.)
   - **Tout fonctionne dans UNE SEULE application Laravel**

   ---

   ## 📁 STRUCTURE DES DOSSIERS PRINCIPAUX

   ```
   racine-backend/
   ├── app/                          # Code principal de l'application
   │   ├── Console/Commands/         # Commandes Artisan personnalisées
   │   ├── Exports/                  # Export de données (Excel, CSV)
   │   ├── Http/
   │   │   ├── Controllers/          # 29 contrôleurs organisés par domaine
   │   │   ├── Middleware/           # 10 middlewares (auth, security, etc.)
   │   │   └── Requests/             # 10 classes de validation
   │   ├── Mail/                     # 4 classes d'emails (notifications)
   │   ├── Models/                   # 21 modèles Eloquent
   │   ├── Observers/                # 2 observers (Order, Product)
   │   ├── Policies/                 # 5 policies (autorisations)
   │   ├── Providers/                # 3 providers (App, Auth, Modules)
   │   └── Services/                  # 10 services métier
   │
   ├── modules/                      # Modules métier (architecture modulaire)
   │   ├── Accounting/               # Module Comptabilité
   │   ├── Analytics/                # Module Analytics
   │   ├── Assistant/                # Module Assistant IA
   │   ├── Atelier/                  # Module Atelier Créateurs
   │   ├── Auth/                     # Module Authentification (complément)
   │   ├── Boutique/                 # Module Boutique E-commerce
   │   ├── Brand/                    # Module Marque
   │   ├── CMS/                      # Module CMS
   │   ├── Core/                     # Module Core (fonctionnalités de base)
   │   ├── CRM/                      # Module CRM
   │   ├── ERP/                      # Module ERP (Stocks, Fournisseurs)
   │   ├── Frontend/                 # Module Frontend
   │   ├── HR/                       # Module Ressources Humaines
   │   ├── Reporting/                # Module Reporting
   │   ├── Showroom/                 # Module Showroom
   │   └── Social/                   # Module Social
   │
   ├── resources/
   │   ├── css/                      # Styles CSS (app.css)
   │   ├── js/                       # JavaScript (app.js)
   │   ├── lang/                     # Fichiers de traduction
   │   └── views/                    # 100+ vues Blade organisées
   │       ├── admin/                # Vues Admin (dashboard, users, products, etc.)
   │       ├── auth/                 # Vues Authentification
   │       ├── frontend/             # Vues Frontend (boutique, produit, etc.)
   │       ├── layouts/               # Layouts principaux
   │       └── components/            # Composants Blade réutilisables
   │
   ├── routes/
   │   ├── web.php                   # Routes web principales
   │   └── console.php                # Routes console
   │
   ├── database/
   │   ├── migrations/               # 33 migrations
   │   ├── seeders/                  # 3 seeders
   │   └── factories/                # Factories pour tests
   │
   ├── public/                       # Fichiers publics (assets compilés)
   ├── config/                       # Fichiers de configuration
   └── storage/                      # Fichiers de stockage (logs, cache, etc.)
   ```

   ---

   ## 🎮 CONTRÔLEURS (29 fichiers)

   ### Admin (8 contrôleurs)
   - `AdminAuthController` - Authentification admin
   - `AdminDashboardController` - Dashboard admin (statistiques, graphiques)
   - `AdminUserController` - Gestion des utilisateurs
   - `AdminRoleController` - Gestion des rôles
   - `AdminProductController` - Gestion des produits
   - `AdminCategoryController` - Gestion des catégories
   - `AdminOrderController` - Gestion des commandes (avec QR Code)
   - `AdminStockAlertController` - Gestion des alertes de stock

   ### Auth (4 contrôleurs)
   - `AuthHubController` - Hub central de choix d'authentification
   - `PublicAuthController` - Authentification publique (Client & Créateur)
   - `ErpAuthController` - Authentification ERP (Admin & Staff)
   - `TwoFactorController` - Gestion 2FA (double authentification)

   ### Front (8 contrôleurs)
   - `FrontendController` - Pages frontend (home, shop, product, etc.)
   - `CartController` - Gestion du panier
   - `OrderController` - Gestion des commandes
   - `PaymentController` - Paiements génériques
   - `CardPaymentController` - Paiements par carte (Stripe)
   - `MobileMoneyPaymentController` - Paiements Mobile Money
   - `ReviewController` - Gestion des avis produits
   - `SearchController` - Recherche de produits

   ### Creator (2 contrôleurs)
   - `CreatorController` - Contrôleur créateur
   - `CreatorDashboardController` - Dashboard créateur

   ### Autres (7 contrôleurs)
   - `ProfileController` - Profil utilisateur
   - `AppearanceController` - Paramètres d'apparence
   - `NotificationController` - Gestion des notifications
   - `LanguageController` - Changement de langue
   - `Controller` - Contrôleur de base

   ---

   ## 📦 MODÈLES (21 fichiers)

   ### E-commerce
   - `Product` - Produits
   - `Category` - Catégories
   - `Order` - Commandes
   - `OrderItem` - Lignes de commande
   - `Cart` - Panier
   - `CartItem` - Articles du panier
   - `Review` - Avis produits
   - `Collection` - Collections de produits

   ### Utilisateurs & Authentification
   - `User` - Utilisateurs
   - `Role` - Rôles
   - `TwoFactorAuth` - Authentification 2FA
   - `TwoFactorVerification` - Vérifications 2FA
   - `LoginAttempt` - Tentatives de connexion
   - `UserSetting` - Paramètres utilisateur

   ### Paiements & Fidélité
   - `Payment` - Paiements
   - `LoyaltyPoint` - Points de fidélité
   - `LoyaltyTransaction` - Transactions fidélité

   ### Autres
   - `Address` - Adresses
   - `CreatorProfile` - Profils créateurs
   - `Notification` - Notifications
   - `StockAlert` - Alertes de stock

   ---

   ## 🛣️ ROUTES PRINCIPALES

   ### Authentification
   ```
   /auth                    → Hub de choix (Public/ERP)
   /login                   → Connexion publique (Client/Créateur)
   /register                → Inscription publique
   /erp/login               → Connexion ERP (Admin/Staff)
   /admin/login             → Connexion Admin
   /2fa/*                   → Routes 2FA
   ```

   ### Frontend (E-commerce)
   ```
   /                        → Page d'accueil
   /boutique                → Catalogue produits
   /produit/{id}            → Fiche produit
   /showroom                → Showroom
   /atelier                 → Atelier
   /createurs               → Liste créateurs
   /search                  → Recherche produits
   ```

   ### Panier & Checkout
   ```
   /cart                    → Panier
   /checkout                → Page de paiement
   /checkout/card/*         → Paiement par carte
   /checkout/mobile-money/* → Paiement Mobile Money
   ```

   ### Compte Utilisateur
   ```
   /compte                  → Dashboard client
   /atelier-creator         → Dashboard créateur
   /profil                  → Profil utilisateur
   /profil/commandes        → Commandes utilisateur
   /profil/adresses         → Adresses utilisateur
   /profil/fidelite         → Points de fidélité
   ```

   ### Admin
   ```
   /admin/dashboard         → Dashboard admin
   /admin/users             → Gestion utilisateurs
   /admin/products          → Gestion produits
   /admin/categories        → Gestion catégories
   /admin/orders            → Gestion commandes
   /admin/roles             → Gestion rôles
   /admin/stock-alerts      → Alertes de stock
   ```

   ### Pages Informatives
   ```
   /a-propos                → À propos
   /contact                → Contact
   /aide                    → Aide
   /livraison               → Livraison
   /retours-echanges       → Retours & Échanges
   /cgv                     → Conditions générales
   /confidentialite         → Confidentialité
   ```

   ---

   ## 🎨 VUES (100+ fichiers Blade)

   ### Layouts Principaux
   - `layouts/admin-master.blade.php` - Layout Admin (sidebar, header)
   - `layouts/frontend.blade.php` - Layout Frontend (navbar, footer)
   - `layouts/creator-master.blade.php` - Layout Créateur
   - `layouts/auth.blade.php` - Layout Authentification
   - `layouts/master.blade.php` - Layout de base

   ### Vues Admin (19 fichiers)
   - `admin/dashboard.blade.php` - Dashboard avec KPIs et graphiques
   - `admin/users/*` - Gestion utilisateurs (index, create, edit, show)
   - `admin/products/*` - Gestion produits
   - `admin/categories/*` - Gestion catégories
   - `admin/orders/*` - Gestion commandes (avec QR Code)
   - `admin/roles/*` - Gestion rôles
   - `admin/stock-alerts/*` - Alertes de stock

   ### Vues Frontend (13 fichiers)
   - `frontend/home.blade.php` - Page d'accueil
   - `frontend/shop.blade.php` - Catalogue produits
   - `frontend/product.blade.php` - Fiche produit
   - `frontend/showroom.blade.php` - Showroom
   - `frontend/atelier.blade.php` - Atelier
   - `frontend/creators.blade.php` - Liste créateurs
   - `frontend/about.blade.php` - À propos
   - `frontend/contact.blade.php` - Contact
   - `frontend/checkout/*` - Pages de paiement

   ### Vues Auth (7 fichiers)
   - `auth/hub.blade.php` - Hub de choix
   - `auth/login-neutral.blade.php` - Connexion (style neutre)
   - `auth/login-female.blade.php` - Connexion (style féminin)
   - `auth/login-male.blade.php` - Connexion (style masculin)
   - `auth/register.blade.php` - Inscription
   - `auth/erp-login.blade.php` - Connexion ERP
   - `auth/2fa/*` - Pages 2FA

   ### Composants Réutilisables (17 fichiers)
   - `components/card.blade.php` - Carte
   - `components/badge.blade.php` - Badge
   - `components/button.blade.php` - Bouton
   - `components/input.blade.php` - Input
   - `components/modal.blade.php` - Modal
   - `components/kpi-card.blade.php` - Carte KPI
   - `components/data-table.blade.php` - Tableau de données
   - Et plus...

   ---

   ## 🔧 SERVICES (10 services)

   ### Panier
   - `CartMergerService` - Fusion des paniers (session + DB)
   - `DatabaseCartService` - Panier en base de données
   - `SessionCartService` - Panier en session

   ### Paiements
   - `StripePaymentService` - Intégration Stripe
   - `CardPaymentService` - Paiements par carte
   - `MobileMoneyPaymentService` - Paiements Mobile Money

   ### Autres
   - `TwoFactorService` - Service 2FA
   - `NotificationService` - Service de notifications
   - `LoyaltyService` - Service de fidélité
   - `ProductSearchService` - Service de recherche produits

   ---

   ## 🛡️ MIDDLEWARES (10 middlewares)

   - `AdminOnly` - Accès admin uniquement
   - `CheckRole` - Vérification de rôle
   - `CheckPermission` - Vérification de permission
   - `CreatorMiddleware` - Accès créateur
   - `TwoFactorMiddleware` - Vérification 2FA
   - `TwoFactorPendingMiddleware` - 2FA en attente
   - `TwoFactorVerifiedMiddleware` - 2FA vérifié
   - `SecurityHeaders` - En-têtes de sécurité
   - `SetLocale` - Définition de la langue
   - `RedirectIfAuthenticated` - Redirection si connecté

   ---

   ## 🗄️ BASE DE DONNÉES

   ### Tables Principales
   - `users` - Utilisateurs
   - `roles` - Rôles
   - `products` - Produits
   - `categories` - Catégories
   - `orders` - Commandes
   - `order_items` - Lignes de commande
   - `payments` - Paiements
   - `carts` - Paniers
   - `cart_items` - Articles panier
   - `reviews` - Avis
   - `addresses` - Adresses
   - `loyalty_points` - Points de fidélité
   - `notifications` - Notifications
   - `two_factor_auths` - Authentifications 2FA
   - `stock_alerts` - Alertes de stock

   ### Relations Principales
   - `User` → `Role` (belongsTo)
   - `Order` → `User` (belongsTo)
   - `Order` → `OrderItem[]` (hasMany)
   - `Product` → `Category` (belongsTo)
   - `Product` → `Review[]` (hasMany)
   - `Cart` → `User` (belongsTo)
   - `Cart` → `CartItem[]` (hasMany)

   ---

   ## 🔐 SYSTÈME D'AUTHENTIFICATION

   ### 3 Canaux d'Authentification

   1. **Authentification Publique** (`/login`, `/register`)
      - Pour : Clients et Créateurs
      - Contrôleur : `PublicAuthController`
      - Redirections :
      - Client → `/compte`
      - Créateur → `/atelier-creator`

   2. **Authentification ERP** (`/erp/login`)
      - Pour : Admin et Staff
      - Contrôleur : `ErpAuthController`
      - Redirection : `/admin/dashboard`

   3. **Authentification Admin** (`/admin/login`)
      - Pour : Administrateurs uniquement
      - Contrôleur : `AdminAuthController`
      - Redirection : `/admin/dashboard`

   ### 2FA (Double Authentification)
   - Activation optionnelle pour tous les utilisateurs
   - QR Code pour configuration
   - Codes de récupération
   - Routes : `/2fa/*`

   ### Rôles Disponibles
   - `super_admin` - Super Administrateur
   - `admin` - Administrateur
   - `staff` - Personnel
   - `createur` - Créateur
   - `client` - Client

   ---

   ## 📦 MODULES MÉTIER (14 modules)

   ### Modules Actifs
   1. **Core** - Fonctionnalités de base
   2. **Frontend** - Interface publique
   3. **Auth** - Authentification (complément)
   4. **Boutique** - E-commerce
   5. **Showroom** - Showroom physique
   6. **Atelier** - Atelier créateurs
   7. **ERP** - ERP (Stocks, Fournisseurs)
   8. **CRM** - CRM (Contacts, Opportunités)
   9. **Analytics** - Analytics
   10. **CMS** - CMS
   11. **Brand** - Marque
   12. **HR** - Ressources Humaines
   13. **Accounting** - Comptabilité
   14. **Reporting** - Reporting
   15. **Social** - Social
   16. **Assistant** - Assistant IA

   ### Chargement des Modules
   Les modules sont chargés automatiquement via `ModulesServiceProvider` :
   - Routes : `modules/[Module]/routes/web.php`
   - Vues : `modules/[Module]/Resources/views/`
   - Migrations : `modules/[Module]/database/migrations/`

   ---

   ## 🎨 SYSTÈME DE DESIGN

   ### Charte Graphique RACINE BY GANDA
   - **Fond clair :** `#F5F5F5`
   - **Texte foncé :** `#111111`
   - **Couleur primaire :** Marron doré `#C19A6B`
   - **Boutons premium :** Noir + Doré

   ### Framework CSS
   - **Tailwind CSS 4.0** - Utilitaire CSS
   - **Bootstrap** - Composants (complément)
   - **Vite** - Build des assets

   ### Composants Blade
   17 composants réutilisables dans `resources/views/components/`

   ---

   ## 🔄 FLUX PRINCIPAUX

   ### Flux E-commerce
   1. Visiteur → `/boutique` → Sélection produit
   2. Ajout au panier → `/cart`
   3. Checkout → `/checkout`
   4. Paiement (Carte ou Mobile Money)
   5. Confirmation → `/checkout/success`

   ### Flux Authentification
   1. Visiteur → `/auth` (Hub)
   2. Choix : Public ou ERP
   3. Connexion/Inscription
   4. 2FA (si activé)
   5. Redirection selon rôle

   ### Flux Admin
   1. Admin → `/admin/login`
   2. Dashboard → `/admin/dashboard`
   3. Gestion (Users, Products, Orders, etc.)

   ---

   ## 📊 STATISTIQUES DU PROJET

   - **Contrôleurs :** 29 fichiers
   - **Modèles :** 21 fichiers
   - **Vues :** 100+ fichiers Blade
   - **Middlewares :** 10 fichiers
   - **Services :** 10 fichiers
   - **Policies :** 5 fichiers
   - **Migrations :** 33 fichiers
   - **Modules :** 14 modules
   - **Routes :** 100+ routes

   ---

   ## 🚀 POINTS D'ENTRÉE PRINCIPAUX

   ### Pour les Visiteurs
   - **URL :** `http://localhost:8000/`
   - **Page d'accueil** avec catalogue produits

   ### Pour les Clients
   - **Inscription :** `http://localhost:8000/register`
   - **Connexion :** `http://localhost:8000/login`
   - **Dashboard :** `http://localhost:8000/compte`

   ### Pour les Créateurs
   - **Inscription :** `http://localhost:8000/register` (choix "Créateur")
   - **Connexion :** `http://localhost:8000/login`
   - **Dashboard :** `http://localhost:8000/atelier-creator`

   ### Pour les Admins
   - **Connexion Admin :** `http://localhost:8000/admin/login`
   - **Connexion ERP :** `http://localhost:8000/erp/login`
   - **Dashboard :** `http://localhost:8000/admin/dashboard`

   ---

   ## 📝 NOTES IMPORTANTES

   ### Architecture Modulaire
   - Le projet utilise une **architecture modulaire intégrée**
   - Tout fonctionne dans **UNE SEULE application Laravel**
   - Les modules dans `modules/` sont des extensions du code principal

   ### Séparation Frontend/Backend
   - **Frontend** : Code dans `app/Http/Controllers/Front/`
   - **Backend** : Code dans `app/Http/Controllers/Admin/`
   - **Même base de données**, **même application**

   ### Authentification Multi-canaux
   - 3 systèmes d'authentification distincts
   - Même table `users`, mais redirections différentes
   - 2FA optionnel pour tous

   ### Paiements
   - **Stripe** pour cartes bancaires
   - **Mobile Money** pour paiements mobiles
   - Webhooks pour callbacks

   ---

   **Document créé le :** 28 novembre 2025  
   **Dernière mise à jour :** 28 novembre 2025

