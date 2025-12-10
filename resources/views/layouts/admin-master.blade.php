<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - RACINE BY GANDA</title>

    {{-- Fonts & Icons --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- TinyMCE --}}
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        body {
            font-family: 'Aileron', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #F5F2EC;
            color: #160D0C;
        }

        /* ===== LAYOUT ADMIN WRAPPER ===== */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #F5F2EC;
        }

        /* ===== SIDEBAR ===== */
        .admin-sidebar {
            width: 260px;
            background: #160D0C;
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .admin-sidebar-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .admin-sidebar-title {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }

        .admin-sidebar-subtitle {
            font-size: 0.85rem;
            font-weight: 600;
            color: #FFB800;
        }

        .admin-sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem 1rem;
            overflow-y: auto;
        }

        .admin-nav-section-title {
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.5;
            padding: 0.75rem 1rem 0.25rem;
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            margin: 0.25rem 0.5rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .admin-nav-link i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
        }

        .admin-nav-link:hover {
            background: rgba(237, 95, 30, 0.2);
            color: #FFB800;
            transform: translateX(2px);
        }

        .admin-nav-link.active {
            background: linear-gradient(135deg, #ED5F1E, #FFB800);
            color: #160D0C;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        }

        .admin-sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.85rem;
        }

        .admin-sidebar-footer strong {
            color: #FFB800;
        }

        .admin-user-email {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .admin-logout-btn {
            margin-top: 0.75rem;
            width: 100%;
            padding: 0.5rem;
            background: rgba(237, 95, 30, 0.2);
            border: 1px solid rgba(237, 95, 30, 0.3);
            border-radius: 8px;
            color: #FFB800;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .admin-logout-btn:hover {
            background: rgba(237, 95, 30, 0.3);
            border-color: #ED5F1E;
        }

        /* MAIN */
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* TOPBAR */
        .admin-topbar {
            background: #FFFFFF;
            border-bottom: 2px solid #ED5F1E;
            padding: 1rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .admin-topbar-left h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #160D0C;
            margin: 0;
        }

        .admin-topbar-left span {
            font-size: 0.85rem;
            color: #8B7355;
            display: block;
            margin-top: 0.25rem;
        }

        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #F5F2EC;
            border: 1px solid #E5DDD3;
            color: #160D0C;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .admin-icon-btn:hover {
            background: rgba(237, 95, 30, 0.9);
            border-color: #ED5F1E;
            color: white;
        }

        .admin-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            background: rgba(0,0,0,0.05);
            font-size: 0.8rem;
        }

        .admin-user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ED5F1E, #FFB800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            color: #160D0C;
        }

        /* CONTENT WRAPPER */
        .admin-content-wrapper {
            padding: 1.5rem 1.75rem 2rem;
            flex: 1;
            overflow-y: auto;
        }

        .admin-breadcrumb {
            font-size: 0.8rem;
            color: #8B7355;
            margin-bottom: 0.75rem;
        }

        .admin-breadcrumb a {
            color: #8B7355;
            text-decoration: none;
        }

        .admin-breadcrumb a:hover {
            color: #ED5F1E;
        }

        /* RESPONSIVE */
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

    @stack('styles')
</head>
<body>

<div class="admin-layout">
    {{-- SIDEBAR ADMIN --}}
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-sidebar-title">Administration</div>
            <div class="admin-sidebar-subtitle">
                RACINE BY GANDA
            </div>
        </div>

        <nav class="admin-sidebar-nav">
            <div class="admin-nav-section-title">Tableau de bord</div>
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <div class="admin-nav-section-title">Gestion</div>
            <a href="{{ route('cms.admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('cms.admin.*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>CMS</span>
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
            <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Utilisateurs</span>
            </a>
            <a href="{{ route('admin.roles.index') }}" class="admin-nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="fas fa-user-tag"></i>
                <span>Rôles</span>
            </a>

            <div class="admin-nav-section-title">E-commerce</div>
            <a href="{{ route('admin.categories.index') }}" class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="fas fa-folder"></i>
                <span>Catégories</span>
            </a>
            <a href="{{ route('admin.products.index') }}" class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i>
                <span>Produits</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-cart"></i>
                <span>Commandes</span>
            </a>
            <a href="{{ route('admin.stock-alerts.index') }}" class="admin-nav-link {{ request()->routeIs('admin.stock-alerts.*') ? 'active' : '' }}">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Alertes stock</span>
            </a>

            <div class="admin-nav-section-title">Modules Business</div>
            <a href="{{ route('erp.dashboard') }}" class="admin-nav-link {{ request()->routeIs('erp.*') ? 'active' : '' }}">
                <i class="fas fa-warehouse"></i>
                <span>ERP</span>
            </a>
            <a href="{{ route('crm.dashboard') }}" class="admin-nav-link {{ request()->routeIs('crm.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i>
                <span>CRM</span>
            </a>

            <div class="admin-nav-section-title">Boutique</div>
            <a href="{{ route('admin.pos.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}">
                <i class="fas fa-cash-register"></i>
                <span>Point de Vente (POS)</span>
            </a>
            <a href="{{ route('admin.orders.scan') }}" class="admin-nav-link {{ request()->routeIs('admin.orders.scan') ? 'active' : '' }}">
                <i class="fas fa-qrcode"></i>
                <span>Scanner QR</span>
            </a>

            <div class="admin-nav-section-title">Outils</div>
            <a href="{{ route('frontend.home') }}" class="admin-nav-link">
                <i class="fas fa-home"></i>
                <span>Voir le site</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div><strong>{{ auth()->user()->name ?? 'Admin' }}</strong></div>
            <div class="admin-user-email">{{ auth()->user()->email ?? 'admin@racine.cm' }}</div>
            <form action="{{ route('admin.logout') }}" method="POST" class="mt-1">
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
                <h1>@yield('page-title', 'Dashboard')</h1>
                <span>@yield('page-subtitle', "Administration RACINE BY GANDA")</span>
            </div>
            <div class="admin-topbar-right">
                <a href="{{ route('admin.notifications.index') }}" class="admin-icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="{{ route('frontend.home') }}" class="admin-icon-btn" title="Voir le site">
                    <i class="fas fa-globe"></i>
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
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

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

<script src="{{ asset('racine/js/jquery.min.js') }}"></script>
<script src="{{ asset('racine/js/bootstrap.min.js') }}"></script>

{{-- CHATBOT AMIRA --}}
@include('assistant::chat')

@stack('scripts')
</body>
</html>
