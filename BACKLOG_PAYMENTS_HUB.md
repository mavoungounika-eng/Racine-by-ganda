# 📋 BACKLOG EXÉCUTABLE — PAYMENTS HUB ADMIN (v1.1)

## RACINE BY GANDA — Laravel 12 (E-commerce + ERP)

**Date de création :** 2025-12-14
**Statut :** Prêt pour exécution
**Format :** Compatible Jira / Notion / Trello / Linear
**Périmètre V1.1 :** Stripe + Monetbil (extensible)

---

## 🎯 VUE D'ENSEMBLE

Ce backlog contient **10 phases** et **50+ tickets** pour construire un **Payments Hub Admin** conforme production : pilotage, fiabilité, sécurité.

### Principes non négociables

* **Aucun secret en clair** (UI, logs, exceptions, exports). Secrets dans `.env` / secrets manager.
* Admin affiche uniquement **Config OK/KO** (présence des variables, jamais les valeurs).
* **Source of truth : `payment_transactions` + `orders`** (webhook/callback serveur uniquement).
* **Persist event d'abord** (idempotence), puis **dispatch Job**, réponse HTTP rapide.
* Jobs **idempotents**, avec **locks** + **tries/backoff/timeout**.
* Reprocess **contrôlé** (permission + rate limit + audit + reason obligatoire).
* `payment_routing_rules` utilise **FK bigint** (`provider_id`) et non FK string sur `code`.

---

## ✅ DEFINITION OF DONE (DoD) GLOBALE — Payments Hub v1.1

Un ticket est "DONE" uniquement si :

* ✅ Tests liés au ticket (unit/feature) passent en CI/local
* ✅ Aucun secret n'apparaît en clair (UI, logs, exceptions, exports)
* ✅ PayloadRedactionService appliqué sur toutes vues/exports concernés
* ✅ Export CSV validé anti "CSV injection" (=, +, -, @)
* ✅ Reprocess protégé : permission + throttle + reason obligatoire + audit log
* ✅ Jobs observables : en cas d'échec, `failed_jobs` exploitable et procédure documentée
* ✅ Performance : listes paginées, requêtes indexées, pas de N+1 (vérifié via debugbar/logs)

---

## 🔁 NAMING / CONVENTIONS (appliqué partout)

* **Controllers :** `App\Http\Controllers\Admin\Payments\*`
* **Views :** `resources/views/admin/payments/*`
* **Routes :** préfixe `/admin`, noms `admin.payments.*`
* **ACL :** `payments.view`, `payments.config`, `payments.reprocess`, `payments.refund`

---

# PHASE 1 — PRÉPARATION (AUDIT TECHNIQUE)

## TICKET #PH1-001 : Audit de l'existant — Routes et Layout Admin

**Type :** Tâche
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 1

### Objectif

Documenter l'état actuel de l'admin et verrouiller les conventions (routes, layout, menu, CSS).

### Actions

1. Lister routes admin actuelles et leurs middlewares
2. Identifier layout admin principal et menu (fichier(s) exacts)
3. Identifier framework CSS (Bootstrap 4 / Tailwind / mix)
4. Identifier conventions de noms de routes (admin.* ou non)
5. Créer `docs/payments/ADMIN_EXISTING_STRUCTURE.md`

### Fichiers à examiner

* `routes/web.php` et/ou `routes/admin.php`
* `resources/views/**/admin*.blade.php` (layouts, menu)
* `app/Http/Controllers/Admin/*`

### Livrable

Doc markdown clair et exploitable.

### Critères d'acceptation

* ✅ Routes admin documentées
* ✅ Layout/menu identifiés
* ✅ Framework UI confirmé
* ✅ Convention de nommage validée

---

## TICKET #PH1-002 : Audit RBAC — Rôles, permissions, middleware

**Type :** Tâche
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Comprendre et documenter le système RBAC existant (Spatie ou custom) pour intégrer Payments Hub sans casser l'existant.

