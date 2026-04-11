# Racine by Ganda — Instructions Claude Code

## RÈGLE 0 — PLANIFICATION OBLIGATOIRE AVANT TOUTE TÂCHE

Avant de commencer n'importe quelle tâche, affiche TOUJOURS :
ESTIMATION :

Fichiers à lire    : [liste]
Fichiers à écrire  : [liste]
Opérations totales : [N]
Complexité         : SMALL (<5 fichiers) / MEDIUM (5-15) / LARGE (15+)
Contexte suffisant : OUI / NON


Si LARGE ou NON → découpe en sous-tâches numérotées et attends confirmation.
Ne commence JAMAIS sans avoir affiché cette estimation.

---

## RÈGLE 1 — PÉRIMÈTRE FERMÉ

- 1 suite de tests à la fois maximum
- 1 groupe fonctionnel cohérent maximum
- STOP après chaque unité — ne pas enchaîner sans confirmation explicite
- Si une tâche touche plus de 10 fichiers → demander un découpage manuel

---

## RÈGLE 2 — ÉDITION FICHIERS PHP

- Toujours lire le fichier complet avant de modifier
- Fichiers > 200 lignes → lire par tranches de 50 lignes
- Toujours utiliser Python pour les éditions multi-lignes (sed échoue sur UTF-8 WSL)
- Toujours vérifier `php -l FICHIER` après chaque écriture
- Ne jamais fermer une classe prématurément — vérifier les accolades ouvrantes/fermantes
- Ne jamais assumer le contenu d'un fichier sans l'avoir lu

---

## RÈGLE 3 — VÉRIFICATION OBLIGATOIRE APRÈS CHAQUE MODIFICATION

Dans cet ordre exact :
1. `php -l FICHIER_MODIFIE`
2. `./vendor/bin/phpunit --filter NomDeLaSuite --testdox 2>&1 | tail -15`
3. `./vendor/bin/phpunit 2>&1 | tail -3` (vérification régression globale)

Si régression détectée → STOP immédiat, signaler, ne pas continuer.

---

## RÈGLE 4 — FICHIERS PROTÉGÉS (NE JAMAIS MODIFIER)

- tests/Feature/Ai/
- tests/Feature/Pos/
- tests/Feature/Erp/
- tests/Feature/SaaSPur/
- tests/Feature/ERPProduction/
- tests/Feature/Currency/
- tests/Feature/Crm/
- tests/Feature/Auth/LogoutTest.php
- tests/Feature/Auth/LoginRedirectTest.php
- tests/Feature/Auth/DashboardAccessTest.php

---

## RÈGLE 5 — DÉFINITION DE "TERMINÉ"

Une tâche n'est JAMAIS terminée tant que ces critères ne sont pas tous validés :
- [ ] php -l sur tous les fichiers modifiés → 0 erreurs syntaxe
- [ ] Run ciblé PHPUnit → 0 failures, skipped stables ou en baisse
- [ ] Run global PHPUnit → pas de régression vs référence (894 tests, 0 failures)
- [ ] Aucun TODO / markTestSkipped ajouté sans justification écrite
- [ ] Aucun fichier protégé touché
- [ ] Commit proposé avec message conventionnel

Si un seul critère manque → la tâche est EN COURS, pas terminée.

---

## RÈGLE 6 — QUAND ÇA ÉCHOUE

Si une modification casse quelque chose :
1. STOP immédiat — ne pas continuer sur d'autres fichiers
2. Lire l'erreur complète avant de proposer un fix
3. Proposer UNE seule solution, pas plusieurs options
4. Si 2 tentatives échouent → expliquer pourquoi et demander une décision humaine

Ne jamais :
- Supprimer un test pour faire passer le build
- Commenter du code pour masquer une erreur
- Ajouter markTestSkipped sans expliquer la raison exacte
- Enchaîner des fixes sans vérifier chaque étape

---

## RÈGLE 7 — SPÉCIFICITÉS WSL / ENVIRONNEMENT

