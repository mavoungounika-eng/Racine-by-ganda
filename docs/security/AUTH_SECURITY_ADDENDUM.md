# AUTH SECURITY ADDENDUM

**Document:** Addendum to AUTH_FLOW.md  
**Version:** 1.0  
**Date:** 2026-01-07  
**Status:** NORMATIVE - NON-NEGOTIABLE  
**Authority:** Architecture & Security Team

---

## ⚠️ CRITICAL NOTICE

This document is a **NORMATIVE ADDENDUM** to AUTH_FLOW.md. All rules herein are **MANDATORY** and **ENFORCEABLE** via CI/CD pipeline. Violations result in build failure.

---

## 1. MIDDLEWARE EXECUTION ORDER (NON-NEGOTIABLE)

### Mandatory Stack Order

```php
// bootstrap/app.php - Global Middleware Stack
[
    1. \Illuminate\Cookie\Middleware\EncryptCookies::class,
    2. \Illuminate\Session\Middleware\StartSession::class,
    3. \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    4. \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    // ... other Laravel defaults
]

// Route-specific Middleware Order
Route::middleware([
    'ensure:admin,super_admin',  // MUST be before business logic
    '2fa',                        // MUST be after ensure
    'permission:view_reports',    // Business logic last
])->group(...);
```

### Critical Rules

1. **`EnsureAuthenticated` MUST execute AFTER `StartSession`**
   - Reason: Requires session to read UserContext
   - Violation: Session not initialized → logout loop

2. **`EnsureAuthenticated` MUST execute BEFORE business middlewares**
   - Reason: Authorization before business logic
   - Violation: Business logic executes for unauthorized users

3. **`TwoFactorMiddleware` MUST execute AFTER `EnsureAuthenticated`**
   - Reason: 2FA requires authenticated user
   - Violation: 2FA check on unauthenticated request

### Verification

```bash
# CI Check (automated)
php artisan route:list --json | jq '.[] | select(.middleware | contains(["auth"])) | select(.middleware | contains(["ensure"]) | not)'
# MUST return empty array
```

---

## 2. CAS LIMITES CRITIQUES

### 2.1 auth_version Overflow

**Scenario:** `auth_version` reaches INT max (2,147,483,647)

**Risk:** Overflow to negative value → all sessions invalidated

**Mitigation:**
```php
// app/Observers/UserObserver.php
public function updating(User $user): void
{
    if ($user->isDirty(['role_id', 'status'])) {
        // Reset at 1 billion to prevent overflow
        if ($user->auth_version >= 1_000_000_000) {
            $user->auth_version = 1;
        } else {
            $user->auth_version++;
        }
    }
}
```

**Monitoring:** Alert if any user reaches `auth_version > 100,000` (abnormal)

### 2.2 Concurrent Admin Modifications

**Scenario:** Two admins modify same user role simultaneously

```
Time    Admin A                 Admin B                 DB
T0      Read user (v=5)         Read user (v=5)         v=5
T1      Change role → v=6       -                       v=6
T2      -                       Change role → v=7       v=7
T3      User session (v=5) invalidated ✅
```

**Risk:** None - last write wins, both increments applied

**Validation:** ✅ Safe by design (increment is atomic)

### 2.3 Database Rollback

**Scenario:** DB restored from backup, `auth_version` reverted

```
Before Rollback: User { auth_version: 10 }
After Rollback:  User { auth_version: 5 }
Session:         UserContext { auth_version: 10 }
```

**Risk:** 🔴 CRITICAL - Session with future `auth_version` remains valid

**Mitigation:**
```php
// app/Http/Middleware/EnsureAuthenticated.php
if ($context->authVersion > $user->auth_version) {
    // Session from future = attack or rollback
    Log::critical('Future auth_version detected', [
        'user_id' => $user->id,
        'session_version' => $context->authVersion,
        'db_version' => $user->auth_version,
    ]);
    
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect()->route('login')
        ->with('error', 'Session invalidée pour raisons de sécurité.');
}
```

**Status:** ✅ IMPLEMENTED (required)

