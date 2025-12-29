# CI Runbook — RACINE BY GANDA

## Overview
This runbook provides procedures for handling CI/CD failures and maintaining the continuous integration pipeline.

## CI Workflows

### 1. Tests Workflow (`.github/workflows/tests.yml`)
**Purpose**: Run full test suite on MySQL with PHP matrix

**Triggers**:
- Push to `main`, `develop`, `security/**`
- Pull requests to `main`, `develop`

**Execution Modes**:
- **PR**: `--stop-on-failure` (fast feedback)
- **Main**: `--parallel` (comprehensive)

**Duration**: ~5 minutes (parallel mode)

---

### 2. Coverage Workflow (`.github/workflows/coverage.yml`)
**Purpose**: Generate code coverage reports (informational only)

**Triggers**: Same as Tests workflow

**Outputs**:
- HTML coverage report (artifact)
- XML coverage report (artifact)
- PR comment with summary

**Status**: ℹ️ Non-blocking

---

### 3. Performance Workflow (`.github/workflows/performance.yml`)
**Purpose**: Enforce N+1 query regression limits

**Triggers**: Same as Tests workflow

**Thresholds**:
- Creator Dashboard: ≤ 40 queries
- Admin Orders: ≤ 20 queries
- ERP Stock: ≤ 20 queries

**Status**: 🔴 Blocking

---

## Troubleshooting Procedures

### Scenario 1: Test Failures

#### Symptoms
- ❌ Red CI status
- Failed test output in logs
- Artifact: `test-logs-php-X.X`

#### Diagnosis Steps
1. Check which PHP version failed (8.1 or 8.2)
2. Review failed test output in CI logs
3. Download `test-logs-php-X.X` artifact for detailed logs
4. Reproduce locally:
   ```bash
   # Use same PHP version as CI
   php artisan test --env=testing --filter=FailingTestName
   ```

#### Common Causes
| Cause | Solution |
|:---|:---|
| **Database migration issue** | Check migrations, ensure `up()` and `down()` are correct |
| **Missing seeder data** | Add required seeders to test `setUp()` |
| **Environment-specific** | Verify `.env.testing` configuration |
| **Flaky test** | Add proper setup/teardown, check for race conditions |

#### Resolution
1. Fix the failing test locally
2. Verify fix: `php artisan test --filter=FailingTestName`
3. Commit and push
4. Verify CI passes

---

### Scenario 2: MySQL Connection Failure

#### Symptoms
- Error: `SQLSTATE[HY000] [2002] Connection refused`
- Tests fail immediately
- MySQL healthcheck timeout

#### Diagnosis Steps
1. Check MySQL service status in CI logs
2. Verify healthcheck configuration
3. Check database credentials in workflow

#### Common Causes
| Cause | Solution |
|:---|:---|
| **Healthcheck timeout** | Increase `health-retries` in workflow |
| **Port conflict** | Verify port `3306` is not in use |
| **Wrong credentials** | Check `DB_USERNAME`/`DB_PASSWORD` match service config |

#### Resolution
```yaml
# Increase healthcheck retries
options: >-
  --health-cmd="mysqladmin ping --silent"
  --health-interval=10s
  --health-timeout=5s
  --health-retries=5  # Increased from 3
```

---

### Scenario 3: Performance Regression (N+1)

#### Symptoms
- ❌ Performance workflow fails
- Error: "Creator Dashboard exécute trop de requêtes (45). N+1 possible..."
- Artifact: `query-logs`

#### Diagnosis Steps
1. Download `query-logs` artifact
2. Review `QueryLogger` output
3. Identify new queries or missing eager loading
4. Check recent changes to controllers/models

#### Common Causes
| Cause | Solution |
|:---|:---|
| **Missing `with()`** | Add eager loading: `->with(['relation'])` |
| **Loop queries** | Move query outside loop or use `load()` |
| **New feature** | Add eager loading from start |
| **Threshold too strict** | Justify and update limit (rare) |

#### Resolution
1. Fix N+1 issue with eager loading
2. Verify locally:
   ```bash
   php artisan test --filter=NPlusOneRegressionTest
   ```