- Heredocs bash instables → toujours utiliser Python pour écrire des fichiers
- Fichiers > 300 lignes → lire par tranches, ne jamais assumer le contenu
- Si une commande ne retourne rien → WSL peut être bloqué, signaler immédiatement
- Chemin projet : /home/nika/projects/racine-backend
- Ne jamais utiliser wsl.localhost paths dans les commandes bash
- Toujours utiliser des chemins relatifs depuis la racine projet
- Queue : Redis en prod, sync en test (QUEUE_CONNECTION=sync dans .env.testing)

---

## RÈGLE 8 — MÉMOIRE DE SESSION

Au début de chaque session, exécuter et afficher :
```bash
git log --oneline -5
./vendor/bin/phpunit 2>&1 | tail -3
git status --short
cat storage/logs/css-fix/progress.txt 2>/dev/null | grep -v "^#" || echo "Aucune correction CSS en cours"
```

Cela permet de savoir exactement où on en est AVANT de toucher quoi que ce soit.
Ne pas sauter cette étape même si la tâche semble simple.

---

## RÈGLE 9 — COMMITS

Après chaque groupe de tâches terminé (RÈGLE 5 entièrement validée) :
- Proposer un message de commit au format conventionnel :
  - `fix(tests): débloquer AdminDashboardGlobalTest session 2FA`
  - `feat(payments): implémenter validateCaptcha reCAPTCHA v3`
  - `refactor(jobs): ajouter tries/timeout sur 11 jobs/listeners`
- Ne jamais commiter si PHPUnit retourne des failures
- Grouper les fichiers liés dans un seul commit cohérent

---

## RÈGLE 10 — MODIFICATION DU CLAUDE.md

Ne JAMAIS remplacer `CLAUDE.md` entièrement.
Toujours FUSIONNER — ajouter les nouvelles sections sans supprimer le contenu existant.

Procédure :
1. Lire le fichier actuel complet
2. Identifier ce qui manque
3. Ajouter uniquement les sections nouvelles
4. Vérifier que rien n'a été perdu

---

## RÈGLE 11 — GESTION DU CONTEXTE

Quand le contexte approche la limite :
1. STOP sur la tâche en cours
2. Résumer l'état dans ce format exact :

---CHECKPOINT---
Tâche : [nom]
Fichiers modifiés : [liste]
Tests avant : [N failures, N skipped]
Tests après : [N failures, N skipped]
Prochaine étape : [action précise]
Commit à faire : oui/non
---FIN CHECKPOINT---

3. Attendre confirmation avant de continuer

---

## RÈGLE 12 — REPRISE CSS APRÈS RESET DE QUOTA

Au début de chaque session, après lecture de la RÈGLE 8, si `storage/logs/css-fix/progress.txt` existe et contient des batches non marqués DONE :
- Reprendre automatiquement le prochain batch non terminé
- Ne pas redemander confirmation pour les batches déjà marqués DONE
- Ne pas réexpliquer ce qui a déjà été fait

Ordre strict : Batch 1 → 2 → 3 → 4 → 5 → 6

Charte graphique officielle — source de vérité absolue pour tout le CSS :
- Noir   : #160D0C
- Orange : #ED5F1E
- Jaune  : #FFB800
- Blanc  : #FFFFFF
- Polices : Aleppo (titres/logo) · Coco Gothic (texte principal) · Aileron (texte accentué)

Toute couleur hors charte détectée = bug critique à corriger dans le batch en cours.

---

## SKILL — LECTURE FICHIER AVANT MODIFICATION

Pour tout fichier à modifier, suivre cet ordre :
1. `wc -l FICHIER` → connaître la taille totale
2. `head -30 FICHIER` → voir namespace, imports, déclaration de classe
3. `grep -n "function\|class\|TODO\|markTestSkipped" FICHIER` → carte du fichier
4. Lire uniquement les sections concernées par la tâche

Ne jamais modifier sans avoir fait les étapes 1 à 3.

---

## SKILL — DÉBLOQUER UN TEST SKIPPED

Suivre cet ordre exact :
1. Lire le markTestSkipped et comprendre la raison précise
2. Identifier le pattern de fix selon la catégorie :

