# Diagnostic LegacyWebhookGuard

## Problème
Le middleware `LegacyWebhookGuard` ne produit aucun log "Legacy webhook used/blocked" lors d'un appel POST /webhooks/stripe.

## Vérifications effectuées

### ✅ Route vérifiée
```bash
php artisan route:list --path=webhooks/stripe
```

**Résultat :**
- ✅ Route `POST webhooks/stripe` existe
- ✅ Pointe vers `Front\CardPaymentController@webhook`
- ⚠️ **PROBLÈME :** Le middleware n'apparaît pas dans la sortie de `route:list`

### ✅ Middleware vérifié
- ✅ `LegacyWebhookGuard` existe dans `app/Http/Middleware/LegacyWebhookGuard.php`
- ✅ Le middleware est utilisé dans `routes/web.php` ligne 455
- ⚠️ **PROBLÈME :** Le middleware n'est **PAS enregistré** dans `bootstrap/app.php`

### ✅ Configuration vérifiée
- ✅ `payments.legacy_webhooks_enabled` = `true`
- ✅ Channel de log par défaut = `stack` (qui utilise `single` par défaut)
- ✅ Channel `single` écrit dans `storage/logs/laravel.log`

## Cause probable

Le middleware `LegacyWebhookGuard` est utilisé directement avec le FQCN dans la route, mais n'est pas enregistré dans `bootstrap/app.php`. En Laravel 12, cela peut fonctionner, mais il est recommandé de l'enregistrer pour éviter les problèmes d'autoload ou de résolution de classe.

## Correctifs appliqués ✅

### ✅ Correctif 1 : Enregistrer le middleware (APPLIQUÉ)

**Modification dans `bootstrap/app.php` ligne 40 :**
```php
'legacy.webhook.guard' => \App\Http\Middleware\LegacyWebhookGuard::class,
```

**Modification dans `routes/web.php` lignes 453-458 :**
```php
->middleware([
    'legacy.webhook.guard',
    'legacy.webhook.deprecation',
])
```

**Modification dans `routes/web.php` lignes 462-467 (route `/payment/card/webhook`) :**
```php
->middleware([
    'legacy.webhook.guard',
    'legacy.webhook.deprecation',
])
```

### Correctif 2 : Vérifier l'autoload

```bash
composer dump-autoload
php artisan optimize:clear
```

### Correctif 3 : Forcer le channel single pour les logs

Vérifier que `LOG_CHANNEL=single` dans `.env` ou que `LOG_STACK=single` est configuré.

## Commandes de test

### 1. Vérifier la route avec middleware
```bash
php artisan route:list --path=webhooks/stripe --columns=method,uri,name,middleware
```

### 2. Générer un hit legacy webhook
```powershell
curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"
```

### 3. Vérifier les logs
```bash
# Vérifier les dernières lignes du log
tail -n 50 storage/logs/laravel.log | grep -i "legacy webhook"
```

### 4. Test avec debug
```bash
php artisan payments:legacy-webhooks-report --hours=1 --debug=1
```

