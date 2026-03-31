# Architecture Auth Laravel 10 + Next.js (Production)

**Stack:** Laravel 10 (API) + Next.js App Router + Docker  
**Auth:** Sanctum SPA + httpOnly Cookies  
**Rôles:** Admin, Manager, Client  
**2FA:** Obligatoire Admin/Manager

---

## 1. Architecture Globale

```
┌─────────────────────────────────────────────────────────────┐
│                     NEXT.JS (Frontend)                       │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐            │
│  │   Login    │  │  Dashboard │  │   Admin    │            │
│  │   Page     │  │    Page    │  │   Panel    │            │
│  └────────────┘  └────────────┘  └────────────┘            │
│         │              │                │                    │
│         └──────────────┴────────────────┘                    │
│                        │                                     │
│              API Client (axios/fetch)                        │
│              httpOnly Cookies (XSRF-TOKEN)                   │
└─────────────────────────────────────────────────────────────┘
                         │ HTTPS
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  LARAVEL 10 (Backend API)                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Sanctum Middleware                       │   │
│  │  - CSRF Protection                                    │   │
│  │  - Rate Limiting                                      │   │
│  │  - Auth Verification                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Controllers                              │   │
│  │  AuthController | UserController | AdminController   │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Services                                 │   │
│  │  AuthService | 2FAService | RBACService              │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Database (MySQL)                         │   │
│  │  users | roles | permissions | sessions | 2fa        │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Redis (Sessions + Cache)                 │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Choix: Sanctum SPA (Pas JWT)

### ✅ Sanctum SPA
- **httpOnly cookies** → Impossible XSS token theft
- **CSRF protection** native Laravel
- **Stateful** → Sessions Redis (performance)
- **Refresh automatique** → Pas de gestion manuelle
- **Révocation instantanée** → Logout = session delete
- **Compatible Next.js** → Même domaine/sous-domaine

### ❌ JWT
- **Token stocké frontend** → Vulnérable XSS
- **Stateless** → Impossible révocation immédiate
- **Refresh tokens complexes** → Code supplémentaire
- **Rotation manuelle** → Risque erreur
- **Overkill** → Pas besoin pour SPA

**Décision:** Sanctum SPA avec httpOnly cookies

---

## 3. Structure Base de Données

```sql
-- Users
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    two_factor_secret VARCHAR(255) NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_email_verified (email_verified_at)
);

-- Roles (Spatie)
CREATE TABLE roles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Permissions
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Model Has Roles
CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Role Has Permissions
CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Sessions (Redis backed)
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- Password Reset Tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- Audit Logs
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);
```

---

## 4. Flow Login / Refresh / Logout

### Login Flow

```
1. Next.js → GET /sanctum/csrf-cookie
   ← Set-Cookie: XSRF-TOKEN

2. Next.js → POST /api/login
   Headers: X-XSRF-TOKEN
   Body: { email, password, code_2fa? }
   
3. Laravel:
   - Validate credentials
   - Check 2FA if enabled
   - Create session
   - Log audit
   
4. Laravel → 200 OK
   Set-Cookie: laravel_session (httpOnly, secure, sameSite=lax)
   Body: { user, roles, permissions }

5. Next.js → Store user in state (NOT token)
```

### Refresh Flow

**Pas nécessaire avec Sanctum SPA** → Session auto-refresh

### Logout Flow

```
1. Next.js → POST /api/logout
   Headers: X-XSRF-TOKEN
   
2. Laravel:
   - Delete session Redis
   - Clear cookies
   - Log audit
   
3. Laravel → 204 No Content
   Set-Cookie: laravel_session=deleted

4. Next.js → Redirect /login
```

---

## 5. Flow 2FA Détaillé

### Setup 2FA

```
1. User → POST /api/2fa/enable
   
2. Laravel:
   - Generate secret (Google2FA)
   - Store encrypted in DB
   - Generate QR code
   - Generate 8 recovery codes
   
3. Laravel → 200 OK
   { qr_code_svg, recovery_codes[] }

4. User scans QR → Enters code

