<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tableau de Bord Admin - ' . config('app.company.name'))</title>

    {{-- Fonts RACINE (Cormorant Garamond → Aleppo, Nunito → Coco Gothic, Aileron) --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&family=Cormorant+Garamond:wght@400;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    {{-- Bootstrap 5 chargé via Vite (app.scss) — Bootstrap 4 legacy supprimé --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-enhanced.css') }}">
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

        body {
            font-family: var(--racine-font-body);
            background: var(--racine-white);
            color: var(--racine-black);
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: var(--racine-white);
        }

        .admin-sidebar {
            width: 260px;
            background: var(--racine-black);
            color: var(--racine-white);
            display: flex;
            flex-direction: column;
        }

        .admin-sidebar-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-sidebar-title {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.78;
            margin-bottom: 0.25rem;
            font-family: var(--racine-font-accent);
        }

        .admin-sidebar-subtitle {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--racine-yellow);
            font-family: var(--racine-font-heading);
        }

        .admin-sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem 1rem;
        }

        .admin-nav-section-title {
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.62;
            padding: 0.75rem 1rem 0.25rem;
            font-family: var(--racine-font-accent);
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            margin: 0.25rem 0.5rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            font-family: var(--racine-font-accent);
        }

        .admin-nav-link i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
        }

        .admin-nav-link:hover {
            background: rgba(255, 184, 0, 0.16);
            color: var(--racine-yellow);
            transform: translateX(2px);
        }

        .admin-nav-link.active {
            background: linear-gradient(135deg, var(--racine-orange), var(--racine-yellow));
            color: var(--racine-black);
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(22, 13, 12, 0.34);
        }

        .admin-nav-link.active i {
            color: var(--racine-black);
        }

        .admin-sidebar-footer {
            padding: 0.75rem 1.5rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            font-family: var(--racine-font-accent);
        }

        .admin-user-email {
            font-size: 0.8rem;
            opacity: 0.78;
        }

        .admin-logout-btn {
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 184, 0, 0.18);
            color: var(--racine-white);
            border: 1px solid rgba(255, 184, 0, 0.4);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .admin-logout-btn:hover {
            background: var(--racine-orange);
            border-color: var(--racine-orange);
            color: var(--racine-white);
        }

        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-orange) 100%);
            color: var(--racine-white);
            padding: 0.85rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(22, 13, 12, 0.35);
        }

        .admin-topbar-left h1 {
            font-size: 1.1rem;
            margin: 0;
            color: var(--racine-white);
            font-family: var(--racine-font-heading);
        }

        .admin-topbar-left span {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.84);
            font-family: var(--racine-font-accent);
        }

        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-badge-env {
            background: rgba(22, 13, 12, 0.45);
            border-radius: 999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--racine-yellow);
            font-family: var(--racine-font-accent);
        }

        .admin-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.32);
            background: rgba(22, 13, 12, 0.26);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--racine-white);
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .admin-icon-btn:hover {
            background: var(--racine-yellow);
            border-color: var(--racine-yellow);
            color: var(--racine-black);
        }

        .admin-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            background: rgba(22, 13, 12, 0.35);
            font-size: 0.8rem;
            font-family: var(--racine-font-accent);
        }

        .admin-user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--racine-orange), var(--racine-yellow));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--racine-black);
        }

        .admin-content-wrapper {
            padding: 1.5rem 1.75rem 2rem;
        }

        .admin-breadcrumb {
            font-size: 0.8rem;
            color: rgba(22, 13, 12, 0.7);
            margin-bottom: 0.75rem;
            font-family: var(--racine-font-accent);
        }

        .admin-breadcrumb a {
            color: rgba(22, 13, 12, 0.7);
            text-decoration: none;
        }

        .admin-breadcrumb a:hover {
            color: var(--racine-orange);
        }

        .card-racine {
            background: var(--racine-white);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(22, 13, 12, 0.08);
            border: 1px solid rgba(255, 184, 0, 0.24);
            transition: all 0.3s ease;
        }

        .card-racine:hover {
            box-shadow: 0 8px 32px rgba(22, 13, 12, 0.12);
            transform: translateY(-2px);
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid rgba(22, 13, 12, 0.08);
            padding: 0.875rem 1rem;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item:hover {
            background: rgba(255, 184, 0, 0.12);
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                display: none;
            }

            .admin-layout {
                flex-direction: column;
            }

            .admin-main {
                width: 100%;
            }
        }
    </style>

    {{-- Vite assets --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>

<div class="admin-layout">
    {{-- SIDEBAR ADMIN --}}
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-sidebar-title">Espace Admin</div>
            <div class="admin-sidebar-subtitle">
                {{ config('app.company.name') }}
            </div>
        </div>

        <nav class="admin-sidebar-nav">
            <div class="admin-nav-section-title">Tableau de bord</div>
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>

            <div class="admin-nav-section-title">Catalogue</div>
            <a href="{{ route('admin.products.index') }}" class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i>
                <span>Produits</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i>
                <span>Catégories</span>
            </a>
            <a href="{{ route('admin.creators.index') }}" class="admin-nav-link {{ request()->routeIs('admin.creators.*') ? 'active' : '' }}">
                <i class="fas fa-user-ninja"></i>
                <span>Vendeurs Partenaires</span>
            </a>

            <div class="admin-nav-section-title">Ventes</div>
            <a href="{{ route('admin.orders.index') }}" class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="fas fa-receipt"></i>
                <span>Commandes</span>
            </a>
            @can('payments.view')
            <a href="{{ route('admin.payments.index') }}" class="admin-nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i>
                <span>Paiements</span>
            </a>
            @endcan
            <a href="{{ route('admin.finances.index') }}" class="admin-nav-link {{ request()->routeIs('admin.finances.*') ? 'active' : '' }}">
                <i class="fas fa-coins"></i>
                <span>Finances</span>
            </a>
            <a href="{{ route('admin.stats.index') }}" class="admin-nav-link {{ request()->routeIs('admin.stats.*') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Statistiques</span>
            </a>

            <div class="admin-nav-section-title">Analyse & Reporting</div>
            <a href="{{ route('admin.analytics.index') }}" class="admin-nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <span>Dashboard Analytics</span>
            </a>
            <a href="{{ route('admin.analytics.funnel') }}" class="admin-nav-link {{ request()->routeIs('admin.analytics.funnel') ? 'active' : '' }}">
                <i class="fas fa-funnel-dollar"></i>
                <span>Funnel d'achat</span>
            </a>
            <a href="{{ route('admin.analytics.sales') }}" class="admin-nav-link {{ request()->routeIs('admin.analytics.sales') ? 'active' : '' }}">
                <i class="fas fa-dollar-sign"></i>
                <span>Ventes & CA</span>
            </a>

            <div class="admin-nav-section-title">Outils Internes</div>
            <a href="{{ route('erp.dashboard') }}" class="admin-nav-link {{ request()->routeIs('erp.*') ? 'active' : '' }}">
                <i class="fas fa-warehouse"></i>
                <span>ERP</span>
            </a>
            <a href="{{ route('crm.dashboard') }}" class="admin-nav-link {{ request()->routeIs('crm.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i>
                <span>CRM</span>
            </a>

            <div class="admin-nav-section-title">Outils</div>
            <a href="{{ route('pos.interface.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}">
                <i class="fas fa-cash-register"></i>
                <span>Point de Vente (POS)</span>
            </a>
            <a href="{{ route('messages.index') }}" class="admin-nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <i class="fas fa-comments"></i>
                <span>Messagerie</span>
                @php
                    $unreadCount = app(\App\Services\ConversationService::class)->getUnreadConversationsCount(auth()->id());
                @endphp
                @if($unreadCount > 0)
                    <span class="badge bg-primary ms-auto" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile.edit') }}" class="admin-nav-link {{ request()->routeIs('profile.*') && !request()->routeIs('messages.*') ? 'active' : '' }}">
                <i class="fas fa-user-edit"></i>
                <span>Mon profil</span>
            </a>
            @can('access-staff-tools')
            <a href="{{ route('admin.orders.scan') }}" class="admin-nav-link {{ request()->routeIs('admin.orders.scan') ? 'active' : '' }}">
                <i class="fas fa-qrcode"></i>
                <span>Scanner QR</span>
            </a>
            @endcan
            <a href="{{ route('frontend.home') }}" class="admin-nav-link">
                <i class="fas fa-home"></i>
                <span>Voir le site</span>
            </a>

            <div class="admin-nav-section-title">Système</div>
            <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Utilisateurs</span>
            </a>
            @can('access-system-config')
            <a href="{{ route('cms.admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('cms.admin.*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>CMS</span>
            </a>
            @endcan
            @can('access-system-config')
            <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i>
                <span>Paramètres</span>
            </a>
            @endcan
        </nav>

        <div class="admin-sidebar-footer">
            <div><strong>Admin</strong></div>
            <div class="admin-user-email">{{ auth()->user()->email ?? 'admin@racinebyganda.com' }}</div>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">
                @csrf
                <button type="submit" class="admin-logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="admin-main">
        {{-- TOPBAR --}}
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <h1>@yield('page-title', 'Tableau de bord')</h1>
                <span>@yield('page-subtitle', "Vue d'ensemble de l'activité " . config('app.company.name'))</span>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-badge-env">
                    <i class="fas fa-server"></i>
                    <span>{{ app()->environment('production') ? 'Production' : 'Environnement de test' }}</span>
                </div>
                <a href="{{ url('/') }}" class="admin-icon-btn" title="Voir le site">
                    <i class="fas fa-globe"></i>
                </a>
                <a href="{{ route('admin.notifications.index') }}" class="admin-icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                </a>
                <div class="admin-user-chip">
                    <div class="admin-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <span>{{ auth()->user()->name ?? 'Administrateur' }}</span>
                </div>
            </div>
        </header>

        {{-- CONTENU --}}
        <div class="admin-content-wrapper">
            <div class="admin-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                @hasSection('breadcrumb')
                    &nbsp;/&nbsp; @yield('breadcrumb')
                @endif
            </div>

            @yield('content')
        </div>
    </main>
</div>

{{-- Bootstrap 5 + JS chargés via Vite (app.js) — jQuery/Bootstrap 4 legacy supprimés --}}

@stack('scripts')
</body>
</html>
