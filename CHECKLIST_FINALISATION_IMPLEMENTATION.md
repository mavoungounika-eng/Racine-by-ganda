# ✅ CHECKLIST DE FINALISATION - IMPLÉMENTATION POS ET CODES

**Date :** 8 décembre 2025

---

## 📋 VÉRIFICATIONS À EFFECTUER

### 1. Migration
- [ ] Exécuter `php artisan migrate`
- [ ] Vérifier que la colonne `order_number` existe dans la table `orders`
- [ ] Vérifier que les contraintes d'unicité sont bien appliquées

### 2. Services
- [ ] Vérifier que `ProductCodeService` est bien enregistré dans `AppServiceProvider`
- [ ] Vérifier que `OrderNumberService` est bien enregistré dans `AppServiceProvider`
- [ ] Tester la génération d'un SKU : `app(ProductCodeService::class)->generateSku()`
- [ ] Tester la génération d'un numéro de commande : `app(OrderNumberService::class)->generateOrderNumber()`

### 3. Observers
- [ ] Vérifier que `ProductObserver` est bien enregistré dans `AppServiceProvider`
- [ ] Créer un produit de test et vérifier que `ErpProductDetail` est créé avec SKU/code-barres
- [ ] Vérifier que le format est correct : `SKU-YYYYMMDD-XXXXX` et `CB-YYYYMMDD-XXXXX`

### 4. Modèles
- [ ] Vérifier que `Product` a bien la relation `erpDetails()`
- [ ] Vérifier que `Product` a bien les accesseurs `sku` et `barcode`
- [ ] Vérifier que `Order` génère bien `order_number` lors de la création
- [ ] Tester : `$product->sku` et `$product->barcode`

### 5. Routes POS
- [ ] Vérifier que les routes POS sont bien définies dans `routes/web.php`
- [ ] Exécuter `php artisan route:list | grep pos` pour vérifier
- [ ] Routes attendues :
  - `GET /admin/pos` → `admin.pos.index`
  - `POST /admin/pos/search-product` → `admin.pos.search-product`
  - `POST /admin/pos/create-order` → `admin.pos.create-order`
  - `GET /admin/pos/order/{order}` → `admin.pos.order`

### 6. Menu Admin
- [ ] Vérifier que le lien POS apparaît dans le menu latéral
- [ ] Vérifier que le lien est dans la section "Boutique"
- [ ] Vérifier que le lien est actif quand on est sur `/admin/pos`

### 7. Interface POS
- [ ] Accéder à `/admin/pos`
- [ ] Vérifier que l'interface s'affiche correctement
- [ ] Vérifier que le champ de scan a l'autofocus
- [ ] Tester le scan d'un produit (par SKU, code-barres, ou ID)
- [ ] Vérifier que le produit apparaît dans le panier
- [ ] Tester la modification des quantités
- [ ] Tester la suppression d'un produit du panier
- [ ] Tester la création d'une commande
- [ ] Vérifier que le stock est décrémenté
- [ ] Vérifier qu'un mouvement de stock est créé avec raison "Vente en boutique"

### 8. Commandes Artisan
- [ ] Tester `php artisan products:generate-codes` (sur un produit de test)
- [ ] Tester `php artisan orders:generate-numbers` (sur une commande de test)
- [ ] Vérifier que les codes sont bien générés

### 9. Distinction vente en ligne/boutique
- [ ] Créer une commande en ligne et vérifier le mouvement stock avec raison "Vente en ligne"
- [ ] Créer une commande POS et vérifier le mouvement stock avec raison "Vente en boutique"

### 10. Tests de régression
- [ ] Vérifier que la création de produit fonctionne toujours
- [ ] Vérifier que la création de commande en ligne fonctionne toujours
- [ ] Vérifier que la décrémentation de stock fonctionne toujours

---

## 🚀 COMMANDES À EXÉCUTER

```bash
# 1. Migration
php artisan migrate

# 2. Vider le cache
php artisan optimize:clear
composer dump-autoload

# 3. Générer les codes pour les données existantes (optionnel)
php artisan products:generate-codes
php artisan orders:generate-numbers

# 4. Vérifier les routes
php artisan route:list | grep pos
```

---

## 📝 TESTS MANUELS

### Test 1 : Création produit avec codes automatiques
1. Aller sur `/admin/products/create`
2. Créer un nouveau produit
3. Vérifier dans la base de données que `erp_product_details` contient :
   - `sku` au format `SKU-YYYYMMDD-XXXXX`
   - `barcode` au format `CB-YYYYMMDD-XXXXX`

### Test 2 : Création commande avec numéro
1. Créer une commande (via POS ou checkout)
2. Vérifier que `order_number` est généré au format `CMD-YYYY-XXXXXX`

### Test 3 : POS - Scan produit
1. Aller sur `/admin/pos`
2. Scanner ou entrer un code-barres/SKU/ID
3. Vérifier que le produit apparaît dans le panier
4. Vérifier le récapitulatif

### Test 4 : POS - Création commande
1. Ajouter plusieurs produits au panier
2. Remplir le formulaire client
3. Sélectionner un mode de paiement
4. Valider la vente
5. Vérifier :
   - Commande créée avec statut `completed`
   - Paiement `paid`
   - Stock décrémenté
   - Mouvement stock créé avec raison "Vente en boutique"

---

## ✅ STATUT FINAL

Une fois toutes les vérifications effectuées, cochez cette case :
- [ ] **IMPLÉMENTATION TERMINÉE ET VALIDÉE**

---

**En cas de problème, consulter :**
- `GUIDE_UTILISATION_POS_ET_CODES.md`
- `RAPPORT_IMPLEMENTATION_NUMEROS_UNIQUES_CODES_BARRES.md`

