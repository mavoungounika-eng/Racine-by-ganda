# Activation des terminaux POS

## Endpoint admin

`POST /api/admin/pos/devices/{device}/activate`

- Auth: `auth:sanctum`
- Role: `admin`
- Controller: `App\Http\Controllers\Api\Admin\PosDeviceController@activate`

## Exemple cURL

```bash
curl -X POST "https://<host>/api/admin/pos/devices/<device-uuid>/activate" \
  -H "Authorization: Bearer <admin-sanctum-token>" \
  -H "Accept: application/json"
```

## Réponse attendue (200)

```json
{
  "success": true,
  "data": {
    "id": "...",
    "machine_id": "...",
    "name": "POS-xxxx",
    "status": "active",
    "blocked_at": null,
    "blocked_reason": null
  },
  "message": "POS terminal activated successfully."
}
```

## Procédure opératoire

1. Installer/lancer le POS sur le poste client.
2. Le terminal s'auto-enregistre (statut `pending`).
3. Depuis l'interface admin/backoffice ou API, activer le terminal via endpoint.
4. Le staff peut se connecter et opérer.

## Message frontend

Si terminal `pending`, l'écran login affiche:

`Terminal en attente d'activation administrateur. Contactez l'admin avant de vous connecter.`
