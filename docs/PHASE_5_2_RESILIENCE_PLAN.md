# Phase 5.2 — Resilience & Governance

**Version**: 1.0.0  
**Status**: 📋 Planning  
**Target**: Post v1.0.0 Production Deployment  
**Scope**: Hardening against real-world production incidents

---

## 🎯 Objective

Strengthen the RACINE BY GANDA system against **real production incidents**, not theoretical edge cases.

**Core Principle**: Zero massive refactoring. 100% aligned with current architecture.

---

## 📊 Context

### What We Have (v1.0.0)
✅ Security hardening complete  
✅ Performance optimizations deployed  
✅ Test coverage at industrial level  
✅ Production readiness checklist validated  

### What We Need (v1.0.1+)
🎯 Webhook failure recovery  
🎯 Queue overload protection  
🎯 Audit trail for critical operations  
🎯 Incident response runbooks  

---

## 🔧 Technical Scope

### 1. Webhook Resilience

**Current Risk**: Webhook failures from payment providers (Stripe, PayPal) can cause order state inconsistencies.

#### Proposed Enhancements

##### 1.1 Retry Logic with Exponential Backoff
```php
// app/Services/WebhookRetryService.php
class WebhookRetryService
{
    public function retry(callable $handler, array $payload, int $maxAttempts = 3): bool
    {
        $attempt = 0;
        $delay = 1; // seconds
        
        while ($attempt < $maxAttempts) {
            try {
                $handler($payload);
                return true;
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    $this->sendToDeadLetter($payload, $e);
                    return false;
                }
                sleep($delay);
                $delay *= 2; // exponential backoff
            }
        }
    }
}
```

##### 1.2 Anti-Loop Protection (Enhanced)
- Track webhook signature + timestamp in cache (5 min TTL)
- Reject duplicate webhooks within window
- Log duplicate attempts for monitoring

##### 1.3 Dead Letter Queue (Soft Implementation)
- Store failed webhooks in `webhook_failures` table
- Admin dashboard alert for manual review
- Retry mechanism via artisan command

**Migration Required**:
```php
Schema::create('webhook_failures', function (Blueprint $table) {
    $table->id();
    $table->string('provider'); // stripe, paypal
    $table->string('event_type');
    $table->json('payload');
    $table->text('error_message');
    $table->integer('retry_count')->default(0);
    $table->timestamp('last_retry_at')->nullable();
    $table->timestamps();
});
```

---

### 2. Queue & Jobs Protection

**Current Risk**: Queue overload during high-traffic events (flash sales, viral products).

#### Proposed Enhancements

##### 2.1 Rate Limiting for Jobs
```php
// app/Jobs/ProcessOrderJob.php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware(): array
{
    return [new RateLimited('orders')];
}
```

**Config** (`config/queue.php`):
```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
        'throttle' => [
            'orders' => [
                'allow' => 100,
                'every' => 60, // 100 jobs per minute
            ],
        ],
    ],
],
```

##### 2.2 Slow Job Detection
- Monitor job execution time via Horizon
- Alert if job exceeds 30s (configurable threshold)
- Auto-log slow jobs to `slow_jobs` table

##### 2.3 Queue Health Monitoring
- Artisan command: `php artisan queue:health`
- Check pending jobs count
- Alert if queue depth > 1000 jobs

---

### 3. Governance & Audit Trail

**Current Risk**: No audit trail for critical financial operations (refunds, manual adjustments).

#### Proposed Enhancements

##### 3.1 Audit Log for Critical Actions
**Events to Track**:
- Order refunds (full/partial)
- Manual stock adjustments
- Payment method changes
- Webhook manual retries

**Implementation**:
```php
// app/Services/AuditService.php
class AuditService
{
    public function log(string $action, string $entity, int $entityId, ?User $user = null): void
    {
        AuditLog::create([
            'action' => $action,
            'entity_type' => $entity,
            'entity_id' => $entityId,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'before' => $this->captureState($entity, $entityId),
            ],
        ]);
    }
}
```

**Migration**:
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('action'); // refund_created, stock_adjusted
    $table->string('entity_type'); // Order, Product
    $table->unsignedBigInteger('entity_id');
    $table->foreignId('user_id')->nullable()->constrained();
    $table->ipAddress('ip_address');
    $table->text('user_agent');
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['entity_type', 'entity_id']);
    $table->index('created_at');
});
```

##### 3.2 Escalation Policy
**Incident Severity Levels**:
- **P0 (Critical)**: Payment processing down → Alert CTO + DevOps immediately
- **P1 (High)**: Webhook failures > 10/hour → Alert DevOps within 15 min
- **P2 (Medium)**: Queue depth > 1000 → Alert DevOps within 1 hour
- **P3 (Low)**: Slow jobs detected → Log for review

**Notification Channels**:
- Slack webhook (production alerts channel)
- Email (ops@racine-by-ganda.com)
- SMS (P0 only, via Twilio)

---

### 4. Incident Runbooks

**Purpose**: Clear, actionable steps for common production incidents.

#### 4.1 Runbook: Payment Processing Failure

**Symptoms**:
- Orders stuck in "pending" status
- Stripe/PayPal webhooks returning 500 errors

**Diagnosis**:
```bash
# Check recent webhook failures
php artisan tinker
>>> WebhookFailure::where('created_at', '>', now()->subHours(1))->count();

