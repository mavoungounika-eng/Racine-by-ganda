# RACINE BY GANDA

[![Tests](https://github.com/YOUR_USERNAME/racine-backend/actions/workflows/tests.yml/badge.svg)](https://github.com/YOUR_USERNAME/racine-backend/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

E-commerce platform with creator marketplace, ERP, CRM, and financial management.

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/racine-backend.git
cd racine-backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Start development server
php artisan serve
```

## 🧪 Testing

### Local Testing

```bash
# Run all tests
php artisan test

# Run with profiling
php artisan test --profile

# Stop on first failure
php artisan test --stop-on-failure

# Run in parallel
php artisan test --parallel
```

### Cross-Platform Test Scripts

```bash
# Linux/macOS/Git Bash
./scripts/run-tests.sh --parallel --profile

# Windows PowerShell
.\scripts\run-tests.ps1 -Parallel -Profile
```

## 📊 Quality Gates

Our CI/CD pipeline enforces the following quality standards:

| Gate | Threshold | Status |
|:---|:---|:---:|
| **Test Suite** | 100% pass (MySQL) | ✅ Enforced |
| **N+1 Queries** | Regression tests | ✅ Enforced |
| **Code Coverage** | Informational only | ℹ️ Tracked |
| **Performance** | Query count limits | ✅ Enforced |

### Performance Regression Tests

Critical pages are protected against N+1 query regressions:

- **Creator Dashboard**: ≤ 40 queries
- **Admin Orders**: ≤ 20 queries
- **ERP Stock**: ≤ 20 queries

## 🏗️ Architecture

### Modules
- **ERP**: Stock management, suppliers, purchases, production
- **CRM**: Customer relationship management
- **Finance**: OHADA-compliant accounting, ledger, reports
- **Payments**: Multi-provider (Stripe, Monetbil), webhooks
- **Subscriptions**: Creator plans, billing

### Key Features
- Multi-vendor marketplace
- Real-time inventory tracking
- OHADA financial reporting
- Multi-payment gateway support
- Role-based access control (RBAC)
- Two-factor authentication (2FA)

## 📚 Documentation

- [Test Strategy](docs/PHASE_3_TEST_STRATEGY.md)
- [Test Execution Profile](docs/TEST_EXECUTION_PROFILE.md)
- [Performance Audit](docs/PERFORMANCE_N_PLUS_ONE_AUDIT.md)
- [CI Runbook](docs/CI_RUNBOOK.md) *(coming soon)*

## 🔧 Development

### Requirements
- PHP 8.1 or 8.2
- MySQL 8.0
- Composer 2.x
- Node.js 18+ (for frontend assets)

### Database
- **Production**: MySQL 8.0
- **Testing**: MySQL 8.0 (CI), SQLite (local quick tests)

### CI/CD
Tests run automatically on:
- Push to `main`, `develop`, `security/**`
- Pull requests to `main`, `develop`

**Execution Modes**:
- **PR**: Stop on first failure (fast feedback)
- **Main**: Parallel execution (comprehensive)

## 📈 Performance

- **Test Suite**: ~120s (local), ~5min (CI parallel)
- **N+1 Protection**: Active on critical paths
- **Query Monitoring**: Performance dashboard available

## 🤝 Contributing

1. Create feature branch from `develop`
2. Write tests for new features
3. Ensure CI passes (all tests green)
4. Submit PR with clear description

## 📝 License

Proprietary - RACINE BY GANDA

---

**Status**: Production-ready with industrial-grade test coverage