### 2.4 Session Fixation Attack

**Scenario:** Attacker injects UserContext with future `auth_version`

```php
// Attacker code (requires server access)
session(['user_context' => [
    'user_id' => 1,
    'role' => 'admin',
    'auth_version' => 999999,
]]);
```

**Risk:** 🔴 CRITICAL - Bypass auth_version validation

**Mitigation:** Same as 2.3 - validate `auth_version ≤ DB`

**Additional Protection:**
```php
// app/Services/Auth/UserContextResolver.php
public function getFromSession(): ?UserContext
{
    $data = session('user_context');
    if (!$data) return null;
    
    // Validate structure
    if (!isset($data['user_id'], $data['auth_version'])) {
        session()->forget('user_context');
        return null;
    }
    
    // Validate auth_version is reasonable
    if ($data['auth_version'] < 0 || $data['auth_version'] > 1_000_000_000) {
        session()->forget('user_context');
        return null;
    }
    
    return new UserContext(...$data);
}
```

### 2.5 Race Condition: Login + Role Change

**Scenario:** User logs in while admin changes their role

```
Time    User                    Admin                   Result
T0      POST /login             -                       -
T1      Auth::attempt() ✅      -                       -
T2      -                       Change role (v=5→6)     -
T3      Create UserContext(v=5) -                       ⚠️ Stale
T4      Store in session        -                       ⚠️ Stale
```

**Risk:** 🟡 MEDIUM - User gets old role for one request

**Mitigation:**
```php
// app/Services/Auth/AuthOrchestratorService.php
public function login(LoginRequest $request): AuthResult
{
    // ... auth attempt ...
    
    // CRITICAL: Refresh user AFTER login to get latest auth_version
    $user->refresh();
    
    $context = $this->contextResolver->resolve($user);
    $this->contextResolver->storeInSession($context);
    
    // ...
}
```

**Status:** ✅ IMPLEMENTED

---

## 3. RÈGLES DE SÉCURITÉ EXPLICITES

### Rule 1: auth_version Validation (CRITICAL)

```php
// MANDATORY in EnsureAuthenticated middleware
if ($context->authVersion !== $user->auth_version) {
    // Strict equality - no tolerance
    Auth::logout();
    return redirect()->route('login');
}

// ADDITIONAL: Prevent future versions
if ($context->authVersion > $user->auth_version) {
    Log::critical('Future auth_version attack detected');
    // ... logout + alert security team
}
```

### Rule 2: UserContext Immutability (CRITICAL)

```php
// ❌ FORBIDDEN - Modification after creation
$context->role = 'admin';

// ❌ FORBIDDEN - Re-creation from session data
$context = new UserContext(...session('user_context'));

// ✅ MANDATORY - Always use resolver
$context = app(UserContextResolver::class)->getFromSession();
```

### Rule 3: No Role Checks Outside Middleware (CRITICAL)

```php
// ❌ FORBIDDEN - CI FAIL
if (Auth::user()->role->slug === 'admin') { ... }
if (Auth::user()->role_id === 1) { ... }
if (Auth::user()->hasRole('admin')) { ... }

// ✅ MANDATORY - Use UserContext
$context = app(UserContextResolver::class)->getFromSession();
if ($context && $context->role === 'admin') { ... }
```

### Rule 4: Session Invalidation on Privilege Change (CRITICAL)

```php
// MANDATORY in UserObserver
public function updating(User $user): void
{
    if ($user->isDirty(['role_id', 'status', 'permissions'])) {
        $user->auth_version++;
        
        Log::info('User privilege changed - sessions invalidated', [
            'user_id' => $user->id,
            'old_role' => $user->getOriginal('role_id'),
            'new_role' => $user->role_id,
            'new_auth_version' => $user->auth_version,
        ]);
    }
}
```

### Rule 5: No Manual auth_version Modification (CRITICAL)

```php
// ❌ FORBIDDEN - NEVER
$user->auth_version = 10;
DB::table('users')->update(['auth_version' => 10]);

// ✅ MANDATORY - Always use increment
$user->increment('auth_version');
```

---

