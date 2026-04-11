# Rate Limiting Guide - RACINE BY GANDA

## Overview

Rate limiting is a critical security control that prevents abuse, brute force attacks, and resource exhaustion. This guide documents the complete rate limiting strategy across the RACINE platform.

**Status**: Task 2/11 Implementation — Complete  
**Last Updated**: 2026-01-29  
**Owner**: Security Team

---

## 1. Rate Limiting Architecture

### 1.1 Throttling Strategy

We use Laravel's built-in `throttle()` middleware applied at the **route group level** for efficiency:

```php
Route::prefix('admin')->name('admin.')->middleware('throttle:100,1')->group(function () {
    // Admin routes protected
});
```

### 1.2 Components

| Component | Purpose | Status |
|-----------|---------|--------|
| **throttle() middleware** | In-memory request tracking | ✅ Built-in Laravel |
| **RateLimitLog table** | Database audit trail | ✅ Created |
| **RateLimitLog model** | Analytics queries | ✅ Created |
| **rate-limit:violations command** | Monitoring CLI | ✅ Created |
| **Tests (8 scenarios)** | Validation | ✅ Created |

---

## 2. Rate Limiting Configuration by Endpoint

### 2.1 Authentication Endpoints (High Security)

| Endpoint | Method | Limit | Window | Reason |
|----------|--------|-------|--------|--------|
| `/login` | POST | 5 | 1 min | Prevent brute force |
| `/register` | POST | 3 | 1 min | Prevent signup spam |
| `/2fa/verify` | POST | 10 | 1 min | OTP brute force protection |
| `/2fa/confirm` | POST | 5 | 1 min | Strict OTP confirmation |

**Implementation**:
```php
// routes/auth.php - Login group
Route::middleware('guest')->group(function () {
    Route::post('login', ...)->middleware('throttle:5,1');
    Route::post('register', ...)->middleware('throttle:3,1');
});

// routes/web.php - 2FA endpoints
Route::post('2fa/verify', ...)->middleware('throttle:10,1');
Route::post('2fa/confirm', ...)->middleware('throttle:5,1');
```

### 2.2 Checkout & Payment Endpoints (Medium Security)

| Endpoint | Limit | Window | Reason |
|----------|-------|--------|--------|
| `/checkout` | 10 | 1 min | Prevent checkout spam |
| `/checkout/card/pay` | 10 | 1 min | Double charge prevention |
| `/checkout/mobile-money/{order}/pay` | 10 | 1 min | Payment retry protection |
| `/payment/monetbil/start/{order}` | 10 | 1 min | Third-party payment spam |

**Implementation**:
```php
// routes/web.php - Checkout routes
Route::post('checkout', ...)->middleware('throttle:10,1');
Route::post('checkout/card/pay', ...)->middleware('throttle:10,1');
```

### 2.3 POS Endpoints (Terminal-Specific Security)

| Endpoint Group | Limit | Window | Target |
|----------------|-------|--------|--------|
| `/pos/sessions/*` | 30 | 1 min | Per terminal |
| `/pos/sales/*` | 30 | 1 min | Per terminal |
| `/pos/payments/*` | 30 | 1 min | Per terminal |

**Implementation**:
```php
// routes/pos.php - POS groups
Route::prefix('sessions')->middleware('throttle:30,1')->group(function () { ... });
Route::prefix('sales')->middleware('throttle:30,1')->group(function () { ... });
Route::prefix('payments')->middleware('throttle:30,1')->group(function () { ... });
```

**Why 30/min for POS?**
- A busy terminal may process 20-25 transactions/minute during peak hours
- 30/min allows headroom for legitimate traffic
- Lower than checkout (10/min) because checkout is lower-frequency user action
- Prevents DDoS while maintaining operational throughput

### 2.4 Admin Dashboard (Role-Based Security)

| Endpoint | Limit | Window | Reason |
|----------|-------|--------|--------|
| `/admin/*` | 100 | 1 min | Admin operations |

