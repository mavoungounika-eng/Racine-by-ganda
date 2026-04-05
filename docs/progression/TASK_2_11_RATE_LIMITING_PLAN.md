# Task 2/11 — Rate Limiting Implementation

**Status:** 🟡 IN PLANNING  
**Priority:** 🔴 CRITICAL (Security)  
**Depends On:** Task 1/11 (Idempotency) — ✅ COMPLETED  
**Effort:** 3-4 hours  

---

## 📋 Overview

Rate Limiting protects API endpoints from:
- **Brute force attacks** (password guessing on login)
- **DDoS attacks** (flooding with requests)
- **Resource exhaustion** (expensive queries)
- **Abuse** (scraping, spam)

---

## 🎯 Objectives

### 1. Protect Login Endpoints
```
POST /login → Max 5 attempts per 1 minute per IP
POST /createur/register → Max 3 attempts per 1 minute per IP
GET /2fa/challenge → Max 10 attempts per 1 minute per IP
```

### 2. Protect API Endpoints
```
POST /api/webhooks/* → Max 60 requests per minute per IP
POST /checkout → Max 10 orders per minute per user
POST /pos/sales → Max 20 sales per minute per machine
```

### 3. Protect Admin Endpoints
```
All /admin/* → Max 100 requests per minute per user
POST /admin/users → Max 10 creates per hour per admin
```

### 4. Monitor & Alert
```
Track rate limit violations
Log suspicious patterns
Alert on repeated offenders
```

---

## 🛠️ Implementation Plan

### Phase 1: Configuration (30 min)
- [ ] Create `config/rate_limiting.php`
- [ ] Define rate limit rules
- [ ] Configure cache driver for limits

### Phase 2: Middleware (1 hour)
- [ ] Create `RateLimitMiddleware.php` (if needed beyond throttle)
- [ ] Add route middleware
- [ ] Configure `throttle` routes

### Phase 3: Protection (1 hour)
- [ ] Protect login routes
- [ ] Protect checkout routes
- [ ] Protect API routes
- [ ] Protect admin routes

### Phase 4: Monitoring (30 min)
- [ ] Add logging for violations
- [ ] Create artisan command to view violations
- [ ] Setup alerting (optional)

### Phase 5: Testing (1 hour)
- [ ] Unit tests for rate limit logic
- [ ] Integration tests for protected routes
- [ ] Load test (simulate abuse)

---

## 📁 Deliverables

```
config/rate_limiting.php                          (NEW)
app/Http/Middleware/RateLimitMiddleware.php       (NEW)
app/Console/Commands/RateLimitViolations.php      (NEW)
routes/web.php                                    (MODIFIED)
routes/api.php                                    (MODIFIED)
routes/auth.php                                   (MODIFIED)
tests/Feature/Security/RateLimitingTest.php       (NEW)
docs/RATE_LIMITING_GUIDE.md                       (NEW)
database/migrations/2026_01_29_000002_create_rate_limit_logs_table.php  (NEW)
```

---

## 🔄 Architecture

### How Rate Limiting Works

```
Request arrives
  ↓
Check X-RateLimit-Key (IP or User ID)
  ↓
Increment counter in cache/database
  ↓
Counter >= Limit?
  - NO: Continue to next middleware
  - YES: Return 429 Too Many Requests
  ↓
Response sent with headers:
  X-RateLimit-Limit: 60
  X-RateLimit-Remaining: 45
  X-RateLimit-Reset: 1643472000
```

### Storage Options

**Option 1: Cache (Recommended for API)**
```php
Cache::increment("rate_limit:login:{$ip}");  // Fast, in-memory
// Pros: Fast, simple
// Cons: Lost on restart
```

**Option 2: Database**
```php
RateLimitLog::create([
  'key' => "login:{$ip}",
  'count' => 1,
  'reset_at' => now()->addMinute()
]);
// Pros: Persistent
// Cons: Slower, disk I/O
```

**Decision:** Use **Cache** for most endpoints, **Database** for audit trail.

---

## 📝 Configuration

### Planned Rules

```php
// config/rate_limiting.php
return [
    'limits' => [
        // Authentication
        'login' => '5/1min',              // 5 per minute per IP
        'register' => '3/1min',           // 3 per minute per IP
        '2fa_challenge' => '10/1min',     // 10 per minute per IP
        
        // Checkout (high priority)
        'checkout' => '10/1min:user',     // 10 orders per minute per user
        'checkout_payment' => '5/1min:user',    // 5 payments per minute
        
        // POS (per machine)
        'pos_sales' => '20/1min:machine', // 20 sales per minute per machine
        'pos_payment' => '10/1min:machine',
        
        // API (per IP)
        'api' => '60/1min',               // 60 requests per minute per IP
        'api_webhooks' => '100/1min',     // Higher limit for webhooks
        
        // Admin (per user)
        'admin' => '100/1min:user',       // 100 requests per minute per admin
        'admin_create' => '10/1hour:user',    // 10 creates per hour per admin
    ],
    
    'storage' => 'cache',     // 'cache' or 'database'
    'cleanup_after' => 86400, // 24 hours
];
```

