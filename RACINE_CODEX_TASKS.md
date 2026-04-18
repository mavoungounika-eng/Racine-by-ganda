# RACINE BY GANDA — Liste de tâches CODEX
**Projet :** `~/projects/racine-backend` (Laravel 12 + Vite + Vue 3 + Electron POS)  
**Préparé le :** 2026-04-14  
**Usage :** À soumettre à Codex. Codex applique et produit un rapport pour chaque tâche.  
**Contexte :** Les tâches déjà complétées cette session sont marquées ✅. Ne pas les refaire.

---

## TÂCHES DÉJÀ COMPLÉTÉES (cette session — NE PAS REFAIRE)
✅ Fix CSS/Bootstrap non chargé (stale `public/hot` + `vite build --watch`)  
✅ Fix blocs noirs `/messages` et `/createur/finances`  
✅ Fix Chart.js silencieux — nonce CSP ajouté sur 51 fichiers Blade  
✅ `start.sh` fiable one-command avec nettoyage cache  
✅ `ProductPolicy::create()` — créateurs autorisés nativement  
✅ Handler 403 global — ne déconnecte plus l'utilisateur  

---

## PRIORITÉ 1 — CRITIQUE (bugs qui plantent / bloquent)

### T-01 · `DetectStockAnomalies` — Conflit propriété `$queue`
**Fichier :** `app/Jobs/AI/DetectStockAnomalies.php:17`  
**Problème :** Le job déclare `public string $queue = 'ai-processing';` mais le trait `Queueable` définit déjà `public ?string $queue = null;`. PHP 8.x considère les types incompatibles → FatalError à chaque dispatch.  
**Erreur log :** `App\Jobs\AI\DetectStockAnomalies and Illuminate\Bus\Queueable define the same property ($queue)`  
**Fix :** Supprimer la déclaration explicite et utiliser `$this->onQueue()` dans le constructeur :
```php
// Supprimer la ligne : public string $queue = 'ai-processing';
public function __construct()
{
    $this->onQueue('ai-processing');
}
```
**Même vérification :** Faire la même recherche sur TOUS les jobs dans `app/Jobs/AI/` qui utilisent `public string $queue`.

---

### T-02 · Page `/createurs` — Grille hardcodée au lieu de la DB
**Fichier :** `resources/views/frontend/creators.blade.php` (environ ligne 450–550)  
**Problème :** La vue ignore la variable `$creators` (paginator Eloquent) et affiche 6 artisans hardcodés (Amina Diallo, Kwame Asante, etc.).  
**Controller OK :** `app/Http/Controllers/Front/FrontendController.php:147` — `compact('creators', 'totalProducts', 'cmsPage')` est correct.  
**Fix :** Remplacer le bloc HTML des 6 cartes statiques par :
```blade
@forelse ($creators as $creator)
    <div class="creator-card">
        <img src="{{ $creator->logo_url ?? asset('images/placeholder-creator.jpg') }}" alt="{{ $creator->brand_name }}">
        <h2 class="section-title">{{ $creator->brand_name }}</h2>
        <p>{{ $creator->bio ?? '' }}</p>
        <a href="{{ route('frontend.creator.shop', $creator->slug) }}">Voir la boutique</a>
    </div>
@empty
    <p class="text-center">Aucun créateur actif pour le moment.</p>
@endforelse
```
Ajouter la pagination en bas : `{{ $creators->links() }}`

---

### T-03 · `SubscriptionOptimizationService` — Table manquante
**Fichier :** `app/Services/Financial/SubscriptionOptimizationService.php:142`  
**Problème :** Le code référence la table `creator_subscription_events` qui n'existe pas en DB.  
**Fix :** Créer la migration :
```
php artisan make:migration create_creator_subscription_events_table
```
Colonnes : `id`, `creator_profile_id` (FK), `event_type` (string), `plan_from`, `plan_to`, `metadata` (json nullable), `created_at`.

---

### T-04 · `RiskDetectionService` — Colonne `risk_level` manquante
**Fichier :** `app/Services/Financial/RiskDetectionService.php:98`  
**Problème :** Code essaie de mettre à jour `risk_level` sur `creator_profiles` mais la colonne n'existe pas.  
**Fix :** Créer migration `add_risk_level_to_creator_profiles_table` :
```php
$table->string('risk_level')->default('normal')->after('status');
// Valeurs : 'normal', 'watch', 'high', 'critical'
```

---

### T-05 · `RiskDetectionService` — Envoi email non implémenté
**Fichier :** `app/Services/Financial/RiskDetectionService.php:113`  
**Commentaire :** `// TODO: Implémenter l'envoi d'email`  
**Fix :** Créer notification `App\Notifications\CreatorRiskAlert` et la dispatcher :
```php
$creator->user->notify(new CreatorRiskAlert($creator, $riskLevel));
```

