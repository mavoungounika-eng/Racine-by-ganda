---
paths:
  - "modules/ERP*"
  - "app/Http/Controllers/Erp*"
  - "app/Services/Erp*"
  - "tests/Feature/Erp*"
  - "tests/Unit/Erp*"
---
# Règles — Module ERP

## Architecture
- Module autonome dans modules/ERP/
- Namespace : Modules\ERP\
- Routes protégées par middleware : ensure:staff,admin,super_admin + 2fa

## Fonctionnalités
- Gestion des fournisseurs (ErpSupplier)
- Gestion des matières premières (ErpRawMaterial)
- Ordres de production (ProductionOrder)
- Gestion du stock (StockService)
- Tableaux de bord ERP

## Conventions ERP
- Toujours vérifier les permissions ERP avant toute action
- Le middleware 2fa vérifie session("2fa_verified") — requis pour admin/super_admin
- Les routes ERP nécessitent withSession(["2fa_verified" => true]) en test

## Tests ERP
- modules/ERP/Tests/ — fichiers de tests du module
- tests/Feature/Erp/ — PROTÉGÉS, ne jamais modifier
- tests/Feature/ERPProduction/ — PROTÉGÉS, ne jamais modifier
- Pour les tests de routes ERP : actingAs($user)->withSession(["2fa_verified" => true])
- Assertions flexibles : assertTrue(in_array($status, [200, 403]))

## Cache ERP
- TTL cache tableau de bord : configurable via config("erp.cache_ttl")
- Invalider le cache après chaque mise à jour stock
