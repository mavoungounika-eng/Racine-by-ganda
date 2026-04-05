---
paths:
  - "app/Services/Payments/Monetbil*"
  - "app/Services/Payments/MobileMoney*"
  - "app/Http/Controllers/Payments/Monetbil*"
  - "app/Http/Controllers/Api/WebhookController.php"
  - "tests/Feature/Monetbil*"
  - "tests/Feature/Payments/Monetbil*"
---
# Règles — Monetbil / Mobile Money (XAF)

## Variables d environnement requises
MONETBIL_SERVICE_ID=9nz1NImduWunYrct5p8gucGRPaaZuUzp
MONETBIL_SERVICE_KEY=GgifAl9Oey...
MONETBIL_SERVICE_SECRET=[configuré]
MONETBIL_CURRENCY=XAF
MONETBIL_COUNTRY=CG
MONETBIL_NOTIFY_URL=[URL prod à configurer]
MONETBIL_RETURN_URL=[URL prod à configurer]

## Flow Monetbil
1. createPaymentUrl() → redirection vers page Monetbil
2. Monetbil envoie webhook POST /api/webhooks/monetbil
3. WebhookController::monetbil() persiste status: "received"
4. ProcessMonetbilCallbackEventJob traite en async

## Conventions webhook
- Signature vérifiée via X-Signature header (HMAC-SHA256)
- En test : Queue::fake() pour éviter exécution sync du job
- Status initial persisté : "received" (pas "success" ni "failed")
- Idempotence via event_key unique (hash stable du payload)
- Circuit breaker via CircuitBreakerService

## Tests Monetbil
- Ne jamais appeler l API Monetbil réelle en test
- Config::set("services.monetbil.service_secret", "test_secret_key") dans le test
- Générer signature : hash_hmac("sha256", json_encode($payload), "test_secret_key")
- Ajouter Queue::fake() au début des tests qui déclenchent des jobs