| Raison du skip | Fix à appliquer |
|---|---|
| Session 2FA manquante | `withSession(["2fa_verified" => true, "auth_version" => $user->auth_version])` |
| Model manquant | Créer model + migration + factory minimal, puis php artisan migrate |
| Route inexistante | Vérifier `php artisan route:list` avant d'asserter quoi que ce soit |
| Architecture obsolète | Réécrire le test selon l'architecture actuelle, documenter le changement |
| SoftDeletes manquant | Adapter le test sans SoftDeletes, ne pas modifier le modèle User |
| Job s'exécute en sync | Ajouter `Queue::fake()` au début du test |
| Permissions insuffisantes | Utiliser `Role::firstOrCreate()` + seed minimal dans setUp() |

3. Appliquer le fix
4. `php -l FICHIER`
5. Run ciblé PHPUnit
6. Run global PHPUnit
7. STOP — ne pas enchaîner sur le test suivant sans confirmation

---

## SKILL — PATTERN D'ÉDITION PYTHON (WSL-SAFE)

Pour remplacer du contenu dans un fichier PHP :
```python
python3 -c "
lines = open('FICHIER.php').readlines()
# Inspecter les lignes cibles
for i, l in enumerate(lines[N-3:N+3], N-2):
    print(i, repr(l))
"

# Puis modifier
python3 -c "
lines = open('FICHIER.php').readlines()
lines[INDEX] = '    nouveau contenu\n'
open('FICHIER.php', 'w').writelines(lines)
print('OK')
"
```

Pour écrire un fichier entier :
```python
python3 << 'PYEOF'
from pathlib import Path
Path('FICHIER.php').write_text('''<?php
// contenu complet ici
''')
print('OK')
PYEOF
```

---

## Présentation du projet

Plateforme e-commerce multi-rôles (marketplace, POS, gestion stock).
Statut : ~95% complet — phase stabilisation et dette technique.
Objectif immédiat : rendre le projet fonctionnel et prêt pour la mise en production publique.

---

## Stack technique

- Backend  : PHP 8.3.6 · Laravel 12 · MySQL 8.0.45 · Redis
- Frontend : Vue 3 · Vite 7 · Bootstrap 5 · Laravel Echo (WebSockets via Reverb)
- POS      : Electron 28 (application de caisse desktop)
- Auth     : Laravel Sanctum · Socialite (OAuth) · pragmarx/google2fa (2FA TOTP)
- Paiements: Stripe PHP SDK v19 · Monetbil (Mobile Money XAF)
- IA       : openai-php/laravel (service Amira) · Anthropic
- Tests    : PHPUnit 11.5.51 · Cypress 15 (E2E)
- Monitoring: Sentry Laravel
- Temps réel: Laravel Reverb · Pusher JS
- Exports  : Maatwebsite Excel
- QR       : bacon/bacon-qr-code · simplesoftwareio/simple-qrcode

---

## Architecture
app/
Http/Controllers/     — contrôleurs Laravel
Models/               — modèles Eloquent
Services/             — logique métier
Notifications/        — notifications mail/queue
modules/                — modules Laravel autonomes (PSR-4: Modules\)
resources/js/           — composants Vue 3
database/
migrations/
seeders/
factories/
tests/
Unit/
Feature/

---

## Conventions de code

- PSR-12 strict (Laravel Pint configuré)
- Composants Vue 3 en Composition API avec script setup
- Nommage : camelCase JS/Vue · snake_case PHP · kebab-case fichiers Vue
- Pas de logique métier dans les contrôleurs — tout passe par les Services
- Toujours valider via Form Requests dédiées
- Utiliser JsonResource pour toutes les réponses JSON

---

## Commandes essentielles
```bash
composer run dev      # Démarrage complet (server + queue + logs + vite)
composer run test     # Tests uniquement
php artisan test      # Alias tests
npm run build         # Build production
./vendor/bin/pint     # Linting PHP
composer run setup    # Setup initial
```

---

## État tests — RÉFÉRENCE (5 avril 2026)
Tests: 895 | Failures: 0 (stable) | Skipped: 19 | Flaky Redis: 0-4 par run

⚠️ Tests flaky Redis : QueueCircuitBreaker et QueueRateLimiter utilisent Redis::
directement. L'état s'accumule entre les tests. Toujours exécuter `redis-cli FLUSHDB`
avant un run de suite complète pour un résultat fiable.