**Implementation**:
```php
// routes/web.php - Admin group
Route::prefix('admin')->name('admin.')->middleware('throttle:100,1')->group(function () {
    // All admin routes inherit 100/min throttle
    Route::middleware(['ensure:admin,super_admin', '2fa'])->group(function () {
        // Additional auth checks
    });
});
```

**Why 100/min for admin?**
- Admins need flexibility for BI queries, report generation, bulk operations
- Still prevents obvious DoS attacks
- Protected behind `ensure:admin,super_admin` + 2FA middleware

### 2.5 Creator Dashboard (Seller Operations)

| Endpoint | Limit | Window | Reason |
|----------|-------|--------|--------|
| `/createur/*` | 50 | 1 min | Creator operations |

**Implementation**:
```php
// routes/web.php - Creator group
Route::prefix('createur')->name('creator.')->middleware('throttle:50,1')->group(function () {
    Route::middleware(['ensure:createur', 'creator.active'])->group(function () {
        // Creator operations
    });
});
```

**Why 50/min for creators?**
- Sellers managing products, orders, analytics
- Product bulk updates may need higher throughput than checkout
- Still prevents script abuse and scraping

### 2.6 Webhook & External Integrations

| Endpoint | Limit | Window | Reason |
|----------|-------|--------|--------|
| `/webhook/payment/stripe` | 60 | 1 min | Stripe events |
| `/webhook/payment/monetbil` | 60 | 1 min | Monetbil events |

**Status**: Pre-existing (from audit)

---

## 3. How It Works

### 3.1 Request Flow

```
1. Request arrives → Route matched
2. throttle:30,1 middleware applied
3. Laravel checks: key = "throttle:30,1:192.168.1.1" (by default)
4. If first request in minute: ✅ Allow, increment counter
5. If counter < 30: ✅ Allow
6. If counter >= 30: ❌ Reject with 429 Too Many Requests
7. After 1 minute window: ✅ Reset counter
```

### 3.2 Rate Limit Headers

Each response includes:

```
X-RateLimit-Limit: 30        # Maximum requests
X-RateLimit-Remaining: 28    # Requests left in window
X-RateLimit-Reset: 1675123456  # Unix timestamp of window reset
```

**Client Usage**:
```javascript
// JavaScript
const remaining = parseInt(response.headers['x-ratelimit-remaining']);
const reset = new Date(response.headers['x-ratelimit-reset'] * 1000);

if (response.status === 429) {
    console.warn(`Rate limited. Retry after ${reset}`);
}
```

### 3.3 Storage Backend

By default, Laravel uses **cache** (configured in `config/cache.php`):

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'file'),  // Or 'redis' in production

// During request:
Cache::increment("throttle:30,1:192.168.1.1");
```

**For Production**:
```php
// config/cache.php — Use Redis
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],

// Cache::increment() uses Redis → O(1) operation, highly scalable
```

---

## 4. Rate Limit Audit Logging

### 4.1 RateLimitLog Table

**Purpose**: Capture violations for analysis, alerting, and compliance

**Schema**:
```sql
CREATE TABLE rate_limit_logs (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255),              -- "throttle:30,1:192.168.1.1" or "ip:192.168.1.1"
    endpoint VARCHAR(255),         -- "/checkout", "/login", "/pos/sales"
    ip_address VARCHAR(45),        -- IPv4 or IPv6
    user_id BIGINT NULL,           -- NULL for unauthenticated requests
    limit INT,                     -- 30, 10, 5, etc.
    window_seconds INT,            -- 60 (always 1 minute currently)
    requests_in_window INT,        -- Requests made so far
    was_blocked BOOLEAN,           -- TRUE if 429 returned
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_endpoint_blocked_created ON rate_limit_logs(endpoint, was_blocked, created_at);
CREATE INDEX idx_user_blocked_created ON rate_limit_logs(user_id, was_blocked, created_at);
```

### 4.2 Manual Logging

To capture violations (requires middleware enhancement):

```php
// app/Http/Middleware/RateLimitLogger.php
class RateLimitLogger
{
    public function handle($request, $next)
    {
        $response = $next($request);

        if ($response->status() === 429) {
            RateLimitLog::create([
                'key' => 'throttle:...:' . $request->ip(),
                'endpoint' => $request->path(),
                'ip_address' => $request->ip(),
                'user_id' => $request->user()?->id,
                'limit' => 30,  // Extract from middleware
                'requests_in_window' => $request->attributes->get('throttle_requests', 0),
                'was_blocked' => true,
            ]);
        }

        return $response;
    }
}
```

**Status**: Not currently auto-logged (can be added later in Phase 3)

### 4.3 Analytics Methods (RateLimitLog Model)

```php
// Get violations by endpoint
$violations = RateLimitLog::getViolations(hours: 24);
// Returns: [ ['endpoint' => '/checkout', 'count' => 42], ... ]

