<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- SEO Meta Tags --}}
    <title>@yield('title', 'RACINE BY GANDA - Mode Africaine Premium')</title>
    <meta name="description" content="@yield('meta-description', 'RACINE BY GANDA - Mode africaine premium. Créations authentiques qui célèbrent l\'héritage africain avec une touche contemporaine. Découvrez nos collections exclusives.')">
    <meta name="keywords" content="@yield('meta-keywords', 'mode africaine, vêtements africains, mode premium, RACINE BY GANDA, créations authentiques, style africain, fashion africa')">
    <meta name="author" content="RACINE BY GANDA">
    <link rel="canonical" href="@yield('canonical-url', url()->current())">
    
    {{-- Open Graph Meta Tags (Facebook, LinkedIn, etc.) --}}
    <meta property="og:type" content="@yield('og-type', 'website')">
    <meta property="og:title" content="@yield('og-title', $__env->yieldContent('title', 'RACINE BY GANDA - Mode Africaine Premium'))">
    <meta property="og:description" content="@yield('og-description', $__env->yieldContent('meta-description', 'RACINE BY GANDA - Mode africaine premium. Créations authentiques qui célèbrent l\'héritage africain avec une touche contemporaine.'))">
    <meta property="og:image" content="@yield('og-image', asset('images/og-image-racine.jpg'))">
    <meta property="og:url" content="@yield('canonical-url', url()->current())">
    <meta property="og:site_name" content="{{ config('app.company.name') }}">
    <meta property="og:locale" content="fr_FR">
    
    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og-title', $__env->yieldContent('title', 'RACINE BY GANDA - Mode Africaine Premium'))">
    <meta name="twitter:description" content="@yield('og-description', $__env->yieldContent('meta-description', 'RACINE BY GANDA - Mode africaine premium. Créations authentiques qui célèbrent l\'héritage africain avec une touche contemporaine.'))">
    <meta name="twitter:image" content="@yield('og-image', asset('images/og-image-racine.jpg'))">
    <meta name="twitter:site" content="@racinebyganda">
    <meta name="twitter:creator" content="@racinebyganda">
    
    {{-- Additional SEO --}}
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <meta name="theme-color" content="#ED5F1E">
    
    {{-- Fonts RACINE --}}
    {{-- Aileron (accentué, disponible Google Fonts) --}}
    {{-- Cormorant Garamond (fallback Aleppo) + Nunito (fallback Coco Gothic) --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 chargé via Vite (app.scss) — Bootstrap 4 legacy supprimé --}}

    {{-- RACINE Design System --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    
    {{-- RACINE Layout CSS (extrait du inline pour optimisation) --}}
    <link rel="stylesheet" href="{{ asset('css/layout-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-footer-cta.css') }}">
    
    {{-- Page-specific CSS (chargement conditionnel pour performance) --}}
    @if(request()->routeIs('home') || request()->routeIs('frontend.home'))
        <link rel="stylesheet" href="{{ asset('css/frontend-home.css') }}">
    @endif
    @if(request()->routeIs('frontend.shop'))
        <link rel="stylesheet" href="{{ asset('css/frontend-shop.css') }}">
    @endif
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --racine-black: #160D0C;
            --racine-orange: #ED5F1E;
            --racine-yellow: #FFB800;
            --racine-white: #FFFFFF;
            --racine-font-heading: 'Aleppo', 'Cormorant Garamond', 'Aileron', serif;
            --racine-font-body: 'Coco Gothic', 'Nunito', 'Aileron', 'Helvetica Neue', sans-serif;
            --racine-font-accent: 'Aileron', 'Nunito', 'Helvetica Neue', sans-serif;
        }

        body.racine-frontend-layout {
            background: var(--racine-white);
            color: var(--racine-black);
            font-family: var(--racine-font-body);
        }

        body.racine-frontend-layout h1,
        body.racine-frontend-layout h2,
        body.racine-frontend-layout h3,
        body.racine-frontend-layout h4,
        body.racine-frontend-layout h5,
        body.racine-frontend-layout h6 {
            font-family: var(--racine-font-heading);
        }

        .announcement-bar {
            background: var(--racine-black);
            color: var(--racine-yellow);
        }

        .announcement-text {
            font-family: var(--racine-font-accent);
        }

        .navbar-racine {
            background: var(--racine-black);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .logo-text {
            color: var(--racine-yellow);
            font-family: var(--racine-font-heading);
            letter-spacing: 0.08em;
        }

        .main-nav-racine .nav-link-racine,
        .main-nav-racine .nav-dropdown-toggle.nav-link-racine {
            color: var(--racine-white) !important;
            font-family: var(--racine-font-accent);
        }

        .main-nav-racine .nav-link-racine:hover,
        .main-nav-racine .nav-dropdown-toggle.nav-link-racine:hover {
            color: var(--racine-yellow) !important;
        }

        .nav-dropdown-menu {
            background: var(--racine-black);
            border: 1px solid rgba(255, 184, 0, 0.35);
        }

        .nav-dropdown-menu a {
            color: var(--racine-white);
        }

        .nav-dropdown-menu a:hover {
            color: var(--racine-yellow);
            background: rgba(255, 184, 0, 0.12);
        }

        .nav-icon-btn {
            color: var(--racine-white);
            border-color: rgba(255, 255, 255, 0.32);
        }

        .nav-icon-btn:hover {
            background: var(--racine-orange);
            border-color: var(--racine-orange);
            color: var(--racine-white);
        }

        .nav-icon-btn-primary {
            background: var(--racine-orange);
            color: var(--racine-white);
        }

        #mobile-menu {
            background: var(--racine-black) !important;
        }

        #mobile-menu a,
        #mobile-menu p,
        #mobile-menu button {
            font-family: var(--racine-font-accent);
        }

        #mobile-menu .text-white-50 {
            color: rgba(255, 255, 255, 0.78) !important;
        }

        #cta-racine .cta-title,
        #cta-racine .cta-card-title {
            font-family: var(--racine-font-heading);
        }

        #cta-racine .cta-subtitle,
        #cta-racine .cta-card-text,
        #cta-racine .cta-note {
            font-family: var(--racine-font-body);
        }

        .footer-main {
            background: var(--racine-black);
            color: var(--racine-white);
        }

        .footer-brand span,
        .footer-links-col h4,
        .footer-contact-col h4 {
            color: var(--racine-yellow);
            font-family: var(--racine-font-heading);
        }

        .footer-main a {
            color: var(--racine-white);
        }

        .footer-main a:hover {
            color: var(--racine-yellow);
        }

        .social-link {
            color: var(--racine-yellow);
            border-color: rgba(255, 184, 0, 0.45);
        }

        .social-link:hover {
            background: var(--racine-orange);
            border-color: var(--racine-orange);
            color: var(--racine-white);
        }

        .footer-bottom {
            background: var(--racine-orange);
            color: var(--racine-white);
        }

        .footer-bottom a,
        .footer-bottom strong,
        .footer-bottom p {
            color: var(--racine-white);
        }

        .footer-bottom a:hover {
            color: var(--racine-yellow);
        }

        .footer-bottom .dev-separator,
        .footer-bottom .legal-links span {
            color: var(--racine-yellow);
        }
    </style>
    
    {{-- Vite assets --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="racine-frontend-layout">
    {{-- ANNOUNCEMENT BAR PREMIUM --}}
    <div class="announcement-bar">
        <div class="container text-center">
            <span class="announcement-text">✨ Livraison offerte dès 150€ d'achat | Collection "Héritage" disponible 🌿</span>
        </div>
    </div>

    {{-- HEADER PREMIUM RACINE BY GANDA --}}
    <header role="banner" class="navbar-racine sticky-top w-100">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between" style="height: 70px;">
                
                {{-- LOGO + NOM --}}
                <a href="{{ route('frontend.home') }}" class="logo-navbar-wrapper">
                    <div class="logo-navbar-container">
                        <img src="{{ asset('images/logo-racine.png') }}" alt="Logo RACINE BY GANDA" class="logo-navbar-img">
                    </div>
                    <span class="logo-text d-none d-md-block">RACINE BY GANDA</span>
                </a>
                
                {{-- MENU DESKTOP --}}
                <nav role="navigation" aria-label="Navigation principale" class="d-none d-lg-flex align-items-center main-nav-racine">
                    <a href="{{ route('frontend.home') }}" class="nav-link-racine">Accueil</a>
                    <a href="{{ route('frontend.atelier') }}" class="nav-link-racine">Atelier</a>
                    
                    {{-- Dropdown Boutique --}}
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-toggle nav-link-racine" aria-label="Menu boutique" aria-expanded="false" aria-haspopup="true">
                            Boutique <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            <a href="{{ route('frontend.shop') }}"><i class="fas fa-store"></i> RACINE BY GANDA</a>
                            <a href="{{ route('frontend.marketplace') }}"><i class="fas fa-shopping-bag"></i> Marketplace</a>
                        </div>
                    </div>
                    
                    <a href="{{ route('frontend.showroom') }}" class="nav-link-racine">Showroom</a>
                    
                    {{-- Panier --}}
                    <a href="{{ route('cart.index') }}" class="nav-link-racine d-flex align-items-center position-relative" style="gap: 0.5rem;" aria-label="Voir le panier">
                        <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                        <span>Panier</span>
                        @if(isset($cartCount) && $cartCount > 0)
                          <span class="badge badge-danger" 
                                style="font-size: 0.65rem; padding: 0.2rem 0.4rem; border-radius: 10px; margin-left: 0.25rem;"
                                id="cart-count-badge">{{ $cartCount }}</span>
                        @endif
                    </a>
                </nav>
                
                {{-- ICÔNES DROITE --}}
                <div class="d-flex align-items-center" style="gap: 0.75rem;">
                    
                    {{-- Sélecteur de Devise --}}
                    @include('components.currency-selector')

                    {{-- Dropdown Info (À propos + Contact) --}}
                    <div class="nav-dropdown d-none d-lg-block">
                        <button class="nav-icon-btn nav-dropdown-toggle" title="Informations" aria-label="Menu informations" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                        </button>
                        <div class="nav-dropdown-menu nav-dropdown-menu-right">
                            <a href="{{ route('frontend.about') }}"><i class="fas fa-heart"></i> À propos</a>
                            <a href="{{ route('frontend.contact') }}"><i class="fas fa-envelope"></i> Contact</a>
                            <div class="nav-dropdown-divider"></div>
                            <a href="{{ route('frontend.help') }}"><i class="fas fa-question-circle"></i> Aide</a>
                        </div>
                    </div>
                    
                    @auth
                        {{-- Dropdown Compte (Utilisateur Connecté) --}}
                        <div class="nav-dropdown d-none d-lg-block">
                            <button class="nav-icon-btn nav-icon-btn-primary nav-dropdown-toggle" title="Mon compte" aria-label="Menu mon compte" aria-expanded="false" aria-haspopup="true">
                                <i class="fas fa-user" aria-hidden="true"></i>
                            </button>
                            <div class="nav-dropdown-menu nav-dropdown-menu-right">
                                @if(auth()->user()->getRoleSlug() === 'createur')
                                    <a href="{{ route('creator.dashboard') }}"><i class="fas fa-tachometer-alt"></i> Espace créateur</a>
                                @elseif(in_array(auth()->user()->getRoleSlug(), ['admin', 'super_admin', 'staff']))
                                    <a href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt"></i> Administration</a>
                                @else
                                    <a href="{{ route('account.dashboard') }}"><i class="fas fa-tachometer-alt"></i> Mon compte</a>
                                @endif
                                <a href="{{ route('profile.edit') }}"><i class="fas fa-user-circle"></i> Mon profil</a>
                                <a href="{{ route('profile.orders') }}"><i class="fas fa-shopping-bag"></i> Mes commandes</a>
                                <div class="nav-dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="all: unset; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; font-size: 0.875rem; color: rgba(255, 255, 255, 0.8); cursor: pointer; width: 100%; transition: all 0.2s;">
                                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        {{-- Bouton Connexion (Guest) --}}
                        <a href="{{ route('login') }}" class="nav-icon-btn nav-icon-btn-primary" title="Connexion" aria-label="Se connecter ou créer un compte">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </a>
                    @endauth
                    
                    {{-- Burger menu mobile --}}
                    <button id="mobile-menu-toggle" class="d-lg-none btn btn-link text-white p-0" style="font-size: 1.75rem; border: none; background: none;" aria-label="Ouvrir le menu mobile" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            {{-- MENU MOBILE --}}
            <div id="mobile-menu" class="d-lg-none pb-4" style="background: #160D0C; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                <div class="d-flex flex-column" style="gap: 0.5rem;">
                    <a href="{{ route('frontend.home') }}" class="text-white py-2" style="text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Accueil</a>
                    <a href="{{ route('frontend.atelier') }}" class="text-white py-2" style="text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Atelier</a>
                    
                    {{-- Boutique section --}}
                    <div class="py-2" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <p class="text-white mb-2" style="font-weight: 600; font-size: 0.9rem;">Boutique</p>
                        <a href="{{ route('frontend.shop') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">
                            <i class="fas fa-store" style="margin-right: 0.5rem;"></i> RACINE BY GANDA
                        </a>
                        <a href="{{ route('frontend.marketplace') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">
                            <i class="fas fa-shopping-bag" style="margin-right: 0.5rem;"></i> Marketplace
                        </a>
                    </div>
                    
                    <a href="{{ route('frontend.showroom') }}" class="text-white py-2" style="text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Showroom</a>
                    
                    {{-- Info section --}}
                    <div class="py-2" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <p class="text-white mb-2" style="font-weight: 600; font-size: 0.9rem;">Informations</p>
                        <a href="{{ route('frontend.about') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">→ À propos</a>
                        <a href="{{ route('frontend.contact') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">→ Contact</a>
                        <a href="{{ route('frontend.help') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">→ Aide</a>
                    </div>
                    
                    <a href="{{ route('cart.index') }}" class="text-white d-flex align-items-center py-2" style="gap: 0.5rem; text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <span style="font-size: 1.25rem;">🛒</span> Panier
                    </a>
                    
                    
                    @auth
                        {{-- Options compte pour utilisateurs connectés (Mobile) --}}
                        <a href="{{ route('account.dashboard') }}" class="text-white d-flex align-items-center py-2" style="gap: 0.5rem; text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="font-size: 1.25rem;">🏠</span> Mon compte
                        </a>
                        <a href="{{ route('profile.orders') }}" class="text-white d-flex align-items-center py-2" style="gap: 0.5rem; text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="font-size: 1.25rem;">📦</span> Mes commandes
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="text-white d-flex align-items-center py-2 w-100 text-left" style="gap: 0.5rem; text-decoration: none; border: none; background: none; cursor: pointer; font-size: 1rem; font-family: inherit;">
                                <span style="font-size: 1.25rem;">🚪</span> Déconnexion
                            </button>
                        </form>
                    @else
                        {{-- Bouton connexion pour invités (Mobile) --}}
                        <a href="{{ route('login') }}" class="text-white d-flex align-items-center py-2" style="gap: 0.5rem; text-decoration: none;">
                            <span style="font-size: 1.25rem;">👤</span> Connexion
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>
    
    
    {{-- CONTENT --}}
    <main role="main">
    {{-- Messages flash globaux --}}
    @if(session('success'))
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid #ED5F1E; background: #FFFFFF; border-radius: 8px; color: #160D0C;">
                <i class="fas fa-check-circle mr-2" style="color: #ED5F1E;"></i>
                <strong>{{ session('success') }}</strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mt-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid #ED5F1E; background: #FFFFFF; border-radius: 8px; color: #160D0C;">
                <i class="fas fa-exclamation-circle mr-2" style="color: #ED5F1E;"></i>
                <strong>{{ session('error') }}</strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    @yield('content')
    </main>
    
    {{-- BLOC CTA RACINE - CSS CLASSIQUE --}}
    <section id="cta-racine">
        <div class="cta-wrapper">
            <h2 class="cta-title">Restez connecté à l'univers RACINE BY GANDA</h2>
            <p class="cta-subtitle">
                Que vous soyez client, créateur ou porteur de projet, choisissez le parcours qui vous correspond
                et laissez-nous vous accompagner.
            </p>

            <div class="cta-cards">
                {{-- Carte 1 : Nous contacter --}}
                <div class="cta-card cta-card-contact">
                    <h3 class="cta-card-title">Besoin d'un conseil ou d'un rendez-vous&nbsp;?</h3>
                    <p class="cta-card-text">
                        Notre équipe vous répond pour toute question concernant nos produits, le showroom
                        ou un projet sur-mesure.
                    </p>
                    <a href="{{ route('frontend.contact') }}" class="cta-btn cta-btn-dark">
                        Nous contacter
                    </a>
                </div>

                {{-- Carte 2 : Devenir créateur / envoyer une candidature --}}
                <div class="cta-card cta-card-creator">
                    <h3 class="cta-card-title">Rejoignez l'univers RACINE BY GANDA</h3>
                    <p class="cta-card-text">
                        Vous êtes styliste, créateur, artisan ou porteur d'un projet&nbsp;? Rejoignez notre
                        écosystème et accédez à notre marketplace avec un accompagnement personnalisé.
                    </p>

                    <div class="cta-actions">
                        <a href="{{ route('creator.register') }}" class="cta-btn cta-btn-light">
                            Devenir créateur
                        </a>

                        <a href="mailto:partenaires@racinebyganda.com?subject=Candidature%20Créateur%20RACINE"
                           class="cta-btn cta-btn-ghost">
                            Envoyer une candidature
                        </a>
                    </div>

                    <p class="cta-note">
                        Chaque candidature est étudiée avec soin par notre équipe.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER PREMIUM RACINE BY GANDA --}}
    <footer class="footer-premium">
        
        {{-- MAIN FOOTER --}}
        <div class="footer-main">
            <div class="container">
                <div class="footer-grid">
                    {{-- Colonne 1: Brand --}}
                    <div class="footer-brand">
                        <div class="brand-logo">
                            <img src="{{ asset('images/logo-racine.png') }}" alt="RACINE BY GANDA" onerror="this.style.display='none'">
                            <span>RACINE BY GANDA</span>
                        </div>
                        <p class="brand-tagline">Mode Africaine Premium</p>
                        <p class="brand-description">
                            Des créations uniques qui célèbrent l'héritage africain avec une touche contemporaine. Chaque pièce raconte une histoire.
                        </p>
                        <div class="social-links">
                            <a href="#" class="social-link" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Pinterest">
                                <i class="fab fa-pinterest-p"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="TikTok">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        </div>
                    </div>
                    
                    {{-- Colonne 2: Boutique --}}
                    <div class="footer-links-col">
                        <h4>Boutique</h4>
                        <ul>
                            <li><a href="{{ route('frontend.shop') }}"><i class="fas fa-chevron-right"></i> Tous les produits</a></li>
                            <li><a href="{{ route('frontend.creators') }}"><i class="fas fa-chevron-right"></i> Nos créateurs</a></li>
                            <li><a href="{{ route('frontend.showroom') }}"><i class="fas fa-chevron-right"></i> Showroom virtuel</a></li>
                            <li><a href="{{ route('frontend.atelier') }}"><i class="fas fa-chevron-right"></i> L'Atelier</a></li>
                            <li><a href="{{ route('cart.index') }}"><i class="fas fa-chevron-right"></i> Mon panier</a></li>
                        </ul>
                    </div>
                    
                    {{-- Colonne 3: Découverte --}}
                    <div class="footer-links-col">
                        <h4>Découverte</h4>
                        <ul>
                            <li><a href="{{ route('frontend.portfolio') }}"><i class="fas fa-chevron-right"></i> Portfolio</a></li>
                            <li><a href="{{ route('frontend.albums') }}"><i class="fas fa-chevron-right"></i> Albums</a></li>
                            <li><a href="{{ route('frontend.events') }}"><i class="fas fa-chevron-right"></i> Événements</a></li>
                            <li><a href="{{ route('frontend.ceo') }}"><i class="fas fa-chevron-right"></i> {{ config('app.company.ceo') }}</a></li>
                        </ul>
                    </div>
                    
                    {{-- Colonne 4: Informations --}}
                    <div class="footer-links-col">
                        <h4>Informations</h4>
                        <ul>
                            @if(!empty($cmsFooterPages) && $cmsFooterPages->where('footer_column', 'info')->count())
                                @foreach($cmsFooterPages->where('footer_column', 'info') as $fp)
                                    <li>
                                        <a href="{{ route('frontend.page.show', $fp->slug) }}">
                                            <i class="fas fa-chevron-right"></i> {{ $fp->title }}
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li><a href="{{ route('frontend.about') }}"><i class="fas fa-chevron-right"></i> Notre histoire</a></li>
                                <li><a href="{{ route('frontend.contact') }}"><i class="fas fa-chevron-right"></i> Contact</a></li>
                                <li><a href="{{ route('frontend.shipping') }}"><i class="fas fa-chevron-right"></i> Livraison</a></li>
                                <li><a href="{{ route('frontend.returns') }}"><i class="fas fa-chevron-right"></i> Retours & Échanges</a></li>
                                <li><a href="{{ route('frontend.help') }}"><i class="fas fa-chevron-right"></i> FAQ & Aide</a></li>
                            @endif
                        </ul>
                    </div>
                    
                    {{-- Colonne 5: Légal --}}
                    <div class="footer-links-col">
                        <h4>Légal</h4>
                        <ul>
                            @if(!empty($cmsFooterPages) && $cmsFooterPages->count())
                                {{-- On peut filtrer par type ou juste afficher tout ici si approprié --}}
                                @foreach($cmsFooterPages->where('footer_column', 'legal') as $fp)
                                    <li><a href="{{ route('frontend.page.show', $fp->slug) }}"><i class="fas fa-chevron-right"></i> {{ $fp->title }}</a></li>
                                @endforeach
                            @else
                                <li><a href="{{ route('frontend.terms') }}"><i class="fas fa-chevron-right"></i> Conditions Générales</a></li>
                                <li><a href="{{ route('frontend.privacy') }}"><i class="fas fa-chevron-right"></i> Confidentialité</a></li>
                                <li><a href="{{ route('frontend.cookies') }}"><i class="fas fa-chevron-right"></i> Cookies</a></li>
                            @endif
                        </ul>
                    </div>
                    
                    {{-- Colonne 6: Contact --}}
                    <div class="footer-contact-col">
                        <h4>Contact</h4>
                        <div class="contact-items">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <span>République du Congo, Pointe-Noire</span>
                                    <span>Centre ville, Galerie NF</span>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <span>{{ config('app.company.phone') }}</span>
                                    <span>Lun-Sam: 9h-18h</span>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-text">
                                    <span>{{ config('app.company.email') }}</span>
                                    <span>support@racinebyganda.com</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- BOTTOM BAR --}}
        <div class="footer-bottom">
            <div class="container">
                <div class="bottom-content">
                    <div class="copyright">
                        <p>© {{ date('Y') }} <strong>RACINE BY GANDA</strong>. Tous droits réservés.</p>
                        <p class="dev-credit">
                            Développé par <a href="#" class="dev-link"><strong>NIKA DIGITAL HUB</strong></a> 
                            <span class="dev-separator">|</span> 
                            <span class="dev-desc">Solutions Web & Communication</span>
                            <span class="dev-flag">🇨🇬</span>
                            <span class="dev-country">République du Congo</span>
                        </p>
                    </div>
                    <div class="legal-links">
                        <a href="{{ route('frontend.terms') }}">CGV</a>
                        <span>•</span>
                        <a href="{{ route('frontend.privacy') }}">Confidentialité</a>
                        <span>•</span>
                        <a href="{{ route('frontend.cookies') }}">Cookies</a>
                    </div>
                    <div class="payment-methods">
                        <span>Paiement sécurisé</span>
                        <div class="payment-icons">
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                            <i class="fab fa-cc-paypal"></i>
                            <i class="fas fa-mobile-alt" title="Mobile Money"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    {{-- CSS inline supprimé - Déjà extrait vers layout-footer-cta.css et layout-navigation.css --}}
    
    {{-- Scripts --}}
    <script src="{{ asset('racine/js/jquery.min.js') }}"></script>
    <script src="{{ asset('racine/js/bootstrap.min.js') }}"></script>
    
    {{-- RACINE Navigation JavaScript (extrait du inline) --}}
    <script src="{{ asset('js/layout-navigation.js') }}"></script>
    
    {{-- RACINE Core JavaScript (AXE E - namespace Racine.*) --}}
    <script src="{{ asset('js/core/utilities.js') }}" defer></script>
    <script src="{{ asset('js/core/ajax.js') }}" defer></script>
    
    {{-- Page-specific JS (chargement conditionnel pour performance) --}}
    @if(request()->routeIs('frontend.shop'))
        <script src="{{ asset('js/frontend-shop.js') }}"></script>
    @endif
    @if(request()->routeIs('cart.index'))
        <script src="{{ asset('js/pages/cart.js') }}"></script>
    @endif
    @if(request()->routeIs('checkout.index'))
        <script src="{{ asset('js/pages/checkout.js') }}"></script>
    @endif
    @if(request()->routeIs('frontend.product') || request()->routeIs('product.show'))
        <script src="{{ asset('js/pages/product.js') }}"></script>
    @endif
    
    {{-- RACINE AJAX Spinner -- Désactivé --}}
    {{-- <script src="{{ asset('js/racine-ajax-spinner.js') }}"></script> --}}
    
    @stack('scripts')
    
    {{-- SPLASH SCREEN PREMIUM --}}
    {{-- Animation désactivée --}}
    {{-- @include('components.racine-logo-animation', ['variant' => 'splash', 'theme' => 'dark']) --}}
    
    {{-- LOADING ANIMATION (LEGACY - FALLBACK) --}}
    {{-- @include('components.loading-animation') --}}
    
    {{-- TOAST NOTIFICATIONS --}}
    @include('components.toast')
    
    {{-- CHATBOT AMIRA (VUE.JS) --}}
    <div id="amira-app">
        <amira-widget></amira-widget>
    </div>
    
    {{-- SCROLL TO TOP BUTTON --}}
    @include('components.scroll-to-top')
</body>
</html>
