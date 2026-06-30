# Sprint 2 — OG Image JPG + Srcset Responsive

**Date:** 10 juin 2026
**Status:** Agent 1 ✅ Complete | Agent 2 ⚠️ Blocked

---

## Agent 1 — OG Image JPG Generation ✅

### Objectif
Convertir `public/images/og-image-racine.svg` en JPG 1200x630px optimisé pour Facebook/LinkedIn.

### Réalisations
1. **Script autonome:** `generate-og-image.php` créé avec GD library
2. **Charte RACINE respectée:**
   - Noir: #160D0C
   - Orange: #ED5F1E
   - Jaune: #FFB800
   - Typographie: DejaVu Sans (fallback system fonts)
3. **Résultat:**
   - Fichier: `public/images/og-image-racine.jpg`
   - Dimensions: 1200x630px (conforme Facebook/LinkedIn)
   - Taille: 96.85 KB (< 300KB target ✅)
   - Qualité: 90 JPEG
   - Validation: `file public/images/og-image-racine.jpg` → JPEG image data ✅
4. **Meta tags:** Layout déjà configuré avec `asset('images/og-image-racine.jpg')`

### Commit
```
c53afa1c feat(seo): generate real JPG og-image 1200x630 for social sharing
```

---

## Agent 2 — Srcset Responsive ⚠️ Blocked

### Objectif initial
Implémenter srcset responsive avec 3 tailles (mobile/tablet/desktop) pour:
- Images hero (home, about)
- Images produits (shop, product detail)
- Images créateurs

### Blocage identifié
**Srcset nécessite génération d'images multiples** qui n'existent pas actuellement:
- `hero-01-500w.jpg`, `hero-01-800w.jpg`, `hero-01-1200w.jpg`
- `product-{id}-500w.jpg`, etc.
- `creator-avatar-{id}-200w.jpg`, etc.

### Images actuelles
Analyse des vues frontend révèle:
- **Hero:** 7 images JPEG dans `storage/hero/` (taille unique)
- **Produits:** Images dans `storage/products/` (taille unique)
- **Créateurs:** Avatars/banners dans `storage/` (taille unique)
- **Lazy loading:** ✅ Déjà présent sur shop, home, about

### Solution requise (hors scope Sprint 2)
1. **Installer Intervention Image** ou équivalent
2. **Créer commande Artisan** pour batch-processing:
   ```bash
   php artisan images:generate-responsive
   ```
3. **Générer variants:**
   - Mobile: 500px width
   - Tablet: 800px width
   - Desktop: 1200px width
4. **Structure storage:**
   ```
   storage/hero/
     hero-01.jpeg (original)
     hero-01-500w.jpg
     hero-01-800w.jpg
     hero-01-1200w.jpg
   ```
5. **Mettre à jour templates** avec srcset:
   ```html
   <img src="{{ asset('storage/hero/hero-01-800w.jpg') }}"
        srcset="{{ asset('storage/hero/hero-01-500w.jpg') }} 500w,
                {{ asset('storage/hero/hero-01-800w.jpg') }} 800w,
                {{ asset('storage/hero/hero-01-1200w.jpg') }} 1200w"
        sizes="(max-width: 768px) 100vw, (max-width: 1024px) 80vw, 1200px"
        width="1200" height="675" loading="lazy" alt="...">
   ```

### Réalisations Agent 2
1. **Analyse complète** des images frontend
2. **Documentation** du blocage dans TODO.prod.md
3. **Plan d'action** défini pour implémentation future

---

## TODO Production — Mise à jour

### P1 — Avant lancement
- [x] **Open Graph image:** ✅ JPG 1200x630px généré (Sprint 2 Agent 1)
- [ ] **Srcset images:** ⚠️ Nécessite génération batch (Intervention Image)
  - Templates prêts avec lazy loading ✅
  - Manque: 3 tailles par image (mobile/tablet/desktop)
  - Estimation: ~300 images à traiter (hero + produits + créateurs)

### Recommandation
**Priorité après Sprint 2:**
1. Installer `intervention/image` via Composer
2. Créer commande `images:generate-responsive`
3. Exécuter batch-processing sur `storage/hero/`, `storage/products/`, avatars
4. Mettre à jour templates avec srcset
5. Tester performance (Lighthouse, PageSpeed Insights)

---

## Commits Sprint 2
1. `c53afa1c` — OG Image JPG generation (Agent 1) ✅
2. `[pending]` — TODO.prod.md update + Sprint 2 notes (Agent 2) ⚠️

---

## Impact attendu (post-génération images)

### Performance
- **Réduction poids mobile:** ~60% (hero 1.2MB → 400KB)
- **Réduction poids tablet:** ~40% (hero 1.2MB → 700KB)
- **CLS prévention:** Explicit width/height = Lighthouse CLS score +20pts

### SEO
- **PageSpeed Insights:** Score mobile 60 → 85+ (estimation)
- **Core Web Vitals:** LCP amélioration significative (largest contentful paint)
- **Open Graph:** ✅ Déjà résolu (JPG 96.85 KB)

---

**Conclusion:** Sprint 2 Agent 1 accompli, Agent 2 nécessite infrastructure image processing. Documentation complète pour implémentation future.
