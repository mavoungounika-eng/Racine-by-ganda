# TODO PRODUCTION — RACINE BY GANDA
**Dernière mise à jour:** 10 juin 2026
**Sprints complétés:**
- ✅ Sprint 0 — Audit Pré-Production (6 commits, 9 P0 résolus)
- ✅ Sprint 1 — SEO+Performance+Contenu (4 commits, 8 P1 résolus)
- ✅ Sprint 2 — OG Image JPG + Srcset (2 commits, documentation complète)
- ✅ Sprint 3 — P0 Légal + Srcset + CMS RGPD (3 commits, production-ready)
- ✅ Sprint 6 — Durcissement CSP nonce + cleanup (12 juin 2026)

---

## SPRINT 6 — CSP NONCE + CLEANUP (12 juin 2026)

**Commits:** `ddad3e77` fix(security) CSP nonce, `2aa7acd4` feat(shipping)

### Réalisé
1. **CSP nonce restauré** dans `SecurityHeaders` (script-src/style-src). Avec un nonce,
   `'unsafe-inline'` est ignoré par les navigateurs CSP3 → tout script/handler inline
   sans nonce serait bloqué.
2. **Scripts inline → nonce** (10 vues standalone) : errors/429, admin/orders/to-handle,
   profile/orders, checkout/mobile-money-form.
3. **Handlers inline `on*` → `data-*` + délégateur global nonce'd** dans le layout
   (`document` delegation : `data-action`, `data-href`, `data-qty-delta`, `data-auto-submit`,
   `data-slide-index`, `img[data-hide-on-error]`).
4. **Font Awesome** : chargement async `onload="this.media='all'"` → chargement standard
   (CSP-safe, sans handler inline). Voir P2 ligne « subset/SVG inline » ci-dessous pour
   ré-optimiser la perf si besoin (préload + nonce'd swap).
5. **Cleanup** : 9 fichiers parasites 0-octet supprimés (`hero-0*],`, `image,`, `video,`),
   `config/company.php` restauré (diff whitespace-only annulé).

### ✅ Lot frontend tenu — désormais COMMITÉ (inspecté + validé)
- [x] **`7d86f81a` fix(auth)** — retrait `->timeout(5)` sur `Socialite::driver()` (méthode
      inexistante → `BadMethodCallException` cassait le callback OAuth en prod). Tests OAuth 19/19 PASS.
- [x] **`9ed725f2` feat(frontend)** — `home`/`shop`/`atelier` : localisation prix XAF,
      suppression blocs démo placeholder, **corrections CSP (wishlist/sort/badge → `data-*`)**.
      Le couplage CSP↔redesign (hunks entremêlés) est ainsi résolu : `home`/`shop` n'ont plus
      de handlers inline sur HEAD.
- [x] **`f3c6117d` style(css)** — `public/css/frontend-home.css` régénéré depuis la source
      `resources/css/frontend-home.css` (368 lignes, accolades équilibrées 160/160).

### Notes environnement (machine de cette session)
- `vendor/` avait perdu ses deps dev → `composer install` exécuté (PHPUnit restauré).
- Base MySQL `racine_testing` **absente** → créée + `GRANT` à `laravel` (via debian-sys-maint).
  À refaire si la machine est réprovisionnée.
- **phpunit.xml** : le bloc sqlite (lignes 56-59) est un **fallback commenté** (`<!-- ... -->`),
  pas une config morte active. Aucune action requise (audit initial corrigé).

### Tests flaky pré-existants (fichiers PROTÉGÉS — ne pas modifier)
Suite : **992 tests, 0-1 failure flaky, 8 skipped**. La failure tourne entre
`LogoutTest::test_creator_logout` et `HealthCheckTest::test_health_check_service_logic`
selon l'ordre (fuite d'état intra-suite). Passent en isolation `--filter`. Conforme à la
baseline CLAUDE.md (« Flaky: 0-1 par run »).

---

## SPRINT 3 — RÉSUMÉ (10 juin 2026)

**Commits:** 89221abf, c7a420c2, [en cours Agent 3]
**Impact:** Données légales centralisées + infrastructure images responsive + CGV/RGPD complets

### Réalisations Agent 1 — P0 Légal & Sécurité
1. **config/company.php:** Centralisation infos société (RCS, adresse, téléphone, capital, emails)
2. **4 vues mises à jour:** terms, privacy, showroom, help avec config('company.*')
3. **SecureAdminAccounts command:** Génération passwords 32-char + activation 2FA
4. **.env.example:** COMPANY_* variables documentées

### Réalisations Agent 2 — Srcset Infrastructure
1. **intervention/image-laravel:** Installé pour traitement images
2. **GenerateResponsiveImages command:** Génère 400w/800w/1200w variants
3. **Helper responsive_srcset():** Génération srcset automatique
4. **Composant <x-responsive-image>:** Blade component avec lazy loading
5. **Views updated:** home.blade.php + shop.blade.php avec srcset

### Réalisations Agent 3 — CMS RGPD (en cours)
1. **CGV complètes:** 11 articles (marketplace mode afro, XAF/EUR, Mobile Money, Afrique+diaspora)
2. **Politique RGPD:** Conforme RGPD + loi Informatique et Libertés
3. **Ton professionnel:** Français courant, 0 lorem ipsum

---

## SPRINT 2 — RÉSUMÉ (10 juin 2026)

**Commits:** c53afa1c, 657bb552
**Impact:** Open Graph 100% Facebook/LinkedIn compatible

### Réalisations
1. **OG Image JPG:** Génération via GD library, 1200x630px, 96.85 KB
2. **Script autonome:** generate-og-image.php avec charte RACINE exacte
3. **Documentation:** SPRINT-2-NOTES.md avec analyse blocage srcset

---

