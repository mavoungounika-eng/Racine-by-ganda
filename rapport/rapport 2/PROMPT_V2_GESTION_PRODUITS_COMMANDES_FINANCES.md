# 🧠 PROMPT MASTER V2 — GESTION PRODUITS / COMMANDES / FINANCES CRÉATEUR

**Projet :** RACINE BY GANDA — Espace Créateur  
**Version :** v2.0  
**Contexte :** Le module Créateur/Vendeur v1 est déjà en place (auth, rôles, dashboard, statuts)  
**Objectif V2 :** Donner au créateur un **vrai mini back-office** : produits, commandes, finances

---

## 1️⃣ CONTEXTE À RESPECTER

* Framework : **Laravel 12 + Blade + Tailwind** (comme le reste du projet)

* Le module créateur v1 existe déjà :
  * Routes `/createur/*`
  * Middlewares : `auth`, `role.creator`, `creator.active`
  * `CreatorDashboardController` fonctionne
  * Modèle `CreatorProfile` & `User` avec `isCreator()`

* Ne pas casser :
  * l'auth client
  * l'auth admin
  * les routes existantes

---

## 2️⃣ OBJECTIFS FONCTIONNELS V2

### 1. Gestion PRODUITS côté créateur

* Liste des produits du créateur
* Formulaire de création produit
* Formulaire d'édition produit
* Changement de statut : brouillon / en attente / publié
* Soft delete (ou archivage) optionnel

### 2. Gestion COMMANDES

* Liste des commandes où les produits appartiennent au créateur
* Détail d'une commande (articles, client, adresse, statut)
* Update du statut côté créateur : ex. `new` → `in_production` → `ready_to_ship`

### 3. Vue FINANCES SIMPLE

* Total des ventes confirmées (commandes livrées)
* Montant des commissions RACINE
* Net créateur
* Historique simple des commandes payées

---

## 3️⃣ HYPOTHÈSES DE BASE CÔTÉ MODÈLES

Tu peux partir sur ce qui existe déjà ou, si besoin, ajuster :

### `Product`

* `id`, `user_id`, `name`, `slug`, `description`
* `price`, `status` (`draft`, `pending_review`, `published`, `archived`)
* `stock` (nullable si sur commande)
* `is_active`
* timestamps

### `Order`

* `id`, `customer_id`, `status`, `total_amount`, `payment_status`, timestamps

### `OrderItem`

* `id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`

**Si certains champs n'existent pas, les créer en migration propre avec `up()`/`down()`.**

---

## 4️⃣ ROUTES À AJOUTER / COMPLÉTER

Dans le groupe déjà existant :

```php
Route::prefix('createur')->name('creator.')->middleware(['auth', 'role.creator', 'creator.active'])->group(function () {
    
    // Dashboard
    Route::get('dashboard', [CreatorDashboardController::class, 'index'])->name('dashboard');

    // Produits
    Route::get('produits', [CreatorProductController::class, 'index'])->name('products.index');
    Route::get('produits/nouveau', [CreatorProductController::class, 'create'])->name('products.create');
    Route::post('produits', [CreatorProductController::class, 'store'])->name('products.store');
    Route::get('produits/{product}/edit', [CreatorProductController::class, 'edit'])->name('products.edit');
    Route::put('produits/{product}', [CreatorProductController::class, 'update'])->name('products.update');
    Route::delete('produits/{product}', [CreatorProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('produits/{product}/publier', [CreatorProductController::class, 'publish'])->name('products.publish');

    // Commandes
    Route::get('commandes', [CreatorOrderController::class, 'index'])->name('orders.index');
    Route::get('commandes/{order}', [CreatorOrderController::class, 'show'])->name('orders.show');
    Route::patch('commandes/{order}/statut', [CreatorOrderController::class, 'updateStatus'])->name('orders.updateStatus');

    // Finances
    Route::get('finances', [CreatorFinanceController::class, 'index'])->name('finances.index');
});
```

**Exigences :**

* Filtrer toutes les requêtes par `user_id` (le créateur connecté)
* Protéger les `Route Model Binding` pour qu'un créateur ne puisse jamais accéder au produit/commande d'un autre

---

## 5️⃣ CONTRÔLEURS À CRÉER

### 5.1. `CreatorProductController`

#### Méthodes :

**`index()`**
* Récupère tous les produits du créateur connecté
* Filtre simple : par statut (facultatif)

**`create()`**
* Retourne un formulaire vide

