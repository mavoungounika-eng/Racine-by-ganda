# MAPPING RÔLES & PERMISSIONS — RACINE BY GANDA

**Version:** 1.0  
**Date:** 2026-01-19

---

## 🎯 CLARIFICATION PRODUIT

### Staff ≠ Rôle Métier

**`staff` est une catégorie technique**, pas un rôle métier.

**Rôles métiers réels :**
- `vendeur`
- `caissier`
- `gestionnaire_stock`
- `admin`
- `super_admin`

---

## 📊 TABLE COMPLÈTE RÔLES → PERMISSIONS → UI

### Vendeur

**Responsabilités :** Vente produits, consultation commandes

| Permission | Zone UI Visible |
|------------|-----------------|
| `view-products` | Liste produits, détails produits |
| `view-orders` | Liste commandes, détails commandes |

**Zones MASQUÉES :**
- ❌ Analytics ventes
- ❌ Gestion utilisateurs
- ❌ Gestion stock
- ❌ Paramètres système

---

### Caissier

**Responsabilités :** Gestion paiements, consultation commandes

| Permission | Zone UI Visible |
|------------|-----------------|
| `view-orders` | Liste commandes |
| `view-all-orders` | Toutes commandes (pas seulement siennes) |
| `process-payments` | Interface paiements, remboursements |

**Zones MASQUÉES :**
- ❌ Analytics ventes
- ❌ Gestion utilisateurs
- ❌ Gestion produits
- ❌ Gestion stock

---

### Gestionnaire Stock

**Responsabilités :** Gestion stocks, analytics stock

| Permission | Zone UI Visible |
|------------|-----------------|
| `view-products` | Liste produits |
| `edit-products` | Modification produits |
| `view-stock` | Niveaux stock |
| `edit-stock` | Modification stock |
| `view-stock-analytics` | Analytics stock (ruptures, mouvements) |

**Zones MASQUÉES :**
- ❌ Analytics ventes
- ❌ Gestion utilisateurs
- ❌ Gestion paiements
- ❌ Paramètres système

---

### Admin

**Responsabilités :** Administration complète (sauf config système)

| Permission | Zone UI Visible |
|------------|-----------------|
| Toutes permissions sauf `access-system-config` | Tout dashboard |

**Zones MASQUÉES :**
- ❌ Configuration système critique (réservé super_admin)

---

### Super Admin

**Responsabilités :** Administration totale + configuration système

| Permission | Zone UI Visible |
|------------|-----------------|
| **TOUTES** (bypass via `Gate::before()`) | **TOUT** |

**Aucune zone masquée.**

---

## 🔄 DASHBOARDS UNIQUES

**Principe :** 1 dashboard équipe unique, filtré par permissions.

**Route :** `/staff/dashboard` → `admin.dashboard`

**Différenciation :** `@can()` dans Blade

**Exemple :**

```blade
@can('view-sales-analytics')
    <!-- KPI Ventes (admin uniquement) -->
@endcan

@can('view-stock-analytics')
    <!-- KPI Stock (gestionnaire_stock + admin) -->
@endcan

@can('view-all-orders')
    <!-- Commandes (tous staff) -->
@endcan
```

---

## ✅ VALIDATION

**Question :** "On donne quoi au staff ?"  
**Réponse :** **Staff n'existe pas en tant que rôle métier.**

**Question correcte :** "On donne quoi au vendeur/caissier/gestionnaire_stock ?"  
**Réponse :** Voir table ci-dessus.

---

**Document créé par:** Antigravity  
**Date:** 2026-01-19  
**Statut:** 🔒 **OFFICIEL**
