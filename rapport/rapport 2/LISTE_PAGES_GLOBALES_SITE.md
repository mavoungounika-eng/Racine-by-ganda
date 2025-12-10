# 📄 LISTE COMPLÈTE DES PAGES GLOBALES DU SITE

**Date :** 28 novembre 2025  
**Projet :** RACINE BY GANDA  
**Statut :** ✅ **COMPLET**

---

## 📊 RÉSUMÉ

**Total de pages :** 80+ pages  
**Organisées en :** 8 catégories principales

---

## 🌐 PAGES PUBLIQUES (Accessibles à tous)

### 🏠 Pages Principales

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Accueil** | `/` | `frontend.home` | `frontend/home.blade.php` | Page d'accueil avec hero, produits, collections |
| **Boutique** | `/boutique` | `frontend.shop` | `frontend/shop.blade.php` | Catalogue complet des produits avec filtres |
| **Showroom** | `/showroom` | `frontend.showroom` | `frontend/showroom.blade.php` | Présentation du showroom physique |
| **Atelier** | `/atelier` | `frontend.atelier` | `frontend/atelier.blade.php` | Présentation de l'atelier de création |
| **Créateurs** | `/createurs` | `frontend.creators` | `frontend/creators.blade.php` | Liste des créateurs partenaires |
| **Contact** | `/contact` | `frontend.contact` | `frontend/contact.blade.php` | Formulaire de contact |

### 🛍️ Pages E-commerce

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Produit** | `/produit/{id}` | `frontend.product` | `frontend/product.blade.php` | Fiche détaillée d'un produit |
| **Recherche** | `/search` | `frontend.search` | `frontend/search/results.blade.php` | Résultats de recherche produits |
| **Recherche API** | `/api/search/suggest` | `frontend.search.suggest` | API | Suggestions de recherche (AJAX) |
| **Panier** | `/cart` | `cart.index` | `cart/index.blade.php` | Panier d'achat |
| **Checkout** | `/checkout` | `checkout` | `frontend/checkout/index.blade.php` | Page de paiement |

### 📚 Pages Informatives

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **À Propos** | `/a-propos` | `frontend.about` | `frontend/about.blade.php` | Histoire de la marque, valeurs |
| **Aide** | `/aide` | `frontend.help` | `frontend/help.blade.php` | Centre d'aide et support |
| **Livraison** | `/livraison` | `frontend.shipping` | `frontend/shipping.blade.php` | Informations sur la livraison |
| **Retours & Échanges** | `/retours-echanges` | `frontend.returns` | `frontend/returns.blade.php` | Politique de retours |
| **CGV** | `/cgv` | `frontend.terms` | `frontend/terms.blade.php` | Conditions générales de vente |
| **Confidentialité** | `/confidentialite` | `frontend.privacy` | `frontend/privacy.blade.php` | Politique de confidentialité |

### 🎨 Pages Contenu CMS

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Événements** | `/evenements` | `frontend.events` | `frontend/events.blade.php` | Liste des événements |
| **Portfolio** | `/portfolio` | `frontend.portfolio` | `frontend/portfolio.blade.php` | Portfolio de créations |
| **Albums** | `/albums` | `frontend.albums` | `frontend/albums.blade.php` | Albums photos |
| **Amira Ganda (CEO)** | `/amira-ganda` | `frontend.ceo` | `frontend/ceo.blade.php` | Page dédiée à la CEO |
| **Charte Graphique** | `/charte-graphique` | `frontend.brand-guidelines` | `frontend/brand-guidelines.blade.php` | Guide de la charte graphique |

---

## 🔐 PAGES D'AUTHENTIFICATION

