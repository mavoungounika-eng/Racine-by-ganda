# PROMPT — Intégration des vraies images Racine by Ganda dans les vues publiques

## Contexte

Le projet est un e-commerce Laravel 12 + Blade + Bootstrap 5 pour la marque **Racine by Ganda**.  
Un inventaire complet d'images réelles de la marque est maintenant disponible dans `storage/app/public/`,  
accessible via le lien symbolique `public/storage/` → toujours utiliser `asset('storage/...')` dans les vues.

---

## Inventaire des images disponibles

### hero/ — 14 images (slides accueil)
```
hero/hero-01.jpeg → hero-07.jpeg   (7 images)
hero/slide-1.jpg  → slide-7.jpg    (7 images — anciens slides déjà en place)
```
> Utiliser hero-01 à hero-07 comme slides principaux. Les slide-*.jpg sont les anciens placeholders à remplacer.

### catalogue/vetements/ — 160 images
```
bazin-01 → bazin-06.jpeg           (6)
bomber-01.jpeg                     (1)
chemise-01 → chemise-51.jpeg       (51)
kimono-01 → kimono-08.jpeg         (8)
pagne-01 → pagne-13.jpeg           (13)
soie-01 → soie-05.jpeg             (5)
soiree-01 → soiree-22.jpeg         (22)
traditionnel-01 → traditionnel-02  (2)
veste-01 → veste-04.jpeg           (4)
```

### catalogue/accessoires/ — 63 images
```
kit-voyage-bleu-01 → kit-voyage-bleu-28.jpeg   (28)
kit-voyage-noir-01 → kit-voyage-noir-25.jpeg   (25)
kit-voyage-rouge-01 → kit-voyage-rouge-10.jpeg (10)
```

### showroom/gallery/ — 43 images
```
gallery-01 → gallery-09.jpeg
showroom-01 → showroom-34.jpeg
```

### showroom/collections/ — 7 images
```
collection-01 → collection-07.jpeg
```

### showroom/hero/ — 5 images
```
showroom-hero-01 → showroom-hero-05.jpeg
```

### atelier/ — 6 vidéos mp4
```
WhatsApp Video 2026-04-13 at 20.47.23.mp4
WhatsApp Video 2026-04-13 at 20.47.24.mp4
WhatsApp Video 2026-04-18 at 12.30.22.mp4
WhatsApp Video 2026-04-18 at 12.30.39.mp4
WhatsApp Video 2026-04-18 at 12.30.46.mp4
WhatsApp Video 2026-04-18 at 12.31.04.mp4
```

### products/ — 5 images (upload Laravel, noms hashés)
> Ne pas toucher — utilisées par les fiches produits via le système d'upload existant.

### creators/ — 2 images (upload Laravel, noms hashés)
> Ne pas toucher — utilisées par le système créateurs existant.

---

## Règles absolues

- **Toujours** utiliser `asset('storage/chemin/image.jpeg')` — jamais de chemin absolu
- **Jamais** modifier les images dans `products/` ni `creators/` (gérées par upload)
- **Palette de couleurs** stricte : `#160D0C` (noir), `#ED5F1E` (orange), `#FFB800` (jaune), `#FFFFFF` (blanc)
- **Aucun placeholder générique** (picsum, via.placeholder, lorempixel) ne doit rester dans les vues publiques
- Conserver la structure HTML/Blade existante — modifier uniquement les `src` des images et les `<video>` si besoin

---

## Vues à modifier — par priorité

### Phase 1 — Pages à fort impact visuel (commencer ici)

**1. `resources/views/frontend/home/` — Page d'accueil**
- Hero/slider : remplacer par `hero/hero-01.jpeg` à `hero/hero-07.jpeg`
- Section mise en avant vêtements : utiliser un mix de `chemise-01`, `soiree-01`, `bazin-01`, `kimono-01`, `pagne-01`
- Section collections/lookbook : utiliser `showroom/collections/collection-01` à `collection-07`
- Toute image placeholder → remplacer par une image réelle pertinente

**2. `resources/views/components/hero/` — Composant hero réutilisable**
- Slides : `hero/hero-01` à `hero/hero-07`

**3. `resources/views/components/banner-slider/` — Slider bannières**
- Utiliser les images `showroom/gallery/showroom-01` à `showroom-05` comme bannières d'ambiance

### Phase 2 — Catalogue et shop

**4. `resources/views/frontend/shop/` — Boutique**
- Cards produits vêtements : piocher dans `catalogue/vetements/` selon la catégorie affichée
  - Chemises → `chemise-01` à `chemise-05` (pour les cards de preview)
  - Soirée → `soiree-01` à `soiree-05`
  - Pagnes → `pagne-01` à `pagne-05`
  - Kimonos → `kimono-01` à `kimono-04`
  - Bazin → `bazin-01` à `bazin-03`
  - Vestes → `veste-01` à `veste-04`
  - Bombers → `bomber-01`
  - Accessoires → `kit-voyage-bleu-01`, `kit-voyage-noir-01`, `kit-voyage-rouge-01`

**5. `resources/views/frontend/product/` — Fiche produit**
- Image principale et galerie : utiliser des images cohérentes avec la catégorie du produit
- Galerie carousel : 3 à 5 images de la même famille

### Phase 3 — Showroom, Atelier, About

**6. `resources/views/frontend/showroom/` — Showroom**
- Hero section : `showroom/hero/showroom-hero-01` à `showroom-hero-05`
- Galerie principale : `showroom/gallery/showroom-01` à `showroom-34`
- Collections : `showroom/collections/collection-01` à `collection-07`
- Vidéo ambiance (si section `<video>` présente) : `atelier/WhatsApp Video 2026-04-18 at 12.30.39.mp4`

**7. `resources/views/frontend/atelier/` — Page Atelier**
- Intégrer les 6 vidéos mp4 disponibles dans `atelier/`
- Format : balise `<video controls>` avec `src="{{ asset('storage/atelier/NOM.mp4') }}"`
- Images d'illustration : `showroom/gallery/gallery-01` à `gallery-09`

**8. `resources/views/frontend/about/` et `frontend/ceo/` — À propos / CEO**
- Utiliser `showroom/gallery/showroom-01` à `showroom-05` comme images d'ambiance marque
- Si section portrait CEO : `showroom-hero-01` (photo la plus sobre)

### Phase 4 — Marketplace et créateurs

**9. `resources/views/frontend/marketplace/` — Marketplace**
- Backgrounds et illustrations : `showroom/gallery/showroom-06` à `showroom-15`

**10. `resources/views/frontend/creators/` et `frontend/creator-shop/`**
- Images de fond/ambiance seulement — ne pas toucher aux images de profil créateurs (gérées en BDD)

---

## Pour chaque vue modifiée

1. Lire la vue existante en entier
2. Identifier tous les `src=`, `url()`, `background-image` contenant des placeholders ou chemins génériques
3. Remplacer par les vraies images selon la correspondance ci-dessus
4. Afficher le diff complet
5. Confirmer avant de passer à la vue suivante

---

## Ordre d'exécution recommandé

```
1. frontend/home          (impact maximal)
2. components/hero
3. components/banner-slider
4. frontend/shop
5. frontend/product
6. frontend/showroom
7. frontend/atelier
8. frontend/about + ceo
9. frontend/marketplace
10. frontend/creators
```

Commence par la Phase 1. Montre le diff complet de chaque fichier modifié avant de passer au suivant.
