# Phase 3 Environment Configuration Guide

This document explains all environment variables added in Phase 3 (Optimizations).

## 🔍 Sentry Error Tracking

### Required Variables

```bash
# Sentry DSN (Data Source Name) - Get from sentry.io project settings
SENTRY_LARAVEL_DSN=https://your-key@o123456.ingest.sentry.io/123456
```

### Optional Variables

```bash
# Traces sampling (0.0 to 1.0) - 0.1 = 10% of requests
SENTRY_TRACES_SAMPLE_RATE=0.1

# Profiling sampling (0.0 to 1.0)
SENTRY_PROFILES_SAMPLE_RATE=0.1

# Environment tag (auto-set from APP_ENV)
SENTRY_ENVIRONMENT="${APP_ENV}"

# Release version (e.g., git commit hash)
SENTRY_RELEASE=v1.0.0

# Send user PII (email, IP) - false for GDPR compliance
SENTRY_SEND_DEFAULT_PII=false

# Enable log breadcrumbs
SENTRY_ENABLE_LOGS=false
```

---

## 🛡️ Queue Protection - Circuit Breaker

Prevents queue overload by temporarily stopping job processing after repeated failures.

```bash
# Enable/disable circuit breaker
QUEUE_CIRCUIT_BREAKER_ENABLED=true

# Number of consecutive failures before opening circuit
QUEUE_CIRCUIT_BREAKER_FAILURE_THRESHOLD=5

# Number of consecutive successes to close circuit
QUEUE_CIRCUIT_BREAKER_SUCCESS_THRESHOLD=2

# Cooldown period (seconds) before attempting reset
QUEUE_CIRCUIT_BREAKER_TIMEOUT=60

# Redis TTL for circuit state (seconds)
QUEUE_CIRCUIT_BREAKER_TTL=3600
```

**Recommended Values:**
- **Production:** `FAILURE_THRESHOLD=5`, `TIMEOUT=60`
- **Staging:** `FAILURE_THRESHOLD=3`, `TIMEOUT=30`
- **Development:** `ENABLED=false`

---

## ⏱️ Queue Protection - Rate Limiting

Throttles job processing to prevent system overload.

```bash
# Enable/disable rate limiting
QUEUE_RATE_LIMIT_ENABLED=true

# Webhooks: max 100 jobs per 60 seconds
QUEUE_RATE_LIMIT_WEBHOOKS_MAX=100
QUEUE_RATE_LIMIT_WEBHOOKS_WINDOW=60

# Emails: max 50 jobs per 60 seconds
QUEUE_RATE_LIMIT_EMAILS_MAX=50
QUEUE_RATE_LIMIT_EMAILS_WINDOW=60

# Notifications: max 200 jobs per 60 seconds
QUEUE_RATE_LIMIT_NOTIFICATIONS_MAX=200
QUEUE_RATE_LIMIT_NOTIFICATIONS_WINDOW=60
```

**Recommended Values by Environment:**

| Environment | Webhooks | Emails | Notifications |
|-------------|----------|--------|---------------|
| Production  | 100/min  | 50/min | 200/min       |
| Staging     | 50/min   | 25/min | 100/min       |
| Development | Disabled | Disabled | Disabled     |

---

## 📊 Queue Monitoring

Collects metrics and exposes them via `/metrics` endpoint (Prometheus format).

```bash
# Enable/disable monitoring
QUEUE_MONITOR_ENABLED=true

# Queue size thresholds
QUEUE_MONITOR_SIZE_WARNING=500
QUEUE_MONITOR_SIZE_CRITICAL=1000

# Processing time thresholds (seconds)
QUEUE_MONITOR_PROCESSING_TIME_WARNING=5.0
QUEUE_MONITOR_PROCESSING_TIME_CRITICAL=10.0

# Failure rate thresholds (0.0 to 1.0)
QUEUE_MONITOR_FAILURE_RATE_WARNING=0.05   # 5%
QUEUE_MONITOR_FAILURE_RATE_CRITICAL=0.10  # 10%
```

---

## 🚨 Alert System

Sends notifications via Slack, Email, and Logs when thresholds are exceeded.

### Slack Configuration

```bash
# Slack incoming webhook URL
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Channel to post alerts
SLACK_ALERT_CHANNEL="#alerts"

# Bot username
SLACK_ALERT_USERNAME="RACINE Monitoring"
```

**Setup Instructions:**
1. Go to https://api.slack.com/apps
2. Create new app → Incoming Webhooks
3. Activate webhooks and add to workspace
4. Copy webhook URL to `SLACK_WEBHOOK_URL`

### Email Configuration

```bash
# Comma-separated list of alert recipients
ALERT_EMAIL_RECIPIENTS="admin@racine.com,tech@racine.com"

# Enable/disable all alerts
ALERTS_ENABLED=true
```

### Alert Thresholds

```bash
# Queue size alerts
ALERT_QUEUE_SIZE_WARNING=500
ALERT_QUEUE_SIZE_CRITICAL=1000

# Processing time alerts (seconds)
ALERT_PROCESSING_TIME_WARNING=5.0
ALERT_PROCESSING_TIME_CRITICAL=10.0

# Failure rate alerts (0.0 to 1.0)
ALERT_FAILURE_RATE_WARNING=0.05   # 5%
ALERT_FAILURE_RATE_CRITICAL=0.10  # 10%

# Error rate alerts (0.0 to 1.0)
ALERT_ERROR_RATE_WARNING=0.01     # 1%
ALERT_ERROR_RATE_CRITICAL=0.05    # 5%
```