### 🔑 Connexion & Inscription

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Hub Auth** | `/auth` | `auth.hub` | `auth/hub.blade.php` | Page de choix (Boutique/Équipe) |
| **Connexion** | `/login` | `login` | `auth/login-neutral.blade.php` | Formulaire de connexion unifié |
| **Connexion (Féminin)** | `/login?style=female` | `login` | `auth/login-female.blade.php` | Style féminin |
| **Connexion (Masculin)** | `/login?style=male` | `login` | `auth/login-male.blade.php` | Style masculin |
| **Inscription** | `/register` | `register` | `auth/register.blade.php` | Formulaire d'inscription (Client/Créateur) |
| **Mot de passe oublié** | `/password/forgot` | `password.request` | - | Demande de réinitialisation |
| **Réinitialisation** | `/password/reset/{token}` | `password.reset` | - | Formulaire de réinitialisation |

### 🔒 2FA (Double Authentification)

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Challenge 2FA** | `/2fa/challenge` | `2fa.challenge` | `auth/2fa/challenge.blade.php` | Vérification 2FA à la connexion |
| **Setup 2FA** | `/2fa/setup` | `2fa.setup` | `auth/2fa/setup.blade.php` | Configuration 2FA |
| **Gestion 2FA** | `/2fa/manage` | `2fa.manage` | `auth/2fa/manage.blade.php` | Gestion du 2FA |
| **Codes de récupération** | `/2fa/recovery-codes` | - | `auth/2fa/recovery-codes.blade.php` | Afficher les codes de récupération |

---

## 👤 PAGES COMPTE UTILISATEUR (Authentifiées)

### 📊 Dashboards par Rôle

| Page | URL | Route | Vue | Rôle Requis |
|------|-----|-------|-----|-------------|
| **Dashboard Client** | `/compte` | `account.dashboard` | `account/dashboard.blade.php` | `client` |
| **Dashboard Créateur** | `/atelier-creator` | `creator.dashboard` | `creator/dashboard.blade.php` | `createur`, `creator` |
| **Dashboard Staff** | `/staff/dashboard` | `staff.dashboard` | `admin/dashboard.blade.php` | `staff` |
| **Dashboard Admin** | `/admin/dashboard` | `admin.dashboard` | `admin/dashboard.blade.php` | `admin`, `super_admin` |

### 👤 Profil Utilisateur

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Mon Profil** | `/profil` | `profile.index` | `profile/index.blade.php` | Informations personnelles |
| **Mes Commandes** | `/profil/commandes` | `profile.orders` | `profile/orders.blade.php` | Historique des commandes |
| **Mes Adresses** | `/profil/adresses` | `profile.addresses` | `profile/addresses.blade.php` | Gestion des adresses |
| **Fidélité** | `/profil/fidelite` | `profile.loyalty` | `profile/loyalty.blade.php` | Points de fidélité |

### ⚙️ Paramètres

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Apparence** | `/appearance/settings` | `appearance.settings` | `appearance/settings.blade.php` | Paramètres d'apparence |
| **Notifications** | `/notifications` | `notifications.index` | - | Liste des notifications |
| **Compteur Notifications** | `/notifications/count` | `notifications.count` | API | Nombre de notifications (AJAX) |

---

## 💳 PAGES PAIEMENT (Authentifiées)

### 💰 Paiements

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Paiement Commande** | `/orders/{order}/pay` | `payment.pay` | - | Initier le paiement |
| **Succès Paiement** | `/orders/{order}/payment/success` | `payment.success` | - | Confirmation paiement |
| **Annulation Paiement** | `/orders/{order}/payment/cancel` | `payment.cancel` | - | Annulation paiement |

### 💳 Paiement par Carte (Stripe)

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Paiement Carte** | `/checkout/card/pay` | `checkout.card.pay` | - | Traitement paiement carte |
| **Succès Carte** | `/checkout/card/{order}/success` | `checkout.card.success` | `frontend/checkout/card-success.blade.php` | Confirmation paiement carte |
| **Annulation Carte** | `/checkout/card/{order}/cancel` | `checkout.card.cancel` | `frontend/checkout/card-cancel.blade.php` | Annulation paiement carte |

