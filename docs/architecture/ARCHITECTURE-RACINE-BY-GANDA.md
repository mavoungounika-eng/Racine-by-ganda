# Architecture — RACINE BY GANDA

> **Version** : 1.0  
> **Statut** : Production-ready

---

## Vue d'Ensemble

**RACINE BY GANDA** est un monolithe Laravel structuré en **modules PSR-4**, fonctionnant comme marketplace e-commerce avec trois espaces utilisateurs distincts :

1. **Espace Client** - Achat produits, paiements, profil
2. **Espace Créateur** - Boutiques, produits, commandes, finances
3. **Espace Équipe** - Administration, POS, ERP, BI

---

## Modules Principaux

### ✅ Modules Actifs (Production)

| Module | Rôle | Fichiers | Statut |
|--------|------|----------|--------|
| **Accounting** | Comptabilité, écritures, bootstrap | 35 | ✅ Production |
| **Analytics** | BI, métriques, dashboards | 6 | ✅ Production |
| **Assistant (Amira)** | AI Assistant (Vue components) | 7 | ⚠️ Partiel (2 composants Vue) |
| **Auth** | Authentification centrale | 1 | ✅ Production |
| **CMS** | Pages publiques, sections | 59 | ✅ Production |
| **CRM** | Messagerie, conversations | 21 | ✅ Production |
| **ERP** | Stock, production, mouvements| 66 | ✅ Production |
| **ERPProduction** | *À auditer* (doublon ERP?) | 41 | ⚠️ À clarifier |
| **Frontend** | Contrôleurs frontend SSR | 6 | ✅ Production |
| **POSSync** | Point de vente physique | 16 | ✅ Production |

### ❄️ Modules Gelés (Non actifs)

- **Atelier** - Non développé
- **Boutique** - Non développé  
- **Brand** - Non développé
- **Core** - Non développé
- **HR** - Non développé
- **Reporting** - Non développé
- **Showroom** - Non développé
- **Social** - Non développé

**Statut** : FROZEN — Aucun développement prévu.

---

## Architecture Authentification & RBAC

### Système de Rôles

**Modèle** : Permission-Based RBAC (Role-Based Access Control)

**Rôles disponibles** :
- `super_admin` - Accès total, bypass RBAC
- `admin` - Gestion complète équipe
- `staff` - Accès commandes, stocks, POS
- `creator`/`createur` - Espace créateur
- `client` - Espace client boutique

### Gates & Permissions

**Architecture** : **1 Gate = 1 Permission**

- **33 Gates** définis dans `AuthServiceProvider`
- **23 mappings directs** (70%)
- **10 mappings indirects** justifiés (30%)
- **3 Gates hors RBAC** (super_admin, créateur, client)

**Documentation complète** : [docs/security/GATES_TO_PERMISSIONS_MAP.md](../security/GATES_TO_PERMISSIONS_MAP.md)

### Flow Authentification

```mermaid
graph TD
    A[Login] --> B{OAuth ou Email?}
    B -->|OAuth| C[Google Socialite]
    B -->|Email| D[Credential Check]
    C --> E[User Exists?]
    D --> E
    E -->|Non| F[Create Account]
    E -->|Oui| G[2FA Required?]
    F --> G
    G -->|Oui| H[2FA Challenge]
    G -->|Non| I[PostLoginDecisionEngine]
    H --> I
    I --> J{Rôle?}
    J -->|Client| K[/compte]
    J -->|Créateur| L[/createur/dashboard]
    J -->|Staff/Admin| M[/admin/dashboard]
```

**Services clés** :
- `AuthOrchestratorService` - Orchestration auth
- `PostLoginDecisionEngine` - Redirection par rôle
- `UserContextResolver` - Résolution contexte user
- `OAuthService` - Gestion OAuth providers

---

## Architecture Paiements

### Providers

1. **Stripe** - Cartes bancaires (checkout sessions)
2. **Mobile Money** - Monetbil (MTN, Orange Money)
3. **Cash on Delivery** - Paiement à la livraison

### Flow Paiement Stripe

```
Client → Checkout → Stripe Session → Webhook → Payment Validation → Order Confirmed
```

**Services** :
- `CardPaymentController` - Sessions Stripe
- `StripeWebhookService` - Traitement webhooks
- `PaymentAccountingService` - Écritures comptables

### Webhooks

- **Stripe** : `/api/webhooks/stripe` (signature vérifiée)
- **Monetbil** : `/payment/monetbil/notify`

**Sécurité** :
- Signature verification
- Idempotence (via `idempotency_key`)
- Logs audit

---

## Modules Backend Détaillés

### Accounting

**Responsabilité** : Comptabilité conforme, écritures audit trail

**Services clés** :
- `AccountingBootstrapService` - Assertions environnement (fiscal year, journaux, comptes OHADA)
- `FinancialIntentService` - Intent-based architecture, idempotence garantie
- `LedgerService` - Seul entry point pour `AccountingEntry`

**Point critique** : Architecture verrouillée, tout passage par `LedgerService`.

### ERP

**Responsabilité** : Stock, production, manufacturing

**Entités** :
- `ProductionOrder` - Ordres de fabrication
- `StockMovement` - Mouvements stock
- `ProductionOperation` - Étapes production
- `ProductionCostSummary` - Coûts de revient

