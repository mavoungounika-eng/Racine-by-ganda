# Idempotency Implementation Guide

## Overview

**Idempotency** ensures that repeated requests with the same parameters produce the same result, preventing duplicate processing of orders, payments, and other critical operations.

## Implementation

### 1. Database Table

Table: `idempotency_keys`

```sql
CREATE TABLE idempotency_keys (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('processing', 'completed') DEFAULT 'processing',
    response LONGTEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 2. Migration

Run migration to create the table:
```bash
php artisan migrate
```

### 3. How It Works

#### Request Flow

```
Client Request
  ↓
Check for X-Idempotency-Key header
  ↓
Key not in DB? → Insert with status=processing → Proceed to handler
  ↓
Key exists with status=processing? → Return 409 (conflict)
  ↓
Key exists with status=completed? → Return cached response
  ↓
Handler executes → Save response → Update status=completed
  ↓
Return response to client
```

#### Middleware: `CheckIdempotency`

Location: `app/Http/Middleware/CheckIdempotency.php`

```php
// Validates X-Idempotency-Key header
// Returns 400 if missing
// Returns 409 if already processing or exists
// Caches response for future identical requests
```

### 4. Protected Routes

#### POST Endpoints Protected

| Route | Controller | Purpose |
|-------|-----------|---------|
| POST `/checkout` | CheckoutController | Create order |
| POST `/checkout/card/pay` | CardPaymentController | Process card payment |
| POST `/checkout/mobile-money/{order}/pay` | MobileMoneyPaymentController | Process mobile money |
| POST `/payment/monetbil/start/{order}` | MonetbilController | Start Monetbil payment |
| POST `/pos/sessions/open` | PosSessionController | Open POS session |
| POST `/pos/sessions/{session}/close` | PosSessionController | Close POS session |
| POST `/pos/sessions/{session}/adjustments` | PosSessionController | POS adjustments |
| POST `/pos/sales` | PosSaleController | Record POS sale |
| POST `/pos/sales/{sale}/cancel` | PosSaleController | Cancel POS sale |
| POST `/pos/payments/{payment}/confirm-card` | PosPaymentController | Confirm card payment |

## Client Usage

### For Frontend Clients

Generate a UUID for each request:

```javascript
// Install uuid package
// npm install uuid

import { v4 as uuidv4 } from 'uuid';

async function createOrder(orderData) {
    const idempotencyKey = uuidv4();

    const response = await fetch('/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Idempotency-Key': idempotencyKey,  // ← Add this header
        },
        body: JSON.stringify(orderData),
    });

    return await response.json();
}
```

### For API Clients

```bash
curl -X POST http://localhost:8000/checkout \
  -H "X-Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "items": [...],
    "amount": 10000
  }'
```

### Retry Logic (Recommended)

Client should retry with **same idempotency key** on network failure:

```javascript
async function createOrderWithRetry(orderData, maxRetries = 3) {
    const idempotencyKey = uuidv4();
    let lastError;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch('/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Idempotency-Key': idempotencyKey,
                },
                body: JSON.stringify(orderData),
            });

            if (!response.ok && response.status !== 409) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            lastError = error;
            console.log(`Attempt ${attempt} failed:`, error.message);
            
            if (attempt < maxRetries) {
                // Exponential backoff: 1s, 2s, 4s
                await new Promise(r => setTimeout(r, Math.pow(2, attempt - 1) * 1000));
            }
        }
    }

    throw lastError;
}
```

## Error Responses

### 400 Bad Request — Missing Key

```json
{
  "error": "Missing X-Idempotency-Key header"
}
```

**Fix:** Add `X-Idempotency-Key` header with UUID value.

### 409 Conflict — Request Already Processing

```json
{
  "error": "Request is already being processed"
}
```

**What it means:** Your request is currently being processed. Wait a moment and retry with the same key.

**Why it happens:** 
- Two concurrent requests with identical key
- Network timeout before previous request completed

**Fix:** Retry after 2-3 seconds with same key.

### 409 Conflict — Duplicate Request

```json
{
  "error": "Duplicate request"
}
```

**What it means:** This request was already processed and the response is being returned from cache.

**Why it happens:**
- Browser refresh during checkout
- Network error then retry
- User submitted form twice

**Fix:** This is normal behavior. The cached response is returned.

## Maintenance

### Clean Up Old Keys

Run cleanup command to remove keys older than 30 days:

```bash
php artisan idempotency:prune --days=30
```

### Schedule in Scheduler

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup old idempotency keys daily
    $schedule->command('idempotency:prune')->daily();
}
```

