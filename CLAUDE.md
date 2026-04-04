# Racine by Ganda — Instructions Claude Code

## Présentation du projet

Plateforme e-commerce multi-rôles (marketplace, POS, gestion stock).
Statut : ~95% complet — phase stabilisation et dette technique.
Objectif immédiat : rendre le projet fonctionnel et prêt pour la mise en production publique.

## Stack technique

- **Backend** : PHP 8.2 · Laravel 12 · MySQL · Redis
- **Frontend** : Vue 3 · Vite 7 · Bootstrap 5 · Laravel Echo (WebSockets via Reverb)
- **POS** : Electron 28 (application de caisse desktop)
- **Auth** : Laravel Sanctum · Socialite (OAuth) · pragmarx/google2fa (2FA TOTP)
- **Paiements** : Stripe PHP SDK v19
- **IA** : openai-php/laravel (service Amira)
- **Tests** : PHPUnit 11 · Cypress 15 (E2E)
- **Monitoring** : Sentry Laravel
- **Temps réel** : Laravel Reverb · Pusher JS
- **Exports** : Maatwebsite Excel
- **QR** : bacon/bacon-qr-code · simplesoftwareio/simple-qrcode

## Architecture

`
app/
  Http/Controllers/     — contrôleurs Laravel
  Models/               — modèles Eloquent
  Services/             — logique métier (dont AmiraService)
  Helpers/              — SettingsHelper, AuthHelper, helpers.php
modules/                — modules Laravel autonomes (PSR-4: Modules\)
resources/js/           — composants Vue 3
database/
  migrations/
  seeders/
  factories/
tests/
  Unit/
  Feature/
`

## Conventions de code

- PSR-12 strict (Laravel Pint configuré)
- Composants Vue 3 en Composition API avec <script setup>
- Nommage : camelCase JS/Vue · snake_case PHP · kebab-case fichiers Vue
- Pas de logique métier dans les contrôleurs — tout passe par les Services
- Toujours valider les requêtes via Form Requests dédiées
- Utiliser les ressources API (JsonResource) pour toutes les réponses JSON

## Commandes essentielles

`
# Démarrage complet (server + queue + logs + vite)
composer run dev

# Tests uniquement
composer run test
# ou
php artisan test

# Build production
npm run build

# Linting PHP
./vendor/bin/pint

# Setup initial
composer run setup
`

## Modules critiques — état actuel

| Module | Statut | Priorité |
|--------|--------|----------|
| Auth 2FA (TOTP) | Tests en échec | CRITIQUE |
| OAuth providers (Socialite) | Tests en échec | CRITIQUE |
| Stripe / paiements | Tests en échec | CRITIQUE |
| Service Amira (IA) | Tests en échec | CRITIQUE |
| POS Electron | À stabiliser | HAUTE |

## Règles importantes

- TOUJOURS vérifier les variables d'environnement avant tout test Stripe ou OAuth
- Ne JAMAIS commiter de clés API, secrets ou credentials dans le code
- Les webhooks Stripe doivent être vérifiés avec Stripe\Webhook::constructEvent
- Le service Amira utilise openai-php/laravel — vérifier la config config/openai.php
- Laravel Reverb gère les WebSockets — ne pas utiliser Pusher en production
- Les modules dans modules/ suivent le namespace Modules\ (PSR-4)

## Informations découvertes par CODEX

> Cette section a été remplie automatiquement par CODEX lors de la création de ce fichier.
> Elle reflète l'état réel du projet au moment de la configuration.

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
- app/Services/Production/ProductionCostingService.php
- app/Services/Production/ProductionService.php
- app/Services/ProductSearchService.php
- app/Services/ProfileCompletionService.php
- app/Services/Queue/QueueCircuitBreaker.php
- app/Services/Queue/QueueMonitor.php
- app/Services/Queue/QueueRateLimiter.php
- app/Services/Risk/CreatorRiskAssessmentService.php
- app/Services/SaaSCheckoutService.php
- app/Services/SessionSecurityService.php
- app/Services/SocialAuthService.php
- app/Services/SocialAuthService.php.example
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
- tests/Feature/Erp/StockBroadcastTest.php
- tests/Feature/Erp/StockSyncTest.php
- tests/Feature/ErpGlobalTest.php
- tests/Feature/ErpPerformanceTest.php
- tests/Feature/ERPProduction/CostingFlowTest.php
- tests/Feature/ERPProduction/ProductionOrderTest.php
- tests/Feature/ERPProduction/QualityControlTest.php
- tests/Feature/ERPProduction/StockFlowTest.php
- tests/Feature/ERPProduction/WipFlowTest.php
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
- tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php
- tests/Feature/Payments/OutOfOrderEventsTest.php
- tests/Feature/Payments/PaymentStateConsistencyTest.php
- tests/Feature/Payments/StripeWebhookPaymentMappingTest.php
- tests/Feature/Payments/StripeWebhookPaymentNotFoundTest.php
- tests/Feature/PaymentsHubRbacTest.php
- tests/Feature/PaymentTest.php
- tests/Feature/PaymentWebhookSecurityTest.php
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
- tests/Feature/Webhooks/MonetbilWebhookResilienceTest.php
- tests/Feature/Webhooks/StripeBillingWebhookDeduplicationTest.php
- tests/Feature/Webhooks/WebhookMonitoringTest.php
- tests/Feature/Webhooks/WebhookObservabilityTest.php
- tests/Feature/WebhookSecurityProductionTest.php
- tests/Feature/WebhookSecurityTest.php

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
- Le dossier modules/ est présent à la racine.
- Le dossier POS desktop est présent sous acine-pos-electron/ (pas electron/ à la racine).
- Les fichiers de configuration environnement existent : .env, .env.example, .env.testing, .env.production, .env.production.local.
- Les dépendances sont déjà installées (endor/ et 
ode_modules/ présents).
- Un dossier de sauvegarde de tests est présent : 	ests_backup_20260126_120336/.
- Plusieurs entrées de fichiers inattendues sont présentes à la racine (CREATE, USE, id,, 1, 	rue,, -, Admin Test,, Super Admin Test,, dmin@test.com],, superadmin@test.com],, ase32secret,).

## Contexte de session

Quand je travaille sur un module spécifique, je fournirai :
1. Le fichier concerné (contrôleur, service, test)
2. Le message d'erreur exact
3. Le comportement attendu

Traite chaque session comme isolée — ne suppose pas l'état d'autres modules.