**Note** : Module `ERPProduction` présent (41 fichiers) — **À auditer pour déterminer si doublon**.

### CMS

**Responsabilité** : Pages publiques dynamiques

**Entités** :
- `CmsPage` - Pages génériques (/a-propos, /contact, etc.)
- `CmsSection` - Sections composant une page

**Contrôleur** : `FrontendController` (gère rendu pages CMS)

### CRM

**Responsabilité** : Relation client, messagerie

**Entités** :
- `Conversation` - Fils de discussion
- `Message` - Messages
- `ConversationParticipant` - Participants
- `ConversationProductTag` - Tag produits dans conv

**UI** : `/messages/*` (client, créateur, admin)

### POS (Point of Sale)

**Responsabilité** : Ventes en boutique physique

**Entités** :
- `PosSale` - Ventes terminées
- `PosSession` - Sessions caisse
- `PosPayment` - Paiements
- `PosCashMovement` - Mouvements espèces

**UI** : `/admin/pos` (accès staff/admin uniquement)

---

## Frontend Architecture

### Stack

- **Rendu** : Blade templates (Server-Side Rendering)
- **JavaScript** : Vanilla JS + 2 composants Vue (Amira)
- **CSS** : Vanilla CSS
- **Build** : Vite

### Composants Vue

**Uniquement 2 composants** :
- `AmiraChat.vue` - Interface chat AI
- `AmiraWidget.vue` - Widget déclencheur

**Note** : Pas de SPA. Application traditionnelle Laravel SSR.

### Routes Principales

**Fichiers** :
- `routes/web.php` - Routes principales (641 lignes)
- `routes/auth.php` - Authentification
- `routes/api.php` - Webhooks & API interne
- `routes/pos.php` - POS

**Groupes** :
- `/` - Frontend public
- `/compte` - Espace client
- `/createur/*` - Espace créateur
- `/admin/*` - Espace équipe
- `/checkout` - Tunnel achat

---

## Base de Données

**77 migrations** appliquées couvrant :
- Users & roles
- Products, categories, collections
- Orders, payments
- Creator profiles & subscriptions
- CMS pages
- Messages & conversations
- POS entities
- Accounting entries
- Production orders
- Analytics metrics

**Modèles** : 76 Eloquent models dans `app/Models/`

---

## Tests

**133 tests** (Feature + Unit)

**Couverture** :
- ✅ Auth flow & RBAC
- ✅ Checkout & payments
- ✅ Créateur subscriptions
- ✅ Accounting integrity
- ⚠️ Partielle sur modules secondaires

**Commande** : `php artisan test`

---

## Services Core

### Decision Engine

**AI/BI Services** :
- `ChurnPredictionService` - Prédiction désabonnement
- `CreatorDecisionScoreService` - Scoring créateurs
- `RecommendationEngineService` - Recommandations produits
- `BiMetricsService` - Métriques BI

### Capabilities (Créateurs)

**Service** : `CreatorCapabilityService`

**Logique** : Gestion capabilities par plan d'abonnement (Free/Pro/Premium)

**Exemples capabilities** :
- `can_sell_products`
- `max_products`
- `can_customize_shop`
- `analytics_advanced`

---

## Diagramme Modules

```
┌─────────────────────────────────────────┐
│           RACINE BY GANDA               │
│        (Laravel 12 Monolith)            │
└─────────────────────────────────────────┘
           │
           ├── Auth/RBAC ──────┐
           │                   ├── OAuth (Google)
           │                   ├── 2FA
           │                   └── Gates/Permissions
           │
           ├── Frontend (Blade SSR)
           │    ├── Public (/)
           │    ├── Client (/compte)
           │    ├── Créateur (/createur)
           │    └── Admin (/admin)
           │
           ├── Modules Business
           │    ├── ERP (Stock/Production)
           │    ├── CMS (Pages publiques)
           │    ├── CRM (Messagerie)
           │    ├── POS (Ventes physiques)
           │    └── Accounting (Comptabilité)
           │
           ├── Paiements
           │    ├── Stripe (Cartes)
           │    ├── Monetbil (Mobile Money)
           │    └── Cash on Delivery
           │
           └── Analytics & BI
                ├── Dashboards
                ├── Metrics
                └── Decision AI
```

---

## Points d'Attention Techniques

### Dette Technique Identifiée

1. ⚠️ **Modules ERP vs ERPProduction** - Potentiel doublon à clarifier
2. ⚠️ **Routes massives** - `web.php` 641 lignes (refactoring optionnel)
3. ⚠️ **Legacy webhooks** - Routes dépréciées commentées dans `web.php` L602-616

### Décisions Architecturales Clés

1. **Monolithe modulaire** - Choix délibéré vs microservices
2. **Blade SSR** - Pas de SPA (simplicité, SEO)
3. **RBAC verrouillé** - Système stable, modifications interdites
4. **Accounting locked** - Tout passage par `LedgerService`

---

## Références

- **RBAC Mapping** : [docs/security/GATES_TO_PERMISSIONS_MAP.md](../security/GATES_TO_PERMISSIONS_MAP.md)
- **Auth Flow** : [docs/AUTH_FLOW.md](../AUTH_FLOW.md)
- **Payments** : [docs/payments/](../payments/)
- **Production Checklist** : [docs/PRODUCTION_CHECKLIST.md](../PRODUCTION_CHECKLIST.md)
