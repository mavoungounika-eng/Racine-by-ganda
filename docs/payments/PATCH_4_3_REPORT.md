# 🔧 PATCH 4.3 — Observabilité + UI Admin Stuck Events + Requeue Sécurisé

**Date :** 2025-12-15  
**Statut :** ✅ TERMINÉ  
**Objectif :** ajouter observabilité admin + page "stuck webhooks" + requeue auditée + scheduler.

---

## 1) Résumé des livrables

### 1.1 Observabilité (dashboard admin)
- Service `WebhookObservabilityService` : agrégation counts par provider + status + stuck + last_event_at
- Cache court (60s)
- Intégration sur `/admin/payments` (Bootstrap 4)

### 1.2 Page Admin "Stuck Webhooks"
- `/admin/payments/webhooks/stuck`
- Filtres provider/status/minutes/q/dates
- Table normalisée (stripe + monetbil)
- Actions :
  - requeue unitaire (modal reason)
  - requeue bulk (modal reason)
- RBAC strict :
  - view : `payments.view`
  - requeue : `payments.reprocess`
- Aucun payload affiché

### 1.3 Requeue sécurisé
- Atomic claim (UPDATE conditionnel) pour éviter double-dispatch
- **Garde-fou anti-boucle** : max 5 requeue/heure par event (via `requeue_count` et `last_requeue_at`)
- Audit log obligatoire :
  - action = `reprocess`
  - reason obligatoire (min 5)
  - target_type/target_id + diff (mode, threshold, requeue_count)
- Colonnes ajoutées : `requeue_count` (unsigned int, default 0), `last_requeue_at` (timestamp nullable)

### 1.4 Scheduler (auto-requeue)
- Piloté par config/env :
  - `PAYMENTS_STUCK_REQUEUE_ENABLED`
  - `PAYMENTS_STUCK_REQUEUE_MINUTES`
  - fréquence (par défaut everyFiveMinutes)
- `withoutOverlapping()` + `onOneServer()`

### 1.5 Documentation
- Runbook : `INCIDENT_RUNBOOK_WEBHOOKS.md`
- Anti-stuck : `ANTI_STUCK_WEBHOOKS.md` (scheduler + env)

---

## 2) Définition "stuck"
Un event est "stuck" si :
- status ∈ {`received`, `failed`}
- ET (`dispatched_at` IS NULL
  OU (`failed` ET `dispatched_at` < now - threshold_minutes))
- ET (`requeue_count` < 5 OU `last_requeue_at` <= now - 1 heure) (garde-fou anti-boucle)

---

## 3) Tests attendus
- RBAC (403/200)
- Validation reason obligatoire
- Dispatch via atomic claim (Bus::fake, assertDispatchedOnce)
- Audit log créé (action/target/reason)
- Observability service cohérent (counts, stuck_counts)
- Non-régression : endpoints, security tests, idempotence tests
