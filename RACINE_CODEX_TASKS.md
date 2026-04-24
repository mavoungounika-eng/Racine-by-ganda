# RACINE BY GANDA - Suivi des taches Codex

**Projet:** `~/projects/racine-backend` (Laravel 12 + Vite + Vue 3 + Electron POS)  
**Derniere mise a jour consolidee:** 2026-04-23

Ce document remplace les notes brutes precedentes et regroupe toutes les mises a jour/realisations en un suivi unique.

---

## 1) Etat global

| Statut | Taches |
|---|---|
| Terminees | T-01, T-02, T-04, T-05, T-06, T-08, T-09, T-10, T-11, T-12, T-14, T-15, T-16, T-17, T-18, T-20, T-21, T-22, T-24, T-25 |
| Verifiees (pas de patch necessaire) | T-03, T-07, T-13 |
| En cours / partiel | T-19 (code pret + actions Stripe externes), T-23 (conversion partielle) |

---

## 2) Realisations deja completees (historique)

### Correctifs transverses deja valides
- Fix CSS/Bootstrap non charge (stale `public/hot` + `vite build --watch`)
- Fix blocs noirs `/messages` et `/createur/finances`
- Fix Chart.js silencieux (nonce CSP ajoute sur fichiers Blade)
- `start.sh` one-command avec nettoyage cache
- `ProductPolicy::create()` (createurs autorises nativement)
- Handler 403 global (ne deconnecte plus l'utilisateur)

### Taches critiques completees (avec commits)
- **T-01** - Fix `DetectStockAnomalies` (conflit propriete `$queue`) - commit `29ca972e`
- **T-04** - `RiskDetectionService` (colonne `risk_level`) - commit `c70f11d5`
- **T-02** - Page `/createurs` (featured dynamique DB) - commit `fb97e47f`
- **T-05** - `RiskDetectionService` (notification `CreatorRiskAlert`) - commit `afdf0541`

---

## 3) Realisations implementees ensuite

### T-06 - OrderRepository (paniers abandonnes)
- Fichier: `app/Repositories/OrderRepository.php`
- Resultat: metriques des paniers branchees sur `carts/cart_items` en base
- Verification: `php -l app/Repositories/OrderRepository.php`

### T-08 - PermissionCheck log flood
- Fichier: `app/Models/User.php`
- Resultat: log passe en `Log::debug` conditionne par `config('app.debug')`
- Verification: `php -l app/Models/User.php`

### T-09 - MessageService thumbnails
- Fichier: `app/Services/MessageService.php`
- Resultat: generation reelle de thumbnail 200x200 a l'upload (sans nouvelle dependance)
- Verification: `php -l app/Services/MessageService.php`

### T-10 - ProductionService (couts matieres reels)
- Fichier: `app/Services/Production/ProductionService.php`
- Resultat: calcul base sur derniers `stock_movements` entrants (priorite achats)
- Verification: `php -l app/Services/Production/ProductionService.php`

### T-12 - Creation produit + mouvement ERP initial
- Fichier: `app/Http/Controllers/Creator/CreatorProductController.php`
- Resultat: creation `ErpStockMovement` type `initial` si `stock > 0`
- Verification: `php -l app/Http/Controllers/Creator/CreatorProductController.php`

### T-20 - POSSync event handlers manquants
- Fichiers:
  - `modules/POSSync/Services/EventDispatcher.php`
  - `modules/POSSync/Jobs/FinalizePosStockMovement.php`
  - `modules/POSSync/Jobs/ProcessPosSessionClosure.php`
- Resultat: ajout handlers `PosSaleFinalized` et `PosSessionClosed` + jobs associes
- Verification: `php artisan test --filter=PosSync --stop-on-failure` (2 tests passes)

### T-24 - start.sh check migrations pending
- Fichier: `start.sh`
- Resultat: warning non bloquant si migrations en attente
- Verification: `bash -n start.sh`

### T-25 - Rapport d'audit
- Fichier: `racine-audit.txt`
- Resultat: rapport structure cree (stack, architecture, etat, risques, priorites)

---

## 4) Verifications faites sans patch

### T-03 - SubscriptionOptimizationService
- Statut: table/mecanisme deja couverts (dont garde `Schema::hasTable`).

### T-07 - ValidateSessionContext
- Statut: middleware non enregistre globalement dans `bootstrap/app.php`.

### T-13 - POS token auth sync
- Statut: token deja injecte dans `racine-pos-electron/src/api/posClient.js`.

### T-15 - Offline POS
- Statut: monitor offline + redirection + persistance locale deja presents.

### T-16 - Electron version conflict
- Statut: pas de dependance `electron` dans le `package.json` racine.

### T-21 - Analytics admin/createur
- Statut: donnees deja branchees DB + cache.

### T-22 - Module Assistant
- Statut: module non vide (conserve).

---

## 5) Mises a jour recentes (dernier passage)

### T-11 - Analytics createur
- Statut: verifie
- Action: commentaire TODO obsolete retire dans `AnalyticsController.php`.

### T-14 - POS cloture session (rapport Z)
- Statut: implemente
- Fichiers: `session.js`, `SessionCloseView.vue`
- Action: getter `zReport` + fallback local `localDb`.

### T-17 - POS paiement (cash)
- Statut: implemente
- Fichiers: `PaymentView.vue`, `cart.js`
- Action: rendu monnaie + validation flux cash.

### T-18 - CMS frontend
- Statut: implemente
- Fichier: `app/Http/Controllers/Front/FrontendController.php`
- Action: injection CMS sur pages statiques frontend.

### T-19 - CreatorNetwork / Stripe plans
- Statut: **partiel**
- Fichiers modifies:
  - `app/Http/Controllers/Creator/CreatorSubscriptionCheckoutController.php`
  - `app/Console/Commands/StripeSyncPlans.php`
  - `tests/Feature/Creator/SubscriptionCheckoutTest.php`
- Fait:
  - garde plan gratuit + message guide checkout
  - commande `stripe:sync-plans` ajoutee
  - test checkout ajoute
- Reste:
  - creer/valider produits + prices dans Stripe Dashboard
  - renseigner les `price_id` reels en base
  - executer `php artisan stripe:sync-plans --dry-run` puis sans `--dry-run`

### T-23 - Audit Tailwind/Bootstrap
- Statut: **partiel**
- Fichier traite: `resources/views/creator/products/create.blade.php`
- Reste: audit complet des autres vues createur/admin avec classes Tailwind orphelines.

---

## 6) Verifications executees

- `php -l` sur les fichiers PHP modifies: OK
- `bash -n start.sh`: OK
- `php artisan test --filter=PosSync --stop-on-failure`: 2 passes
- `php artisan test tests/Feature/Creator/SubscriptionCheckoutTest.php`: 5 passes
- `php artisan list --raw | grep '^stripe:sync-plans'`: commande visible
- `php artisan stripe:sync-plans --dry-run`: comportement gere (erreur Stripe possible si cle expiree)
- `node --check` sur stores POS modifies: OK

---

## 7) Priorites restantes

1. **T-19** - Finaliser la synchro Stripe (bloquant business externe)
2. **T-23** - Terminer l'audit/normalisation Tailwind -> Bootstrap

