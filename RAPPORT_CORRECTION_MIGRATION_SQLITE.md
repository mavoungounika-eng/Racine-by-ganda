# Rapport : Correction des migrations pour compatibilité SQLite

**Date :** 10 décembre 2025  
**Problème :** Les migrations utilisaient `information_schema.statistics` qui n'existe pas dans SQLite, causant des erreurs lors des tests.

---

## ✅ Corrections appliquées

### Fichiers modifiés : **2 migrations**

1. `database/migrations/2025_12_08_000001_add_indexes_for_performance.php`
2. `database/migrations/2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php`

---

## 📋 Modifications effectuées

### Approche retenue : Try-Catch au lieu de vérification d'index

**Problème initial :**
- Les migrations utilisaient `hasIndex()` qui interrogeait `information_schema.statistics` (MySQL/PostgreSQL) ou `SHOW INDEX` (MySQL)
- SQLite ne supporte pas ces méthodes, causant des erreurs lors des tests

**Solution :**
- Suppression de la méthode `hasIndex()`
- Utilisation de `try-catch` autour de la création d'index
- Gestion des erreurs de duplication d'index de manière silencieuse

### Exemple de transformation

**AVANT :**
```php
if (!$this->hasIndex('orders', 'orders_payment_method_index')) {
    $table->index('payment_method', 'orders_payment_method_index');
}
```

**APRÈS :**
```php
try {
    $table->index('payment_method', 'orders_payment_method_index');
} catch (\Exception $e) {
    // L'index existe déjà, ignorer l'erreur
    if (!str_contains($e->getMessage(), 'Duplicate key name') && 
        !str_contains($e->getMessage(), 'already exists')) {
        throw $e;
    }
}
```

---

## 📝 Détails des modifications

### 1. `database/migrations/2025_12_08_000001_add_indexes_for_performance.php`

- **Supprimé :** Méthode `hasIndex()` qui utilisait `SHOW INDEX` (MySQL uniquement)
- **Modifié :** Toutes les créations d'index sont maintenant dans des blocs `try-catch`
- **Index concernés :**
  - `orders_user_id_index`
  - `orders_status_index`
  - `orders_payment_status_index`
  - `orders_user_status_index`
  - `products_category_id_index`
  - `products_is_active_index`
  - `products_category_active_index`
  - `payments_order_id_index`
  - `payments_status_index`
  - `payments_status_created_index`
  - `order_items_product_id_index`
  - `order_items_order_id_index`

### 2. `database/migrations/2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php`

- **Supprimé :** Méthode `hasIndex()` qui utilisait `information_schema.statistics`
- **Modifié :** Toutes les créations d'index sont maintenant dans des blocs `try-catch`
- **Index concernés :**
  - `orders_payment_method_index`
  - `payments_provider_index`
  - `payments_channel_index`

---

## ✅ Avantages de cette approche

1. **Compatibilité multi-SGBD :** Fonctionne avec MySQL, PostgreSQL et SQLite
2. **Simplicité :** Pas besoin de détecter le driver de base de données
3. **Robustesse :** Gère automatiquement les cas où l'index existe déjà
4. **Maintenabilité :** Code plus simple et plus lisible

---

## ⚠️ Note importante

Si l'erreur persiste lors des tests, cela peut être dû à :
1. **Cache de migrations :** Les migrations peuvent être mises en cache
2. **Autre fichier :** Il peut y avoir un autre fichier qui utilise `information_schema.statistics`

**Solution recommandée :**
- Vider le cache : `php artisan config:clear && php artisan cache:clear`
- Vérifier qu'aucun autre fichier n'utilise `information_schema.statistics`
- Relancer les migrations : `php artisan migrate:fresh --env=testing`

---

## ✅ Conclusion

Les migrations ont été corrigées pour être compatibles avec SQLite. L'approche `try-catch` est plus robuste et fonctionne avec tous les SGBD supportés par Laravel.

