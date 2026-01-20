# 🔒 POLITIQUE DE LOGS — PAYMENTS HUB

**Date :** 2025-12-14  
**Version :** 1.0  
**Statut :** ✅ **ACTIF**

---

## 🎯 OBJECTIF

Interdire toute fuite de secrets dans les logs et le monitoring applicatif. Aucun payload brut (webhook/callback) ne doit être loggé tel quel.

---

## 📋 RÈGLES OBLIGATOIRES

### 1. Redaction systématique avant logs

**AVANT de logger** toute erreur ou événement lié aux paiements :

```php
use App\Services\Payments\PayloadRedactionService;

// ❌ INTERDIT
Log::error('Webhook failed', ['payload' => $payload]);

// ✅ CORRECT
$redacted = app(PayloadRedactionService::class)->redactForLogs($payload);
Log::error('Webhook failed', [
    'event_id' => $event->event_id,
    'provider' => 'stripe',
    'status' => 'failed',
    'payload_redacted' => $redacted, // Payload redacted
]);
```

### 2. Champs à logger uniquement

Pour les erreurs webhook/callback, logger **uniquement** :

- `event_id` / `event_key` (identifiant non sensible)
- `provider` (stripe/monetbil)
- `status` (received/processed/failed/ignored)
- `error_code` (si disponible, code générique)
- `payment_ref` (référence métier, non sensible)
- Payload **redacted** (via `PayloadRedactionService::redactForLogs()`)

### 3. Champs interdits dans les logs

**NE JAMAIS logger** :

- ❌ Headers complets (peuvent contenir `Authorization`, `X-Signature`)
- ❌ Signatures brutes (`whsec_*`, `X-Callback-Signature`)
- ❌ Clés API (`sk_*`, `pk_*`)
- ❌ Tokens d'accès
- ❌ Secrets de configuration
- ❌ Payload brut sans redaction

### 4. Messages d'exception

Les exceptions liées aux paiements doivent **masquer les secrets** :

```php
// ❌ INTERDIT
throw new PaymentException('Stripe error: ' . $stripeResponse->getBody());

// ✅ CORRECT
throw new PaymentException('Stripe error: Invalid signature', [
    'event_id' => $event->event_id,
    'provider' => 'stripe',
]);
```

---

## 🔍 VÉRIFICATIONS

### Patterns à rechercher dans les logs

Si vous trouvez ces patterns dans les logs, c'est une **fuite de secret** :

- `sk_` (Stripe secret key)
- `whsec_` (Stripe webhook secret)
- `pk_` (Stripe public key - masqué par précaution)
- `token`
- `secret`
- `password`
- `api_key`
- `authorization`

### Test de validation

```bash
# Rechercher des fuites potentielles dans les logs
grep -r "sk_\|whsec_\|token\|secret" storage/logs/laravel.log

# Devrait retourner 0 résultat
```

---

## 🛠️ IMPLÉMENTATION

### Service utilisé

**`App\Services\Payments\PayloadRedactionService`**

- `redact($payload)` : Redaction pour affichage UI
- `redactForLogs($payload)` : Redaction stricte pour logs (supprime headers/signatures)

### Exemple d'utilisation

```php
use App\Services\Payments\PayloadRedactionService;

$redactionService = app(PayloadRedactionService::class);

// Pour logs
$logPayload = $redactionService->redactForLogs($webhookPayload);
Log::info('Webhook received', [
    'event_id' => $event->event_id,
    'payload' => $logPayload,
]);

// Pour UI
$uiPayload = $redactionService->redact($webhookPayload);
// Afficher dans la vue
```

---

## ✅ CHECKLIST

- ✅ `PayloadRedactionService` créé et testé
- ✅ Tous les logs webhook/callback utilisent `redactForLogs()`
- ✅ Aucun payload brut dans les exceptions
- ✅ Headers/signatures jamais loggés
- ✅ Tests de validation passent (grep patterns)

---

## 📝 NOTES

### Pourquoi cette politique ?

1. **Sécurité** : Les logs peuvent être accessibles à plusieurs personnes (devs, ops, monitoring)
2. **Conformité** : Éviter l'exposition de données sensibles
3. **Audit** : Les logs doivent être exploitables sans risque

### Monitoring externe

Si vous utilisez un service de monitoring externe (Sentry, LogRocket, etc.) :

- Vérifier que les secrets ne sont pas envoyés
- Configurer les filtres de redaction si disponibles
- Utiliser `redactForLogs()` avant envoi

---

**Politique en vigueur depuis le Sprint 3 (2025-12-14)**






