# Audit POS — Racine by Ganda

Date: 2026-03-13

**Périmètre**
Audit en lecture seule du module POS dans `/home/nika/projects/racine-backend`.

**Sources clés consultées**
- Contrôleurs POS: `app/Http/Controllers/Pos/PosSessionController.php`, `app/Http/Controllers/Pos/PosSaleController.php`, `app/Http/Controllers/Pos/PosPaymentController.php`, `app/Http/Controllers/Pos/PosOfflineStatusController.php`
- Admin POS: `app/Http/Controllers/Admin/PosController.php`, `app/Http/Controllers/Admin/PosAnalyticsController.php`
- Modèles POS: `app/Models/PosSession.php`, `app/Models/PosSale.php`, `app/Models/PosPayment.php`, `app/Models/PosCashMovement.php`, `app/Models/PosOperatorAuditLog.php`
- Services POS: `app/Services/Pos/PosSessionService.php`, `app/Services/Pos/PosSaleService.php`, `app/Services/Pos/PosOfflineService.php`, `app/Services/Pos/PosReportsService.php`, `app/Services/Pos/PosFinanceIntegrationService.php`
- Migrations POS: `database/migrations/2026_01_06_000001_create_pos_sessions_table.php`, `database/migrations/2026_01_06_000002_create_pos_sales_table.php`, `database/migrations/2026_01_06_000003_create_pos_payments_table.php`, `database/migrations/2026_01_06_000004_create_pos_cash_movements_table.php`, `database/migrations/2026_01_28_000002_create_pos_operator_audit_logs_table.php`, `database/migrations/2026_02_25_094206_create_pos_offline_queue_table.php`, `database/migrations/2026_03_01_214150_add_unique_active_constraint_to_pos_sessions.php`
- POS Sync Module: `modules/POSSync/Http/Controllers/SyncGatewayController.php`, `modules/POSSync/Services/DeviceAuthService.php`, `modules/POSSync/Services/EventDispatcher.php`, `modules/POSSync/Services/IdempotenceService.php`, `modules/POSSync/Models/PosDevice.php`, `modules/POSSync/Models/PosSyncedEvent.php`, `modules/POSSync/Models/PosSyncLog.php`, `modules/POSSync/routes/api.php`, `modules/POSSync/database/migrations/2025_12_25_000001_create_pos_synced_events_table.php`, `modules/POSSync/database/migrations/2025_12_25_000002_create_pos_devices_table.php`, `modules/POSSync/database/migrations/2025_12_25_000003_create_pos_sync_logs_table.php`, `modules/POSSync/config/pos.php`
- Frontend admin POS: `resources/views/admin/pos/index.blade.php`, `resources/views/admin/pos/analytics.blade.php`
- Tests POS: `tests/Feature/Pos/PosSessionLifecycleTest.php`, `tests/Feature/Pos/PosDoubleClosureTest.php`, `tests/Feature/Pos/PosInvariantsTest.php`, `tests/Feature/Pos/PosOfflineSyncTest.php`, `tests/Feature/Pos/PosSettlementWithoutAccountingBootstrapTest.php`, `tests/Feature/Pos/PosSimulationCompleteTest.php`, `tests/Feature/Pos/PosSyncRoutingTest.php`, `tests/Feature/Pos/PosValidationTest.php`

## 1) POS Feature Scope (ce que fait le POS)
Le module POS gère la vente en boutique physique avec sessions de caisse, ventes multi-modes (cash, carte, mobile money), réconciliation cash, auditabilité, et synchronisation offline/online.

Fonctionnalités principales identifiées:
- Ouverture et clôture de sessions de caisse avec contrôle de cash.
- Création de ventes POS et paiements associés.
- Confirmation des paiements carte et mobile money.
- Réconciliation de fin de session et génération de rapport Z.
- Journal d’audit opérateur (actions critiques). 
- Offline queue + sync (POSSync) avec idempotence et signature HMAC.
- Dashboard admin POS + analytics.

## 2) Data Model (tables + relations)
Tables POS principales:
- `pos_sessions`: session par machine et opérateur, statut `open/closing/closed`, cash d’ouverture, cash attendu, cash réel, écart.
- `pos_sales`: ventes liées à `pos_sessions`, paiement `cash/card/mobile_money`, statut `pending/confirmed/cancelled`, total, idempotence UUID.
- `pos_payments`: paiements liés à `pos_sales`, statut `pending/confirmed/cancelled`, type `cash/card/mobile_money`, traçabilité transaction.
- `pos_cash_movements`: mouvements cash liés à `pos_sessions` et `pos_sales` (opening, sale, refund, adjustment, closing).
- `pos_operator_audit_logs`: journal d’audit pour actions opérateurs.
- `pos_offline_queue`: file d’attente locale pour ventes offline.

Tables POSSync:
- `pos_devices`: inventaire des terminaux POS, secrets HMAC, statuts.
- `pos_synced_events`: événements synchronisés signés, idempotence (machine_id + event_uuid), versionnage.
- `pos_sync_logs`: journaux de synchronisation et décisions (rejet, fraude, conflit).

