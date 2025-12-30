# 🔧 PATCH 4.4 — Fix LegacyWebhookDeprecationTest (Headers absents)

**Date :** 2025-12-15  
**Statut :** ✅ CORRIGÉ  
**Problème :** Tests LegacyWebhookDeprecationTest échouaient (2 FAIL sur 3) car headers de dépréciation absents.

---

## 1) Diagnostic

### Cause racine identifiée

**Routes dupliquées dans `routes/web.php` :**
- Lignes 453-461 : Routes **avec middleware** `LegacyWebhookDeprecationHeaders`
- Lignes 468-469 : Routes **SANS middleware** (dupliquées)

Laravel utilise la **première route trouvée** lors de la résolution, donc les routes sans middleware étaient utilisées, empêchant l'exécution du middleware.

### Status code réel
- **400** (pas 419 CSRF) → CSRF bien exempté
- Headers de dépréciation **absents** → middleware non exécuté

---

## 2) Correctif appliqué

### A) Suppression des routes dupliquées

**Fichier :** `routes/web.php`

**Avant :**
```php
// Lignes 453-461 : Routes avec middleware
Route::post('/webhooks/stripe', ...)
    ->middleware([\App\Http\Middleware\LegacyWebhookDeprecationHeaders::class])
    ->name('payment.webhook');

Route::post('/payment/card/webhook', ...)
    ->middleware([\App\Http\Middleware\LegacyWebhookDeprecationHeaders::class])
    ->name('payment.card.webhook');

// Lignes 468-469 : Routes SANS middleware (DUPLIQUÉES)
Route::post('/webhooks/stripe', ...)->name('payment.webhook');
Route::post('/payment/card/webhook', ...)->name('payment.card.webhook');
```

**Après :**
```php
// Routes avec middleware uniquement
Route::post('/webhooks/stripe', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])
    ->middleware([\App\Http\Middleware\LegacyWebhookDeprecationHeaders::class])
    ->name('payment.webhook');

Route::post('/payment/card/webhook', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])
    ->middleware([\App\Http\Middleware\LegacyWebhookDeprecationHeaders::class])
    ->name('payment.card.webhook');
```

**Résultat :** Le middleware est maintenant toujours exécuté sur les routes legacy.

---

## 3) Validation

### ✅ Tests LegacyWebhookDeprecationTest

```bash
php artisan test --filter LegacyWebhookDeprecationTest
# ✅ 3 tests passent (13 assertions)
```

- ✅ `test_legacy_endpoint_returns_deprecation_headers` : headers présents
- ✅ `test_official_endpoint_does_not_return_deprecation_headers` : headers absents sur `/api/webhooks/stripe`
- ✅ `test_payment_card_webhook_returns_deprecation_headers` : headers présents

### ✅ Tests non-régression

```bash
php artisan test --filter "LegacyWebhookDeprecationTest|WebhookEndpointsTest|WebhookSecurityTest"
# ✅ 17 tests passent (62 assertions)
```

- ✅ WebhookEndpointsTest (7 tests)
- ✅ WebhookSecurityTest (3 tests)
- ✅ LegacyWebhookDeprecationTest (3 tests)
- ✅ PaymentWebhookSecurityTest (4 tests)

---

## 4) Headers de dépréciation

### Headers ajoutés sur routes legacy

- `Deprecation: true` (string)
- `Sunset: <date RFC 7231>` (6 mois)
- `Link: <https://.../api/webhooks/stripe>; rel="successor-version"`

### Routes concernées

- ✅ `POST /webhooks/stripe` → headers présents
- ✅ `POST /payment/card/webhook` → headers présents
- ✅ `POST /api/webhooks/stripe` → headers **absents** (officiel)
- ✅ `POST /api/webhooks/monetbil` → headers **absents** (officiel)

---

## 5) Fichiers modifiés

1. ✅ `routes/web.php` (suppression routes dupliquées lignes 468-469)

---

## 6) Checklist sécurité

- ✅ Aucun secret/payload dans logs middleware
- ✅ Headers en string (`'true'`, pas bool)
- ✅ Endpoints officiels non impactés
- ✅ CSRF bien exempté (`webhooks/*`, `payment/card/webhook`)

---

**Correction terminée le 2025-12-15**  
**LegacyWebhookDeprecationTest : 100% PASS ✅**