---

## 🔐 Security Considerations

### IP Spoofing Prevention
```php
// Use trusted proxy headers only
$ip = Request::ip();  // Checks X-Forwarded-For only if trusted proxies configured

// In config/trustedproxy.php, configure:
$trustedProxies = ['10.0.0.0/8', '172.16.0.0/12'];  // Load balancer IPs
```

### Rate Limit Bypass Prevention
```php
❌ DON'T: Use only User ID (authenticated users bypass by logging out)
✅ DO: Combine IP + User ID when available
```

### Whitelist Exceptions
```php
// Some IPs should be whitelisted:
- CI/CD pipeline (GitHub Actions)
- Monitoring services (Sentry, Bugsnag)
- Approved integrations (Stripe, Monetbil webhooks)
```

---

## 🧪 Testing Strategy

### Unit Tests
```php
✅ test_throttle_decorator_increments_counter
✅ test_throttle_decorator_returns_429_when_exceeded
✅ test_throttle_decorator_resets_after_window
✅ test_different_ips_have_separate_limits
✅ test_authenticated_users_use_user_key
```

### Integration Tests
```php
✅ test_login_endpoint_rate_limited_to_5_per_minute
✅ test_checkout_endpoint_rate_limited_to_10_per_minute
✅ test_rate_limit_headers_present_in_response
✅ test_429_response_body_is_json
```

### Load Tests
```php
✅ test_simulate_10_concurrent_requests_to_checkout
✅ test_simulate_brute_force_attack_on_login (blocked after 5)
✅ test_rate_limit_survives_1000_requests_per_second
```

---

## 📊 Expected Outcomes

### Before Rate Limiting ❌
```
Login endpoint:
  - Attacker sends 1000 requests/second
  - All processed (CPU exhaustion)
  - Database overloaded
  - Service becomes unavailable
  
Checkout endpoint:
  - User double-clicks submit button
  - Both requests create orders (2 orders from 1 click)
  - PII exposed in logs
  
POS Terminal:
  - Network error → Automatic retry
  - Same sale recorded twice
  - Cash reconciliation fails
```

### After Rate Limiting ✅
```
Login endpoint:
  - 5th request → 429 Too Many Requests
  - Connection closed
  - Attacker blocked for 1 minute
  - Service remains responsive
  
Checkout endpoint:
  - 1st click → Order created
  - 2nd click (within 1 minute) → 429 (blocked)
  - Only 1 order created
  - User gets clear error message
  
POS Terminal:
  - Rate limiting per machine ID
  - Sales capped at 20/minute
  - Prevents accidental double-entry
  - Cash reconciliation passes
```

---

## 🚀 Deployment Steps

```bash
# 1. Create configuration
php artisan vendor:publish --tag=rate-limiting-config

# 2. Run migration (for audit logs)
php artisan migrate

# 3. Update routes
# See routes/web.php, routes/api.php, routes/auth.php

# 4. Test locally
php artisan test tests/Feature/Security/RateLimitingTest.php

# 5. Monitor in production
php artisan rate-limit:violations

# 6. Setup alerts (Slack/Email)
php artisan rate-limit:monitor --channel=slack
```

---

## 📋 Checklist

```
Planning:
  ✅ Define rate limit rules
  ✅ Identify endpoints to protect
  ✅ Choose storage backend
  ✅ Plan exception whitelist

Implementation:
  [ ] Create config/rate_limiting.php
  [ ] Update routes with throttle middleware
  [ ] Create migration for logs
  [ ] Add monitoring commands
  [ ] Write tests

Documentation:
  [ ] Client guide (retry-after headers)
  [ ] Admin guide (viewing violations)
  [ ] Troubleshooting guide
  [ ] API documentation update

Testing:
  [ ] Unit tests pass
  [ ] Integration tests pass
  [ ] Load tests pass
  [ ] Manual testing in staging

Deployment:
  [ ] Merge to main
  [ ] Deploy to production
  [ ] Monitor for false positives
  [ ] Adjust limits if needed
```

---

## 📖 References

- [Laravel Throttle Middleware](https://laravel.com/docs/11.x/rate-limiting)
- [OWASP Rate Limiting](https://owasp.org/www-community/attacks/Brute_force_attack)
- [HTTP 429 Status Code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429)

---

## Next Steps

1. ✅ Task 1/11 Complete (Order Idempotency)
2. ⏳ Task 2/11 Start (Rate Limiting) — THIS ONE
3. ⏳ Task 3/11 (Global Audit Trail)
4. ⏳ Task 4/11 (Webhook Deduplication)
5. ⏳ Task 5/11 (Performance Optimization)

---

**Ready to proceed with Task 2/11? Start implementation: Y/N**
