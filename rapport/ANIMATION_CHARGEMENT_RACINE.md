# 🎬 ANIMATION DE CHARGEMENT DYNAMIQUE — RACINE BY GANDA

**Date :** 2025  
**Fonctionnalité :** Loading animation avec logo RACINE qui se décroche

---

## 📋 DESCRIPTION

Animation de chargement dynamique qui s'affiche lors du chargement des pages. Les lettres **R-A-C-I-N-E** se décrochent les unes des autres avec une animation fluide et élégante, puis flottent légèrement avant que la page ne soit complètement chargée.

---

## ✨ CARACTÉRISTIQUES

### Animation principale
- **Lettres RACINE** qui se décrochent avec effet de rebond
- **Rotation** individuelle de chaque lettre
- **Flottement continu** après le décrochage
- **Transition fluide** vers la page chargée

### Design
- **Fond dégradé** sombre (charte RACINE)
- **Couleur orange** (#ED5F1E) pour les lettres
- **Effet de lueur** (glow) sur les lettres
- **Typographie** Cormorant Garamond (premium)

### Comportement
- **Affichage automatique** au chargement
- **Masquage automatique** après chargement complet
- **Timeout de sécurité** (3 secondes max)
- **Responsive** mobile/desktop

---

## 🎯 INSTALLATION

L'animation est déjà installée dans les layouts suivants :

- ✅ `resources/views/layouts/frontend.blade.php`
- ✅ `resources/views/layouts/creator.blade.php`
- ✅ `resources/views/layouts/admin-master.blade.php`

### Composant

Le composant est situé dans :
```
resources/views/components/loading-animation.blade.php
```

### Intégration

L'animation est incluse automatiquement dans chaque layout via :
```blade
@include('components.loading-animation')
```

---

## 🎨 CUSTOMISATION

### Modifier les couleurs

Dans `resources/views/components/loading-animation.blade.php` :

```css
/* Couleur des lettres */
.racine-letter {
    color: #ED5F1E; /* Changer cette couleur */
}

/* Couleur du fond */
.racine-loader {
    background: linear-gradient(135deg, #160D0C 0%, #2C1810 50%, #1a0f09 100%);
    /* Modifier ce gradient */
}
```

### Modifier la vitesse d'animation

```css
/* Vitesse de décrochage */
.racine-loader.active .racine-letter-1 {
    animation: decrocheR 1.2s ease-out 0s; /* Changer 1.2s */
}

/* Vitesse de flottement */
@keyframes floatR {
    animation-duration: 2s; /* Changer 2s */
}
```

### Modifier le délai de masquage

Dans la section `<script>` :

```javascript
// Délai avant masquage (en millisecondes)
setTimeout(() => {
    loader.classList.add('hidden');
}, 800); // Changer 800 pour ajuster
```

### Personnaliser le texte

```html
<div class="racine-loader-subtitle">Chargement...</div>
<!-- Changer "Chargement..." par votre texte -->
```

---

## 🔧 DÉSACTIVER L'ANIMATION

Pour désactiver l'animation sur certaines pages, retirez simplement l'inclusion dans le layout :

```blade
{{-- {{-- LOADING ANIMATION --}} --}}
{{-- @include('components.loading-animation') --}}
```

Ou conditionnellement :

```blade
@if(config('app.show_loading_animation', true))
    @include('components.loading-animation')
@endif
```

Puis dans `.env` :
```env
SHOW_LOADING_ANIMATION=false
```

---

## 📱 RESPONSIVE

L'animation s'adapte automatiquement :

- **Desktop** : Lettres plus grandes, décrochage plus prononcé
- **Mobile** : Lettres plus petites, décrochage réduit pour l'écran

---

## 🎬 VARIANTES D'ANIMATION

### Variante 1 : Décrochage rapide

Réduire les délais dans les animations :

```css
.racine-loader.active .racine-letter-1 {
    animation: decrocheR 0.8s ease-out 0s; /* Plus rapide */
}
```

### Variante 2 : Effet de zoom

Ajouter un effet de zoom au décrochage :

```css
@keyframes decrocheR {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
    }
    50% {
        transform: translate(-60px, -40px) rotate(-20deg) scale(1.5); /* Zoom plus fort */
    }
    100% {
        transform: translate(-50px, -30px) rotate(-15deg) scale(1.2);
    }
}
```

### Variante 3 : Animation en cascade

Pour un effet de cascade plus prononcé, augmenter les délais :

```css
.racine-loader.active .racine-letter-1 {
    animation: decrocheR 1.2s ease-out 0s;
}
.racine-loader.active .racine-letter-2 {
    animation: decrocheA 1.2s ease-out 0.2s; /* Augmenter 0.2s */
}
.racine-loader.active .racine-letter-3 {
    animation: decrocheC 1.2s ease-out 0.4s; /* Augmenter 0.4s */
}
/* ... etc */
```

---

## 🐛 DÉPANNAGE

### L'animation ne s'affiche pas

1. Vérifier que le composant est inclus dans le layout
2. Vérifier la console JavaScript pour erreurs
3. Vérifier que la page charge correctement

### L'animation ne disparaît pas

1. Vérifier que `window.addEventListener('load')` fonctionne
2. Vérifier le timeout de sécurité (3 secondes)
3. Vérifier la console pour erreurs JavaScript

### Performance

Si l'animation ralentit le site :

1. Réduire la durée des animations
2. Simplifier les effets CSS
3. Désactiver sur mobile avec une condition

```blade
@if(!request()->isMobile())
    @include('components.loading-animation')
@endif
```

---

## 📊 COMPATIBILITÉ

- ✅ Chrome/Edge (dernières versions)
- ✅ Firefox (dernières versions)
- ✅ Safari (dernières versions)
- ✅ Mobile iOS Safari
- ✅ Mobile Chrome Android

---

## 🎯 PROCHAINES AMÉLIORATIONS POSSIBLES

1. **Particules animées** en arrière-plan
2. **Variante avec logo SVG** animé
3. **Option de personnalisation** dans l'admin
4. **Animation différente** selon la page
5. **Progress bar** sous les lettres

---

**Dernière mise à jour :** 2025


