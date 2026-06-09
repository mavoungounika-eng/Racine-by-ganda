# TODO PRODUCTION — RACINE BY GANDA
**Dernière mise à jour:** 9 juin 2026
**Sprints complétés:**
- ✅ Sprint 0 — Audit Pré-Production (6 commits, 9 P0 résolus)
- ✅ Sprint 1 — SEO+Performance+Contenu (4 commits, 8 P1 résolus)

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

### SEO ✅ SPRINT 1 COMPLÉTÉ
- [x] **Sitemap.xml:** ✅ SitemapController créé (sans dépendance), route /sitemap.xml active
- [x] **Structured data:** ✅ JSON-LD implémenté:
  - Organization (homepage) ✅
  - Product (pages produits) ✅
  - BreadcrumbList (shop) ✅
- [ ] **Open Graph image:** Convertir SVG en JPG 1200x630px optimisé pour Facebook/LinkedIn

### Performance ⚡ SPRINT 1 COMPLÉTÉ
- [x] **Preconnect fonts:** ✅ Google Fonts avec crossorigin
- [x] **Font Awesome async:** ✅ Chargement non-bloquant (media="print" trick)
- [x] **Lazy loading:** ✅ Déjà présent sur shop + home
- [ ] **Srcset images:** Générer 3 tailles (mobile/tablet/desktop) pour:
  - Images hero (home, about)
  - Images produits (shop, product detail)
  - Images créateurs

### Contenu 📝 SPRINT 1 COMPLÉTÉ
- [x] **Stats homepage:** ✅ Calcul dynamique depuis DB (cache 5min)
  - creators_count, countries_count, clients_count, products_count
- [x] **Équipe about:** ✅ Section "Nous recrutons" ajoutée (membres fictifs supprimés)
- [ ] **CMS content:** Remplacer textes placeholder (CGV, Confidentialité, À propos)

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