**Alert Severity Levels:**
- **Critical** → Slack + Email + Logs
- **High** → Slack + Logs
- **Warning** → Slack + Logs
- **Info** → Logs only

---

## 🔧 Quick Setup Guide

### 1. Copy Environment File

```bash
cp .env.example .env
```

### 2. Configure Sentry (Optional but Recommended)

1. Create account at https://sentry.io
2. Create new Laravel project
3. Copy DSN to `SENTRY_LARAVEL_DSN`

### 3. Configure Slack Alerts (Optional)

1. Create Slack webhook (see above)
2. Set `SLACK_WEBHOOK_URL`
3. Set `ALERT_EMAIL_RECIPIENTS`

### 4. Adjust Thresholds

Review and adjust thresholds based on your traffic:

```bash
# Low traffic (<1000 jobs/day)
QUEUE_MONITOR_SIZE_WARNING=100
QUEUE_MONITOR_SIZE_CRITICAL=200

# Medium traffic (1000-10000 jobs/day)
QUEUE_MONITOR_SIZE_WARNING=500
QUEUE_MONITOR_SIZE_CRITICAL=1000

# High traffic (>10000 jobs/day)
QUEUE_MONITOR_SIZE_WARNING=2000
QUEUE_MONITOR_SIZE_CRITICAL=5000
```

### 5. Test Configuration

```bash
# Test alert system
php artisan tinker
>>> app(\App\Services\Monitoring\AlertService::class)->test()

# Check metrics endpoint
curl http://localhost/metrics

# Check health endpoint
curl http://localhost/health
```

---

## 📈 Monitoring Endpoints

### Prometheus Metrics

```
GET /metrics
Authorization: Basic Auth (configured in web server)
```

**Metrics exposed:**
- `queue_size{queue="default"}` - Current queue size
- `queue_processing_time_seconds{queue="default"}` - Avg processing time
- `queue_failure_rate{queue="default"}` - Job failure rate
- `circuit_breaker_state{queue="default"}` - Circuit breaker state (0=closed, 1=open, 2=half-open)

### Health Check

```
GET /health
```

**Response:**
```json
{
  "status": "healthy",
  "alerts": [],
  "timestamp": "2026-02-12T21:30:00Z"
}
```

### Queue Metrics Dashboard

```
GET /admin/queue-metrics
Authorization: Admin role required
```

**Response:**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "default": {
        "size": 42,
        "processing_time": 2.5,
        "failure_rate": 0.02
      }
    },
    "alerts": [
      {
        "severity": "warning",
        "message": "Queue size approaching threshold"
      }
    ]
  }
}
```

---

## 🔒 Security Considerations

### Sensitive Variables

**Never commit to Git:**
- `SENTRY_LARAVEL_DSN`
- `SLACK_WEBHOOK_URL`
- `ALERT_EMAIL_RECIPIENTS`

**Add to `.gitignore`:**
```
.env
.env.local
.env.production
```

### Production Checklist

- [ ] Set `SENTRY_SEND_DEFAULT_PII=false` (GDPR)
- [ ] Configure `ALERT_EMAIL_RECIPIENTS` with ops team
- [ ] Set `QUEUE_CIRCUIT_BREAKER_ENABLED=true`
- [ ] Set `QUEUE_RATE_LIMIT_ENABLED=true`
- [ ] Test alert delivery (Slack + Email)
- [ ] Configure Prometheus scraping
- [ ] Set up Grafana dashboards

---

## 🆘 Troubleshooting

### Circuit Breaker Stuck Open

```bash
# Reset circuit breaker via admin dashboard
POST /admin/queue-metrics/circuit-breaker/{queue}/reset

# Or via tinker
php artisan tinker
>>> app(\App\Services\Queue\QueueCircuitBreaker::class)->reset('default')
```

### Rate Limiter Blocking Jobs

```bash
# Clear rate limiter
POST /admin/queue-metrics/rate-limiter/{jobType}/reset

# Or via tinker
php artisan tinker
>>> app(\App\Services\Queue\QueueRateLimiter::class)->clear('webhooks')
```

### Alerts Not Sending

1. Check `ALERTS_ENABLED=true`
2. Verify Slack webhook URL
3. Check email configuration in `.env`
4. Test manually:
```bash
php artisan tinker
>>> app(\App\Services\Monitoring\AlertService::class)->test()
```

### Metrics Not Appearing

1. Check `/metrics` endpoint (should return text/plain)
2. Verify `QUEUE_MONITOR_ENABLED=true`
3. Check Redis connection
4. Ensure jobs are being processed

---

## 📚 Additional Resources

- **Sentry Docs:** https://docs.sentry.io/platforms/php/guides/laravel/
- **Prometheus Docs:** https://prometheus.io/docs/
- **Slack Webhooks:** https://api.slack.com/messaging/webhooks
- **Circuit Breaker Pattern:** https://martinfowler.com/bliki/CircuitBreaker.html

---

**Last Updated:** 2026-02-12  
**Phase:** 3 - Optimizations  
**Version:** 1.0.0