Commande de vérification rapide :
```bash
redis-cli FLUSHDB && ./vendor/bin/phpunit 2>&1 | tail -3
```

Toute régression sur ces chiffres = STOP immédiat avant toute autre action.

---

## Ce qui reste à faire

### Priorité haute
1. EXCHANGE_RATE_API_KEY — obtenir sur exchangerate-api.com (free tier)
2. URLs Monetbil prod — MONETBIL_NOTIFY_URL et MONETBIL_RETURN_URL avec vrai domaine
3. RECAPTCHA_SITE_KEY + RECAPTCHA_SECRET_KEY — Google reCAPTCHA v3

### Priorité moyenne — Code
- Refactorer QueueCircuitBreaker/QueueRateLimiter : utiliser Cache:: au lieu de Redis::
  pour respecter CACHE_STORE=array en tests (fix flaky tests)
- Audit Trail — non implémenté
- Sentry — SENTRY_LARAVEL_DSN à configurer
- CI/CD GitHub Actions — à compléter

### Terminé (ne plus refaire)
- ✅ webhook updatePaymentAndOrder — implémenté dans PaymentEventMapperService
- ✅ POS refund (statut refunded + restauration stock)
- ✅ Audit P1/P2/P3 (paiements, tests, queue, auth_version)
- ✅ Namespace AI jobs (Ai → AI)
- ✅ Frontend CreatorProfile::active()

### Tests skipped légitimes (ne pas forcer)
- CreatorPayoutAccountingTest — architecture SaaS pur, hors scope
- AuthPrivilegeEscalationTest:200 — SoftDeletes non implémenté sur User

---

## Règles de sécurité

- TOUJOURS vérifier les variables d'environnement avant tout test Stripe ou OAuth
- Ne JAMAIS commiter de clés API, secrets ou credentials dans le code
- Les webhooks Stripe doivent être vérifiés avec Stripe\Webhook::constructEvent
- Le service Amira utilise openai-php/laravel — vérifier config/openai.php
- Laravel Reverb gère les WebSockets — ne pas utiliser Pusher en production
- Les modules dans modules/ suivent le namespace Modules\ (PSR-4)

---

## Modules critiques — état actuel

| Module | Statut | Priorité |
|---|---|---|
| Auth 2FA (TOTP) | Tests en échec | CRITIQUE |
| OAuth providers (Socialite) | Tests en échec | CRITIQUE |
| Stripe / paiements | Tests en échec | CRITIQUE |
| Service Amira (IA) | Tests en échec | CRITIQUE |
| POS Electron | À stabiliser | HAUTE |

---

## Informations découvertes par CODEX

> Cette section a été reconstruite par CODEX depuis l'état actuel du dépôt.
> Elle sert de carte rapide du projet pour éviter une ré-exploration complète à chaque session.