### Monitor Database

Check idempotency keys table:

```sql
-- Check current keys (should be small)
SELECT COUNT(*) as total_keys,
       SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM idempotency_keys;

-- Find stuck processing keys (older than 1 hour)
SELECT * FROM idempotency_keys
WHERE status = 'processing' 
  AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

## Testing

Run tests:

```bash
php artisan test tests/Feature/Idempotency/IdempotencyTest.php
```

Expected output:
```
✓ test_missing_idempotency_key_returns_400
✓ test_first_request_with_key_is_processed
✓ test_duplicate_request_returns_cached_response
✓ test_request_in_progress_returns_409
✓ test_different_keys_processed_independently
✓ test_get_requests_skip_idempotency_check
✓ test_header_key_takes_precedence
✓ test_cleanup_removes_old_keys

PASSED 8/8
```

## Best Practices

### ✅ DO

- ✅ Generate a new UUID for each user action
- ✅ Store UUID client-side (session storage)
- ✅ Reuse same UUID when retrying failed requests
- ✅ Clear UUID after successful response
- ✅ Use `X-Idempotency-Key` header (not body parameter)
- ✅ Retry on network errors with exponential backoff
- ✅ Handle 409 as success (cached response)

### ❌ DON'T

- ❌ Reuse same UUID for different requests
- ❌ Use static/hardcoded UUIDs
- ❌ Ignore 409 responses
- ❌ Retry without idempotency key
- ❌ Store UUID in cookies (session storage preferred)
- ❌ Send without HTTPS in production

## Examples

### Complete Order Checkout Flow

```javascript
async function checkoutFlow() {
    // Generate unique key for this checkout session
    const checkoutKey = uuidv4();

    try {
        // Step 1: Verify stock (GET, no key needed)
        const stockCheck = await fetch('/api/checkout/verify-stock', {
            method: 'POST',
            body: JSON.stringify(items),
        });

        // Step 2: Create order (POST, needs key)
        const orderResponse = await fetch('/checkout', {
            method: 'POST',
            headers: {
                'X-Idempotency-Key': checkoutKey,
            },
            body: JSON.stringify({ items, customer }),
        });

        if (!orderResponse.ok) throw new Error('Order creation failed');
        const order = await orderResponse.json();

        // Step 3: Process payment (POST, different key)
        const paymentKey = uuidv4();
        const paymentResponse = await fetch(`/checkout/card/pay`, {
            method: 'POST',
            headers: {
                'X-Idempotency-Key': paymentKey,
            },
            body: JSON.stringify({ orderId: order.id, card: cardData }),
        });

        if (!paymentResponse.ok) throw new Error('Payment failed');

        // Success!
        showSuccessMessage('Order created and paid');
        sessionStorage.removeItem('checkoutKey');
        sessionStorage.removeItem('paymentKey');

    } catch (error) {
        // On error, retry with same keys
        console.error('Checkout failed:', error);
        // User can retry without duplication risk
    }
}
```

## Troubleshooting

### Q: "Missing X-Idempotency-Key header" on every request

**A:** Add the header to all POST requests:

```javascript
headers: {
    'X-Idempotency-Key': idempotencyKey,
}
```

### Q: Getting 409 "Request already processing" repeatedly

**A:** There may be a stuck processing request. Contact admin to clean up:

```sql
DELETE FROM idempotency_keys 
WHERE status = 'processing' 
  AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Q: Idempotency key not being stored

**A:** Check:
1. Migration was run: `php artisan migrate:status`
2. Table exists: `SHOW TABLES LIKE 'idempotency_keys';`
3. Middleware is applied to route: `php artisan route:list | grep checkout`

### Q: Cache response is huge (LONGTEXT)?

**A:** Consider compression or archiving old keys using cleanup command:

```bash
php artisan idempotency:prune --days=7  # Keep only 7 days
```

## References

- [Stripe Idempotency](https://stripe.com/docs/api/idempotent_requests)
- [RFC 7231 - HTTP Semantics](https://tools.ietf.org/html/rfc7231)
- [UUID (RFC 4122)](https://tools.ietf.org/html/rfc4122)