### Actions

1. Vérifier Spatie Permission (présence tables/traits) ou Gates/Policies custom
2. Lister rôles existants (super_admin, admin, staff, finance, etc.)
3. Lister middleware d'accès admin
4. Documenter dans `docs/payments/RBAC_EXISTING.md`

### Fichiers à examiner

* `app/Providers/AuthServiceProvider.php`
* `app/Models/User.php`
* `app/Http/Middleware/*`

### Livrable

Doc RBAC existant + recommandations d'intégration.

### Critères d'acceptation

* ✅ Système RBAC identifié (Spatie ou custom)
* ✅ Rôles existants listés
* ✅ Points d'intégration documentés

---

## TICKET #PH1-003 : Audit DB — Schéma paiements existant

**Type :** Tâche
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 1

### Objectif

Cartographier tables et modèles paiements existants pour éviter doublons et incohérences.

### Actions

1. Examiner `payment_transactions` (champs, index, contraintes)
2. Examiner `stripe_webhook_events` (idempotence, status, error)
3. Examiner table legacy éventuelle `payments` (si existe)
4. Documenter relations avec `orders`
5. Créer `docs/payments/DB_SCHEMA_EXISTING.md`

### Fichiers à examiner

* migrations paiement existantes
* `app/Models/*` liés aux paiements
* `app/Models/Order.php` et relations

### Livrable

Schéma DB + champs + relations + index + statuts.

### Critères d'acceptation

* ✅ `payment_transactions` documentée
* ✅ `stripe_webhook_events` documentée
* ✅ relation avec `orders` confirmée
* ✅ table legacy identifiée (ou absence confirmée)

---

## TICKET #PH1-004 : Standardisation statuts paiement (enum)

**Type :** Tâche technique
**Priorité :** Moyenne
**Estimation :** 3h
**Sprint :** Sprint 1

### Objectif

Normaliser les statuts pour reporting et traitement.

### Statuts cible

* `pending`, `processing`, `succeeded`, `failed`, `canceled`, `refunded`

### Actions

1. Analyser statuts réellement utilisés dans le code
2. Créer enum `PaymentStatus` (ou équivalent)
3. Aligner `payment_transactions.status` sur l'enum
4. Mettre à jour services existants (card/mobile money)
5. Mettre à jour tests existants si nécessaire

### Livrable

Enum + alignement code + migrations si besoin.

### Critères d'acceptation

* ✅ Statuts uniques et cohérents
* ✅ Aucune régression checkout
* ✅ Tests passent

---

## TICKET #PH1-005 : Décision "Source of truth" (Payment vs PaymentTransaction)

**Type :** Décision technique
**Priorité :** Critique
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Verrouiller la règle "une seule vérité" en production.

### Actions

1. Confirmer `payment_transactions` = vérité métier (avec `orders`)
2. Définir le statut de la table legacy `payments` (legacy / déprécier / vue)
3. Documenter dans `docs/payments/SOURCE_OF_TRUTH.md`
4. Ajouter un "do/don't" clair pour devs (où écrire / où lire)

### Critères d'acceptation

* ✅ Une règle unique validée
* ✅ Appliquée dans jobs, services, UI

---

# PHASE 2 — FONDATIONS DB + SEEDERS (V1.1)

## TICKET #PH2-001 : Migration — `payment_providers`

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Table de pilotage providers (non sensible).

### Colonnes (exigences)

* `id` bigint
* `code` unique (`stripe`, `monetbil`)
* `name`
* `is_enabled` bool
* `priority` int
* `currency` (XAF)
* `health_status` (`ok|degraded|down`)
* `last_health_at`, `last_event_at`, `last_event_status`
* `meta` json (non sensible)
* timestamps
* indexes : `code`, `is_enabled`, `health_status`

### Critères d'acceptation

* ✅ Migration OK
* ✅ Index OK
* ✅ Migrate sans erreur

---

