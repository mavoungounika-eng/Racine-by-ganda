# 📖 GUIDE D'UTILISATION - POS ET CODES UNIQUES

**Date :** 8 décembre 2025  
**Version :** 1.0

---

## 🚀 DÉMARRAGE RAPIDE

### 1. Exécuter la migration

```bash
php artisan migrate
```

Cette migration ajoute le champ `order_number` à la table `orders`.

### 2. Générer les codes pour les données existantes (optionnel)

#### Pour les produits existants
```bash
php artisan products:generate-codes
```
Génère des SKU et code-barres pour tous les produits qui n'en ont pas encore.

#### Pour les commandes existantes
```bash
php artisan orders:generate-numbers
```
Génère des numéros de commande formatés pour toutes les commandes qui n'en ont pas encore.

### 3. Vider le cache (si nécessaire)
```bash
php artisan optimize:clear
composer dump-autoload
```

---

## 🛍️ UTILISATION DU POS (Point of Sale)

### Accès au POS

1. Connectez-vous en tant qu'admin ou staff
2. Dans le menu latéral, cliquez sur **"Point de Vente (POS)"** dans la section **"Boutique"**
3. Ou accédez directement à : `/admin/pos`

### Fonctionnement

#### 1. Scanner un produit
- Placez le curseur dans le champ de scan (autofocus automatique)
- Scannez le code-barres avec un lecteur de code-barres
- Ou entrez manuellement le SKU ou l'ID du produit
- Appuyez sur **Entrée**

#### 2. Gérer le panier
- Les produits scannés apparaissent dans la zone de gauche
- Utilisez les boutons **+** et **-** pour ajuster les quantités
- Cliquez sur l'icône **🗑️** pour retirer un produit

#### 3. Finaliser la vente
- Remplissez les informations client (optionnel) :
  - Nom du client
  - Email
  - Téléphone
- Sélectionnez le mode de paiement :
  - **Espèces**
  - **Carte bancaire**
  - **Mobile Money**
- Cliquez sur **"Valider la vente"**

#### 4. Confirmation
- Une commande est créée automatiquement avec :
  - Numéro de commande formaté (ex: `CMD-2025-000001`)
  - Statut : `completed`
  - Paiement : `paid`
- Le stock est décrémenté immédiatement
- Un mouvement de stock est créé avec la raison "Vente en boutique"

---

## 📦 GESTION DES CODES PRODUITS

### Format des codes

#### SKU (Stock Keeping Unit)
```
Format: SKU-YYYYMMDD-XXXXX
Exemple: SKU-20251208-00001

- SKU- : Préfixe fixe
- YYYYMMDD : Date de création (8 chiffres)
- XXXXX : Numéro séquentiel sur 5 chiffres (par jour)
```

#### Code-barres
```
Format: CB-YYYYMMDD-XXXXX
Exemple: CB-20251208-00001

- CB- : Préfixe fixe (Code-Barres)
- Même structure que le SKU
```

### Génération automatique

Les SKU et code-barres sont générés **automatiquement** lors de la création d'un produit :
- Via l'admin : `/admin/products/create`
- Via l'espace créateur : `/createur/produits/nouveau`

### Accès aux codes dans le code

```php
// Dans un contrôleur ou une vue
$product = Product::find(1);

// Accéder au SKU
$sku = $product->sku; // Ex: "SKU-20251208-00001"

// Accéder au code-barres
$barcode = $product->barcode; // Ex: "CB-20251208-00001"

// Accéder aux détails ERP complets
$details = $product->erpDetails;
$sku = $details->sku;
$barcode = $details->barcode;
```

---

## 📋 GESTION DES NUMÉROS DE COMMANDE

### Format

```
Format: CMD-YYYY-XXXXXX
Exemple: CMD-2025-000001

- CMD- : Préfixe fixe
- YYYY : Année (4 chiffres)
- XXXXXX : Numéro séquentiel sur 6 chiffres (par année)
```

### Génération automatique

Le numéro de commande est généré **automatiquement** lors de la création d'une commande :
- Via le POS
- Via le checkout en ligne
- Via l'admin (si création manuelle)

### Accès au numéro de commande

```php
// Dans un contrôleur ou une vue
$order = Order::find(1);

// Accéder au numéro de commande
$orderNumber = $order->order_number; // Ex: "CMD-2025-000001"
```

---

## 🔍 RECHERCHE PAR CODE

### Dans le POS

Le POS permet de rechercher un produit par :
- **Code-barres** : `CB-20251208-00001`
- **SKU** : `SKU-20251208-00001`
- **ID produit** : `1`, `2`, `3`, etc.

### Dans l'admin (à implémenter)

