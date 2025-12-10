# Guide d'Utilisation - Nouveau Design System

## 🎨 Layouts Disponibles

### 1. Layout Frontend (`layouts/master.blade.php`)

**Usage:**
```blade
@extends('layouts.master')

@section('title', 'Titre de la page')

@section('content')
    <!-- Votre contenu ici -->
@endsection
```

**Caractéristiques:**
- Header fixe avec navigation responsive
- Menu mobile avec Alpine.js
- Footer complet
- Panier avec compteur dynamique
- Bouton "Espace Membre" conditionnel

---

### 2. Layout Admin ERP (`layouts/admin-master.blade.php`)

**Usage:**
```blade
@extends('layouts.admin-master')

@section('title', 'Titre de la page')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Votre contenu ici -->
@endsection
```

**Caractéristiques:**
- Dark mode complet
- Sidebar collapsible
- Top bar avec notifications
- User menu dropdown

---

### 3. Layout Creator (`layouts/creator-master.blade.php`)

**Usage:**
```blade
@extends('layouts.creator-master')

@section('title', 'Titre de la page')
@section('page-title', 'Mes Produits')
@section('page-subtitle', 'Gérez vos créations')

@section('content')
    <!-- Votre contenu ici -->
@endsection
```

**Caractéristiques:**
- Light mode avec accents or
- Sidebar organisée par catégories
- Quick actions dans le top bar
- Navigation créative

---

## 🧩 Composants Blade

### Button (`<x-button>`)

**Variants:** primary, secondary, accent, danger, outline  
**Sizes:** sm, md, lg

```blade
<!-- Button simple -->
<x-button variant="primary">
    Cliquez ici
</x-button>

<!-- Button avec icône -->
<x-button variant="accent" icon="fas fa-plus">
    Ajouter
</x-button>

<!-- Button avec lien -->
<x-button variant="primary" href="{{ route('shop') }}">
    Voir la boutique
</x-button>

<!-- Button taille personnalisée -->
<x-button variant="accent" size="lg" icon="fas fa-shopping-bag">
    Acheter maintenant
</x-button>
```

---

### Input (`<x-input>`)

```blade
<!-- Input simple -->
<x-input 
    name="email" 
    type="email" 
    label="Adresse Email"
    placeholder="votre@email.com"
/>

<!-- Input avec icône -->
<x-input 
    name="search" 
    type="text" 
    label="Rechercher"
    icon="fas fa-search"
    placeholder="Rechercher un produit..."
/>

<!-- Input requis avec erreur -->
<x-input 
    name="password" 
    type="password" 
    label="Mot de passe"
    icon="fas fa-lock"
    required
    :error="$errors->first('password')"
/>
```

---

### Card (`<x-card>`)

**Variants:** default, header, dark, gradient  
**Padding:** Personnalisable (p-4, p-6, p-8, etc.)

```blade
<!-- Card simple -->
<x-card>
    <h3>Titre</h3>
    <p>Contenu de la carte</p>
</x-card>

<!-- Card avec padding personnalisé -->
<x-card padding="p-6">
    <h3>Titre</h3>
    <p>Contenu</p>
</x-card>

<!-- Card dark (pour ERP) -->
<x-card variant="dark">
    <h3 class="text-white">Titre</h3>
    <p class="text-gray-300">Contenu</p>
</x-card>

<!-- Card avec gradient -->
<x-card variant="gradient">
    <h3>Titre</h3>
    <p>Contenu</p>
</x-card>

<!-- Card sans hover -->
<x-card :hover="false">
    <h3>Titre</h3>
</x-card>
```

---

### Badge (`<x-badge>`)

**Variants:** default, success, warning, danger, info, accent