### 📱 Paiement Mobile Money

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Formulaire Mobile Money** | `/checkout/mobile-money/{order}/form` | `checkout.mobile-money.form` | `frontend/checkout/mobile-money-form.blade.php` | Formulaire Mobile Money |
| **Traitement Mobile Money** | `/checkout/mobile-money/{order}/pay` | `checkout.mobile-money.pay` | - | Traitement paiement |
| **En Attente** | `/checkout/mobile-money/{order}/pending` | `checkout.mobile-money.pending` | `frontend/checkout/mobile-money-pending.blade.php` | Paiement en attente |
| **Vérification Statut** | `/checkout/mobile-money/{order}/status` | `checkout.mobile-money.status` | - | Vérifier le statut (AJAX) |
| **Succès Mobile Money** | `/checkout/mobile-money/{order}/success` | `checkout.mobile-money.success` | `frontend/checkout/mobile-money-success.blade.php` | Confirmation |
| **Annulation Mobile Money** | `/checkout/mobile-money/{order}/cancel` | `checkout.mobile-money.cancel` | `frontend/checkout/mobile-money-cancel.blade.php` | Annulation |

### ✅ Checkout

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Succès Commande** | `/checkout/success` | `checkout.success` | `frontend/checkout/success.blade.php` | Confirmation de commande |

---

## 🔧 PAGES ADMINISTRATION (Admin/Super Admin)

### 📊 Dashboard & Gestion

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Dashboard Admin** | `/admin/dashboard` | `admin.dashboard` | `admin/dashboard.blade.php` | Tableau de bord avec KPIs |
| **Login Admin** | `/admin/login` | `admin.login` | `admin/login.blade.php` | ⚠️ Désactivé (utiliser `/login`) |

### 👥 Gestion Utilisateurs

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Utilisateurs** | `/admin/users` | `admin.users.index` | `admin/users/index.blade.php` | Liste tous les utilisateurs |
| **Créer Utilisateur** | `/admin/users/create` | `admin.users.create` | `admin/users/create.blade.php` | Formulaire création |
| **Éditer Utilisateur** | `/admin/users/{user}/edit` | `admin.users.edit` | `admin/users/edit.blade.php` | Formulaire édition |
| **Détails Utilisateur** | `/admin/users/{user}` | `admin.users.show` | `admin/users/show.blade.php` | Détails utilisateur |

### 🎭 Gestion Rôles

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Rôles** | `/admin/roles` | `admin.roles.index` | `admin/roles/index.blade.php` | Liste des rôles |
| **Créer Rôle** | `/admin/roles/create` | `admin.roles.create` | `admin/roles/create.blade.php` | Formulaire création |
| **Éditer Rôle** | `/admin/roles/{role}/edit` | `admin.roles.edit` | `admin/roles/edit.blade.php` | Formulaire édition |

### 📦 Gestion Produits

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Produits** | `/admin/products` | `admin.products.index` | `admin/products/index.blade.php` | Liste tous les produits |
| **Créer Produit** | `/admin/products/create` | `admin.products.create` | `admin/products/create.blade.php` | Formulaire création |
| **Éditer Produit** | `/admin/products/{product}/edit` | `admin.products.edit` | `admin/products/edit.blade.php` | Formulaire édition |
| **Détails Produit** | `/admin/products/{product}` | `admin.products.show` | - | Détails produit |

### 📁 Gestion Catégories

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Catégories** | `/admin/categories` | `admin.categories.index` | `admin/categories/index.blade.php` | Liste toutes les catégories |
| **Créer Catégorie** | `/admin/categories/create` | `admin.categories.create` | `admin/categories/create.blade.php` | Formulaire création |
| **Éditer Catégorie** | `/admin/categories/{category}/edit` | `admin.categories.edit` | `admin/categories/edit.blade.php` | Formulaire édition |
| **Détails Catégorie** | `/admin/categories/{category}` | `admin.categories.show` | - | Détails catégorie |

### 📋 Gestion Commandes

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Commandes** | `/admin/orders` | `admin.orders.index` | `admin/orders/index.blade.php` | Liste toutes les commandes |
| **Détails Commande** | `/admin/orders/{order}` | `admin.orders.show` | `admin/orders/show.blade.php` | Détails commande |
| **QR Code Commande** | `/admin/orders/{order}/qrcode` | `admin.orders.qr` | `admin/orders/qrcode.blade.php` | QR Code de la commande |
| **Scanner QR Code** | `/admin/orders/scan` | `admin.orders.scan` | `admin/orders/scan.blade.php` | Scanner QR Code |

