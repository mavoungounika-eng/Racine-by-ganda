# 📋 PROPOSITION — AJOUT DES PAGES PUBLIQUES DANS LE FOOTER

**Date :** 30 novembre 2025  
**Fichier concerné :** `resources/views/layouts/frontend.blade.php`

---

## 🔍 ANALYSE DES PAGES PUBLIQUES

### Pages actuellement dans le footer

**Section "Boutique" :**
- ✅ Tous les produits (`/boutique`)
- ✅ Nos créateurs (`/createurs`)
- ✅ Showroom virtuel (`/showroom`)
- ✅ L'Atelier (`/atelier`)
- ✅ Mon panier (`/cart`)

**Section "Informations" :**
- ✅ Notre histoire (`/a-propos`)
- ✅ Contact (`/contact`)
- ✅ Livraison (`/livraison`)
- ✅ Retours & Échanges (`/retours-echanges`)
- ✅ FAQ & Aide (`/aide`)

**Bottom Bar (Légal) :**
- ✅ CGV (`/cgv`)
- ✅ Confidentialité (`/confidentialite`)

---

## ❌ PAGES PUBLIQUES MANQUANTES DANS LE FOOTER

### Pages découvertes (non visibles dans le footer) :

1. **Événements** — `/evenements`
   - Route : `route('frontend.events')`
   - Description : Page des événements RACINE

2. **Portfolio** — `/portfolio`
   - Route : `route('frontend.portfolio')`
   - Description : Portfolio des créations

3. **Albums** — `/albums`
   - Route : `route('frontend.albums')`
   - Description : Albums photos

4. **Amira Ganda (CEO)** — `/amira-ganda`
   - Route : `route('frontend.ceo')`
   - Description : Page de présentation de la fondatrice

5. **Charte Graphique** — `/charte-graphique`
   - Route : `route('frontend.brand-guidelines')`
   - Description : Charte graphique de la marque

---

## 📝 PROPOSITION D'ORGANISATION DU FOOTER

### Structure proposée (4 colonnes + Bottom Bar)

```
┌─────────────────────────────────────────────────────────────┐
│                    FOOTER - 4 COLONNES                       │
├──────────────┬──────────────┬──────────────┬──────────────┤
│   BOUTIQUE   │  DÉCOUVERTE  │ INFORMATIONS │    LÉGAL    │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ • Produits   │ • Portfolio  │ • À propos   │ • CGV        │
│ • Créateurs  │ • Albums      │ • Contact    │ • Confidentialité│
│ • Showroom   │ • Événements │ • Livraison  │ • Cookies    │
│ • Atelier    │ • Amira Ganda│ • Retours    │              │
│ • Panier     │ • Charte     │ • FAQ        │              │
└──────────────┴──────────────┴──────────────┴──────────────┘
```

---

## 🎯 PROPOSITION DÉTAILLÉE

### Colonne 1 : BOUTIQUE (inchangée)
```
Boutique
├── Tous les produits
├── Nos créateurs
├── Showroom virtuel
├── L'Atelier
└── Mon panier
```

### Colonne 2 : DÉCOUVERTE (NOUVELLE SECTION)
```
Découverte
├── Portfolio
├── Albums
├── Événements
├── Amira Ganda
└── Charte Graphique
```

### Colonne 3 : INFORMATIONS (inchangée)
```
Informations
├── Notre histoire
├── Contact
├── Livraison
├── Retours & Échanges
└── FAQ & Aide
```

### Colonne 4 : LÉGAL (NOUVELLE SECTION)
```
Légal
├── CGV
├── Confidentialité
└── Cookies
```

---

## 📋 CODE PROPOSÉ

### Section "Découverte" (Nouvelle colonne)

```blade
{{-- Colonne 2: Découverte --}}
<div class="footer-links-col">
    <h4>Découverte</h4>
    <ul>
        <li><a href="{{ route('frontend.portfolio') }}"><i class="fas fa-chevron-right"></i> Portfolio</a></li>
        <li><a href="{{ route('frontend.albums') }}"><i class="fas fa-chevron-right"></i> Albums</a></li>
        <li><a href="{{ route('frontend.events') }}"><i class="fas fa-chevron-right"></i> Événements</a></li>
        <li><a href="{{ route('frontend.ceo') }}"><i class="fas fa-chevron-right"></i> Amira Ganda</a></li>
        <li><a href="{{ route('frontend.brand-guidelines') }}"><i class="fas fa-chevron-right"></i> Charte Graphique</a></li>
    </ul>
</div>
```

### Section "Légal" (Nouvelle colonne)

```blade
{{-- Colonne 4: Légal --}}
<div class="footer-links-col">
    <h4>Légal</h4>
    <ul>
        <li><a href="{{ route('frontend.terms') }}"><i class="fas fa-chevron-right"></i> Conditions Générales</a></li>
        <li><a href="{{ route('frontend.privacy') }}"><i class="fas fa-chevron-right"></i> Confidentialité</a></li>
        <li><a href="#"><i class="fas fa-chevron-right"></i> Cookies</a></li>
    </ul>
</div>
```

### Réorganisation complète (4 colonnes)

**Ordre des colonnes :**
1. **Brand** (logo, description, réseaux sociaux) — Colonne 1
2. **Boutique** — Colonne 2
3. **Découverte** — Colonne 3 (NOUVELLE)
4. **Informations** — Colonne 4
5. **Légal** — Colonne 5 (NOUVELLE)

---

## ✅ RÉSUMÉ DES MODIFICATIONS

### Pages à ajouter dans le footer :

1. ✅ **Portfolio** (`/portfolio`) — Section "Découverte"
2. ✅ **Albums** (`/albums`) — Section "Découverte"
3. ✅ **Événements** (`/evenements`) — Section "Découverte"
4. ✅ **Amira Ganda** (`/amira-ganda`) — Section "Découverte"
5. ✅ **Charte Graphique** (`/charte-graphique`) — Section "Découverte"

### Sections à créer :

- ✅ **Section "Découverte"** — Nouvelle colonne avec 5 liens
- ✅ **Section "Légal"** — Nouvelle colonne (ou intégrer dans bottom bar)

---

## 🎨 DESIGN PROPOSÉ

### Structure Footer (5 colonnes)

```
┌─────────────────────────────────────────────────────────────────┐
│  [LOGO + DESCRIPTION + RÉSEAUX]  │  BOUTIQUE  │  DÉCOUVERTE  │  INFORMATIONS  │  LÉGAL  │
└─────────────────────────────────────────────────────────────────┘
```

### Avantages :
- ✅ Toutes les pages publiques accessibles
- ✅ Organisation logique par catégorie
- ✅ Meilleure navigation pour les utilisateurs
- ✅ SEO amélioré (liens internes)

---

## ⚠️ ATTENTION

**Le footer actuel a 4 colonnes :**
1. Brand (logo, description)
2. Boutique
3. Informations
4. Contact

**Proposition : Passer à 5 colonnes :**
1. Brand (logo, description)
2. Boutique
3. **Découverte** (NOUVELLE)
4. Informations
5. **Légal** (NOUVELLE)

**OU garder 4 colonnes et fusionner :**
- Informations + Légal dans une seule colonne
- Découverte comme nouvelle colonne

---

## 📌 RECOMMANDATION

**Option 1 : 5 colonnes (recommandée)**
- Plus d'espace pour chaque section
- Meilleure organisation
- Responsive avec grid adaptatif

**Option 2 : 4 colonnes (compacte)**
- Fusionner "Informations" et "Légal"
- Ajouter "Découverte" comme nouvelle colonne

---

**En attente de votre validation avant application !** ✅

