# TODO PRODUCTION — RACINE BY GANDA
**Dernière mise à jour:** 9 juin 2026
**Sprint:** Sprint 0 — Audit Pré-Production

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

### SEO
- [ ] **Sitemap.xml:** Installer `spatie/laravel-sitemap` et générer sitemap dynamique
  ```bash
  composer require spatie/laravel-sitemap
  php artisan make:command GenerateSitemap
  ```
- [ ] **Structured data:** Implémenter JSON-LD pour:
  - Organization (homepage)
  - Product (pages produits)
  - BreadcrumbList (navigation)
- [ ] **Open Graph image:** Remplacer `public/images/og-image-racine.jpg` par vraie image 1200x630px

### Performance
- [ ] **Srcset images:** Générer 3 tailles (mobile/tablet/desktop) pour:
  - Images hero (home, about)
  - Images produits (shop, product detail)
  - Images créateurs

### Contenu
- [ ] **Stats homepage:** Implémenter calcul dynamique (voir `home.blade.php:313`)
  - Créateurs partenaires (actuellement: hardcodé "50+")
  - Pays représentés (actuellement: hardcodé "15")
  - Clients satisfaits (actuellement: hardcodé "5000+")
- [ ] **Équipe about:** Ajouter vrais membres ou retirer section (about.blade.php:570+)
- [ ] **CMS content:** Remplacer textes placeholder (CGV, Confidentialité, À propos)

### Database
- [ ] **CreatorBundleSeeder:** Corriger plans obsolètes (official, premium) ou supprimer
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
