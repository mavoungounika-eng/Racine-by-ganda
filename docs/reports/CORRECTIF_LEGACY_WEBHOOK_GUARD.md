# Correctif LegacyWebhookGuard - Résumé

## Problème identifié
Le middleware `LegacyWebhookGuard` ne produisait aucun log "Legacy webhook used/blocked" car il n'était pas enregistré dans `bootstrap/app.php`.

## Correctifs appliqués

### 1. Enregistrement du middleware dans `bootstrap/app.php`

**Fichier :** `bootstrap/app.php`  
**Ligne :** 40

```diff
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'legacy.webhook.deprecation' => \App\Http\Middleware\LegacyWebhookDeprecationHeaders::class,
+           'legacy.webhook.guard' => \App\Http\Middleware\LegacyWebhookGuard::class,
```

### 2. Utilisation de l'alias dans les routes

**Fichier :** `routes/web.php`  
**Lignes :** 453-458 et 462-467

```diff
Route::post('/webhooks/stripe', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])
-   ->middleware([
-       \App\Http\Middleware\LegacyWebhookGuard::class,
-       \App\Http\Middleware\LegacyWebhookDeprecationHeaders::class,
-   ])
+   ->middleware([
+       'legacy.webhook.guard',
+       'legacy.webhook.deprecation',
+   ])
```

## Commandes à exécuter après le correctif

### 1. Nettoyer le cache et optimiser
```bash
php artisan optimize:clear
composer dump-autoload
```

### 2. Vérifier que la route utilise bien le middleware
```bash
php artisan route:list --path=webhooks/stripe
```

### 3. Tester le webhook
```powershell
# Générer un hit legacy webhook
curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"
```

### 4. Vérifier les logs
```bash
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "legacy webhook"

# Ou avec le report
php artisan payments:legacy-webhooks-report --hours=1 --debug=1
```

## Validation

Après le correctif, vous devriez voir dans les logs :
```
[2024-01-15 10:30:45] local.WARNING: Legacy webhook used {"provider":"stripe","route_name":"payment.webhook","path":"webhooks/stripe",...}
```

Et dans le report avec `--debug=1` :
```
=== Debug Statistics ===
  File actually used: C:\laravel_projects\racine-backend\storage\logs\laravel.log
  Total log entries (buffers) processed: 1234
  Entries matched (used/blocked): 1
```

## Diff complet

### bootstrap/app.php
```diff
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'legacy.webhook.deprecation' => \App\Http\Middleware\LegacyWebhookDeprecationHeaders::class,
+           'legacy.webhook.guard' => \App\Http\Middleware\LegacyWebhookGuard::class,
```

### routes/web.php (2 occurrences)
```diff
Route::post('/webhooks/stripe', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])
-   ->middleware([
-       \App\Http\Middleware\LegacyWebhookGuard::class,
-       \App\Http\Middleware\LegacyWebhookDeprecationHeaders::class,
-   ])
+   ->middleware([
+       'legacy.webhook.guard',
+       'legacy.webhook.deprecation',
+   ])
```