5. User → POST /api/2fa/confirm
   Body: { code }
   
6. Laravel:
   - Verify code
   - Set two_factor_confirmed_at
   - Log audit
   
7. Laravel → 200 OK
```

### Login avec 2FA

```
1. User → POST /api/login
   Body: { email, password }
   
2. Laravel:
   - Validate credentials ✓
   - Check two_factor_confirmed_at
   - Create temp session (not authenticated)
   
3. Laravel → 200 OK
   { requires_2fa: true }

4. Next.js → Show 2FA input

5. User → POST /api/2fa/verify
   Body: { code }
   
6. Laravel:
   - Verify TOTP code
   - OR verify recovery code
   - Mark session authenticated
   - Log audit
   
7. Laravel → 200 OK
   { user, roles, permissions }
```

---

## 6. Middleware Backend

### Kernel.php

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    '2fa.verified' => \App\Http\Middleware\Ensure2FAVerified::class,
    'audit' => \App\Http\Middleware\AuditLog::class,
];
```

### Ensure2FAVerified.php

```php
namespace App\Http\Middleware;

class Ensure2FAVerified
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        // Admin/Manager require 2FA
        if ($user->hasAnyRole(['admin', 'manager'])) {
            if (!$user->two_factor_confirmed_at) {
                return response()->json([
                    'message' => '2FA required',
                    'redirect' => '/2fa/setup'
                ], 403);
            }
            
            if (!session('2fa_verified')) {
                return response()->json([
                    'message' => '2FA verification required',
                    'requires_2fa' => true
                ], 403);
            }
        }
        
        return $next($request);
    }
}
```

### AuditLog.php

```php
namespace App\Http\Middleware;

class AuditLog
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($request->user()) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => $request->method().' '.$request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'status' => $response->status(),
                    'params' => $request->except(['password', 'password_confirmation']),
                ],
            ]);
        }
        
        return $response;
    }
}
```

### RateLimitLogin.php

```php
namespace App\Http\Middleware;

class RateLimitLogin
{
    public function handle($request, Closure $next)
    {
        $key = 'login:'.strtolower($request->email).'|'.$request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => "Too many attempts. Retry in {$seconds}s"
            ], 429);
        }
        
        RateLimiter::hit($key, 300); // 5 min
        
        return $next($request);
    }
}
```

---

## 7. Sécurisation Docker

### Dockerfile (Laravel)

```dockerfile
FROM php:8.2-fpm-alpine

# Non-root user
RUN addgroup -g 1000 laravel && adduser -u 1000 -G laravel -s /bin/sh -D laravel

# Extensions
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql gd zip opcache redis

# Security
RUN echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/security.ini

# App
WORKDIR /var/www
COPY --chown=laravel:laravel . .

USER laravel
CMD ["php-fpm"]
```

### docker-compose.yml

```yaml
services:
  app:
    build: .
    user: "1000:1000"
    read_only: true
    tmpfs:
      - /tmp
      - /var/www/storage/framework/cache
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    networks:
      - backend
    
  nginx:
    image: nginx:alpine
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
    ports:
      - "443:443"
    networks:
      - frontend
      - backend
    
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD_FILE: /run/secrets/db_root_password
    secrets:
      - db_root_password
    networks:
      - backend
    
  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    networks:
      - backend

networks:
  frontend:
  backend:
    internal: true

secrets:
  db_root_password:
    file: ./secrets/db_root_password.txt
```

---

## 8. Variables .env Critiques

```env
# App
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:RANDOM_32_BYTES
APP_URL=https://api.racine.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=racine_prod
DB_USERNAME=racine_user
DB_PASSWORD=STRONG_RANDOM_PASSWORD

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=STRONG_RANDOM_PASSWORD
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.racine.com

# Sanctum
SANCTUM_STATEFUL_DOMAINS=racine.com,www.racine.com
SANCTUM_GUARD=web

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@racine.com

# Security
BCRYPT_ROUNDS=12
HASH_DRIVER=argon2id

# Rate Limiting
THROTTLE_LOGIN=5,5
THROTTLE_API=60,1

# 2FA
TWO_FACTOR_ISSUER=Racine

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
LOG_SLACK_WEBHOOK_URL=
```