## TICKET #PH2-002 : Migration — `payment_routing_rules` (FK bigint)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Routage par canal/devise/pays avec **FK bigint**.

### Colonnes (exigences)

* `id` bigint
* `channel` (card, mobile_money, …)
* `currency` nullable
* `country` nullable
* `primary_provider_id` FK -> `payment_providers.id`
* `fallback_provider_id` FK nullable -> `payment_providers.id`
* `is_active` bool
* `priority` int (ordre d'évaluation)
* timestamps
* indexes : `channel`, `currency`, `country`, `is_active`, `priority`

### Critères d'acceptation

* ✅ FK bigint en place
* ✅ Index OK
* ✅ Migrate sans erreur

---

## TICKET #PH2-003 : Migration — `monetbil_callback_events`

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Table events Monetbil équivalente Stripe.

### Colonnes (exigences)

* `event_key` unique (hash stable)
* `payment_ref`, `transaction_id`, `transaction_uuid` nullable
* `event_type` nullable
* `status` (`received|processed|ignored|failed`)
* `payload` json
* `error` text nullable
* `received_at`, `processed_at`
* indexes : `event_key`, `status`, `received_at`, `transaction_id`

### Critères d'acceptation

* ✅ Unique event_key
* ✅ Index OK
* ✅ Migrate OK

---

## TICKET #PH2-004 : Migration — `payment_audit_logs`

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Traçabilité admin obligatoire.

### Actions

* Table audit : user_id, action, target_type, target_id, diff, reason, ip, user_agent, timestamps
* Index : action, target, user_id, created_at

### Critères d'acceptation

* ✅ Toutes actions sensibles loggées ensuite en phases 4/8/9

---

## TICKET #PH2-005 : Modèles Eloquent (providers/routing/events/audit)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 1

### Objectif

Modèles cohérents avec FK bigint.

### Modèles

* `PaymentProvider`
* `PaymentRoutingRule` (belongsTo primary/fallback via *_provider_id)
* `MonetbilCallbackEvent`
* `PaymentAuditLog`

### Critères d'acceptation

* ✅ Relations correctes (FK bigint)
* ✅ Scopes utiles (`active`, `enabled`, `healthy`)
* ✅ Tests unitaires basiques passent

---

## TICKET #PH2-006 : Seeder — Providers (Stripe, Monetbil)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Seeder providers par défaut.

### Critères d'acceptation

* ✅ Stripe/Monetbil créés
* ✅ Priorités par défaut définies
* ✅ Seed exécuté via DatabaseSeeder

---

## TICKET #PH2-007 : Seeder — Règles de routage par défaut (FK bigint)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Créer règles de base :

* card -> Stripe (primary_provider_id)
* mobile_money -> Monetbil (primary_provider_id)

### Critères d'acceptation

* ✅ Règles insérées avec FK bigint
* ✅ is_active=true

---

## TICKET #PH2-008 : Index + rétention / purge events (Stripe/Monetbil)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 1

### Objectif

Éviter croissance infinie des payloads.

### Actions

1. Vérifier/ajouter indexes utiles sur events + transactions
2. Ajouter commande `payments:prune-events` (purge/archivage au-delà de X jours)
3. Ajouter scheduler (daily) + config de durée (30/90 jours)
4. Documenter la politique dans `docs/payments/RETENTION_POLICY.md`

### Critères d'acceptation

* ✅ Purge paramétrable
* ✅ Scheduler prêt
* ✅ Tests basiques (commande)

---

## TICKET #PH2-009 : Politique de rétention — `payment_transactions`

**Type :** Décision technique
**Priorité :** Moyenne
**Estimation :** 1h
**Sprint :** Sprint 1

### Objectif

Décider si `payment_transactions` est conservée intégralement ou archivée après X mois.

### Actions

1. Définir la politique : conservation totale OU archivage après X mois
2. Documenter la politique dans `docs/payments/RETENTION_POLICY.md` (section transactions)
3. Si archivage : définir mécanisme (table archive / export / anonymisation) — sans implémentation immédiate si hors scope v1.1

### Critères d'acceptation

* ✅ Politique validée et écrite
* ✅ Cohérence avec conformité interne (si applicable)

---

# PHASE 3 — RBAC + NAVIGATION ADMIN

## TICKET #PH3-001 : RBAC — Définir permissions Payments Hub

**Type :** Tâche technique
**Priorité :** Critique
**Estimation :** 2h
**Sprint :** Sprint 2

### Objectif

Créer permissions fines :

* `payments.view`
* `payments.config`
* `payments.reprocess`
* `payments.refund`

### Actions

* Si Spatie : créer permissions + assign aux rôles
* Sinon : Gates dans AuthServiceProvider + mapping rôles
* Documenter mapping dans `docs/payments/RBAC_PAYMENTS.md`

### Critères d'acceptation

* ✅ Permissions effectives sur routes et UI
* ✅ 403 systématique si non autorisé

---

## TICKET #PH3-002 : Navigation Admin — Menu "Paiements" + sous-menus

**Type :** Tâche UI
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 2

### Sous-menus

* Overview
* Providers
* Transactions
* Webhooks
* Routing
* (plus tard) Incidents

### Critères d'acceptation

* ✅ Menu visible uniquement si `payments.view`
* ✅ Sous-menus cohérents routes `admin.payments.*`

---

## TICKET #PH3-003 : Tests RBAC complets (menu + routes + actions)

**Type :** Test
**Priorité :** Critique
**Estimation :** 2h
**Sprint :** Sprint 2

### Actions

* Tester accès pages + actions (toggle provider, export CSV, reprocess, refund si activé)
* Vérifier menu non visible si non autorisé

### Critères d'acceptation

* ✅ Couverture routes critiques
* ✅ 403/redirect corrects

---

# PHASE 4 — UI ADMIN V1 (MONITORING)

## TICKET #PH4-001 : Controller — Dashboard Payments Hub

**Type :** Backend
**Priorité :** Haute
**Estimation :** 3h
**Sprint :** Sprint 2

**Dépendances :** `#PH1-003`, `#PH1-005`, `#PH2-001`, `#PH2-005`

### Objectif

Overview `/admin/payments` : KPIs + santé providers + derniers événements.

### KPIs minimaux

* total, succeeded, failed, pending, success rate
* montant total, panier moyen (si order liée)
* temps moyen confirmation (si données disponibles)

### Critères d'acceptation

* ✅ KPIs corrects
* ✅ Requêtes optimisées (<500ms cible)
* ✅ ACL active

---

## TICKET #PH4-002 : View — `/admin/payments` (overview)

**Type :** UI
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 2

### Objectif

Vue cohérente admin : cards KPIs, table santé providers, derniers events (Stripe + Monetbil).

### Critères d'acceptation

* ✅ Responsive
* ✅ Pas de secrets
* ✅ Design cohérent

---

## TICKET #PH4-003 : Controller — Providers (liste + update)

**Type :** Backend
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 2

### Actions

* Liste providers
* Update : `is_enabled`, `priority`, `currency`
* Audit log obligatoire sur chaque action
* ACL : `payments.config`

### Critères d'acceptation

* ✅ Toggle ON/OFF fonctionne
* ✅ Audit créé
* ✅ ACL OK

---

## TICKET #PH4-004 : View — Providers

**Type :** UI
**Priorité :** Haute
**Estimation :** 3h
**Sprint :** Sprint 2

### Exigences UI

* Santé (badge)
* Config OK/KO (service dédié)
* Dernier event
* Actions : toggle, edit, health-check (phase 7)

### Critères d'acceptation

* ✅ Pas de secrets
* ✅ Toggle + edit OK

---

## TICKET #PH4-005 : Controller — Transactions (liste + détail)

**Type :** Backend
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 3

**Dépendances :** `#PH1-004`, `#PH1-005`, `#PH2-005`, `#PH4-010`

### Objectif

Lister `payment_transactions` (source of truth) + show détail + timeline events.

### Filtres

provider, status, date range, amount min/max, order_id, payment_ref

### Critères d'acceptation

* ✅ Filtres fonctionnels
* ✅ Pagination
* ✅ Détail complet

---

## TICKET #PH4-006 : View — Transactions (liste)

**Type :** UI
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 3

### Critères d'acceptation

* ✅ Filtres UI + reset
* ✅ Pagination
* ✅ Lien order (si route admin orders existe)

---

## TICKET #PH4-007 : View — Transaction détail + timeline

**Type :** UI
**Priorité :** Moyenne
**Estimation :** 3h
**Sprint :** Sprint 3

### Exigences

* Timeline Stripe/Monetbil events associés
* Payload affiché **redacted**
* Boutons futurs : refund (phase 9), verify (option)

### Critères d'acceptation

* ✅ Timeline lisible
* ✅ Redaction active

---

## TICKET #PH4-008 : Controller — Webhooks/Callbacks monitor

**Type :** Backend
**Priorité :** Haute
**Estimation :** 3h
**Sprint :** Sprint 3

### Objectif

Liste + détails :

* Stripe via `stripe_webhook_events`
* Monetbil via `monetbil_callback_events`

### Critères d'acceptation

* ✅ Onglets + filtres + pagination
* ✅ Détail event affiche payload redacted

---

## TICKET #PH4-009 : View — Webhooks/Callbacks

**Type :** UI
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 3

### Critères d'acceptation

* ✅ Onglets Stripe/Monetbil
* ✅ Filtres
* ✅ Détails payload redacted

---

## TICKET #PH4-010 : Service — PayloadRedactionService (critique)

**Type :** Technique
**Priorité :** Critique
**Estimation :** 2h
**Sprint :** Sprint 3

### Objectif

Redacter systématiquement clés/tokens/secrets.

### Règles minimales

Masquer valeurs de champs contenant : `secret`, `key`, `token`, `password`, et patterns `sk_`, `whsec_`, etc.

### Critères d'acceptation

* ✅ Tests unitaires
* ✅ Utilisé dans webhooks + transaction detail + exports si applicable

---

## TICKET #PH4-011 : Export CSV sécurisé (anti CSV injection)

**Type :** Technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 3

### Objectif

Éviter injection Excel.

### Actions

* Échapper cellules commençant par `= + - @` (préfixer `'`)
* Tester export

### Critères d'acceptation

* ✅ Aucune formule injectable dans CSV

---

## TICKET #PH4-012 : ProviderConfigStatusService (Config OK/KO standardisé + cache)

**Type :** Technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 3

### Objectif

Même logique partout, sans fuite de secrets.

### Actions

* Vérifier seulement présence des env vars requises
* Cache 60s pour éviter surcoût
* Retourner message générique (OK/KO + missing keys names éventuellement, sans valeurs)

### Critères d'acceptation

* ✅ Résultat identique sur dashboard + providers + autres pages
* ✅ Aucun secret exposé

---

## TICKET #PH4-013 : Politique de logs (anti-secret) + scrubbing

**Type :** Tâche technique
**Priorité :** Critique
**Estimation :** 2h
**Sprint :** Sprint 3

### Objectif

Interdire toute fuite de secrets dans les logs/app monitoring.

### Actions

1. Définir une règle : aucun payload brut (webhook/callback) ne doit être loggé tel quel
2. Appliquer systématiquement `PayloadRedactionService` avant tout log d'erreur lié paiements
3. Vérifier messages d'exception : pas de dump de headers/signatures/secrets
4. Ajouter un test/contrôle simple (recherche patterns `sk_`, `whsec_`, `token`) dans logs de test si applicable
5. Documenter dans `docs/payments/LOGGING_POLICY.md`

### Livrable

Politique de logs + scrubbing effectif sur erreurs paiements.

### Critères d'acceptation

* ✅ Aucun log n'expose `sk_`, `whsec_`, `token`, `secret`, `password`
* ✅ Les erreurs webhook/callback loggent uniquement des identifiants non sensibles (event_id/event_key)
* ✅ Documentation créée

---

# PHASE 5 — PIPELINE ÉVÉNEMENTS V2 (FIABILITÉ : ASYNC + IDEMPOTENCE)

## TICKET #PH5-001 : Job — ProcessStripeWebhookEventJob (process only)

**Type :** Technique
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 4

### Règle v1.1

**Le controller persiste l'event d'abord**, puis dispatch le job.

### Objectif

Job traite un event existant, idempotent, safe re-run.

### Critères d'acceptation

* ✅ Job idempotent
* ✅ Lock DB / transaction
* ✅ status event -> processed/failed

---

## TICKET #PH5-002 : Job — ProcessMonetbilCallbackEventJob (process only)

**Type :** Technique
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 4

### Critères d'acceptation

* ✅ Même garanties que Stripe
* ✅ event_key unique géré correctement

---

## TICKET #PH5-003 : Service — PaymentEventMapperService (events -> statuts)

**Type :** Technique
**Priorité :** Haute
**Estimation :** 3h
**Sprint :** Sprint 4

### Objectif

Mapper événements Stripe/Monetbil -> `payment_transactions.status` et `orders.status`.

### Critères d'acceptation

* ✅ Mapping stable documenté
* ✅ Tests unitaires

---

## TICKET #PH5-004 : Endpoints webhook/callback — Persist event puis dispatch job

**Type :** Technique
**Priorité :** Critique
**Estimation :** 2h
**Sprint :** Sprint 4

**Dépendances :** `#PH2-003`, `#PH5-001`, `#PH5-002`, `#PH5-005`

### Objectif

* Verify signature/auth
* Insert-first event (idempotence)
* Dispatch job
* Return 200 rapidement

### Critères d'acceptation

* ✅ Endpoint rapide
* ✅ Event persisted même si queue down
* ✅ Pas de traitement lourd synchronous

---

## TICKET #PH5-005 : Queue config + retry/backoff/timeout + supervision

**Type :** Technique
**Priorité :** Critique
**Estimation :** 3h
**Sprint :** Sprint 4

### Actions

* Vérifier `QUEUE_CONNECTION`
* Définir `tries/backoff/timeout` jobs
* Documenter exécution worker (Horizon si présent, sinon `queue:work` + Supervisor)
* Définir stratégie de retry (limites pour éviter boucles infinies)

### Critères d'acceptation

* ✅ Jobs fiables en prod
* ✅ Retries contrôlés

---

## TICKET #PH5-006 : Tests Feature — endpoint webhook/callback : 200 + dispatch + event persisted

**Type :** Test
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 4

### Critères d'acceptation

* ✅ Endpoint ne bloque pas
* ✅ Dispatch effectué
* ✅ Event en DB

---

## TICKET #PH5-007 : Procédure "failed jobs" / dead-letter (ops)

**Type :** Tâche technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 4

### Objectif

Assurer une exploitation production propre des jobs en échec (sans bricolage).

### Actions

1. Définir le standard : utilisation table `failed_jobs` (ou Horizon si présent)
2. Écrire procédure de relance contrôlée (quand relancer, quand ne pas relancer)
3. Ajouter commande interne/documentée pour lister les jobs échoués paiements (filtrage par type si possible)
4. Documenter dans `docs/payments/FAILED_JOBS_RUNBOOK.md`
5. (Option) Ajouter un lien "Ops" dans Payments Hub vers la doc interne

### Livrable

Runbook exploitation + procédure relance.

### Critères d'acceptation

* ✅ Procédure claire (checklist)
* ✅ Méthode de relance documentée
* ✅ Aucun secret dans les erreurs stockées

---

# PHASE 6 — ROUTAGE + FALLBACK

## TICKET #PH6-001 : Contrat — PaymentGatewayInterface

**Type :** Technique
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 5

### Critères d'acceptation

* ✅ Interface stable, documentée
* ✅ Extensible nouveaux providers

---

## TICKET #PH6-002 : Gateway — StripeGateway

**Type :** Technique
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 5

### Critères d'acceptation

* ✅ Initiate + verify + process + healthCheck
* ✅ Réutilise services existants sans duplication

---

## TICKET #PH6-003 : Gateway — MonetbilGateway

**Type :** Technique
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 5

### Critères d'acceptation

* ✅ Initiate + verify + process + healthCheck

---

## TICKET #PH6-004 : PaymentManager (routing + fallback + explainResolution)

**Type :** Technique
**Priorité :** Critique
**Estimation :** 5h
**Sprint :** Sprint 5

### Objectif

Résoudre provider selon règles + état provider + fallback.

### Exigence v1.1

Ajouter `explainResolution()` pour simulateur admin :

* règle matchée
* raisons fallback (disabled/down)
* provider final

### Critères d'acceptation

* ✅ Routage correct
* ✅ Fallback correct
* ✅ Explication claire disponible

---

## TICKET #PH6-005 : Controller Admin — Routing CRUD + simulateur

**Type :** Backend
**Priorité :** Moyenne
**Estimation :** 4h
**Sprint :** Sprint 5

### Critères d'acceptation

* ✅ CRUD complet
* ✅ simulateur basé sur explainResolution
* ✅ audit log sur modifications

---

## TICKET #PH6-006 : View Admin — Routing

**Type :** UI
**Priorité :** Moyenne
**Estimation :** 4h
**Sprint :** Sprint 5

### Critères d'acceptation

* ✅ CRUD utilisable
* ✅ simulateur visible
* ✅ design cohérent

---

# PHASE 7 — HEALTH CHECKS

## TICKET #PH7-001 : HealthCheckService (config + connectivité minimale)

**Type :** Technique
**Priorité :** Moyenne
**Estimation :** 4h
**Sprint :** Sprint 6

### Règles

* Ne jamais exposer secrets
* Retourner statut + message générique + checked_at
* Écrire dans `payment_providers`

### Critères d'acceptation

* ✅ health_status mis à jour
* ✅ aucune fuite secret

---

## TICKET #PH7-002 : Endpoint Admin — Lancer health check

**Type :** Backend
**Priorité :** Moyenne
**Estimation :** 2h
**Sprint :** Sprint 6

### Critères d'acceptation

* ✅ protégé `payments.config`
* ✅ résultat affiché UI

---

# PHASE 8 — REPROCESSING CONTRÔLÉ

## TICKET #PH8-001 : Endpoints reprocess Stripe/Monetbil (contrôles complets)

**Type :** Backend
**Priorité :** Moyenne
**Estimation :** 4h
**Sprint :** Sprint 6

**Dépendances :** `#PH3-001`, `#PH5-001`, `#PH5-002`, `#PH8-003`

### Règles

* permission `payments.reprocess`
* reason obligatoire
* audit log obligatoire
* seulement status `failed|received`
* dispatch job (idempotent)

### Critères d'acceptation

* ✅ reprocess safe
* ✅ audit complet

---

## TICKET #PH8-002 : UI — bouton Reprocess + modal reason

**Type :** UI
**Priorité :** Moyenne
**Estimation :** 2h
**Sprint :** Sprint 6

### Critères d'acceptation

* ✅ bouton visible uniquement si autorisé
* ✅ modal reason obligatoire

---

## TICKET #PH8-003 : Rate limiting dédié reprocess

**Type :** Technique
**Priorité :** Haute
**Estimation :** 1h
**Sprint :** Sprint 6

### Critères d'acceptation

* ✅ throttle actif (ex 10/min)
* ✅ protège contre abus

---

# PHASE 9 — REMBOURSEMENTS (OPTIONNEL)

## TICKET #PH9-001 : RefundService (Stripe)

**Type :** Technique
**Priorité :** Basse
**Estimation :** 3h
**Sprint :** Sprint 7 (optionnel)

### Critères d'acceptation

* ✅ `payments.refund` requis
* ✅ audit log créé
* ✅ statut mis à jour proprement

---

## TICKET #PH9-002 : Endpoint Admin refund

**Type :** Backend
**Priorité :** Basse
**Estimation :** 2h
**Sprint :** Sprint 7 (optionnel)

---

## TICKET #PH9-003 : UI — bouton refund + modal

**Type :** UI
**Priorité :** Basse
**Estimation :** 2h
**Sprint :** Sprint 7 (optionnel)

---

# PHASE 10 — INCIDENTS & ALERTING (OPTIONNEL)

## TICKET #PH10-001 : PaymentIncidentService (détection anomalies)

**Type :** Technique
**Priorité :** Basse
**Estimation :** 4h
**Sprint :** Sprint 8 (optionnel)

---

## TICKET #PH10-002 : Notifications (email/slack/sentry si existant)

**Type :** Technique
**Priorité :** Basse
**Estimation :** 3h
**Sprint :** Sprint 8 (optionnel)

---

## TICKET #PH10-003 : UI Incidents

**Type :** UI
**Priorité :** Basse
**Estimation :** 3h
**Sprint :** Sprint 8 (optionnel)

---

# TESTS & VALIDATION

## TICKET #TEST-001 : Tests Unit — PaymentManager (routing + fallback + explain)

**Type :** Test
**Priorité :** Haute
**Estimation :** 3h
**Sprint :** Sprint 5

---

## TICKET #TEST-002 : Tests Unit — Jobs idempotence + locks

**Type :** Test
**Priorité :** Critique
**Estimation :** 4h
**Sprint :** Sprint 4

---

## TICKET #TEST-003 : Tests Feature — RBAC Payments Hub

**Type :** Test
**Priorité :** Haute
**Estimation :** 2h
**Sprint :** Sprint 2

---

## TICKET #TEST-004 : Tests Feature — Flux complet (checkout -> event -> order update)

**Type :** Test
**Priorité :** Haute
**Estimation :** 4h
**Sprint :** Sprint 6

---

# DOCUMENTATION

## TICKET #DOC-001 : Guide utilisateur admin Payments Hub

**Type :** Documentation
**Priorité :** Moyenne
**Estimation :** 3h
**Sprint :** Sprint 9

---

## TICKET #DOC-002 : Guide technique (ajouter un provider)

**Type :** Documentation
**Priorité :** Moyenne
**Estimation :** 4h
**Sprint :** Sprint 9

---

# 📦 RÉSUMÉ PAR SPRINT (V1.1)

## Sprint 1 — Audit + DB foundations

PH1-001..005, PH2-001..009
**Objectif :** base propre (source of truth, tables, seeders, retention)

## Sprint 2 — RBAC + menu + dashboard + providers

PH3-001..003, PH4-001..004, TEST-003

## Sprint 3 — Transactions + webhooks UI + redaction + export sécurisé + logs

PH4-005..013

## Sprint 4 — Async + jobs + endpoints persist-first + queue + failed jobs

PH5-001..007, TEST-002

## Sprint 5 — Gateways + routing + simulateur

PH6-001..006, TEST-001

## Sprint 6 — Health checks + reprocess contrôlé + E2E

PH7-001..002, PH8-001..003, TEST-004

## Sprint 7 — Refund (optionnel)

## Sprint 8 — Incidents/alerting (optionnel)

## Sprint 9 — Documentation

---

# ✅ PRIORISATION RECOMMANDÉE

* **Critique (Sprints 1–6)** : Phases 1–8 + redaction + queue + retention + RBAC + anti CSV injection
* **Important** : Refund (si besoin opérationnel)
* **Optionnel** : Incidents/alerting (recommandé production mature)

---

**Fin du backlog v1.1**