# Check queue status
php artisan queue:health
```

**Resolution**:
1. Check Stripe/PayPal dashboard for service status
2. Review `webhook_failures` table for error patterns
3. If provider issue: Wait for resolution, monitor
4. If code issue: Apply hotfix, deploy immediately
5. Retry failed webhooks: `php artisan webhooks:retry --since=1h`

**Rollback**: N/A (diagnostic only)

---

#### 4.2 Runbook: Webhook Loop Detected

**Symptoms**:
- Same webhook signature appearing multiple times in logs
- Cache hit rate for webhook deduplication > 50%

**Diagnosis**:
```bash
# Check cache for duplicate webhooks
php artisan tinker
>>> Cache::get('webhook:stripe:evt_xxx');
```

**Resolution**:
1. Verify anti-loop logic is active
2. Check provider webhook settings (retry policy)
3. If provider is retrying too aggressively: Contact support
4. If code issue: Review `WebhookController` signature validation

**Rollback**: N/A (monitoring only)

---

#### 4.3 Runbook: Queue Overload

**Symptoms**:
- Queue depth > 1000 jobs
- Job processing time increasing
- Horizon dashboard showing backlog

**Diagnosis**:
```bash
# Check queue depth
php artisan queue:health

# Check Horizon metrics
# Visit /horizon dashboard
```

**Resolution**:
1. Scale queue workers: `php artisan horizon:scale high=10`
2. Identify slow jobs: Review Horizon "Failed Jobs" tab
3. If specific job type is slow: Optimize or defer
4. If traffic spike: Wait for normalization, monitor

**Rollback**: Scale down workers after backlog clears

---

#### 4.4 Runbook: Stock Sync Failure (ERP Integration)

**Symptoms**:
- Stock levels not updating after ERP sync
- `SyncStockJob` failing repeatedly

**Diagnosis**:
```bash
# Check failed jobs
php artisan queue:failed

# Check ERP API status
curl -I https://erp-api.example.com/health
```

**Resolution**:
1. Verify ERP API is reachable
2. Check API credentials in `.env`
3. Review `SyncStockJob` logs for error details
4. If ERP down: Pause sync, alert ERP team
5. If code issue: Apply hotfix, retry failed jobs

**Rollback**: Manual stock adjustment via admin panel

---

## 📋 Validation Checklist

### Pre-Implementation
- [ ] Review current webhook handling logic
- [ ] Audit current queue configuration
- [ ] Identify critical actions requiring audit trail
- [ ] Define incident severity levels with stakeholders

### Implementation
- [ ] Create `webhook_failures` migration
- [ ] Implement `WebhookRetryService`
- [ ] Configure queue rate limiting
- [ ] Create `audit_logs` migration
- [ ] Implement `AuditService`
- [ ] Create runbook documents (separate files)

### Testing
- [ ] Unit test: `WebhookRetryService` with mock failures
- [ ] Integration test: Webhook retry flow end-to-end
- [ ] Load test: Queue behavior under 500 jobs/min
- [ ] Manual test: Audit log creation for refund action

### Deployment
- [ ] Run migrations on staging
- [ ] Verify webhook retry logic on staging (simulate failure)
- [ ] Monitor queue metrics for 24 hours on staging
- [ ] Deploy to production during low-traffic window
- [ ] Monitor for 48 hours post-deployment

---

## 🎯 Success Criteria

### Measurable Outcomes
1. **Webhook Resilience**: 99.9% webhook processing success rate (including retries)
2. **Queue Stability**: Queue depth never exceeds 2000 jobs
3. **Audit Coverage**: 100% of critical actions logged
4. **Incident Response**: Mean time to resolution (MTTR) < 30 minutes for P1 incidents

### Non-Functional
- Zero breaking changes to existing API contracts
- No performance degradation (< 5ms overhead per request)
- All new code covered by tests (>80% coverage)

---

## 📅 Timeline (Estimated)

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| **Planning** | 1 day | This document + stakeholder approval |
| **Implementation** | 3-5 days | Code + migrations + tests |
| **Staging Validation** | 2 days | Full test suite + load testing |
| **Production Deployment** | 1 day | Deployment + 24h monitoring |
| **Post-Deployment Review** | 1 day | Metrics analysis + retrospective |

**Total**: ~7-10 days

---

## 🚨 Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Retry logic causes duplicate orders | High | Low | Idempotency keys + transaction locks |
| Queue rate limiting delays critical jobs | Medium | Medium | Separate queue for high-priority jobs |
| Audit logs grow too large | Low | High | Partition by month + archival strategy |
| Runbooks become outdated | Medium | High | Quarterly review + version control |

---

## 📚 References

- [Production Readiness Checklist](file:///c:/laravel_projects/racine-backend/docs/PRODUCTION_READINESS_CHECKLIST.md)
- [Release Notes v1.0.0](file:///c:/laravel_projects/racine-backend/RELEASE_NOTES.md)
- [Laravel Queue Documentation](https://laravel.com/docs/10.x/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/10.x/horizon)

---

## ✅ Approval Required

> [!IMPORTANT]
> This plan requires approval before implementation begins.
> 
> **Review Focus**:
> - Incident severity levels (P0-P3) alignment with business priorities
> - Escalation policy (notification channels, response times)
> - Timeline feasibility given current team capacity

**Approved by**: _Pending_  
**Date**: _Pending_  
**Notes**: _Pending_
