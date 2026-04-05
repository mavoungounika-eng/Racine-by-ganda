# 📚 GUIDE DES MODULES - RACINE BACKEND

**Date :** 28 novembre 2025  
**Version :** 1.0

---

## 🎯 Vue d'Ensemble

Le projet RACINE BACKEND utilise une **architecture modulaire** pour organiser le code par fonctionnalité métier.

### Modules Actifs

1. **ERP** - Gestion des stocks, fournisseurs, achats
2. **CRM** - Gestion des contacts, opportunités, interactions
3. **CMS** - Gestion de contenu (pages, blocs, médias)
4. **Analytics** - Statistiques et rapports
5. **Assistant** - IA "Amira"
6. **Auth** - ⚠️ Désactivé (doublons supprimés)

---

## 📦 MODULE ERP

### Description
Module de gestion d'entreprise (Enterprise Resource Planning) pour la gestion des stocks, fournisseurs, matières premières et achats.

### Structure
```
modules/ERP/
├── Http/Controllers/
│   ├── ErpDashboardController.php
│   ├── ErpStockController.php
│   ├── ErpSupplierController.php
│   ├── ErpRawMaterialController.php
│   └── ErpPurchaseController.php
├── Models/
│   ├── ErpSupplier.php
│   ├── ErpRawMaterial.php
│   ├── ErpStock.php
│   ├── ErpStockMovement.php
│   ├── ErpPurchase.php
│   └── ErpPurchaseItem.php
├── Resources/views/
│   ├── dashboard.blade.php
│   ├── stocks/
│   ├── suppliers/
│   ├── materials/
│   └── purchases/
└── routes/web.php
```

### Routes
- **Préfixe :** `/erp`
- **Middleware :** `auth`, `can:access-erp`
- **Routes principales :**
  - `GET /erp` → Dashboard
  - `GET /erp/stocks` → Gestion des stocks
  - `GET /erp/fournisseurs` → Gestion des fournisseurs
  - `GET /erp/matieres` → Gestion des matières premières
  - `GET /erp/achats` → Gestion des achats

### Accès
- **Rôles autorisés :** `staff`, `admin`, `super_admin`
- **Layout :** `layouts.internal`

### Documentation
Voir `modules/ERP/README.md` (si existe)

---

## 📞 MODULE CRM

### Description
Module de gestion de la relation client (Customer Relationship Management) pour gérer les contacts, opportunités et interactions.

### Structure
```
modules/CRM/
├── Http/Controllers/
│   ├── CrmDashboardController.php
│   ├── CrmContactController.php
│   ├── CrmOpportunityController.php
│   └── CrmInteractionController.php
├── Models/
│   ├── CrmContact.php
│   ├── CrmOpportunity.php
│   └── CrmInteraction.php
├── Resources/views/
│   ├── dashboard.blade.php
│   ├── contacts/
│   └── opportunities/
└── routes/web.php
```

### Routes
- **Préfixe :** `/crm`
- **Middleware :** `auth`, `can:access-crm`
- **Routes principales :**
  - `GET /crm` → Dashboard
  - `GET /crm/contacts` → Gestion des contacts
  - `GET /crm/opportunites` → Gestion des opportunités

### Accès
- **Rôles autorisés :** `staff`, `admin`, `super_admin`
- **Layout :** `layouts.internal`

---

## 📝 MODULE CMS

### Description
Module de gestion de contenu (Content Management System) pour gérer les pages, blocs, médias, FAQ et bannières.

### Structure
```
modules/CMS/
├── Http/Controllers/
│   ├── CmsDashboardController.php
│   ├── CmsPageController.php
│   ├── CmsBlockController.php
│   ├── CmsBannerController.php
│   ├── CmsFaqController.php
│   └── CmsAdminController.php
├── Models/
│   ├── CmsPage.php
│   ├── CmsBlock.php
│   ├── CmsMedia.php
│   ├── CmsBanner.php
│   ├── CmsFaq.php
│   └── ...
├── Resources/views/
│   ├── admin/dashboard.blade.php
│   ├── pages/
│   ├── blocks/
│   ├── banners/
│   └── faq/
└── routes/web.php
```

### Routes
- **Préfixe :** `/cms`
- **Middleware :** `auth`, `can:access-cms`
- **Routes principales :**
  - `GET /cms` → Dashboard
  - `GET /cms/pages` → Gestion des pages
  - `GET /cms/blocks` → Gestion des blocs
  - `GET /cms/banners` → Gestion des bannières
  - `GET /cms/faq` → Gestion de la FAQ

### Accès
- **Rôles autorisés :** `admin`, `super_admin`
- **Layout :** `layouts.internal`

---

## 📊 MODULE ANALYTICS

