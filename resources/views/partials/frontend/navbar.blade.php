<!-- TOP BAR NOIRE -->
<div class="py-1 bg-black">
  <div class="container">
    <div class="row no-gutters d-flex align-items-start align-items-center px-md-0">
      <div class="col-lg-12 d-block">
        <div class="row d-flex">
          <div class="col-md pr-4 d-flex topper align-items-center">
            <div class="icon mr-2 d-flex justify-content-center align-items-center">
              <span class="icon-phone2"></span>
            </div>
            <span class="text">+242 06 6XX XX XX</span>
          </div>
          <div class="col-md pr-4 d-flex topper align-items-center">
            <div class="icon mr-2 d-flex justify-content-center align-items-center">
              <span class="icon-paper-plane"></span>
            </div>
            <span class="text">contact@racinebyganda.com</span>
          </div>
          <div class="col-md-5 pr-4 d-flex topper align-items-center text-lg-right">
            <span class="text">Livraison gratuite à Pointe-Noire &amp; Retours gratuits</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- NAVBAR PRINCIPALE -->
<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
  <div class="container">
    <a class="navbar-brand" href="{{ route('frontend.home') }}">
      <img src="{{ asset('racine/images/logoo.png') }}" alt="RACINE BY GANDA Logo">
      <span class="navbar-brand-text">RACINE BY GANDA</span>
    </a>
    <button class="navbar-toggler" type="button"
      data-toggle="collapse" data-target="#ftco-nav"
      aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="oi oi-menu"></span> Menu
    </button>

    <div class="collapse navbar-collapse" id="ftco-nav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item {{ request()->routeIs('frontend.home') ? 'active' : '' }}">
          <a href="{{ route('frontend.home') }}" class="nav-link">Accueil</a>
        </li>
        <li class="nav-item {{ request()->routeIs('frontend.shop') ? 'active' : '' }}">
          <a href="{{ route('frontend.shop') }}" class="nav-link">Boutique</a>
        </li>
        <li class="nav-item {{ request()->routeIs('frontend.showroom') ? 'active' : '' }}">
          <a href="{{ route('frontend.showroom') }}" class="nav-link">Showroom</a>
        </li>
        <li class="nav-item {{ request()->routeIs('frontend.atelier') ? 'active' : '' }}">
          <a href="{{ route('frontend.atelier') }}" class="nav-link">Atelier</a>
        </li>
        <li class="nav-item {{ request()->routeIs('frontend.contact') ? 'active' : '' }}">
          <a href="{{ route('frontend.contact') }}" class="nav-link">Contact</a>
        </li>

        <!-- COMPTE UTILISATEUR -->
        @auth
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" 
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="icon-user"></span> Mon espace
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
              @php
                $user = Auth::user();
                $user->load('roleRelation');
                $roleSlug = $user->getRoleSlug() ?? 'client';
                
                $dashboardRoutes = [
                  'super_admin' => 'admin.dashboard',
                  'admin' => 'admin.dashboard',
                  'staff' => 'staff.dashboard',
                  'createur' => 'creator.dashboard',
                  'creator' => 'creator.dashboard',
                  'client' => 'account.dashboard',
                ];
                $dashboardRoute = $dashboardRoutes[$roleSlug] ?? 'account.dashboard';
              @endphp
              <a class="dropdown-item" href="{{ route($dashboardRoute) }}">
                <span class="icon-dashboard"></span> Tableau de bord
              </a>
              <div class="dropdown-divider"></div>
              <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                  <span class="icon-sign-out"></span> Déconnexion
                </button>
              </form>
            </div>
          </li>
        @else
          <li class="nav-item">
            <a href="{{ route('login') }}" class="nav-link">
              <span class="icon-user"></span> Mon compte
            </a>
          </li>
        @endauth

        <!-- SÉLECTEUR DE LANGUE -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" 
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="icon-globe"></span> {{ strtoupper(app()->getLocale()) }}
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageDropdown">
            <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" 
               href="{{ route('language.switch', 'fr') }}">
              🇫🇷 Français
            </a>
            <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" 
               href="{{ route('language.switch', 'en') }}">
              🇬🇧 English
            </a>
          </div>
        </li>

        <!-- PANIER -->
        <li class="nav-item cta cta-colored">
          <a href="{{ route('cart.index') }}" class="nav-link position-relative">
            <span class="icon-shopping_cart"></span>
            @if(isset($cartCount) && $cartCount > 0)
              <span class="badge badge-danger position-absolute" 
                    style="top: -8px; right: -8px; font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 50%; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;"
                    id="cart-count-badge">{{ $cartCount }}</span>
            @endif
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
