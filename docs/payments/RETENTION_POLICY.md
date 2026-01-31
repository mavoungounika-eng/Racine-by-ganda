# 📦 Politique de Rétention — Payments Hub v1.1

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Audit  
**Tickets :** #PH2-008, #PH2-009

---

## 🎯 OBJECTIF

Définir la politique de rétention et de purge pour éviter la croissance infinie des données.

---

## 📊 POLITIQUE ÉVÉNEMENTS (Webhooks/Callbacks)

### Tables concernées

1. **`stripe_webhook_events`**
2. **`monetbil_callback_events`**

### Règle de rétention

**Durée de conservation :** 90 jours (configurable)

**Raison :**
- Événements nécessaires pour debugging et audit
- 90 jours = période raisonnable pour investigation incidents
- Au-delà, données archivées ou purgées

### Mécanisme de purge

**Commande Artisan :** `payments:prune-events`

**Paramètres :**
- `--days=90` : Nombre de jours de rétention (défaut : 90)
- `--dry-run` : Mode simulation (affiche ce qui serait supprimé)

**Scheduler :** Exécution quotidienne (daily) à 2h du matin

**Action :**
- Supprimer les événements avec `created_at < now() - 90 days`
- Conserver uniquement les événements `failed` au-delà de 90 jours (pour analyse)

### Configuration

**Fichier :** `.env`

```env
PAYMENTS_EVENTS_RETENTION_DAYS=90
PAYMENTS_EVENTS_KEEP_FAILED=true
```

---

## 📊 POLITIQUE TRANSACTIONS (`payment_transactions`)

### Décision : Conservation totale (v1.1)

**Raison :**
- Transactions = données métier critiques
- Nécessaires pour conformité comptable/fiscale
- Taille raisonnable (pas de payload volumineux)

### Archivage futur (optionnel)

**Si nécessaire plus tard :**
- Archivage après 2 ans dans table `payment_transactions_archive`
- Anonymisation des données sensibles (phone, email)
- Export CSV avant archivage

**Mécanisme :**
- Commande `payments:archive-transactions --years=2`
- Scheduler mensuel

### Configuration

**Fichier :** `.env`

```env
PAYMENTS_TRANSACTIONS_RETENTION_YEARS=unlimited
PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED=false
```

---

## 📊 POLITIQUE AUDIT LOGS (`payment_audit_logs`)

### Règle de rétention

**Durée de conservation :** 1 an (365 jours)

**Raison :**
- Logs d'audit nécessaires pour conformité
- 1 an = période standard pour audit interne

### Mécanisme de purge

**Commande Artisan :** `payments:prune-audit-logs`

**Paramètres :**
- `--days=365` : Nombre de jours de rétention (défaut : 365)
- `--dry-run` : Mode simulation

**Scheduler :** Exécution mensuelle

---

## 🔧 IMPLÉMENTATION TECHNIQUE

### Commande `payments:prune-events`

**Fichier :** `app/Console/Commands/PrunePaymentEvents.php`

**Logique :**
```php
public function handle()
{
    $days = $this->option('days') ?? config('payments.events_retention_days', 90);
    $keepFailed = config('payments.events_keep_failed', true);
    
    $cutoffDate = now()->subDays($days);
    
    // Stripe events
    $stripeQuery = StripeWebhookEvent::where('created_at', '<', $cutoffDate);
    if ($keepFailed) {
        $stripeQuery->where('status', '!=', 'failed');
    }
    $stripeDeleted = $stripeQuery->delete();
    
    // Monetbil events
    $monetbilQuery = MonetbilCallbackEvent::where('created_at', '<', $cutoffDate);
    if ($keepFailed) {
        $monetbilQuery->where('status', '!=', 'failed');
    }
    $monetbilDeleted = $monetbilQuery->delete();
    
    $this->info("Purged {$stripeDeleted} Stripe events and {$monetbilDeleted} Monetbil events");
}
```

### Scheduler

**Fichier :** `app/Console/Kernel.php` ou `bootstrap/app.php`

```php
// Daily purge events
$schedule->command('payments:prune-events')
    ->dailyAt('02:00')
    ->description('Purge old payment events');

// Monthly purge audit logs
$schedule->command('payments:prune-audit-logs')
    ->monthly()
    ->description('Purge old payment audit logs');
```

---

## 📋 CONFIGURATION

### Fichier de config

**Fichier :** `config/payments.php` (à créer)

```php
return [
    'events' => [
        'retention_days' => env('PAYMENTS_EVENTS_RETENTION_DAYS', 90),
        'keep_failed' => env('PAYMENTS_EVENTS_KEEP_FAILED', true),
    ],
    'transactions' => [
        'retention_years' => env('PAYMENTS_TRANSACTIONS_RETENTION_YEARS', 'unlimited'),
        'archive_enabled' => env('PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED', false),
    ],
    'audit_logs' => [
        'retention_days' => env('PAYMENTS_AUDIT_LOGS_RETENTION_DAYS', 365),
    ],
];
```

---

## ✅ CHECKLIST IMPLÉMENTATION

- [x] Politique événements définie (90 jours)
- [x] Politique transactions définie (conservation totale)
- [x] Politique audit logs définie (1 an)
- [x] Commande `payments:prune-events` à créer
- [x] Scheduler à configurer
- [x] Configuration `.env` documentée

---

## 📝 NOTES IMPORTANTES

1. **Événements failed** : Conserver au-delà de 90 jours pour analyse (option `keep_failed`).

2. **Transactions** : Conservation totale en v1.1. Réévaluer si volume devient problématique.

3. **Audit logs** : Conservation 1 an minimum pour conformité.

4. **Dry-run** : Toujours tester avec `--dry-run` avant purge réelle.

5. **Backup** : Avant purge, s'assurer que backup DB est à jour.

---

**Document créé le :** 2025-12-14  
**Prochaine étape :** Implémenter la commande de purge dans Sprint 1 (#PH2-008)