### Description
Module d'analyse et de statistiques pour générer des rapports et visualiser les données.

### Structure
```
modules/Analytics/
├── Http/Controllers/
│   ├── AnalyticsDashboardController.php
│   └── AnalyticsExportController.php
├── Services/
│   └── AnalyticsService.php
├── Resources/views/
│   ├── dashboard.blade.php
│   └── export/
└── routes/web.php
```

### Routes
- **Préfixe :** `/analytics`
- **Middleware :** `auth`, `can:access-analytics`
- **Routes principales :**
  - `GET /analytics` → Dashboard
  - `GET /analytics/export` → Export de rapports

### Accès
- **Rôles autorisés :** `admin`, `super_admin`
- **Layout :** `layouts.internal`

---

## 🤖 MODULE ASSISTANT

### Description
Module d'assistant IA "Amira" pour l'aide et l'interaction avec les utilisateurs.

### Structure
```
modules/Assistant/
├── Http/Controllers/
│   └── AmiraController.php
├── Services/
│   └── AmiraService.php
├── Resources/views/
│   └── chat.blade.php
└── routes/web.php
```

### Routes
- **Préfixe :** `/assistant` (à vérifier)
- **Middleware :** `auth`
- **Routes principales :**
  - `GET /assistant/chat` → Interface de chat

### Accès
- **Rôles autorisés :** Tous les utilisateurs authentifiés
- **Layout :** `layouts.frontend` ou `layouts.internal`

---

## 🔐 MODULE AUTH (DÉSACTIVÉ)

### ⚠️ Statut : Désactivé

Ce module a été **désactivé** car il créait des doublons avec les contrôleurs principaux d'authentification.

### Contrôleurs Supprimés
- ❌ `ClientAuthController` → Remplacé par `PublicAuthController`
- ❌ `EquipeAuthController` → Remplacé par `AdminAuthController` et `ErpAuthController`

### Authentification Utilisée
- ✅ `/login` → `PublicAuthController` (Clients & Créateurs)
- ✅ `/admin/login` → `AdminAuthController` (Administrateurs)
- ✅ `/erp/login` → `ErpAuthController` (Staff ERP)

---

## 🛠️ COMMENT CRÉER UN NOUVEAU MODULE

### 1. Créer la Structure
```bash
mkdir -p modules/MonModule/{Http/Controllers,Models,Resources/views,routes}
```

### 2. Créer le Fichier de Routes
```php
// modules/MonModule/routes/web.php
<?php
use Illuminate\Support\Facades\Route;

Route::prefix('mon-module')->name('mon-module.')->middleware(['auth'])->group(function () {
    Route::get('/', [MonModuleController::class, 'index'])->name('dashboard');
});
```

### 3. Enregistrer le Module
Ajouter dans `app/Providers/ModulesServiceProvider.php` :
```php
protected array $modules = [
    // ...
    'MonModule',
];
```

### 4. Créer le Contrôleur
```php
// modules/MonModule/Http/Controllers/MonModuleController.php
<?php
namespace Modules\MonModule\Http\Controllers;

use App\Http\Controllers\Controller;

class MonModuleController extends Controller
{
    public function index()
    {
        return view('monmodule::dashboard');
    }
}
```

---

## 📋 CONVENTIONS

### Nommage
- **Module :** PascalCase (`MonModule`)
- **Contrôleur :** PascalCase + `Controller` (`MonModuleController`)
- **Route :** kebab-case (`mon-module`)
- **Vue :** kebab-case avec namespace (`monmodule::dashboard`)

### Structure
- Tous les modules suivent la même structure
- Routes dans `routes/web.php`
- Vues dans `Resources/views/`
- Modèles dans `Models/`

### Middleware
- Tous les modules utilisent `auth` par défaut
- Ajouter des permissions avec `can:access-module`

---

## 🔍 TROUBLESHOOTING

### Module non chargé
1. Vérifier que le module est dans `ModulesServiceProvider::$modules`
2. Vérifier que `routes/web.php` existe
3. Vérifier les erreurs dans `storage/logs/laravel.log`

### Routes non trouvées
1. Vérifier le préfixe dans `routes/web.php`
2. Vérifier les middlewares
3. Exécuter `php artisan route:clear`

### Vues non trouvées
1. Vérifier le namespace dans `ModulesServiceProvider::loadModuleViews()`
2. Utiliser `monmodule::view-name` pour référencer les vues

---

## 📚 RESSOURCES

- **Architecture :** `ARCHITECTURE_ERP_SITE.md`
- **Analyse Globale :** `ANALYSE_GLOBALE_COMPLETE.md`
- **Structure Auth :** `CLARIFICATION_STRUCTURE_AUTH_DASHBOARDS.md`

---

**Dernière mise à jour :** 28 novembre 2025

