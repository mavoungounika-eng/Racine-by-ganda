<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Créateur') - RACINE BY GANDA</title>
    
    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    
    {{-- RACINE Design System --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/creator-design-system.css') }}">
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* =============================================
           🎨 LAYOUT CRÉATEUR - RACINE BY GANDA
           ============================================= */
        
        body {
            background: linear-gradient(135deg, #f5f3f0 0%, #faf8f5 100%);
            font-family: var(--font-body);
            color: var(--racine-black);
            min-height: 100vh;
        }
        
        /* ===== SIDEBAR CRÉATEUR ===== */
        .creator-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-xl);
            border-right: 2px solid rgba(237, 95, 30, 0.2);
        }
        
        .creator-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        
        .creator-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .creator-sidebar::-webkit-scrollbar-thumb {
            background: rgba(237, 95, 30, 0.3);
            border-radius: 2px;
        }
        
        .creator-sidebar-header {
            padding: 1.5rem;
            background: rgba(237, 95, 30, 0.1);
            border-bottom: 1px solid rgba(237, 95, 30, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .creator-sidebar-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            box-shadow: var(--shadow-orange);
            flex-shrink: 0;
        }
        
        .creator-sidebar-brand {
            flex: 1;
            min-width: 0;
        }
        
        .creator-sidebar-brand-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--racine-orange);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .creator-sidebar-brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .creator-sidebar-nav {
            padding: 1rem 0;
        }
        
        .creator-sidebar-section {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .creator-sidebar-section:first-child {
            margin-top: 0;
        }
        
        .creator-sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition-fast);
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .creator-sidebar-link:hover {
            background: rgba(237, 95, 30, 0.1);
            color: var(--racine-orange);
            border-left-color: var(--racine-orange);
            text-decoration: none;
        }
        
        .creator-sidebar-link.active {
            background: rgba(237, 95, 30, 0.2);
            color: var(--racine-orange);
            border-left-color: var(--racine-orange);
            font-weight: 600;
        }
        
        .creator-sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }
        
        .creator-sidebar-link.new-product {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            margin: 0.5rem 1.5rem;
            border-radius: var(--radius-md);
            border-left: none;
        }
        
        .creator-sidebar-link.new-product:hover {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .creator-sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(237, 95, 30, 0.2);
            margin-top: auto;
        }
        
        .creator-sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .creator-sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }
        
        .creator-sidebar-user-info {
            flex: 1;
            min-width: 0;
        }
        
        .creator-sidebar-user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .creator-sidebar-user-email {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .creator-sidebar-logout {
            width: 100%;
            padding: 0.625rem 1rem;
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: var(--radius-md);
            color: #e74c3c;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            transition: var(--transition-fast);
            cursor: pointer;
        }
        
        .creator-sidebar-logout:hover {
            background: rgba(231, 76, 60, 0.2);
            border-color: #e74c3c;
            color: #e74c3c;
            text-decoration: none;
        }
        
        /* ===== MAIN CONTENT AREA ===== */
        .creator-main-wrapper {
            margin-left: 280px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f3f0 0%, #faf8f5 100%);
        }
        
        .creator-header {
            background: linear-gradient(135deg, rgba(22, 13, 12, 0.98) 0%, rgba(42, 26, 24, 0.95) 100%);
            backdrop-filter: blur(10px);
            padding: 1.25rem 2rem;
            border-bottom: 2px solid rgba(237, 95, 30, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }
        
        .creator-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .creator-header-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            font-family: var(--font-heading);
            margin: 0;
        }
        
        .creator-header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .creator-notification-btn {
            position: relative;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
            text-decoration: none;
        }
        
        .creator-notification-btn:hover {
            background: rgba(237, 95, 30, 0.2);
            border-color: var(--racine-orange);
            color: var(--racine-orange);
            text-decoration: none;
        }
        
        .creator-notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(237, 95, 30, 0.5);
        }
        
        .creator-content {
            padding: 2rem;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .creator-sidebar {
                transform: translateX(-100%);
            }
            
            .creator-sidebar.show {
                transform: translateX(0);
            }
            
            .creator-main-wrapper {
                margin-left: 0;
            }
            
            .creator-content {
                padding: 1rem;
            }
        }
        
        /* ===== BREADCRUMB NAVIGATION ===== */
        .creator-breadcrumb {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .creator-breadcrumb a {
            color: var(--racine-orange);
            text-decoration: none;
            transition: var(--transition-fast);
        }
        
        .creator-breadcrumb a:hover {
            color: var(--racine-orange-dark);
            text-decoration: underline;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    {{-- SIDEBAR CRÉATEUR --}}
    <aside class="creator-sidebar">
        {{-- Header --}}
        <div class="creator-sidebar-header">
            <div class="creator-sidebar-avatar">
                {{ strtoupper(substr(optional(Auth::user()->creatorProfile)->brand_name ?? Auth::user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="creator-sidebar-brand">
                <div class="creator-sidebar-brand-label">Espace Créateur</div>
                <div class="creator-sidebar-brand-name">
                    {{ optional(Auth::user()->creatorProfile)->brand_name ?? Auth::user()->name ?? 'Ma Boutique' }}
                </div>
            </div>
        </div>
        
        {{-- Navigation --}}
        <nav class="creator-sidebar-nav">
            {{-- Section Tableau de bord --}}
            <div class="creator-sidebar-section">Tableau de bord</div>
            <a href="{{ route('creator.dashboard') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Tableau de bord</span>
            </a>
            
            {{-- Section Mon Profil (NOUVEAU) --}}
            <div class="creator-sidebar-section">Mon Profil</div>
            <a href="{{ route('creator.profile.show') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.profile.*') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i>
                <span>Mon Profil</span>
            </a>
            
            {{-- Section Mes Produits --}}
            <div class="creator-sidebar-section">Mes Produits</div>
            <a href="{{ route('creator.products.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.products.*') && !request()->routeIs('creator.products.create') ? 'active' : '' }}">
                <i class="fas fa-box"></i>
                <span>Mes produits</span>
            </a>
            <a href="{{ route('creator.products.create') }}" 
               class="creator-sidebar-link new-product {{ request()->routeIs('creator.products.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle"></i>
                <span>Nouveau produit</span>
            </a>
            
            {{-- Section Gestion Commandes --}}
            <div class="creator-sidebar-section">Gestion Commandes</div>
            <a href="{{ route('creator.orders.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.orders.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-bag"></i>
                <span>Commandes</span>
            </a>
            <a href="{{ route('creator.messages.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.messages.*') ? 'active' : '' }}">
                <i class="fas fa-comment-alt"></i>
                <span>Messages Clients</span>
                @php
                    // Calcul temporaire si le service n'est pas injecté globalement
                    $msgService = app(\App\Services\ConversationService::class);
                    $unreadMsg = $msgService->getUnreadConversationsCount(Auth::id());
                @endphp
                @if($unreadMsg > 0)
                    <span class="creator-notification-badge" style="margin-left: auto;">{{ $unreadMsg }}</span>
                @endif
            </a>

            {{-- Section Finances --}}
            <div class="creator-sidebar-section">Finances</div>
            <a href="{{ route('creator.finances.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.finances.*') ? 'active' : '' }}">
                <i class="fas fa-coins"></i>
                <span>Revenus & Ventes</span>
            </a>
            
            {{-- Section Données --}}
            <div class="creator-sidebar-section">Données +</div>
            <a href="{{ route('creator.analytics.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.analytics.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
            <a href="{{ route('creator.stats.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.stats.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            
            {{-- Section Paramètres (NOUVEAU) --}}
            <div class="creator-sidebar-section">Paramètres</div>
            <a href="{{ route('creator.settings.payment') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.settings.payment') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i>
                <span>Paiements & Stripe</span>
            </a>
            <a href="{{ route('creator.subscription.plans') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.subscription.*') ? 'active' : '' }}">
                <i class="fas fa-crown"></i>
                <span>Abonnement</span>
            </a>
            <a href="{{ route('creator.notifications.index') }}" 
               class="creator-sidebar-link {{ request()->routeIs('creator.notifications.*') ? 'active' : '' }}">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                @php
                    $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();
                @endphp
                @if($unreadCount > 0)
                    <span class="creator-notification-badge" style="position: relative; top: 0; right: 0; margin-left: auto;">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </a>
        </nav>
        
        {{-- Footer --}}
        <div class="creator-sidebar-footer">
            <div class="creator-sidebar-user">
                <div class="creator-sidebar-user-avatar">
                    {{ strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)) }}
                </div>
                <div class="creator-sidebar-user-info">
                    <div class="creator-sidebar-user-name">{{ Auth::user()->name ?? 'Créateur' }}</div>
                    <div class="creator-sidebar-user-email">{{ Auth::user()->email ?? '' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('creator.logout') }}">
                @csrf
                <button type="submit" class="creator-sidebar-logout">
                    <i class="fas fa-sign-out-alt"></i> Se déconnecter
                </button>
            </form>
        </div>
    </aside>
    
    {{-- MAIN CONTENT WRAPPER --}}
    <div class="creator-main-wrapper">
        {{-- Header --}}
        <header class="creator-header">
            <div class="creator-header-content">
                <h1 class="creator-header-title">@yield('page-title', 'Tableau de bord')</h1>
                <div class="creator-header-actions">
                    @php
                        $unreadNotificationsCount = \App\Models\Notification::where('user_id', Auth::id())
                            ->where('is_read', false)
                            ->count();
                    @endphp
                    <a href="{{ route('creator.notifications.index') }}" class="creator-notification-btn">
                        <i class="fas fa-bell"></i>
                        @if($unreadNotificationsCount > 0)
                            <span class="creator-notification-badge">
                                {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('frontend.home') }}" 
                       class="btn btn-sm" 
                       style="background: var(--racine-orange); color: white; border: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); text-decoration: none;">
                        <i class="fas fa-home"></i> Voir le site
                    </a>
                </div>
            </div>
        </header>
        
        {{-- Content --}}
        <main class="creator-content">
            @yield('content')
        </main>
    </div>
    
    {{-- Bootstrap JS --}}
    <script src="{{ asset('racine/js/jquery.min.js') }}"></script>
    <script src="{{ asset('racine/js/bootstrap.min.js') }}"></script>
    
    {{-- Scroll to Top Component --}}
    @include('components.scroll-to-top')
    
    @stack('scripts')
</body>
</html>