**`store(Request $request)`**
* Valide les champs (name, price, description, etc.)
* Crée un produit avec :
  * `user_id` = utilisateur connecté
  * `status` = `pending_review` ou `draft`

**`edit(Product $product)`**
* Vérifie que `product->user_id === auth()->id()`

**`update(Request $request, Product $product)`**
* Même validation que `store`

**`destroy(Product $product)`**
* Soft delete ou set `status = 'archived'`

**`publish(Product $product)`**
* Change le statut :
  * Si tu veux une validation admin → passer à `pending_review`
  * Si auto-publi → `published`

> **Important :** Toujours vérifier que le produit appartient au créateur connecté.

---

### 5.2. `CreatorOrderController`

#### Méthodes :

**`index()`**
* Récupère toutes les `Order` qui contiennent des `OrderItem` dont `product.user_id = creator_id`
* Pagination
* Filtre par `order_status` (ex : `new`, `in_production`, `ready_to_ship`, `shipped`, `delivered`)

**`show(Order $order)`**
* Vérifie via relation que la commande concerne au moins un produit du créateur
* Affiche :
  * détails client (nom/prénom, email)
  * adresse
  * liste des items du créateur
  * statuts

**`updateStatus(Request $request, Order $order)`**
* Change le statut (par exemple `new` → `in_production` → `ready_to_ship`)
* Le créateur ne doit pas pouvoir toucher au paiement (ça reste piloté par la plateforme)

---

### 5.3. `CreatorFinanceController`

#### Méthode principale : `index()`

* Calcule pour le créateur :
  * **Chiffre d'affaires brut** : somme des `OrderItem.total_price` pour les commandes livrées
  * **Commission RACINE** : ex. 20% configurable (constante ou config)
  * **Net créateur** : brut – commission

* Affiche :
  * Total global
  * Total du mois en cours
  * Liste des dernières commandes payées (avec montant net)

---

## 6️⃣ VUES BLADE À CRÉER / METTRE À JOUR

Layout parent : `layouts/creator.blade.php` (déjà existant).

### 1. `resources/views/creator/products/index.blade.php`

* Tableau des produits (nom, statut, prix, date, actions)
* Bouton "Ajouter un produit"
* Badges de statut (draft, pending, published, archived)

### 2. `resources/views/creator/products/form.blade.php` (ou create + edit séparés)

* Formulaire :
  * Nom, description, prix, stock (ou sur commande), type, etc.
* Boutons :
  * "Enregistrer en brouillon"
  * "Soumettre à validation" (optionnel)

### 3. `resources/views/creator/orders/index.blade.php`

* Tableau :
  * N° commande, date, statut, total, actions
* Filtre par statut

### 4. `resources/views/creator/orders/show.blade.php`

* Détails de la commande :
  * Infos client
  * Adresse
  * Liste des produits du créateur
  * Statut avec dropdown/boutons pour changer de statut

### 5. `resources/views/creator/finances/index.blade.php`

* 3 cards :
  * Total brut
  * Commissions
  * Net créateur
* Tableau des dernières commandes payées

> **Style :**
> * Cohérent avec le dashboard actuel
> * Icônes claires, titres explicites, ton premium (RACINE BY GANDA)

---

## 7️⃣ SÉCURITÉ & QUALITÉ

* Toujours filtrer par `auth()->id()` côté contrôleurs
* Vérifier dans les policies ou directement dans les contrôleurs que :
  * un créateur ne peut pas éditer un produit d'un autre
  * un créateur ne peut pas voir une commande sans produit lui appartenant
* Ne pas exposer les données financières globales de la plateforme, seulement celles du créateur connecté

---

## 8️⃣ LIVRABLES ATTENDUS

À la fin, merci de fournir :

1. **La liste des fichiers créés/modifiés** avec chemins exacts

2. **Le code complet des contrôleurs :**
   * `CreatorProductController`
   * `CreatorOrderController`
   * `CreatorFinanceController`

3. **Les migrations éventuelles** ajoutées ou modifiées

4. **Un récapitulatif des vues Blade** créées

---

## 📋 INSTRUCTIONS D'UTILISATION

Ce prompt peut être copié-collé directement dans Antigravity / Cursor pour implémenter le module v2.

**Après implémentation :**
1. Faire un audit rapide du code généré
2. Tester les fonctionnalités
3. Préparer le v3 orienté **expérience créateur premium** (statistiques, graphiques, notifications, etc.)

---

**Date de création :** 29 novembre 2025  
**Généré par :** Cursor AI Assistant