### Services détectés dans app/Services/
- app/Services/Action/ActionExecutionService.php
- app/Services/Action/ActionProposalService.php
- app/Services/Ai/AdminAiService.php
- app/Services/Ai/AiService.php
- app/Services/Ai/CreatorChatService.php
- app/Services/Ai/CrmAiService.php
- app/Services/Ai/ErpAiService.php
- app/Services/Ai/ProductAiService.php
- app/Services/Alerts/FinancialAlertService.php
- app/Services/Amira/AmiraKnowledgeBase.php
- app/Services/Amira/AmiraService.php
- app/Services/Amira/ScopeValidator.php
- app/Services/Amira/ToneValidator.php
- app/Services/Analytics/BiMetricsService.php
- app/Services/AnalyticsService.php
- app/Services/AuditService.php
- app/Services/Auth/AuthOrchestratorService.php
- app/Services/Auth/DTO/AuthResult.php
- app/Services/Auth/PostLoginDecisionEngine.php
- app/Services/Auth/RecaptchaService.php
- app/Services/Auth/TwoFactorRecoveryCodeService.php
- app/Services/Auth/UserContextResolver.php
- app/Services/AuthLogger.php
- app/Services/BI/AdminFinancialDashboardService.php
- app/Services/BI/AdvancedKpiService.php
- app/Services/Cart/CartMergerService.php
- app/Services/Cart/DatabaseCartService.php
- app/Services/Cart/SessionCartService.php
- app/Services/Cms/BannerService.php
- app/Services/Cms/CategoryService.php
- app/Services/Cms/ContentBlockService.php
- app/Services/Cms/PageService.php
- app/Services/CmsContentService.php
- app/Services/ConversationService.php
- app/Services/Creator/CreatorTeamService.php
- app/Services/CreatorAddonService.php
- app/Services/CreatorAnalyticsService.php
- app/Services/CreatorBundleService.php
- app/Services/CreatorCapabilityService.php
- app/Services/CreatorKycContractualService.php
- app/Services/CreatorKycService.php
- app/Services/CreatorNotificationService.php
- app/Services/CreatorOrderEventService.php
- app/Services/CreatorScoringService.php
- app/Services/CreatorSubscriptionService.php
- app/Services/Crm/LoyaltyService.php
- app/Services/Crm/SegmentationService.php
- app/Services/Currency/CurrencyService.php
- app/Services/Dashboard/DashboardService.php
- app/Services/Dashboard/Widgets/GlobalStateWidget.php
- app/Services/DashboardCacheService.php
- app/Services/Decision/BaseDecisionService.php
- app/Services/Decision/ChurnPredictionService.php
- app/Services/Decision/CreatorDecisionScoreService.php
- app/Services/Decision/RecommendationEngineService.php
- app/Services/EmailMessagingService.php
- app/Services/Financial/AccountingBootstrapService.php
- app/Services/Financial/AccountingIdempotenceService.php
- app/Services/Financial/FinancialDashboardService.php
- app/Services/Financial/FinancialIntentService.php
- app/Services/Financial/MultiCurrencyService.php
- app/Services/Financial/RiskDetectionService.php
- app/Services/Financial/StrategicMetricsService.php
- app/Services/Financial/SubscriptionOptimizationService.php
- app/Services/InvoiceService.php
- app/Services/LoginAttemptService.php
- app/Services/MessageService.php
- app/Services/Monitoring/AlertService.php
- app/Services/Monitoring/HealthCheckService.php
- app/Services/Monitoring/QueueMonitorService.php
- app/Services/NotificationService.php
- app/Services/OAuthService.php
- app/Services/OrderNumberService.php
- app/Services/OrderService.php
- app/Services/Payments/CardPaymentService.php
- app/Services/Payments/CreatorSubscriptionCheckoutService.php
- app/Services/Payments/CsvExportService.php
- app/Services/Payments/MobileMoneyPaymentService.php
- app/Services/Payments/MonetbilService.php
- app/Services/Payments/PayloadRedactionService.php
- app/Services/Payments/PaymentEventMapperService.php
- app/Services/Payments/PaymentStateMachine.php
- app/Services/Payments/ProviderConfigStatusService.php
- app/Services/Payments/StripeConnectService.php
- app/Services/Payments/WebhookObservabilityService.php
- app/Services/Payments/WebhookRequeueGuard.php
- app/Services/Pos/PosFinanceIntegrationService.php
- app/Services/Pos/PosOfflineService.php
- app/Services/Pos/PosReportsService.php
- app/Services/Pos/PosSaleService.php
- app/Services/Pos/PosSessionService.php
- app/Services/ProductCodeService.php
- app/Services/ProductSearchService.php
- app/Services/Production/ProductionCostingService.php
- app/Services/Production/ProductionService.php
- app/Services/ProfileCompletionService.php
- app/Services/Queue/QueueCircuitBreaker.php
- app/Services/Queue/QueueMonitor.php
- app/Services/Queue/QueueRateLimiter.php
- app/Services/Risk/CreatorRiskAssessmentService.php
- app/Services/SaaSCheckoutService.php
- app/Services/SessionSecurityService.php
- app/Services/SocialAuthService.php
- app/Services/Stock/StockService.php
- app/Services/StockReservationService.php
- app/Services/StockValidationService.php
- app/Services/SubscriptionAnalyticsService.php
- app/Services/TwoFactorService.php
- app/Services/Webhooks/CircuitBreakerService.php
- app/Services/Webhooks/WebhookDeduplicationService.php
- app/Services/Webhooks/WebhookObservabilityService.php
- app/Services/Webhooks/WebhookRetryService.php