---

## PRIORITÉ 2 — IMPORTANT (features incomplètes, UX cassée)

### T-06 · `OrderRepository` — Intégration panier non implémentée
**Fichier :** `app/Repositories/OrderRepository.php:198,207`  
**Commentaire :** `// TODO: Implémenter avec table carts quand disponible`  
**Problème :** Les méthodes `getCartItems()` et `clearCart()` retournent des données vides → le checkout ne fonctionne pas correctement pour les paniers persistés.  
**Fix :** Implémenter avec le modèle `Cart` / `CartItem` existant (vérifier `app/Models/Cart.php`).

---

### T-07 · `ValidateSessionContext` — Middleware doublon
**Fichier :** `app/Http/Middleware/ValidateSessionContext.php`  
**Problème :** Ce middleware fait exactement la même chose que la validation déjà présente dans `EnsureAuthenticated.php` (steps 2 et 3). Il est appliqué en plus pour chaque requête web → double appel à `validateSession()` → double `Auth::logout()` potentiel si la validation échoue.  
**Fix :** Vérifier dans `bootstrap/app.php` si `ValidateSessionContext` est enregistré globalement. Si oui, le supprimer du stack global (la logique est déjà dans `EnsureAuthenticated`). Garder uniquement si route spécifique le nécessite.

---

### T-08 · Log `PermissionCheck` — Flood des logs en prod
**Fichier :** `app/Models/User.php:355`  
**Code :** `Log::info("[PermissionCheck] User {$this->id} ({$this->getRoleSlug()}) checking for '{$permission}': " . ($has ? 'YES' : 'NO'));`  
**Problème :** Ce log est appelé à chaque `hasPermission()`. En production, chaque requête génère 5–20 lignes de log → fichiers laravel.log saturés en heures.  
**Fix :** Supprimer ce `Log::info()` ou le conditionner à `APP_DEBUG=true` :
```php
if (config('app.debug')) {
    Log::debug("[PermissionCheck] ...");
}
```

---

### T-09 · `MessageService` — Thumbnails images non créés
**Fichier :** `app/Services/MessageService.php:224`  
**Commentaire :** `// TODO: Créer thumbnail si intervention/image est installé`  
**Fix :** Utiliser `Intervention\Image` (déjà en dépendance ?) pour créer un thumbnail 200×200 des pièces jointes lors de l'upload dans les messages.

---