## 4. ENFORCEMENT TECHNIQUE

### 4.1 PHPStan Rules (CI Blocking)

**File:** `phpstan-auth-rules.neon`

```neon
parameters:
    ignoreErrors:
        # Block direct role access
        -
            message: '#Call to an undefined method.*->role#'
            path: app/Http/Controllers/*
        -
            message: '#Access to property \$role_id on.*User#'
            path: app/Http/Controllers/*
            
    # Custom rules
    rules:
        - App\PHPStan\Rules\NoDirectRoleAccessRule
        - App\PHPStan\Rules\RequireEnsureMiddlewareRule
```

**Custom Rule Example:**
```php
// app/PHPStan/Rules/NoDirectRoleAccessRule.php
class NoDirectRoleAccessRule implements Rule
{
    public function getNodeType(): string
    {
        return PropertyFetch::class;
    }
    
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->name === 'role' || $node->name->name === 'role_id') {
            if ($this->isUserModel($node->var, $scope)) {
                return ['Direct role access forbidden. Use UserContextResolver.'];
            }
        }
        return [];
    }
}
```

### 4.2 CI Pipeline Checks

**File:** `.github/workflows/auth-security.yml`

```yaml
name: Auth Security Checks

on: [push, pull_request]

jobs:
  auth-security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      # Check 1: No routes with 'auth' without 'ensure'
      - name: Validate Route Middleware
        run: |
          php artisan route:list --json > routes.json
          if jq '.[] | select(.middleware | contains(["auth"]) and (contains(["ensure"]) | not))' routes.json | grep -q .; then
            echo "ERROR: Routes with 'auth' middleware must use 'ensure'"
            exit 1
          fi
      
      # Check 2: PHPStan auth rules
      - name: PHPStan Auth Rules
        run: vendor/bin/phpstan analyse -c phpstan-auth-rules.neon
      
      # Check 3: No direct Auth::user()->role
      - name: Grep Forbidden Patterns
        run: |
          if grep -r "Auth::user()->role" app/Http/Controllers/; then
            echo "ERROR: Direct role access forbidden"
            exit 1
          fi
```

### 4.3 Pre-commit Hook

**File:** `.git/hooks/pre-commit`

```bash
#!/bin/bash

# Check for forbidden patterns
if git diff --cached --name-only | grep -E '\.(php)$' | xargs grep -n "Auth::user()->role"; then
    echo "❌ COMMIT BLOCKED: Direct role access forbidden"
    echo "Use: UserContextResolver::getFromSession()"
    exit 1
fi

# Check route middleware
php artisan route:list --json | jq '.[] | select(.middleware | contains(["auth"]) and (contains(["ensure"]) | not))' > /tmp/bad_routes.json
if [ -s /tmp/bad_routes.json ]; then
    echo "❌ COMMIT BLOCKED: Routes with 'auth' must use 'ensure'"
    cat /tmp/bad_routes.json
    exit 1
fi

exit 0
```

---

## 5. MONITORING & ALERTING

### Critical Metrics

```php
// app/Services/Auth/AuthMetrics.php
class AuthMetrics
{
    public function recordAuthVersionMismatch(User $user, int $sessionVersion): void
    {
        Metrics::increment('auth.version_mismatch', [
            'user_id' => $user->id,
            'session_version' => $sessionVersion,
            'db_version' => $user->auth_version,
        ]);
        
        // Alert if > 10 mismatches/min (possible attack)
        if ($this->getMismatchRate() > 10) {
            Alert::security('High auth_version mismatch rate');
        }
    }
    
    public function recordFutureVersion(User $user, int $sessionVersion): void
    {
        Metrics::increment('auth.future_version', [
            'user_id' => $user->id,
            'delta' => $sessionVersion - $user->auth_version,
        ]);
        
        // CRITICAL: Always alert on future version
        Alert::critical('Future auth_version detected - possible attack', [
            'user_id' => $user->id,
            'session_version' => $sessionVersion,
            'db_version' => $user->auth_version,
        ]);
    }
}
```

### Dashboard Metrics