```blade
<!-- Badge simple -->
<x-badge variant="success">
    Actif
</x-badge>

<!-- Badge avec icône -->
<x-badge variant="warning" icon="fas fa-clock">
    En attente
</x-badge>

<!-- Exemples de variants -->
<x-badge variant="success">Validé</x-badge>
<x-badge variant="danger">Annulé</x-badge>
<x-badge variant="info">Information</x-badge>
<x-badge variant="accent">Premium</x-badge>
```

---

### Alert (`<x-alert>`)

**Types:** success, error, warning, info

```blade
<!-- Alert simple -->
<x-alert type="success">
    Votre commande a été créée avec succès !
</x-alert>

<!-- Alert dismissible -->
<x-alert type="info" dismissible>
    Nouvelle fonctionnalité disponible.
</x-alert>

<!-- Alert avec contenu HTML -->
<x-alert type="warning">
    <strong>Attention :</strong> Stock limité sur ce produit.
</x-alert>

<!-- Alert d'erreur -->
<x-alert type="error">
    Une erreur s'est produite. Veuillez réessayer.
</x-alert>
```

---

## 🎨 Classes Tailwind Personnalisées

### Couleurs

```css
/* Primary (Noir) */
bg-primary, text-primary, border-primary
bg-primary-light, bg-primary-dark

/* Accent (Or) */
bg-accent, text-accent, border-accent
bg-accent-light, bg-accent-dark

/* ERP (Dark Mode) */
bg-erp-bg, bg-erp-card, border-erp-border
bg-erp-accent, text-erp-accent
```

### Typographie

```html
<!-- Titres avec Playfair Display -->
<h1 class="font-display text-4xl font-bold">Titre</h1>

<!-- Texte avec Inter -->
<p class="text-base">Texte normal</p>
```

---

## 📱 Responsive

Tous les composants et layouts sont responsive par défaut.

**Breakpoints:**
- `sm:` 640px
- `md:` 768px
- `lg:` 1024px
- `xl:` 1280px

**Exemple:**
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Mobile: 1 colonne, Tablet: 2 colonnes, Desktop: 4 colonnes -->
</div>
```

---

## 🎭 Animations

### AOS (Animate On Scroll)

Déjà inclus dans la page home. Pour l'utiliser ailleurs:

```blade
@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>
@endpush
```

**Usage:**
```html
<div data-aos="fade-up">Contenu animé</div>
<div data-aos="fade-right" data-aos-delay="100">Contenu avec délai</div>
```

---

## 🔧 Bonnes Pratiques

### 1. Utiliser les Composants
```blade
<!-- ❌ Mauvais -->
<button class="px-6 py-3 bg-primary text-white rounded-full...">
    Cliquer
</button>

<!-- ✅ Bon -->
<x-button variant="primary">
    Cliquer
</x-button>
```

### 2. Respecter la Hiérarchie
```blade
<!-- Utiliser les layouts appropriés -->
@extends('layouts.master')        <!-- Pour frontend -->
@extends('layouts.admin-master')  <!-- Pour admin -->
@extends('layouts.creator-master') <!-- Pour créateur -->
```

### 3. Cohérence Visuelle
```blade
<!-- Utiliser les mêmes variants partout -->
<x-button variant="accent">Action principale</x-button>
<x-button variant="outline">Action secondaire</x-button>
```

---

## 📚 Exemples Complets

### Page Produit Simple

```blade
@extends('layouts.master')

@section('title', 'Nos Produits')

@section('content')
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="font-display text-4xl font-bold text-primary mb-8">
            Nos Produits
        </h1>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($products as $product)
            <x-card>
                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-64 object-cover rounded-lg mb-4">
                <h3 class="font-semibold text-lg mb-2">{{ $product->name }}</h3>
                <p class="text-gray-600 mb-4">{{ $product->description }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-bold text-accent">{{ $product->price }} F CFA</span>
                    <x-button variant="primary" size="sm">
                        Ajouter
                    </x-button>
                </div>
            </x-card>
            @endforeach
        </div>
    </div>
</section>
@endsection
```

---

**Dernière mise à jour:** 24/11/2025