### T-10 · `ProductionService` — Prix matériaux non réels
**Fichier :** `app/Services/Production/ProductionService.php:395`  
**Commentaire :** `// TODO Phase C: Use real material prices from stock movements`  
**Problème :** Le calcul du coût de production utilise des prix fictifs → les marges et bilans sont faux.  
**Fix :** Remplacer par une requête sur les derniers `StockMovement` (prix d'achat réel) pour chaque matière première.

---

### T-11 · `AnalyticsController` (Créateur) — Données statiques
**Fichier :** `app/Http/Controllers/Creator/AnalyticsController.php`  
**Problème probable :** Vérifier si le controller retourne des données réelles ou des placeholders. La vue `resources/views/creator/stats/index.blade.php` a des graphiques Chart.js — vérifier que les données JSON injectées viennent bien de la DB.  
**Fix :** Si données statiques, brancher les vraies requêtes SQL depuis `Product`, `Order`, `OrderItem` filtrés par `user_id`.

---

### T-12 · Page `/createur/produits/nouveau` — Champ `stock` sans lien avec ERP
**Fichier :** `app/Http/Controllers/Creator/CreatorProductController.php:150`  
**Problème :** `Product::create($validated)` sauvegarde le `stock` dans la table products, mais ne crée pas de mouvement `StockMovement` dans le module ERP. Si l'ERP est actif, les stocks seront désynchronisés.  
**Fix :** Après `Product::create()`, créer un `StockMovement` de type `initial` :
```php
$product = Product::create($validated);
if ($validated['stock'] > 0) {
    ErpStockMovement::create([
        'product_id' => $product->id,
        'type' => 'initial',
        'quantity' => $validated['stock'],
        'unit_price' => $validated['price'],
        'created_by' => $user->id,
    ]);
}
```

---

## PRIORITÉ 3 — POS ELECTRON (`racine-pos-electron/`)

### T-13 · POS — `syncManager.js` n'envoie pas le token d'auth
**Fichier :** `racine-pos-electron/src/services/syncManager.js:22`  
**Problème :** `this.apiClient.post('/api/pos/sales', sale.saleData, sale.idempotencyKey)` — vérifier que `apiClient` injecte bien le `Authorization: Bearer <token>` du store `auth.js`. Si le token n'est pas inclus, toutes les syncs retournent 401.  
**Fix :** S'assurer que l'intercepteur Axios dans `api/index.js` (ou équivalent) ajoute le header :
```js
api.interceptors.request.use(config => {
    const token = authStore.token
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})
```

---

### T-14 · POS — `SessionCloseView.vue` — Clôture sans rapport Z
**Fichier :** `racine-pos-electron/src/views/SessionCloseView.vue`  
**À vérifier :** La vue de clôture de session doit afficher et permettre d'imprimer le rapport Z (total ventes, total cash, écart). Vérifier que le store `session.js` calcule et expose ces totaux.  
**Fix si manquant :** Ajouter dans `session.js` un getter `zReport` qui agrège les ventes de la session en cours depuis `localDb`.

---

### T-15 · POS — `OfflineView.vue` — Mode hors-ligne non testé
**Fichier :** `racine-pos-electron/src/views/OfflineView.vue`  
**À vérifier :** La vue s'affiche bien quand `networkMonitor` détecte une déconnexion. Vérifier que :
1. `networkMonitor.js` écoute les événements `online`/`offline` du navigateur Electron
2. Le router redirige vers `OfflineView` quand déconnecté
3. Les ventes en mode offline sont bien sauvegardées dans IndexedDB via `localDb.js`
**Fix si incomplet :** Ajouter dans `router/index.js` un guard global qui vérifie `networkMonitor.isOnline` et redirige.

---

### T-16 · POS — Electron version conflict
**Fichier :** `package.json` (racine) vs `racine-pos-electron/package.json`  
**Problème :** Le `package.json` racine a `"electron": "^28.0.0"` en devDependency, et le POS a `electron` dans sa propre dépendance (vérifier version).  
**Fix :** Supprimer `electron` des devDependencies du `package.json` racine (Laravel n'a pas besoin d'Electron). Seul `racine-pos-electron/package.json` doit le déclarer.

---

### T-17 · POS — `PaymentView.vue` — Méthodes de paiement incomplètes
**Fichier :** `racine-pos-electron/src/views/PaymentView.vue`  
**À vérifier :** La vue supporte-t-elle les paiements cash + carte + mobile money ? Le store `cart.js` doit calculer le rendu monnaie pour cash.  
**Fix si manquant :** Ajouter logique rendu monnaie :
```js
const renduMonnaie = computed(() => montantReçu.value - cart.total)
```

---

## PRIORITÉ 4 — MODULES BACKEND (vérifications)

### T-18 · Module `CMS` — CmsPage non utilisée sur les pages frontend
**Fichier :** `app/Http/Controllers/Front/FrontendController.php`  
**Problème :** Les pages `/about`, `/contact`, `/aide`, etc. récupèrent-elles leur contenu depuis le module CMS ou sont-elles en Blade pur statique ? Vérifier chaque méthode du controller.  
**Fix :** Pour chaque page publique, ajouter la résolution CMS :
```php
$cmsPage = \Modules\CMS\Models\CmsPage::where('slug', 'about')->first();
return view('frontend.about', compact('cmsPage'));
```

---

### T-19 · Module `CreatorNetwork` — Abonnements Stripe non synchronisés
**Fichier :** `app/Http/Controllers/Creator/CreatorSubscriptionCheckoutController.php:47`  
**Log :** `Erreur lors de la création de la session Checkout : Le plan 'Gratuit' n'a pas de price_id Stripe configuré`  
**Problème :** Les plans créateurs en DB n'ont pas leur `price_id` Stripe renseigné → aucun upgrade de plan possible.  
**Fix :** 
1. Créer les plans dans le dashboard Stripe
2. Mettre à jour la table `creator_plans` (ou équivalente) avec les vrais `price_id` Stripe
3. Vérifier la commande artisan : `php artisan stripe:sync-plans` (si elle existe)

---

### T-20 · Module `ERP` — `ErpStockMovement` — Sync POS non déclenchée
**Fichier :** `modules/POSSync/Services/EventDispatcher.php`  
**Problème :** L'`EventDispatcher` ne gère que `PosSaleCreated` → `ProcessPosSale`. Il manque les handlers pour :
- `PosSaleFinalized` → décrémenter le stock ERP
- `PosSessionClosed` → valider les paiements cash + créer rapport financier
**Fix :** Ajouter dans `eventHandlers` les nouveaux types et créer les jobs correspondants `FinalizePosStockMovement.php` et `ProcessPosSessionClosure.php`.

---

### T-21 · Module `Analytics` — `AnalyticsController` admin
**Fichier :** `app/Http/Controllers/Admin/AnalyticsController.php`  
**À vérifier :** Les graphiques du dashboard admin (ventes, créateurs actifs, conversions) utilisent-ils des données réelles ou des tableaux statiques ?  
**Fix si statique :** Brancher sur les vraies requêtes avec cache Redis (TTL 1h) pour éviter les requêtes lourdes à chaque chargement.

---

## PRIORITÉ 5 — QUALITÉ / DETTE TECHNIQUE

### T-22 · Supprimer le module `Assistant` si vide
**Dossier :** `modules/Assistant/`  
**À vérifier :** Ce module est-il implémenté ou vide ? Si vide, le supprimer pour garder le projet propre.

---

### T-23 · Audit des vues Blade — classes Tailwind orphelines
**Problème :** Certaines vues utilisent encore des classes Tailwind (`flex`, `grid`, `text-xl`, etc.) au lieu de Bootstrap 5. Sans Tailwind CDN chargé, ces classes n'ont aucun effet.  
**Fichiers probables :** `resources/views/creator/products/create.blade.php`, `resources/views/creator/dashboard/` (layouts internes)  
**Fix :** Remplacer les classes Tailwind par leurs équivalents Bootstrap 5 dans toutes les vues créateur et admin. Ou ajouter Tailwind CDN dans le layout concerné.

---

### T-24 · `start.sh` — Ajouter vérification migrations en attente
**Fichier :** `start.sh` (racine du projet)  
**Amélioration :** Ajouter avant le lancement des serveurs :
```bash
PENDING=$(php artisan migrate:status --no-ansi 2>/dev/null | grep -c "Pending")
if [ "$PENDING" -gt 0 ]; then
    echo "  ⚠ $PENDING migration(s) en attente ! Lancer: php artisan migrate"
fi
```

---

### T-25 · Créer `racine-audit.txt` — Rapport d'audit du projet
**Fichier à créer :** `/racine-audit.txt` (racine du projet)  
**Contenu :** Rapport structuré avec stack technique, architecture des modules, bugs connus, état de chaque section. Voir le plan original dans `.claude/plans/idempotent-wondering-snail.md` pour le contenu détaillé déjà préparé.

---

## RÉSUMÉ EXÉCUTIF POUR CODEX

```
PROJET : RACINE BY GANDA
STACK  : Laravel 12 / PHP 8.3 / Vite 7 / Vue 3 / Bootstrap 5 / Electron (POS)
WSL    : ~/projects/racine-backend

RÈGLE : Pour chaque tâche, produire un rapport indiquant :
  - Fichiers modifiés
  - Lignes ajoutées/supprimées  
  - Commandes exécutées (migrations, artisan, etc.)
  - Résultat attendu vs résultat obtenu

ORDRE RECOMMANDÉ : T-01 → T-04 → T-02 → T-05 → T-07 → T-08 → T-12 → T-19 → reste
```

| Priorité | ID | Domaine | Effort estimé |
|----------|----|---------|---------------|
| 🔴 CRITIQUE | T-01 | Jobs AI | 5 min |
| 🔴 CRITIQUE | T-02 | Vue Frontend | 20 min |
| 🔴 CRITIQUE | T-03 | Migration DB | 10 min |
| 🔴 CRITIQUE | T-04 | Migration DB | 5 min |
| 🔴 CRITIQUE | T-05 | Notification | 15 min |
| 🟠 IMPORTANT | T-06 | Panier/Checkout | 30 min |
| 🟠 IMPORTANT | T-07 | Middleware | 10 min |
| 🟠 IMPORTANT | T-08 | Logs | 2 min |
| 🟠 IMPORTANT | T-09 | Media | 20 min |
| 🟠 IMPORTANT | T-10 | ERP/Finance | 30 min |
| 🟠 IMPORTANT | T-11 | Analytics | 20 min |
| 🟠 IMPORTANT | T-12 | ERP/Stock | 15 min |
| 🟡 POS | T-13 | Electron/API | 10 min |
| 🟡 POS | T-14 | Electron/UX | 20 min |
| 🟡 POS | T-15 | Electron/Offline | 30 min |
| 🟡 POS | T-16 | Config | 2 min |
| 🟡 POS | T-17 | Electron/Paiement | 15 min |
| ⚪ MODULES | T-18 | CMS | 30 min |
| ⚪ MODULES | T-19 | Stripe/Plans | 20 min |
| ⚪ MODULES | T-20 | ERP/POS Sync | 45 min |
| ⚪ MODULES | T-21 | Analytics Admin | 20 min |
| ⚪ DETTE | T-22 | Nettoyage | 5 min |
| ⚪ DETTE | T-23 | CSS/Tailwind | 20 min |
| ⚪ DETTE | T-24 | DevOps | 5 min |
| ⚪ DETTE | T-25 | Documentation | 10 min |