### Tests détectés
#### tests/Feature/
- tests/Feature/Accounting/BankReconciliationTest.php
- tests/Feature/Accounting/CreatorPayoutAccountingTest.php
- tests/Feature/Accounting/CreatorPayoutIdempotenceTest.php
- tests/Feature/Accounting/ErpAccountingWiringTest.php
- tests/Feature/Accounting/FinancialReportsTest.php
- tests/Feature/Accounting/IntentBasedAccountingTest.php
- tests/Feature/Accounting/PaymentAccountingIdempotenceTest.php
- tests/Feature/Accounting/PaymentAccountingIntegrationTest.php
- tests/Feature/Accounting/PurchaseAccountingIntegrationTest.php
- tests/Feature/ActionControllerTest.php
- tests/Feature/Admin/DashboardRBACTest.php
- tests/Feature/Admin/MetricsEndpointsTest.php
- tests/Feature/Admin/PerformanceControllerTest.php
- tests/Feature/AdminDashboardGlobalTest.php
- tests/Feature/AdminDashboardPerformanceTest.php
- tests/Feature/AdminFinancialDashboardTest.php
- tests/Feature/AdminWebhookStuckEventsTest.php
- tests/Feature/AdversarialTest.php
- tests/Feature/Ai/AiModuleTest.php
- tests/Feature/AmiraTest.php
- tests/Feature/Audit/AuditComplianceTest.php
- tests/Feature/AuditTrail/AuditServiceTest.php
- tests/Feature/AuditTrail/GlobalAuditObserverTest.php
- tests/Feature/Auth/AuthAccountDisabledTest.php
- tests/Feature/Auth/AuthDynamicRevocationTest.php
- tests/Feature/Auth/AuthEdgeCasesTest.php
- tests/Feature/Auth/AuthPrivilegeEscalationTest.php
- tests/Feature/Auth/AuthSecurityTest.php
- tests/Feature/Auth/ClientHistoryTest.php
- tests/Feature/Auth/DashboardAccessTest.php
- tests/Feature/Auth/LoginClientTest.php
- tests/Feature/Auth/LoginDebugTest.php
- tests/Feature/Auth/LoginDiagnosticTest.php
- tests/Feature/Auth/LoginRedirectTest.php
- tests/Feature/Auth/LoginTest.php
- tests/Feature/Auth/LogoutTest.php
- tests/Feature/Auth/NonRegressionTest.php
- tests/Feature/Auth/OAuthAppleTest.php
- tests/Feature/Auth/OAuthFacebookTest.php
- tests/Feature/Auth/OAuthGoogleClientTest.php
- tests/Feature/Auth/RbacCacheIntegrityTest.php
- tests/Feature/Auth/RedirectionTest.php
- tests/Feature/AuthGlobalTest.php
- tests/Feature/AuthHardeningTest.php
- tests/Feature/AuthSecurityTest.php
- tests/Feature/AuthTest.php
- tests/Feature/CashOnDeliveryTest.php
- tests/Feature/Checkout/CheckoutTimeoutTest.php
- tests/Feature/CheckoutCashOnDeliveryDebugTest.php
- tests/Feature/CheckoutControllerTest.php
- tests/Feature/CircuitBreaker/CircuitBreakerTest.php
- tests/Feature/CmsIntegrationTest.php
- tests/Feature/CmsTest.php
- tests/Feature/Creator/KycAutomationTest.php
- tests/Feature/Creator/StripeConnectTest.php
- tests/Feature/Creator/SubscriptionCheckoutTest.php
- tests/Feature/Crm/CrmSegmentationTest.php
- tests/Feature/Currency/CurrencyTest.php
- tests/Feature/DecisionIntelligenceControllerTest.php
- tests/Feature/ERPProduction/CostingFlowTest.php
- tests/Feature/ERPProduction/ProductionOrderTest.php
- tests/Feature/ERPProduction/QualityControlTest.php
- tests/Feature/ERPProduction/StockFlowTest.php
- tests/Feature/ERPProduction/WipFlowTest.php
- tests/Feature/Erp/StockBroadcastTest.php
- tests/Feature/Erp/StockSyncTest.php
- tests/Feature/ErpGlobalTest.php
- tests/Feature/ErpPerformanceTest.php
- tests/Feature/ExampleTest.php
- tests/Feature/FinancialBIServiceTest.php
- tests/Feature/GoogleAuthTest.php
- tests/Feature/Governance/AccountingIsolationTest.php
- tests/Feature/Governance/GovernanceHardeningTest.php
- tests/Feature/Governance/InvitationFlowTest.php
- tests/Feature/Governance/MultiAccountAccessTest.php
- tests/Feature/Idempotency/IdempotencyTest.php
- tests/Feature/Middleware/EnsureAuthenticatedTest.php
- tests/Feature/Middleware/RecordPerformanceMetricsTest.php
- tests/Feature/MiddlewareSecurityGuardTest.php
- tests/Feature/MonetbilPaymentTest.php
- tests/Feature/Monitoring/HealthCheckTest.php
- tests/Feature/ObservabilityServiceTest.php
- tests/Feature/Order/StockConcurrencyTest.php
- tests/Feature/OrderTest.php
- tests/Feature/PaymentGlobalTest.php
- tests/Feature/PaymentTest.php
- tests/Feature/PaymentWebhookSecurityTest.php
- tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php
- tests/Feature/Payments/OutOfOrderEventsTest.php
- tests/Feature/Payments/PaymentStateConsistencyTest.php
- tests/Feature/Payments/StripeWebhookPaymentMappingTest.php
- tests/Feature/Payments/StripeWebhookPaymentNotFoundTest.php
- tests/Feature/PaymentsHubRbacTest.php
- tests/Feature/Performance/NPlusOneRegressionTest.php
- tests/Feature/Pos/PosAnalyticsTest.php
- tests/Feature/Pos/PosAuditTrailTest.php
- tests/Feature/Pos/PosAuthJsonResponseTest.php
- tests/Feature/Pos/PosCleanupPendingPaymentsTest.php
- tests/Feature/Pos/PosDeviceAuthTest.php
- tests/Feature/Pos/PosDoubleClosureTest.php
- tests/Feature/Pos/PosInvariantsTest.php
- tests/Feature/Pos/PosOfflineQueueApiTest.php
- tests/Feature/Pos/PosOfflineSyncTest.php
- tests/Feature/Pos/PosOperatorAuthTest.php
- tests/Feature/Pos/PosProductCatalogTest.php
- tests/Feature/Pos/PosSessionLifecycleTest.php
- tests/Feature/Pos/PosSettlementWithoutAccountingBootstrapTest.php
- tests/Feature/Pos/PosSimulationCompleteTest.php
- tests/Feature/Pos/PosSyncRoutingTest.php
- tests/Feature/Pos/PosValidationTest.php
- tests/Feature/PrunePaymentAuditLogsCommandTest.php
- tests/Feature/PrunePaymentEventsCommandTest.php
- tests/Feature/Queue/QueueCircuitBreakerTest.php
- tests/Feature/RateLimiting/RateLimitingTest.php
- tests/Feature/SaaSPur/SaasPurInvariantsTest.php
- tests/Feature/Security/ConcurrentCheckoutTest.php
- tests/Feature/Security/PolicyTest.php
- tests/Feature/Security/RouteProtectionTest.php
- tests/Feature/Security/SecurityHardeningTest.php
- tests/Feature/Security/TransactionalRollbackTest.php
- tests/Feature/Security/WebhookIdempotencyTest.php
- tests/Feature/SecurityTest.php
- tests/Feature/StateMachine/PaymentStateMachineTest.php
- tests/Feature/StripeBillingWebhookIntegrationTest.php
- tests/Feature/StripeCheckoutFlowIntegrationTest.php
- tests/Feature/StripeWebhookIdempotencyTest.php
- tests/Feature/StripeWebhookLoadTest.php
- tests/Feature/StripeWebhookRetryAndOrderTest.php
- tests/Feature/WebhookBlockedStatusTest.php
- tests/Feature/WebhookDeduplication/WebhookDeduplicationTest.php
- tests/Feature/WebhookDispatchAtomicityTest.php
- tests/Feature/WebhookEndpointsTest.php
- tests/Feature/WebhookRateLimitingTest.php
- tests/Feature/WebhookRequeueGuardTest.php
- tests/Feature/WebhookRetentionTest.php
- tests/Feature/WebhookSecurityProductionTest.php
- tests/Feature/WebhookSecurityTest.php
- tests/Feature/Webhooks/MonetbilWebhookResilienceTest.php
- tests/Feature/Webhooks/StripeBillingWebhookDeduplicationTest.php
- tests/Feature/Webhooks/WebhookMonitoringTest.php
- tests/Feature/Webhooks/WebhookObservabilityTest.php