3. Commit and push
4. Verify Performance workflow passes

---

### Scenario 4: Coverage Workflow Failure

#### Symptoms
- ❌ Coverage workflow fails
- Xdebug errors
- Coverage report not generated

#### Diagnosis Steps
1. Check Xdebug installation in CI logs
2. Verify `coverage: xdebug` in PHP setup
3. Check test execution with coverage flag

#### Common Causes
| Cause | Solution |
|:---|:---|
| **Xdebug not installed** | Verify `setup-php` action includes `xdebug` extension |
| **Memory limit** | Increase PHP memory: `php -d memory_limit=512M artisan test` |
| **Timeout** | Increase workflow timeout to 20 minutes |

#### Resolution
**Note**: Coverage is informational only. If it fails, tests still pass. Fix when convenient.

---

### Scenario 5: Composer Cache Issues

#### Symptoms
- Slow dependency installation (>60s)
- Cache miss in logs
- Different dependencies than expected

#### Diagnosis Steps
1. Check cache hit/miss in CI logs
2. Verify `composer.lock` is committed
3. Check cache key matches `composer.lock` hash

#### Resolution
```bash
# Locally, ensure lock file is up to date
composer update --lock

# Commit composer.lock
git add composer.lock
git commit -m "chore: update composer.lock"
```

---

## Escalation Procedures

### Level 1: Developer (Self-Service)
**Scope**: Test failures, code issues, N+1 regressions

**Actions**:
1. Review CI logs
2. Reproduce locally
3. Fix and verify
4. Push fix

**SLA**: Fix within 1 business day

---

### Level 2: Tech Lead
**Scope**: Infrastructure issues, workflow configuration, persistent failures

**Actions**:
1. Review workflow YAML
2. Check GitHub Actions status
3. Verify service configurations
4. Update workflows if needed

**SLA**: Fix within 4 hours (business hours)

---

### Level 3: DevOps/Platform
**Scope**: GitHub Actions outages, runner issues, service unavailability

**Actions**:
1. Check GitHub Status: https://www.githubstatus.com/
2. Contact GitHub Support if needed
3. Consider temporary workarounds
4. Document incident

**SLA**: Best effort (external dependency)

---

## Maintenance

### Weekly
- [ ] Review failed workflow runs
- [ ] Check for flaky tests
- [ ] Monitor CI execution times

### Monthly
- [ ] Review coverage trends
- [ ] Update PHP versions if needed
- [ ] Optimize slow tests
- [ ] Update dependencies

### Quarterly
- [ ] Review and update thresholds
- [ ] Audit workflow configurations
- [ ] Update runbook with new scenarios

---

## Metrics & Monitoring

### Key Metrics
| Metric | Target | Current |
|:---|:---|:---|
| **Test Pass Rate** | 100% | Monitor |
| **CI Duration (parallel)** | < 5 min | ~5 min |
| **CI Duration (PR)** | < 3 min | ~3 min |
| **Flaky Test Rate** | 0% | 0% |

### Alerts
- ❌ Test failures → Slack notification (future)
- ⚠️ Performance regression → Block merge
- ℹ️ Coverage drop → Informational only

---

## Quick Reference

### Useful Commands

```bash
# Run tests locally (MySQL)
php artisan test --env=testing

# Run specific test
php artisan test --filter=TestName

# Run with profiling
php artisan test --profile

# Run N+1 regression tests
php artisan test --filter=NPlusOneRegressionTest

# Generate coverage locally
php artisan test --coverage
```

### Workflow Files
- Tests: `.github/workflows/tests.yml`
- Coverage: `.github/workflows/coverage.yml`
- Performance: `.github/workflows/performance.yml`

### Documentation
- [Test Strategy](PHASE_3_TEST_STRATEGY.md)
- [Test Execution Profile](TEST_EXECUTION_PROFILE.md)
- [Performance Audit](PERFORMANCE_N_PLUS_ONE_AUDIT.md)

---

**Last Updated**: 2025-12-29  
**Maintained By**: Tech Lead  
**Version**: 1.0