Relations clés (déduites des migrations):
- `pos_sessions` 1—N `pos_sales`
- `pos_sales` 1—1 `pos_payments` (dans le flux actuel, un paiement par vente)
- `pos_sessions` 1—N `pos_cash_movements`
- `pos_sales` 1—N `pos_cash_movements` (cash seulement)

## 3) Architecture Controller/Service
Contrôleurs POS:
- `PosSessionController`: ouverture/fermeture session, pré-fermeture, rapport Z, ajustements cash.
- `PosSaleController`: création et annulation de ventes, listing des ventes par session.
- `PosPaymentController`: confirmation carte, webhook mobile money, statut paiement.
- `PosOfflineStatusController`: état offline et taille de queue.

Services POS:
- `PosSessionService`: logique session, verrouillage, audit, calcul cash attendu, création intents comptables.
- `PosSaleService`: création ventes, validation, idempotence, événements de paiement confirmé.
- `PosOfflineService`: gestion offline/online + queue DB.
- `PosReportsService`: analytics et rapports POS.
- `PosFinanceIntegrationService`: création d’intents financiers pour la comptabilité.

POSSync module:
- `SyncGatewayController`: API /api/pos/*, auth JWT device, signature HMAC, idempotence.
- `DeviceAuthService`: JWT + blacklist Redis.
- `EventDispatcher`: routage d’événements (ex: `PosSaleCreated` -> job).
- `IdempotenceService`: idempotence Redis + DB.

## 4) Offline Capability
Capacité offline détectée et structurée:
- Table `pos_offline_queue` pour buffer offline.
- Service `PosOfflineService` pour marquer offline/online et queue des ventes.
- Endpoint `/pos/offline-status` pour monitoring local.
- POSSync permet la ré-ingestion avec idempotence et signature HMAC.

Limite observée:
- Pas d’UI dédiée offline côté POS web (seulement backend + queue). 

## 5) Payment Flow (cash + card + mobile)
Flux cash:
- Vente créée `pending`.
- Mouvement cash créé immédiatement (sale).
- Settlement final à la clôture de session avec création d’intent financier.

Flux carte:
- Vente `pending`.
- Confirmation via `PosPaymentController::confirmCardPayment`.
- Événement `PosCardPaymentConfirmed` pour intégration comptable.

Flux mobile money:
- Webhook `PosPaymentController::mobileMoneyWebhook` avec HMAC via `MobileMoneyPaymentService`.
- Événement `PosMobilePaymentConfirmed` déclenché après validation.

Points de vigilance:
- Les paiements non-cash restent `pending` jusqu’à confirmation explicite.
- L’intégration POS ? comptabilité est volontairement différée (anti-fraude / idempotence).

## 6) Session Management
Gestion de session robuste:
- Unicité d’une session active par utilisateur via contrainte DB.
- Fermeture contrôlée avec audit et rapport Z.
- Détection d’écart cash et création d’événement `CashDiscrepancyDetected`.
- Empêche double clôture (test dédié).

## 7) Sync Mechanism (POSSync)
Le module POSSync expose une API sécurisée:
- `/api/pos/register` pour enrollment device.
- `/api/pos/sync` pour batch d’événements signés.
- `/api/pos/auth/refresh` pour refresh JWT.
- `/api/pos/status` pour status (JWT requis).

Sécurités:
- JWT device + blacklist Redis.
- Signature HMAC par device.
- Idempotence machine_id + event_uuid.
- Logging et journaux de synchronisation.

## 8) Test Coverage (POS)
Tests POS présents en Feature:
- Lifecycle session, double clôture, invariants, offline sync, simulation complète, sync routing, validation métier.

Indicateurs qualité:
- Utilisation de `RefreshDatabase`.
- Tests d’invariants POS critiques et idempotence.

Observation:
- Les fichiers de tests montrent un encodage texte corrompu (accents affichés en mojibake), à corriger à terme.

## 9) Gaps / Risques identifiés
- UI POS principale côté admin est Blade simple, pas de front POS dédié (pas de SPA POS).
- Flux carte/mobile nécessitent confirmation explicite, risque de ventes `pending` si callbacks non reçus.
- Offline queue côté backend existe, mais pas de preuve d’un client POS offline complet (cache local côté terminal).
- Tests unitaires POS absents, tout est en Feature; tests d’intégration bien présents.

## 10) Maturité POS
Évaluation: **FUNCTIONAL**

Justification:
- Couverture fonctionnelle solide (sessions, paiements, audit, sync, offline queue).
- Sécurités et idempotence présentes.
- Manque d’interface POS dédiée robuste et d’implémentation offline client complet.
- Flux non-cash dépendants de callbacks externes.

## Annexes — Points techniques clés
- POS est conçu pour ne pas être source comptable: création d’intents financiers lors du settlement, pas d’écriture directe.
- Idempotence enforce côté POSSync et POS (UUID de vente + clés uniques).
- Audit trail opérateur présent au niveau POS.