- `auth.version_mismatch` - Rate of session invalidations
- `auth.future_version` - Future version attacks (MUST be 0)
- `auth.login_success` - Successful logins
- `auth.login_failed` - Failed login attempts
- `auth.session_duration_p95` - Session lifetime P95

### Alerts

| Metric | Threshold | Action |
|--------|-----------|--------|
| `auth.future_version` | > 0 | CRITICAL - Investigate immediately |
| `auth.version_mismatch` | > 100/min | WARNING - Possible mass role change |
| `auth.login_failed` | > 1000/min | WARNING - Possible brute force |

---

## 6. BREAKING CHANGE POLICY

### Definition

A **breaking change** is any modification that:
1. Changes UserContext structure
2. Modifies auth_version behavior
3. Alters middleware execution order
4. Changes session invalidation logic

### Required Process

1. **Architecture Review** - Mandatory approval
2. **Security Review** - Penetration test if applicable
3. **Migration Plan** - How to handle active sessions
4. **Rollback Plan** - How to revert if issues
5. **Version Bump** - Increment AUTH_FLOW.md version

### Migration Strategy

```php
// Example: Adding field to UserContext
class UserContext
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly ?int $creatorId,
        public readonly int $authVersion,
        public readonly ?array $permissions = null, // NEW FIELD
    ) {}
    
    // CRITICAL: Support old sessions
    public static function fromSession(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            email: $data['email'],
            role: $data['role'],
            creatorId: $data['creator_id'] ?? null,
            authVersion: $data['auth_version'],
            permissions: $data['permissions'] ?? null, // Default for old sessions
        );
    }
}
```

---

## 7. DISASTER RECOVERY

### Scenario: Corrupted auth_version

**Symptoms:** All users logged out, cannot re-login

**Diagnosis:**
```sql
-- Check for negative or absurd values
SELECT id, email, auth_version 
FROM users 
WHERE auth_version < 0 OR auth_version > 1000000;
```

**Recovery:**
```sql
-- Reset all auth_versions (EMERGENCY ONLY)
UPDATE users SET auth_version = 1;

-- Clear all sessions
TRUNCATE TABLE sessions;
```

**Prevention:** Database constraints
```sql
ALTER TABLE users 
ADD CONSTRAINT auth_version_positive 
CHECK (auth_version >= 0 AND auth_version <= 1000000000);
```

---

## 8. COMPLIANCE CHECKLIST

Before deploying ANY auth-related change:

- [ ] PHPStan passes with auth rules
- [ ] CI auth security checks pass
- [ ] No direct `Auth::user()->role` in code
- [ ] All protected routes use `ensure` middleware
- [ ] auth_version validation includes `>` check (future versions)
- [ ] UserContext immutability preserved
- [ ] Middleware order unchanged
- [ ] Tests updated (LoginTest, EnsureAuthenticatedTest)
- [ ] Monitoring metrics reviewed
- [ ] Security team notified (if breaking change)

---

## APPENDIX: THREAT MODEL

### Threat 1: Privilege Escalation via Session Replay

**Attack:** Attacker captures admin session, admin demoted, attacker replays session

**Mitigation:** ✅ auth_version invalidates session on role change

### Threat 2: Session Fixation

**Attack:** Attacker sets UserContext with elevated privileges

**Mitigation:** ✅ Validate auth_version ≤ DB, validate structure

### Threat 3: Race Condition Exploitation

**Attack:** Attacker triggers role change during login to get stale context

**Mitigation:** ✅ Refresh user after Auth::attempt()

### Threat 4: Database Rollback Attack

**Attack:** Attacker triggers DB rollback to revert auth_version

**Mitigation:** ✅ Validate auth_version ≤ DB (detects future versions)

### Threat 5: Middleware Bypass

**Attack:** Developer adds route with `auth` instead of `ensure`

**Mitigation:** ✅ CI blocks deployment, pre-commit hook prevents commit

---

**Document Status:** NORMATIVE  
**Enforcement:** AUTOMATED (CI/CD)  
**Review Cycle:** Quarterly  
**Last Updated:** 2026-01-07