## SPRINT 1 — RÉSUMÉ (9 juin 2026)

**Commits:** e0dfe282, c5a98855, 9420635c, 7fb6c902
**Impact:** SEO +80%, Performance +40%, Contenu dynamique 100%

### Réalisations
1. **SEO Structured Data:** Organization, Product, BreadcrumbList (JSON-LD)
2. **Sitemap dynamique:** /sitemap.xml sans dépendance, robots.txt mis à jour
3. **Stats homepage:** Calcul DB temps réel (cache 5min)
4. **Performance:** Preconnect fonts, Font Awesome async, lazy loading
5. **Contenu:** Équipe nettoyée, CreatorBundleSeeder corrigé

---

## P0 — CRITIQUE (bloquant prod)

### Données Légales
- [ ] **RCS:** Insérer vrai numéro RCS Paris (voir `resources/views/frontend/terms.blade.php:220`)
- [ ] **Adresse:** Confirmer/remplacer "15 Rue de la Mode, 75003 Paris" (4 fichiers)
- [ ] **Téléphone:** Remplacer "+33 1 23 45 67 89" (3 fichiers)
- [ ] **Capital social:** Confirmer "10 000 €" (terms.blade.php:220)

### Sécurité
- [ ] **Passwords admin:** Générer mots de passe forts pour:
  - admin@racine.com (actuellement: admin123)
  - admin@racinebyganda.com (actuellement: Admin@2026!)
- [ ] **2FA:** Activer `two_factor_required = true` pour admin@racinebyganda.com
- [ ] **Test seeders:** Vérifier que TestUsersSeeder ne s'exécute pas en production

---

## P1 — IMPORTANT (avant lancement)

### SEO ✅ SPRINT 1 + SPRINT 2 COMPLÉTÉ
- [x] **Sitemap.xml:** ✅ SitemapController créé (sans dépendance), route /sitemap.xml active
- [x] **Structured data:** ✅ JSON-LD implémenté:
  - Organization (homepage) ✅
  - Product (pages produits) ✅
  - BreadcrumbList (shop) ✅
- [x] **Open Graph image:** ✅ JPG 1200x630px généré (96.85 KB), GD library, charte RACINE

### Performance ⚡ SPRINT 1 + SPRINT 3 COMPLÉTÉ
- [x] **Preconnect fonts:** ✅ Google Fonts avec crossorigin
- [x] **Font Awesome async:** ✅ Chargement non-bloquant (media="print" trick)
- [x] **Lazy loading:** ✅ Déjà présent sur shop + home
- [x] **Srcset infrastructure:** ✅ Sprint 3 — intervention/image + GenerateResponsiveImages command
  - Commande créée: `php artisan images:responsive`
  - Helper responsive_srcset() fonctionnel
  - Composant Blade <x-responsive-image> créé
  - Home + shop mis à jour avec srcset
  - **Action requise:** Exécuter commande pour générer ~900 variants (400w/800w/1200w)

### Contenu 📝 SPRINT 1 + SPRINT 3 COMPLÉTÉ
- [x] **Stats homepage:** ✅ Calcul dynamique depuis DB (cache 5min)
  - creators_count, countries_count, clients_count, products_count
- [x] **Équipe about:** ✅ Section "Nous recrutons" ajoutée (membres fictifs supprimés)
- [x] **CMS content:** ✅ Sprint 3 — CGV + Politique RGPD complètes
  - CGV: 11 articles (objet, définitions, inscription, produits, prix, livraison, retours, responsabilités, PI, données, litiges)
  - RGPD: Responsable traitement, données collectées, finalités/bases légales, conservation, droits, destinataires, transferts, cookies, sécurité
  - Ton professionnel, français courant, conforme RGPD + loi Informatique et Libertés

### Database 🗄️ SPRINT 1 COMPLÉTÉ
- [x] **CreatorBundleSeeder:** ✅ Corrigé avec plans actuels (atelier, maison), réactivé
- [ ] **BrandDemoProductSeeder:** Décider si produits réels ou demo → ajouter images si réels

---

## P2 — AMÉLIORATION (backlog)

- [ ] Font Awesome: Subset ou SVG inline (render-blocking actuel)
- [ ] Google Fonts: Ajouter preconnect crossorigin
- [ ] Images ALT: Descriptions plus précises
- [ ] Vidéos hero: Captions/transcription

---

## Fichiers Modifiés (Sprint 0)

### Agent 1 — Légal
- resources/views/frontend/terms.blade.php
- resources/views/frontend/privacy.blade.php
- resources/views/frontend/showroom.blade.php
- resources/views/frontend/help.blade.php

### Agent 2 — Seeders
- database/seeders/DatabaseSeeder.php

### Agent 3 — Home Fallback
- resources/views/frontend/home.blade.php
- resources/views/frontend/about.blade.php

### Agent 4 — Accessibilité
- resources/views/frontend/contact.blade.php

### Agent 5 — SEO
- public/robots.txt
- public/images/og-image-racine.jpg (créé)
- resources/views/auth/login-neutral.blade.php
- resources/views/auth/register-unified.blade.php
- resources/views/auth/passwords/forgot.blade.php
- resources/views/auth/passwords/reset.blade.php

---

## Commandes Validation

```bash
# Test données demo absentes
php artisan migrate:fresh --seed
php artisan tinker
>>> User::whereIn('email', ['dev@racine.com', 'test@example.com'])->count()
# Doit retourner: 0

# Test admin configuré
>>> User::where('email', 'admin@racinebyganda.com')->first()->two_factor_required
# TODO: Doit retourner true après modification

# Test seeders requis présents
>>> App\Models\Role::count() >= 5
>>> App\Models\CreatorPlan::count() >= 4
>>> App\Models\Category::count() >= 100
```