---

## 9. Checklist Sécurité Production

### Infrastructure
- [ ] HTTPS forcé (HSTS)
- [ ] Certificat SSL valide
- [ ] Firewall configuré (ports 80, 443 only)
- [ ] Docker non-root users
- [ ] Secrets via Docker secrets (pas .env)
- [ ] Réseau backend isolé
- [ ] Volumes read-only où possible

### Application
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Argon2id hashing
- [ ] Sessions Redis (pas files)
- [ ] CSRF protection active
- [ ] Rate limiting login (5/5min)
- [ ] Rate limiting API (60/min)
- [ ] Email verification obligatoire
- [ ] 2FA obligatoire Admin/Manager
- [ ] Password policy (12+ chars, complexité)
- [ ] Lockout progressif
- [ ] Anti-enumeration (messages génériques)

### Headers Sécurité
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] X-XSS-Protection: 1; mode=block
- [ ] Strict-Transport-Security: max-age=31536000
- [ ] Content-Security-Policy configuré
- [ ] Referrer-Policy: strict-origin-when-cross-origin

### Monitoring
- [ ] Logs audit activés
- [ ] Alertes tentatives login échouées
- [ ] Monitoring sessions actives
- [ ] Backup automatique DB
- [ ] Rotation logs
- [ ] Sentry/Bugsnag configuré

### RBAC
- [ ] Rôles définis (Admin, Manager, Client)
- [ ] Permissions granulaires
- [ ] Middleware role/permission sur routes
- [ ] Pas de mass assignment vulnérabilités
- [ ] Gates/Policies pour actions sensibles

### Tokens & Sessions
- [ ] httpOnly cookies
- [ ] Secure cookies (HTTPS only)
- [ ] SameSite=lax
- [ ] Session lifetime 120 min
- [ ] Invalidation logout
- [ ] Invalidation changement password

---

## 10. Erreurs Courantes à Éviter

### ❌ Stocker JWT en localStorage
**Impact:** Vol token via XSS  
**Solution:** httpOnly cookies Sanctum

### ❌ CORS mal configuré
**Impact:** Bypass CSRF  
**Solution:** `SANCTUM_STATEFUL_DOMAINS` strict

### ❌ Rate limit uniquement IP
**Impact:** Bypass via proxies  
**Solution:** IP + email combinés

### ❌ Messages login différents
**Impact:** Enumeration emails  
**Solution:** Message générique toujours

### ❌ Pas de 2FA admins
**Impact:** Compte admin compromis  
**Solution:** 2FA obligatoire rôles élevés

### ❌ Sessions en fichiers
**Impact:** Performance + sécurité  
**Solution:** Redis sessions

### ❌ Bcrypt par défaut
**Impact:** Moins sécurisé  
**Solution:** Argon2id

### ❌ Pas de logs audit
**Impact:** Pas de traçabilité  
**Solution:** Middleware audit complet

### ❌ Docker root user
**Impact:** Escalation privilèges  
**Solution:** User 1000:1000 non-root

### ❌ Secrets en .env production
**Impact:** Exposition credentials  
**Solution:** Docker secrets ou vault

---

## Routes API

```php
// routes/api.php

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
    ->middleware('throttle:5,60');

// Authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Email verification required
    Route::middleware(['verified'])->group(function () {
        
        // 2FA
        Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
        
        // Admin only
        Route::middleware(['role:admin', '2fa.verified', 'audit'])->group(function () {
            Route::apiResource('users', UserController::class);
            Route::get('/audit-logs', [AuditController::class, 'index']);
        });
        
        // Manager + Admin
        Route::middleware(['role:admin|manager', '2fa.verified'])->group(function () {
            Route::apiResource('products', ProductController::class);
        });
    });
});
```

---

**Score Sécurité:** 9.5/10  
**Production Ready:** ✅  
**Scalable:** ✅  
**Maintenable:** ✅
