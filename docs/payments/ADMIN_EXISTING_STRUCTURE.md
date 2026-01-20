# 📋 Structure Admin Existante — RACINE BY GANDA

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Audit  
**Ticket :** #PH1-001

---

## 🎯 OBJECTIF

Documenter la structure admin existante pour garantir la cohérence du Payments Hub avec l'existant.

---

## 📍 ROUTES ADMIN

### Groupe de routes

**Fichier :** `routes/web.php` (lignes 276-374)

**Structure :**
```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('admin')->group(function () {
        // Routes protégées
    });
});
```

**Convention de nommage :**
- Préfixe URL : `/admin`
- Préfixe nom route : `admin.`
- Middleware : `admin` (vérifie accès admin)

### Routes existantes documentées

| Route | Controller | Nom Route | Description |
|-------|-----------|-----------|-------------|
| `GET /admin/dashboard` | `AdminDashboardController@index` | `admin.dashboard` | Dashboard principal |
| `GET /admin/users` | `AdminUserController@index` | `admin.users.index` | Liste utilisateurs |
| `GET /admin/orders` | `AdminOrderController@index` | `admin.orders.index` | Liste commandes |
| `GET /admin/products` | `AdminProductController@index` | `admin.products.index` | Liste produits |
| `GET /admin/categories` | `AdminCategoryController@index` | `admin.categories.index` | Liste catégories |
| `GET /admin/roles` | `AdminRoleController@index` | `admin.roles.index` | Liste rôles |
| `GET /admin/finances` | `AdminFinanceController@index` | `admin.finances.index` | Finances |
| `GET /admin/stats` | `AdminStatsController@index` | `admin.stats.index` | Statistiques |

**Recommandation Payments Hub :**
- Suivre la même convention : `admin.payments.*`
- Utiliser le même middleware `admin`
- Créer un groupe de routes dédié si nécessaire

---

## 🎨 LAYOUT ADMIN

### Fichier principal

**Fichier :** `resources/views/layouts/admin-master.blade.php`

**Structure :**
- Layout Bootstrap 4 (via `bootstrap.min.css`)
- Sidebar gauche (navigation)
- Topbar (header)
- Content wrapper (zone principale)

### Composants Bootstrap 4 utilisés

- **Cards** : `.card`, `.card-body`, `.card-header`
- **Tables** : `.table`, `.table-striped`
- **Badges** : `.badge`, `.bg-primary`, `.bg-success`, etc.
- **Buttons** : `.btn`, `.btn-primary`, `.btn-success`
- **Forms** : `.form-group`, `.form-control`
- **Modals** : `.modal`, `.modal-dialog`, `.modal-content`
- **Nav** : `.nav`, `.nav-tabs` (pour onglets)

### Navigation sidebar

**Structure :**
```blade
<nav class="admin-sidebar-nav">
    <div class="admin-nav-section-title">Section</div>
    <a href="{{ route('...') }}" class="admin-nav-link {{ request()->routeIs('...') ? 'active' : '' }}">
        <i class="fas fa-icon"></i>
        <span>Label</span>
    </a>
</nav>
```

**Sections existantes :**
1. **Tableau de bord** : Dashboard
2. **Gestion** : CMS, Messagerie, Utilisateurs, Rôles
3. **E-commerce** : Catégories, Produits, Commandes, Alertes stock
4. **Modules Business** : ERP, CRM
5. **Boutique** : POS, Scanner QR
6. **Outils** : Voir le site

**Recommandation Payments Hub :**
- Ajouter section "Paiements" dans la sidebar
- Utiliser icône Font Awesome appropriée (`fa-credit-card` ou `fa-money-bill-wave`)
- Sous-menus possibles : Overview, Providers, Transactions, Webhooks, Routing

---

## 🎨 FRAMEWORK CSS

### Bootstrap 4

**Version :** Bootstrap 4 (via CDN local : `racine/css/bootstrap.min.css`)

**Fichiers CSS additionnels :**
- `css/racine-variables.css` (variables custom)
- Font Awesome 6.4.0 (icônes)

**Classes Bootstrap 4 à utiliser pour Payments Hub :**

| Composant | Classes |
|-----------|---------|
| Card KPI | `.card`, `.card-body`, `.card-title`, `.card-text` |
| Table | `.table`, `.table-striped`, `.table-hover` |
| Badge status | `.badge`, `.badge-success`, `.badge-danger`, `.badge-warning` |
| Button | `.btn`, `.btn-primary`, `.btn-sm` |
| Form | `.form-group`, `.form-control`, `.form-label` |
| Modal | `.modal`, `.modal-dialog`, `.modal-content`, `.modal-header`, `.modal-body`, `.modal-footer` |
| Tabs | `.nav`, `.nav-tabs`, `.nav-item`, `.nav-link`, `.tab-content`, `.tab-pane` |
| Switch toggle | `.custom-control`, `.custom-switch`, `.custom-control-input`, `.custom-control-label` |

---

## 📁 STRUCTURE VUES ADMIN

### Convention de nommage

**Dossier :** `resources/views/admin/`

**Structure actuelle :**
```
admin/
├── dashboard.blade.php
├── users/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── products/
├── orders/
├── categories/
└── roles/
```

**Recommandation Payments Hub :**
```
admin/
└── payments/
    ├── index.blade.php          (Dashboard overview)
    ├── providers/
    │   └── index.blade.php
    ├── transactions/
    │   ├── index.blade.php
    │   └── show.blade.php
    ├── webhooks/
    │   └── index.blade.php
    └── routing/
        └── index.blade.php
```

---

## 🔗 CONVENTIONS DE NAMING

### Controllers

**Namespace :** `App\Http\Controllers\Admin\`

**Convention :** `Admin{Resource}Controller`

**Exemples existants :**
- `AdminDashboardController`
- `AdminUserController`
- `AdminOrderController`
- `AdminProductController`

**Recommandation Payments Hub :**
- Créer sous-dossier : `App\Http\Controllers\Admin\Payments\`
- Controllers :
  - `PaymentHubController` (dashboard)
  - `PaymentProviderController`
  - `PaymentTransactionController`
  - `PaymentWebhookController`
  - `PaymentRoutingController`

---

## ✅ CHECKLIST INTÉGRATION

- [x] Routes admin identifiées et documentées
- [x] Layout admin identifié (`admin-master.blade.php`)
- [x] Framework CSS confirmé (Bootstrap 4)
- [x] Structure navigation sidebar documentée
- [x] Conventions de naming validées
- [x] Structure vues admin documentée

---

## 📝 NOTES IMPORTANTES

1. **Bootstrap 4 obligatoire** : Toutes les vues Payments Hub doivent utiliser Bootstrap 4, pas Tailwind ni Bootstrap 5.

2. **Sidebar navigation** : Ajouter le menu "Paiements" dans la section appropriée (probablement "E-commerce" ou nouvelle section dédiée).

3. **Cohérence visuelle** : Respecter les classes CSS existantes et le style RACINE BY GANDA (couleurs #ED5F1E, #FFB800, #160D0C).

4. **Responsive** : Le layout admin est responsive (sidebar masquée sur mobile).

---

**Document créé le :** 2025-12-14  
**Prochaine étape :** Créer les routes `admin.payments.*` dans Sprint 2