Vous pouvez ajouter une recherche par code-barres/SKU dans :
- La liste des produits (`/admin/products`)
- La liste des commandes (`/admin/orders`)

Exemple de recherche dans un contrôleur :
```php
$product = Product::whereHas('erpDetails', function ($query) use ($code) {
    $query->where('barcode', $code)
          ->orWhere('sku', $code);
})->first();
```

---

## 📊 DISTINCTION VENTE EN LIGNE / BOUTIQUE

### Vente en ligne
- **Déclencheur** : Paiement confirmé (`payment_status = 'paid'`)
- **Service** : `StockService::decrementFromOrder()`
- **Raison mouvement stock** : `'Vente en ligne'`
- **Statut commande** : `pending` → `paid` → `processing` → `shipped` → `completed`

### Vente boutique (POS)
- **Déclencheur** : Validation immédiate dans le POS
- **Service** : `PosController::createOrder()`
- **Raison mouvement stock** : `'Vente en boutique'`
- **Statut commande** : `completed` (immédiatement)
- **Paiement** : `paid` (immédiatement)

### Vérification dans les mouvements de stock

```php
use Modules\ERP\Models\ErpStockMovement;

// Ventes en ligne
$onlineSales = ErpStockMovement::where('reason', 'Vente en ligne')->get();

// Ventes en boutique
$storeSales = ErpStockMovement::where('reason', 'Vente en boutique')->get();
```

---

## 🛠️ COMMANDES ARTISAN

### Générer les codes produits

```bash
php artisan products:generate-codes
```

**Description :** Génère des SKU et code-barres pour tous les produits qui n'en ont pas encore.

**Utilisation :** Utile après l'implémentation pour les produits existants.

### Générer les numéros de commande

```bash
php artisan orders:generate-numbers
```

**Description :** Génère des numéros de commande formatés pour toutes les commandes qui n'en ont pas encore.

**Utilisation :** Utile après l'implémentation pour les commandes existantes.

---

## ⚠️ NOTES IMPORTANTES

### Produits existants
- Les produits créés **avant** cette implémentation n'ont pas de SKU/code-barres
- Utilisez `php artisan products:generate-codes` pour les générer

### Commandes existantes
- Les commandes créées **avant** cette implémentation n'ont pas de `order_number`
- Utilisez `php artisan orders:generate-numbers` pour les générer

### Code-barres
- Format interne personnalisé (`CB-YYYYMMDD-XXXXX`)
- Pour utiliser des formats standards (EAN13, Code128), une bibliothèque externe sera nécessaire
- Exemple : `picqer/php-barcode-generator`

### Performance
- La génération de codes est optimisée avec vérification d'unicité
- Les requêtes utilisent des index pour de meilleures performances

---

## 🐛 DÉPANNAGE

### Le POS ne s'affiche pas
1. Vérifiez que vous êtes connecté en tant qu'admin/staff
2. Videz le cache : `php artisan optimize:clear`
3. Vérifiez les routes : `php artisan route:list | grep pos`

### Les codes ne sont pas générés
1. Vérifiez que `ProductObserver` est bien enregistré dans `AppServiceProvider`
2. Vérifiez que `ProductCodeService` est bien enregistré comme singleton
3. Vérifiez les logs : `storage/logs/laravel.log`

### Erreur "Class not found"
1. Exécutez : `composer dump-autoload`
2. Videz le cache : `php artisan optimize:clear`

---

## 📝 EXEMPLES D'UTILISATION

### Créer un produit avec codes automatiques

```php
use App\Models\Product;

$product = Product::create([
    'title' => 'Robe traditionnelle',
    'price' => 25000,
    'stock' => 10,
    // ... autres champs
]);

// Les codes sont générés automatiquement
echo $product->sku; // "SKU-20251208-00001"
echo $product->barcode; // "CB-20251208-00001"
```

### Rechercher un produit par code-barres

```php
use App\Models\Product;

$barcode = 'CB-20251208-00001';
$product = Product::whereHas('erpDetails', function ($query) use ($barcode) {
    $query->where('barcode', $barcode);
})->first();
```

### Créer une commande avec numéro automatique

```php
use App\Models\Order;

$order = Order::create([
    'user_id' => 1,
    'total_amount' => 50000,
    // ... autres champs
]);

// Le numéro est généré automatiquement
echo $order->order_number; // "CMD-2025-000001"
```

---

**Documentation complète disponible dans :**
- `RAPPORT_IMPLEMENTATION_NUMEROS_UNIQUES_CODES_BARRES.md`
- `ANALYSE_SYSTEME_NUMEROS_UNIQUES_CODES_BARRES.md`