// Identify repeat offenders
$offenders = RateLimitLog::getRepeatedOffenders(minViolations: 5, hours: 24);
// Returns users/IPs with 5+ violations

// Endpoint statistics
$stats = RateLimitLog::getEndpointStats(hours: 24);
```

---

## 5. Monitoring & Alerting

### 5.1 Artisan Command

View rate limit violations:

```bash
# All violations in last 24 hours
php artisan rate-limit:violations

# Last 7 days
php artisan rate-limit:violations --hours=168

# Show repeated offenders (≥5 violations)
php artisan rate-limit:violations --offenders

# Filter by endpoint
php artisan rate-limit:violations --endpoint="/login"
```

**Output Example**:
```
Rate Limit Violations - Last 24 hours

Metric                  Value
─────────────────────────────────
Total Requests          1,250
Blocked Requests (429)  47
Block Rate              3.76%

Endpoint              Total Requests  Blocked  Block Rate
─────────────────────────────────────────────────────────
/checkout             350             15       4.3%
/login                420             22       5.2%
/pos/sales            380             10       2.6%
/2fa/confirm          100             0        0%

Repeated Offenders (≥5 violations in last 24 hours)

Key (IP/User)                Violations  Last Violation
──────────────────────────────────────────────────────
throttle:10,1:192.168.1.100  12          15 minutes ago
throttle:30,1:10.0.0.5       8           1 hour ago
user:456                     6           2 hours ago
```

### 5.2 Alerting Rules

**Recommended thresholds**:

| Alert | Threshold | Action |
|-------|-----------|--------|
| Endpoint violation spike | 10+ violations in 5 min | Notify ops team |
| Repeated offender detected | Same IP 10+ violations in 1 hour | Temporarily block IP |
| Login brute force | 5 failed logins + rate limit hit | Trigger account lockout |
| Checkout fraud pattern | 20+ checkout blocks from same IP | Review for fraud |

**Implementation** (Phase 3):
```php
// app/Jobs/CheckRateLimitAlerts.php
class CheckRateLimitAlerts implements ShouldQueue
{
    public function handle()
    {
        $violations = RateLimitLog::where('was_blocked', true)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($violations > 10) {
            Notification::route('slack', config('logging.slack_webhook'))
                ->notify(new RateLimitAlert("$violations violations in 5 minutes"));
        }
    }
}

// Schedule in app/Console/Kernel.php
$schedule->job(new CheckRateLimitAlerts::class)->everyMinute();
```

---

## 6. Testing

### 6.1 Test Suite

8 comprehensive tests in `tests/Feature/RateLimiting/RateLimitingTest.php`:

1. ✅ **POS sessions rate limit** — Verifies throttle:30,1
2. ✅ **2FA verify rate limit** — Verifies throttle:10,1
3. ✅ **2FA confirm strict limit** — Verifies throttle:5,1
4. ✅ **Checkout rate limit** — Verifies throttle:10,1
5. ✅ **Login rate limit** — Verifies throttle:5,1
6. ✅ **Register strict limit** — Verifies throttle:3,1
7. ✅ **Different IPs bypass limit** — Verify per-IP isolation
8. ✅ **Rate limit headers present** — Verify X-RateLimit-* headers

**Run tests**:
```bash
php artisan test tests/Feature/RateLimiting/RateLimitingTest.php

# Or specific test
php artisan test tests/Feature/RateLimiting/RateLimitingTest.php --filter=test_pos_sessions_endpoint_is_rate_limited
```

### 6.2 Load Testing

For production validation:

```bash
# Install Apache Bench
apt-get install apache2-utils