#### tests/Unit/
- tests/Unit/Accounting/LedgerServiceTest.php
- tests/Unit/ActionExecutionServiceTest.php
- tests/Unit/ActionProposalServiceTest.php
- tests/Unit/AdminKpiCalculationTest.php
- tests/Unit/AdvancedKpiServiceTest.php
- tests/Unit/AnalyticsServiceTest.php
- tests/Unit/Auth/AuthOrchestratorServiceTest.php
- tests/Unit/Auth/AuthResultTest.php
- tests/Unit/Auth/PostLoginDecisionEngineTest.php
- tests/Unit/Auth/UserContextResolverTest.php
- tests/Unit/Auth/UserContextTest.php
- tests/Unit/BiMetricsGlobalTest.php
- tests/Unit/BiMetricsServiceTest.php
- tests/Unit/ChurnPredictionServiceTest.php
- tests/Unit/CreatorDecisionScoreServiceTest.php
- tests/Unit/CreatorRiskAssessmentServiceTest.php
- tests/Unit/CreatorSubscriptionCheckoutServiceTest.php
- tests/Unit/ERPProduction/BomServiceTest.php
- tests/Unit/ErpStockCalculationTest.php
- tests/Unit/ExampleTest.php
- tests/Unit/FinancialAlertServiceTest.php
- tests/Unit/Models/ProductionOrderImmutabilityTest.php
- tests/Unit/OrderServiceTest.php
- tests/Unit/PaymentJobsIdempotenceTest.php
- tests/Unit/Pos/PosFinanceIntegrationServiceTest.php
- tests/Unit/Pos/PosOfflineServiceTest.php
- tests/Unit/Pos/PosSaleServiceTest.php
- tests/Unit/Pos/PosSessionServiceTest.php
- tests/Unit/ProcessMonetbilCallbackEventJobTest.php
- tests/Unit/ProcessStripeWebhookEventJobTest.php
- tests/Unit/RecommendationEngineServiceTest.php
- tests/Unit/Responses/PosApiResponseTest.php
- tests/Unit/Services/Auth/RecaptchaServiceTest.php
- tests/Unit/Services/Monitoring/AlertServiceTest.php
- tests/Unit/Services/Production/ProductionCostCalculationTest.php
- tests/Unit/Services/Production/ProductionOrderClosureTest.php
- tests/Unit/Services/Queue/QueueCircuitBreakerTest.php
- tests/Unit/Services/Queue/QueueMonitorTest.php
- tests/Unit/Services/Queue/QueueRateLimiterTest.php
- tests/Unit/StockValidationServiceTest.php
- tests/Unit/StripeBillingWebhookControllerTest.php
- tests/Unit/StripeConnectServiceTest.php
- tests/Unit/Support/MetricsRecorderTest.php

### Autres observations
- Le dossier `modules/` est présent à la racine.
- Le code POS desktop est dans `racine-pos-electron/` (dossier séparé à la racine du projet).
- Les fichiers de configuration environnement présents incluent `.env`, `.env.example`, `.env.testing`, `.env.production` et `.env.production.local`.
- Les dépendances sont déjà installées (`vendor/` et `node_modules/` présents).
- Un dossier de sauvegarde de tests est présent : `tests_backup_20260126_120336/`.

### Points à corriger avant mise en production
- Dossier `tests_backup_20260126_120336/` à supprimer ou à ignorer explicitement dans `.gitignore`.
- Vérifier que `.env.production.local` est bien ignoré dans `.gitignore`.
