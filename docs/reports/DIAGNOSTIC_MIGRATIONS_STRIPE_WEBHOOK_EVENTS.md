# Diagnostic - Migrations stripe_webhook_events

## Problème identifié

**Erreur :** `SQLSTATE[42000]: Duplicate key name 'stripe_webhook_events_checkout_session_id_index'`

## Analyse des migrations

### Migrations concernées

1. **`2025_12_13_225153_create_stripe_webhook_events_table.php`**
   - Crée la table initiale
   - Index : `payment_id`, `event_type`, `status`
   - ✅ Exécutée

2. **`2025_12_15_015923_add_dispatched_at_to_stripe_webhook_events_table.php`**
   - Ajoute `dispatched_at`
   - Index : `dispatched_at`
   - ✅ Exécutée

3. **`2025_12_15_160000_add_requeue_tracking_to_webhook_events.php`**
   - Ajoute `requeue_count`, `last_requeue_at`
   - Index : `requeue_count`, `last_requeue_at`
   - ✅ Exécutée

4. **`2025_12_17_185500_add_stripe_identifiers_to_webhook_events_table.php`** ⚠️
   - Ajoute `checkout_session_id`, `payment_intent_id`
   - Index : `checkout_session_id`, `payment_intent_id`
   - ✅ **DÉJÀ EXÉCUTÉE** (statut: Ran)

5. **`2025_12_19_010518_add_checkout_session_id_and_payment_intent_id_to_stripe_webhook_events_table.php`** ⚠️
   - Ajoute `checkout_session_id`, `payment_intent_id` (REDONDANT)
   - Index : `checkout_session_id`, `payment_intent_id` (REDONDANT)
   - ⏳ **EN ATTENTE** (statut: Pending)

## Conflit identifié

**Les migrations 4 et 5 font exactement la même chose :**
- Les deux créent les colonnes `checkout_session_id` et `payment_intent_id`
- Les deux créent les mêmes index avec les mêmes noms
- La migration 4 a déjà été exécutée, donc les colonnes et index existent déjà
- La migration 5 tente de créer les mêmes index → **ERREUR**

## Solution appliquée

### Fichier modifié

**`database/migrations/2025_12_19_010518_add_checkout_session_id_and_payment_intent_id_to_stripe_webhook_events_table.php`**

### Corrections apportées

1. **Ajout d'une méthode `hasIndex()`** pour vérifier l'existence d'un index via SQL
2. **Vérification de l'existence des index** avant de les créer
3. **Gestion d'erreur améliorée** avec vérification du message d'erreur
4. **Documentation** expliquant la redondance avec l'autre migration

### Code de la méthode `hasIndex()`

```php
private function hasIndex(string $table, string $column): bool
{
    $connection = Schema::getConnection();
    $databaseName = $connection->getDatabaseName();
    
    // Nom d'index standard Laravel: {table}_{column}_index
    $indexName = "{$table}_{$column}_index";
    
    try {
        $indexes = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return isset($indexes[0]) && $indexes[0]->count > 0;
    } catch (\Exception $e) {
        // En cas d'erreur, retourner false pour tenter la création
        return false;
    }
}
```

## Procédure finale

### 1. Vérifier l'état actuel

```powershell
php artisan migrate:status | Select-String "stripe"
```

### 2. Exécuter la migration corrigée

```powershell
php artisan migrate
```

### 3. Vérifier que tout est OK

```powershell
php artisan tinker
>>> Schema::hasColumn('stripe_webhook_events', 'checkout_session_id')
>>> Schema::hasColumn('stripe_webhook_events', 'payment_intent_id')
```

### 4. Vérifier les index

```sql
SHOW INDEXES FROM stripe_webhook_events WHERE Column_name IN ('checkout_session_id', 'payment_intent_id');
```

## Alternative : Supprimer la migration redondante

Si vous préférez supprimer la migration redondante plutôt que de la corriger :

```powershell
# Supprimer la migration
Remove-Item database/migrations/2025_12_19_010518_add_checkout_session_id_and_payment_intent_id_to_stripe_webhook_events_table.php

# Vérifier que tout est OK
php artisan migrate:status
```

**Note :** Cette option est recommandée si vous êtes sûr que la migration `2025_12_17_185500` a été exécutée partout (local + production).

## Résumé

- ✅ **Problème identifié** : Deux migrations créent les mêmes colonnes et index
- ✅ **Solution appliquée** : Vérification de l'existence des index avant création
- ✅ **Compatibilité** : MySQL et Laravel en production
- ✅ **Rétrocompatibilité** : La migration fonctionne même si l'autre n'a pas été exécutée