### ⚠️ Alertes de Stock

| Page | URL | Route | Vue | Description |
|------|-----|-------|-----|-------------|
| **Liste Alertes** | `/admin/stock-alerts` | `admin.stock-alerts.index` | `admin/stock-alerts/index.blade.php` | Liste des alertes de stock |

---

## 🔗 PAGES SYSTÈME & API

### 🌍 Utilitaires

| Page | URL | Route | Description |
|------|-----|-------|-------------|
| **Changement Langue** | `/language/{locale}` | `language.switch` | Changer la langue (fr/en) |

### 🔔 Webhooks (Système)

| Page | URL | Route | Description |
|------|-----|-------|-------------|
| **Webhook Stripe** | `/webhooks/stripe` | `payment.webhook` | Callback Stripe |
| **Webhook Carte** | `/payment/card/webhook` | `payment.card.webhook` | Callback paiement carte |
| **Webhook Mobile Money** | `/payment/mobile-money/{provider}/callback` | `payment.mobile-money.callback` | Callback Mobile Money |

---

## 📊 STATISTIQUES PAR CATÉGORIE

| Catégorie | Nombre de Pages |
|-----------|----------------|
| 🌐 Pages Publiques | 20 |
| 🔐 Authentification | 8 |
| 👤 Compte Utilisateur | 8 |
| 💳 Paiements | 12 |
| 🔧 Administration | 25+ |
| 🔗 Système & API | 4 |
| **TOTAL** | **80+** |

---

## 🎯 ACCÈS PAR RÔLE

### 👤 Visiteur (Non connecté)
- ✅ Toutes les pages publiques
- ✅ Pages d'authentification
- ❌ Pages compte utilisateur
- ❌ Pages administration

### 🛒 Client
- ✅ Toutes les pages publiques
- ✅ Dashboard client (`/compte`)
- ✅ Profil utilisateur
- ✅ Panier & Checkout
- ✅ Paiements
- ❌ Pages administration

### 🎨 Créateur
- ✅ Toutes les pages publiques
- ✅ Dashboard créateur (`/atelier-creator`)
- ✅ Profil utilisateur
- ✅ Panier & Checkout
- ✅ Paiements
- ❌ Pages administration

### 👔 Staff
- ✅ Toutes les pages publiques
- ✅ Dashboard staff (`/staff/dashboard`)
- ✅ Profil utilisateur
- ❌ Pages administration complètes

### 👑 Admin / Super Admin
- ✅ Toutes les pages publiques
- ✅ Dashboard admin (`/admin/dashboard`)
- ✅ Toutes les pages administration
- ✅ Gestion complète du site

---

## 📝 NOTES IMPORTANTES

### ⚠️ Routes Désactivées
- `/erp/login` - Désactivé (utiliser `/login`)
- `/admin/login` - Désactivé (utiliser `/login`)

### ✅ Routes Unifiées
- `/login` - Connexion unifiée pour tous les utilisateurs
- `/logout` - Déconnexion unifiée pour tous les utilisateurs

### 🔄 Redirections Automatiques
Après connexion, redirection automatique selon le rôle :
- `client` → `/compte`
- `createur` → `/atelier-creator`
- `staff` → `/staff/dashboard`
- `admin` / `super_admin` → `/admin/dashboard`

---

## 🚀 PAGES À DÉVELOPPER (Optionnel)

| Page | Description | Priorité |
|------|-------------|----------|
| Dashboard Staff dédié | Vue spécifique pour le staff | Moyenne |
| Page FAQ dynamique | FAQ gérée via CMS | Basse |
| Blog / Actualités | Section blog pour la marque | Basse |
| Page Partenaires | Liste des partenaires | Basse |

---

**Document créé le :** 28 novembre 2025  
**Dernière mise à jour :** 28 novembre 2025  
**Statut :** ✅ **COMPLET**

