---
paths:
  - "app/Http/Controllers/Pos*"
  - "app/Services/Pos*"
  - "app/Models/Pos*"
  - "tests/Feature/Pos*"
  - "tests/Unit/Pos*"
  - "modules/POS*"
---
# Règles — Module POS (Point of Sale)

## Architecture
- Electron 28 — code dans racine-pos-electron/
- Backend Laravel expose des API JSON consommées par Electron
- Le POS doit fonctionner OFFLINE — pas de dépendance réseau pour les opérations de caisse
- Synchronisation différée quand la connexion revient

## Modèles critiques
- PosSession — session de caisse (opened_by, closed_by, status)
- PosSale — vente (session_id, status: pending/completed/cancelled/refunded)
- PosPayment — paiement (pos_sale_id, status: pending/completed/failed/refunded)
- PosOperatorAuditLog — journal des actions opérateur

## Statuts POS
- PosSale::STATUS_PENDING / COMPLETED / CANCELLED / REFUNDED
- PosPayment::STATUS_PENDING / COMPLETED / FAILED / REFUNDED
- Toujours utiliser les constantes — jamais les strings en dur

## Conventions POS
- Toute la logique métier passe par PosSaleService
- Une session doit être ouverte avant toute vente
- Fermeture de session = réconciliation financière obligatoire
- Les remboursements (refunded) doivent restaurer le stock via restoreStockForSale()
- Fallback manuel si ERP StockService indisponible

## Tests POS — FICHIERS PROTÉGÉS
- tests/Feature/Pos/ — NE JAMAIS MODIFIER
- Utiliser RefreshDatabase sur tous les tests POS
- Toujours créer une PosSession avant de tester une vente

## Problèmes connus
- restoreStockForSale() utilise Modules\ERP\Services\StockService::restockFromOrder()
- Fallback : $product->increment("stock", $item->quantity) si ERP indisponible
