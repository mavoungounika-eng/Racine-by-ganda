# Racine POS API Reference
Version 1.0.0

## Endpoints (21 total)

- `/api/pos/offline-status`
- `/api/pos/offline/status`
- `/api/pos/offline/queue`
- `/api/pos/offline/queue/flush`
- `/api/pos/offline/queue/{item}/submit`
- `/api/pos/sessions/open`
- `/api/pos/sessions/current`
- `/api/pos/sessions/{session}/prepare-close`
- `/api/pos/sessions/{session}/close`
- `/api/pos/sessions/{session}/z-report`
- `/api/pos/sessions/{session}/adjustments`
- `/api/pos/sessions/{session}/sales`
- `/api/pos/sales`
- `/api/pos/sales/{sale}`
- `/api/pos/sales/{sale}/cancel`
- `/api/pos/payments/{payment}/status`
- `/api/pos/payments/{payment}/confirm-card`
- `/api/pos/products`
- `/api/pos/products/search`
- `/api/pos/products/categories`
- `/api/pos/products/{product}`

## Authentication
All endpoints require Device JWT:
```
Authorization: Bearer <device_jwt>
```

## Response Envelope
```json
{
  "success": true|false,
  "data": {} | null,
  "error": { "code": "...", "message": "..." } | null,
  "meta": { "request_id": "uuid", "timestamp": "..." }
}
```

## Error Codes
| Code | HTTP | Description |
|---|---|---|
| UNAUTHORIZED | 401 | Invalid/missing JWT |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_ERROR | 422 | Invalid request data |
| SESSION_ALREADY_OPEN | 409 | Machine has open session |
| SESSION_NOT_FOUND | 404 | No open session |
| SESSION_ALREADY_CLOSED | 409 | Session already closed |
| SALE_CREATION_FAILED | 400 | Could not create sale |
| SALE_ALREADY_CONFIRMED | 409 | Cannot cancel confirmed sale |
| PAYMENT_ALREADY_CONFIRMED | 409 | Payment already confirmed |
| PAYMENT_CONFIRM_FAILED | 400 | Could not confirm payment |
| MACHINE_MISMATCH | 403 | Item belongs to different machine |
| Z_REPORT_UNAVAILABLE | 404 | Z-report not available |

## Rate Limiting
300 requests/minute per device. Keyed by device ID.

## Idempotency
Use X-Idempotency-Key header on POST requests to prevent duplicates:
```
X-Idempotency-Key: <uuid-v4>
```
Required for: /sessions/open, /sales, /sessions/{id}/close
