# Variables d'environnement — Payments Hub v1.1

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Étape 2

---

## 📋 Variables à ajouter dans `.env`

Ajoutez ces variables dans votre fichier `.env` (non sensibles, uniquement configuration de rétention) :

```env
# Payments Hub - Politique de rétention
PAYMENTS_EVENTS_RETENTION_DAYS=90
PAYMENTS_EVENTS_KEEP_FAILED=true
PAYMENTS_AUDIT_LOGS_RETENTION_DAYS=365
PAYMENTS_TRANSACTIONS_RETENTION_YEARS=unlimited
PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED=false
```

---

## 📝 Description des variables

### `PAYMENTS_EVENTS_RETENTION_DAYS`
- **Défaut :** `90`
- **Description :** Nombre de jours de conservation des événements webhook/callback avant purge
- **Utilisé par :** Commande `payments:prune-events`

### `PAYMENTS_EVENTS_KEEP_FAILED`
- **Défaut :** `true`
- **Description :** Conserver les événements `failed` au-delà de la période de rétention (pour analyse)
- **Utilisé par :** Commande `payments:prune-events`

### `PAYMENTS_AUDIT_LOGS_RETENTION_DAYS`
- **Défaut :** `365`
- **Description :** Nombre de jours de conservation des logs d'audit avant purge
- **Utilisé par :** Commande `payments:prune-audit-logs`

### `PAYMENTS_TRANSACTIONS_RETENTION_YEARS`
- **Défaut :** `unlimited`
- **Description :** Politique de rétention des transactions (conservation totale en v1.1)
- **Note :** Non utilisé en v1.1, préparé pour futures versions

### `PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED`
- **Défaut :** `false`
- **Description :** Activer l'archivage automatique des transactions (non implémenté en v1.1)
- **Note :** Non utilisé en v1.1, préparé pour futures versions

---

## ✅ Vérification après ajout

```bash
# Vider le cache de configuration
php artisan config:clear
php artisan cache:clear

# Vérifier que les variables sont chargées
php artisan tinker
>>> config('payments.events.retention_days')
>>> config('payments.events.keep_failed')
>>> config('payments.audit_logs.retention_days')
```

---

**Document créé le :** 2025-12-14