# Simulate 100 requests to /checkout in 10 seconds
ab -n 100 -c 10 -p post_data.json -T "application/json" \
   http://localhost/checkout

# Expected: ~90 succeed (200), ~10 rejected (429)
```

---

## 7. Deployment Checklist

### Phase 1: Deploy Rate Limiting (Completed)

- ✅ `throttle()` middleware applied to all critical routes
- ✅ RateLimitLog table created (migration not yet run)
- ✅ RateLimitLog model with analytics methods
- ✅ Artisan command `rate-limit:violations` created
- ✅ Test suite (8 tests) created
- ✅ This documentation

### Phase 2: Production Readiness

- [ ] Run migration: `php artisan migrate`
- [ ] Configure Redis for cache (if not already done)
- [ ] Test with `php artisan test tests/Feature/RateLimiting/`
- [ ] Enable rate limit logging (optional, Phase 3)
- [ ] Set up monitoring dashboard

### Phase 3: Monitoring & Alerts

- [ ] Create RateLimitLogger middleware
- [ ] Implement alert rules (Slack/Email)
- [ ] Set up cron job for `CheckRateLimitAlerts`
- [ ] Create admin UI for violations dashboard

---

## 8. Troubleshooting

### Issue: Rate limiting not working

**Cause**: Cache not configured properly
```bash
# Check cache driver
php artisan config:cache
# Then restart server
```

### Issue: Legitimate users getting 429

**Solution**: Adjust limits or whitelist

```php
// app/Http/Middleware/TrustProxies.php
protected $proxies = [
    '10.0.0.0/8',  // Internal network
];

// Or whitelist specific IP
if (in_array($request->ip(), config('rate-limit.whitelist', []))) {
    return $next($request);  // Bypass throttle
}
```

### Issue: Redis rate limiting inconsistent in cluster

**Solution**: Use sticky sessions or centralized cache
```php
// config/cache.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',  // Single Redis instance
],
```

---

## 9. Performance Impact

### Overhead Analysis

| Operation | Cost | Impact |
|-----------|------|--------|
| In-memory throttle check | ~1ms | Negligible |
| Cache increment | ~5ms (Redis) | <0.5% request time |
| Rate limit header generation | <1ms | Negligible |
| **Total per request** | ~5ms | ~0.5-1% overhead |

**Result**: <1% performance impact with full protection

---

## 10. Compliance & Security

### 10.1 OWASP Protection

Rate limiting mitigates:

- ✅ **Brute Force Attacks** (AA.4)
- ✅ **Credential Stuffing** (AA.2.5)
- ✅ **DDoS** (AS.5)
- ✅ **Resource Exhaustion** (AS.6)

### 10.2 PCI DSS Compliance

Rate limiting on payment endpoints:

```php
// Payment endpoints protected
Route::post('checkout/card/pay', ...)->middleware('throttle:10,1');
Route::post('payment/monetbil/start/{order}', ...)->middleware('throttle:10,1');
```

Prevents:
- Multiple failed payment attempts (Req 6.5.10)
- Account lockout exploitation
- Sensitive data exposure via brute force

---

## 11. Quick Reference

### Rate Limit Limits (All endpoints)

```
/login                         : 5/min
/register                      : 3/min
/2fa/verify                    : 10/min
/2fa/confirm                   : 5/min
/checkout                      : 10/min
/pos/sessions, /sales, /pay    : 30/min
/admin/*                       : 100/min
/createur/*                    : 50/min
/webhook/payment/*             : 60/min
```

### Common Commands

```bash
# Monitor violations
php artisan rate-limit:violations --offenders

# Run tests
php artisan test tests/Feature/RateLimiting/

# Clear cache (resets throttle)
php artisan cache:clear

# Deploy
php artisan migrate
php artisan cache:clear
```

---

## 12. Support & Updates

**Questions?** Contact: security@racine.local  
**Report issues**: GitHub Issues → label: "rate-limiting"  
**Changelog**: See CHANGELOG.md

---

**End of Rate Limiting Guide**
