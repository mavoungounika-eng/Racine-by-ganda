# Test Execution Profile — Baseline

**Date**: 2025-12-29  
**Environment**: Windows, SQLite (testing), PHP 8.x  
**Command**: `php artisan test --profile`

## Executive Summary

| Metric | Value | Status |
|:---|:---|:---|
| **Total Duration** | 118.49s | ⚠️ Needs optimization |
| **Tests Passed** | 219 | ✅ |
| **Tests Failed** | 378 | 🔴 Critical |
| **Pass Rate** | 36.7% | 🔴 Unacceptable |

## Critical Issues Detected

### 1. SQLite Compatibility (Blocking ~200+ tests)
**Root Cause**: MySQL-specific date functions in queries
- `MONTH()`, `YEAR()`, `CURDATE()` not available in SQLite
- Affects: ERP Dashboard, Financial metrics, BI calculations

**Impact**: High - Prevents CI/CD on SQLite
**Priority**: P0 - Must fix immediately

**Affected Areas**:
- `ErpDashboardController` statistics query
- Financial KPI calculations
- BI metrics (ARR, ARPU, Churn)
- Accounting ledger queries

### 2. Missing Factories
**Root Cause**: `ErpSupplier::factory()` not defined
**Impact**: Medium - Blocks ERP module tests
**Priority**: P1

### 3. Test Failures by Category

| Category | Failed | Passed | Notes |
|:---|---:|---:|:---|
| **Auth/RBAC** | 45 | 12 | OAuth, permissions, session management |
| **Payments** | 38 | 15 | Webhooks, state consistency, timeouts |
| **Checkout** | 32 | 8 | Security, validation, stock management |
| **ERP** | 28 | 5 | Dashboard, suppliers, production |
| **Financial/BI** | 25 | 3 | KPIs, metrics, accounting |
| **Subscriptions** | 18 | 4 | Stripe billing, plans |
| **Other** | 192 | 172 | Various feature tests |

## Slow Tests (> 1s)

| Test | Duration | Category | Action |
|:---|---:|:---|:---|
| `RbacCacheIntegrityTest::rbac_cache_respects_configured_ttl` | 2.10s | Auth | ✅ Acceptable (sleep-based) |
| `CreatorDashboardTest::test_creator_dashboard_queries_within_limits` | 1.87s | Performance | ⚠️ Review data setup |
| `AuthTest::login_has_rate_limiting` | 1.41s | Auth | ✅ Acceptable (rate limit test) |
| `LedgerServiceTest::it_can_create_an_accounting_entry` | 1.32s | Accounting | 🔴 Investigate |
| `LoginTest::account_is_locked_after_five_failed_attempts` | 1.27s | Auth | ✅ Acceptable (multiple attempts) |
| `LoginTest::failed_attempts_are_cleared_after_successful_login` | 0.87s | Auth | ✅ OK |

**Observation**: Most slow tests are intentionally slow (rate limiting, sleep-based). Only `LedgerServiceTest` needs investigation.

## Test Isolation Issues

### Detected Problems
1. **Database Strategy Inconsistency**: Mix of `RefreshDatabase` and `DatabaseTransactions`
2. **SQLite Function Compatibility**: Hardcoded MySQL functions breaking portability
3. **Factory Dependencies**: Some tests fail due to missing seeded data

### Recommendations
1. Standardize on `RefreshDatabase` for all feature tests
2. Create database-agnostic query helpers for date functions
3. Ensure all factories are self-contained (no external seed dependencies)

## Next Steps

### P0 (Immediate)
- [ ] Fix SQLite compatibility in `ErpDashboardController`
- [ ] Fix SQLite compatibility in Financial/BI services
- [ ] Create `ErpSupplierFactory`

### P1 (Short-term)
- [ ] Audit all raw SQL for MySQL-specific functions
- [ ] Create helper: `DB::dateFunction('MONTH', 'column')` → SQLite/MySQL compatible
- [ ] Standardize test database strategy

### P2 (Medium-term)
- [ ] Investigate `LedgerServiceTest` slow execution
- [ ] Add parallel test execution
- [ ] Optimize test data setup (reduce unnecessary factories)

## Baseline Metrics (Target)

| Metric | Current | Target | Timeline |
|:---|:---|:---|:---|
| Total Duration | 118.49s | < 60s | 2 weeks |
| Pass Rate | 36.7% | 100% | 1 week |
| Slowest Test | 2.10s | < 2s | N/A (acceptable) |
| SQLite Compatibility | 36.7% | 100% | 3 days |
