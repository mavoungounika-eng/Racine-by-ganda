# 🔧 RUNBOOK — FAILED JOBS (PAYMENTS HUB)

**Date :** 2025-12-14  
**Version :** 1.0  
**Statut :** ✅ **ACTIF**

---

## 🎯 OBJECTIF

Procédure opérationnelle pour gérer les jobs en échec (failed jobs) dans le Payments Hub, avec checklist de relance contrôlée.

---

## 📋 PROCÉDURE STANDARD

### 1. Lister les jobs failed

```bash
php artisan queue:failed
```

**Sortie attendue :**
```
ID | Connection | Queue | Class                                    | Failed At
---|------------|-------|------------------------------------------|------------------
1  | database   | default| App\Jobs\ProcessStripeWebhookEventJob  | 2025-12-14 10:30:00
```

### 2. Analyser un job failed

```bash
# Voir les détails d'un job spécifique
php artisan queue:failed --id=1
```

**Informations importantes :**
- `exception` : Message d'erreur
- `failed_at` : Date/heure de l'échec
- `payload` : Données du job (event_id, etc.)

### 3. Décision : Relancer ou Supprimer

#### ✅ RELANCER si :
- Erreur temporaire (timeout, connexion DB, etc.)
- Job récent (< 24h)
- Pas d'erreur de logique métier

#### ❌ NE PAS RELANCER si :
- Erreur de logique métier (transaction introuvable, etc.)
- Job ancien (> 7 jours)
- Erreur de validation (données invalides)

---

## 🔄 RELANCE CONTRÔLÉE

### Relancer un job spécifique

```bash
php artisan queue:retry 1
```

**Vérifier le résultat :**
```bash
# Le job devrait disparaître de failed_jobs
php artisan queue:failed

# Vérifier les logs
tail -f storage/logs/laravel.log | grep "ProcessStripeWebhookEventJob"
```

### Relancer plusieurs jobs

```bash
# Relancer jobs 1, 2, 3
php artisan queue:retry 1 2 3

# Relancer tous les jobs failed (ATTENTION : à utiliser avec précaution)
php artisan queue:retry all
```

### Relancer avec filtrage (script custom)

Créer `app/Console/Commands/RetryPaymentJobs.php` :

```php
public function handle()
{
    $failedJobs = DB::table('failed_jobs')
        ->where('queue', 'default')
        ->where('failed_at', '>', now()->subDays(1))
        ->get();

    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $class = $payload['displayName'] ?? '';

        // Relancer uniquement les jobs Payments Hub
        if (str_contains($class, 'ProcessStripeWebhookEventJob') 
            || str_contains($class, 'ProcessMonetbilCallbackEventJob')) {
            $this->call('queue:retry', ['id' => $job->id]);
        }
    }
}
```

---

## 🗑️ SUPPRESSION

### Supprimer un job failed

```bash
# Supprimer un job spécifique
php artisan queue:forget 1

# Vider tous les jobs failed (ATTENTION : irréversible)
php artisan queue:flush
```

---

## 📊 CHECKLIST DE RELANCE

Avant de relancer un job, vérifier :

- [ ] **Erreur analysée** : Comprendre pourquoi le job a échoué
- [ ] **Cause corrigée** : Si erreur système (DB down, timeout), vérifier que c'est résolu
- [ ] **Données valides** : Vérifier que l'événement/transaction existe toujours
- [ ] **Pas de doublon** : Vérifier que le traitement n'a pas déjà été fait (idempotence)
- [ ] **Job récent** : Si job > 7 jours, vérifier pertinence avant relance

---

## 🔍 ANALYSE DES ERREURS COMMUNES

### 1. Transaction introuvable

**Erreur :** `Transaction not found`

**Action :**
- Vérifier que la transaction existe dans `payment_transactions`
- Si transaction existe mais job échoue, vérifier les critères de recherche (payment_ref, transaction_id)
- **Ne pas relancer** si transaction vraiment absente (données invalides)

### 2. Timeout

**Erreur :** `Job timeout after 60 seconds`

**Action :**
- Vérifier les logs pour voir où le job bloque
- Vérifier performance DB (indexes, locks)
- **Relancer** si timeout temporaire

### 3. Erreur DB (lock, connection)

**Erreur :** `SQLSTATE[HY000] [2002] Connection refused`

**Action :**
- Vérifier que la DB est accessible
- Vérifier les connexions simultanées
- **Relancer** une fois DB rétablie

### 4. Événement déjà traité (idempotence)

**Erreur :** Aucune (job réussit mais log "already processed")

**Action :**
- **Normal** : L'idempotence fonctionne
- **Pas d'action** nécessaire

---

## 📈 MONITORING QUOTIDIEN

### Script de monitoring (à exécuter quotidiennement)

```bash
#!/bin/bash
# check-failed-jobs.sh

FAILED_COUNT=$(php artisan queue:failed --json | jq '. | length')

if [ "$FAILED_COUNT" -gt 10 ]; then
    echo "ALERT: $FAILED_COUNT failed jobs detected"
    # Envoyer notification (email, Slack, etc.)
fi

# Lister les jobs récents (< 24h)
php artisan queue:failed | grep "$(date +%Y-%m-%d)"
```

---

## 🚨 ALERTES

### Seuils recommandés

- **> 10 jobs failed** : Alerte warning
- **> 50 jobs failed** : Alerte critique
- **Jobs > 7 jours** : Nettoyage recommandé

### Intégration monitoring

Si vous utilisez un système de monitoring (Sentry, Bugsnag, etc.) :

- Configurer alertes sur `failed_jobs` count
- Alertes sur exceptions spécifiques (timeout, DB errors)

---

## ✅ CHECKLIST MAINTENANCE

### Quotidien
- [ ] Vérifier `php artisan queue:failed`
- [ ] Analyser les erreurs récentes
- [ ] Relancer les jobs temporaires si nécessaire

### Hebdomadaire
- [ ] Nettoyer les jobs > 7 jours
- [ ] Analyser les patterns d'erreurs récurrentes
- [ ] Documenter les problèmes fréquents

### Mensuel
- [ ] Review des métriques (taux d'échec, temps de traitement)
- [ ] Optimisation si nécessaire (timeout, backoff)

---

## 📝 NOTES

### Idempotence

Les jobs sont **idempotents** : relancer un job déjà traité ne créera pas de doublon. Le job vérifie l'état avant traitement.

### Locks DB

Les jobs utilisent `lockForUpdate()` pour éviter les race conditions. En cas de deadlock, le job échouera et pourra être relancé.

---

**Runbook en vigueur depuis le Sprint 4 (2025-12-14)**




