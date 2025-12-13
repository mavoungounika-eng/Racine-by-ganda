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
    <meta property="og:site_name" content="RACINE BY GANDA">
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
    
    {{-- Fonts --}}
    {{-- Typographies Officielles RACINE --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    
    {{-- RACINE Design System --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    
    {{-- RACINE Layout CSS (extrait du inline pour optimisation) --}}
    <link rel="stylesheet" href="{{ asset('css/layout-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-footer-cta.css') }}">
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles inline minimaux si nécessaire - La majorité du CSS a été extraite vers fichiers externes */
        /* Tous les styles sont maintenant dans :
         * - layout-navigation.css (navigation, navbar, dropdowns)
         * - layout-components.css (hero, product cards, buttons)
         * - layout-footer-cta.css (footer, CTA section)
         */
    </style>
    
    @stack('styles')
</head>
<body>
    {{-- HEADER PREMIUM RACINE BY GANDA - VERSION ÉPURÉE --}}
    <header role="banner" class="position-fixed w-100 top-0 shadow-lg" style="background: linear-gradient(135deg, #1c1412 0%, #261915 100%); z-index: 1050; border-bottom: 2px solid rgba(237, 95, 30, 0.2);">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between" style="height: 70px;">
                
                {{-- LOGO + NOM (Extrémité gauche) avec animation hover --}}
                <a href="{{ route('frontend.home') }}" class="d-flex align-items-center logo-navbar-wrapper" style="gap: 0.75rem; text-decoration: none; transition: all 0.3s; position: relative;">
                    <div class="d-flex align-items-center justify-content-center overflow-hidden logo-navbar-container" style="height: 42px; width: 42px; border-radius: 10px; background: rgba(0, 0, 0, 0.7); border: 1px solid #ED5F1E; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); position: relative;">
                        <img src="{{ asset('images/logo-racine.png') }}" alt="Logo RACINE BY GANDA" class="logo-navbar-img" style="height: 32px; width: 32px; object-fit: contain; position: relative; z-index: 2;">
                        {{-- Animation hover --}}
                                {{-- Animation hover désactivée --}}
                                {{-- @include('components.racine-logo-animation', ['variant' => 'hover', 'theme' => 'dark']) --}}
                    </div>
                    <p class="mb-0 d-none d-md-block" style="font-size: 1rem; letter-spacing: 0.18em; font-weight: 700; color: #ED5F1E; transition: color 0.3s;">
                        RACINE BY GANDA
                    </p>
                </a>
                
                {{-- MENU DESKTOP ÉPURÉ --}}
                <nav role="navigation" aria-label="Navigation principale" class="d-none d-lg-flex align-items-center" style="gap: 2rem; font-size: 0.9rem; font-weight: 500;">
                    <a href="{{ route('frontend.home') }}" class="nav-link-racine">Accueil</a>
                    <a href="{{ route('frontend.atelier') }}" class="nav-link-racine">Atelier</a>
                    
                    {{-- Dropdown Boutique --}}
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-toggle nav-link-racine" aria-label="Menu boutique" aria-expanded="false" aria-haspopup="true">
                            Boutique <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            <a href="{{ route('frontend.shop') }}"><i class="fas fa-store"></i> Boutique RACINE BY GANDA</a>
                            <a href="{{ route('frontend.creators') }}"><i class="fas fa-palette"></i> Boutique Créateurs</a>
                        </div>
                    </div>
                    
                    <a href="{{ route('frontend.showroom') }}" class="nav-link-racine">Showroom</a>
                    
                    {{-- Panier --}}
                    <a href="{{ route('cart.index') }}" class="nav-link-racine d-flex align-items-center position-relative" style="gap: 0.5rem;" aria-label="Voir le panier">
                        <i class="fas fa-shopping-bag" aria-hidden="true"></i>
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
                    
                    {{-- Bouton Connexion --}}
                    <a href="{{ route('auth.hub') }}" class="nav-icon-btn nav-icon-btn-primary" title="Connexion" aria-label="Se connecter ou créer un compte">
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </a>
                    
                    {{-- Burger menu mobile --}}
                    <button id="mobile-menu-toggle" class="d-lg-none btn btn-link text-white p-0" style="font-size: 1.75rem; border: none; background: none;" aria-label="Ouvrir le menu mobile" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            {{-- MENU MOBILE --}}
            <div id="mobile-menu" class="d-lg-none pb-4" style="background: #1c1412; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                <div class="d-flex flex-column" style="gap: 0.5rem;">
                    <a href="{{ route('frontend.home') }}" class="text-white py-2" style="text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Accueil</a>
                    <a href="{{ route('frontend.atelier') }}" class="text-white py-2" style="text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Atelier</a>
                    
                    {{-- Boutique section --}}
                    <div class="py-2" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <p class="text-white mb-2" style="font-weight: 600; font-size: 0.9rem;">Boutique</p>
                        <a href="{{ route('frontend.shop') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">→ Boutique RACINE BY GANDA</a>
                        <a href="{{ route('frontend.creators') }}" class="text-white-50 d-block pl-3 py-1" style="text-decoration: none; font-size: 0.85rem;">→ Boutique Créateurs</a>
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
                    
                    <a href="{{ route('auth.hub') }}" class="text-white d-flex align-items-center py-2" style="gap: 0.5rem; text-decoration: none;">
                        <span style="font-size: 1.25rem;">👤</span> Connexion
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    {{-- Spacer pour compenser le header fixed --}}
    <div style="height: 70px;"></div>
    
    {{-- CONTENT --}}
    <main role="main">
    {{-- Messages flash globaux --}}
    @if(session('success'))
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid #28a745; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-check-circle mr-2" style="color: #28a745;"></i>
                <strong>{{ session('success') }}</strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mt-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid #dc3545; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-exclamation-circle mr-2" style="color: #dc3545;"></i>
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
                            <li><a href="{{ route('frontend.ceo') }}"><i class="fas fa-chevron-right"></i> Amira Ganda</a></li>
                            <li><a href="{{ route('frontend.brand-guidelines') }}"><i class="fas fa-chevron-right"></i> Charte Graphique</a></li>
                        </ul>
                    </div>
                    
                    {{-- Colonne 4: Informations --}}
                    <div class="footer-links-col">
                        <h4>Informations</h4>
                        <ul>
                            <li><a href="{{ route('frontend.about') }}"><i class="fas fa-chevron-right"></i> Notre histoire</a></li>
                            <li><a href="{{ route('frontend.contact') }}"><i class="fas fa-chevron-right"></i> Contact</a></li>
                            <li><a href="{{ route('frontend.shipping') }}"><i class="fas fa-chevron-right"></i> Livraison</a></li>
                            <li><a href="{{ route('frontend.returns') }}"><i class="fas fa-chevron-right"></i> Retours & Échanges</a></li>
                            <li><a href="{{ route('frontend.help') }}"><i class="fas fa-chevron-right"></i> FAQ & Aide</a></li>
                        </ul>
                    </div>
                    
                    {{-- Colonne 5: Légal --}}
                    <div class="footer-links-col">
                        <h4>Légal</h4>
                        <ul>
                            <li><a href="{{ route('frontend.terms') }}"><i class="fas fa-chevron-right"></i> Conditions Générales</a></li>
                            <li><a href="{{ route('frontend.privacy') }}"><i class="fas fa-chevron-right"></i> Confidentialité</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right"></i> Cookies</a></li>
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
                                    <span>+237 6XX XXX XXX</span>
                                    <span>Lun-Sam: 9h-18h</span>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-text">
                                    <span>contact@racine.cm</span>
                                    <span>support@racine.cm</span>
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
                        <a href="#">Cookies</a>
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
    
    {{-- CHATBOT AMIRA --}}
    @include('assistant::chat')
    
    {{-- SCROLL TO TOP BUTTON --}}
    @include('components.scroll-to-top')
</body>
</html>
