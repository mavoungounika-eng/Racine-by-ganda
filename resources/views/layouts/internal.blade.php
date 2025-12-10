<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'RACINE BY GANDA - Espace Pro')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap & Icons --}}
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('racine/css/open-iconic-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('racine/css/icomoon.css') }}">

    <style>
        /* =============================================
           🎨 CHARTE GRAPHIQUE RACINE BY GANDA
           ============================================= */
        :root {
            /* Couleurs principales */
            --racine-violet: #4B1DF2;
            --racine-violet-dark: #3A16BD;
            --racine-violet-light: #6B4FF2;
            --racine-black: #11001F;
            --racine-black-soft: #1A1A2E;
            --racine-gold: #D4AF37;
            --racine-gold-light: #E5C76B;
            --racine-white: #FAFAFA;
            --racine-gray: #F0F0F5;
            --racine-gray-dark: #6B7280;
            
            /* Layout */
            --sidebar-width: 270px;
            --sidebar-collapsed: 80px;
            --header-height: 70px;
            
            /* Transitions */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(17, 0, 31, 0.05);
            --shadow-md: 0 4px 6px rgba(17, 0, 31, 0.07);
            --shadow-lg: 0 10px 25px rgba(17, 0, 31, 0.1);
            --shadow-xl: 0 20px 40px rgba(17, 0, 31, 0.15);
            
            /* Border radius */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--racine-gray) 0%, #E8E8F0 100%);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* =============================================
           📱 SIDEBAR PREMIUM
           ============================================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-xl);
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
        }

        .sidebar-header {
            padding: 1.5rem 1.25rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .sidebar-brand {
            color: var(--racine-white);
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            font-size: 1.2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition-fast);
        }

        .sidebar-brand:hover {
            color: var(--racine-gold);
            text-decoration: none;
        }

        .sidebar-brand img {
            height: 40px;
            width: 40px;
            object-fit: contain;
            filter: brightness(1.1);
        }

        .sidebar-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .sidebar-brand-text small {
            font-family: 'Inter', sans-serif;
            font-size: 0.65rem;
            font-weight: 400;
            color: var(--racine-gold);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .sidebar-nav {
            padding: 1rem 0 2rem;
        }

        .sidebar-section {
            padding: 1.25rem 1.25rem 0.5rem;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.35);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            margin: 2px 0.75rem;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            transition: var(--transition-fast);
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 450;
            position: relative;
            overflow: hidden;
        }

        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--racine-violet);
            transform: scaleY(0);
            transition: var(--transition-fast);
            border-radius: 0 3px 3px 0;
        }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.05);
            color: var(--racine-white);
            text-decoration: none;
            transform: translateX(4px);
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, rgba(75, 29, 242, 0.2) 0%, rgba(75, 29, 242, 0.1) 100%);
            color: var(--racine-white);
        }

        .sidebar-link.active::before {
            transform: scaleY(1);
        }

        .sidebar-link .icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .sidebar-link .badge-count {
            margin-left: auto;
            background: var(--racine-violet);
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* =============================================
           🖥️ MAIN WRAPPER
           ============================================= */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition-normal);
        }

        /* =============================================
           📍 HEADER PREMIUM
           ============================================= */
        .main-header {
            height: var(--header-height);
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            color: var(--racine-black);
        }

        .page-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--racine-black);
            font-family: 'Playfair Display', serif;
        }

        .page-title small {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--racine-gray-dark);
            margin-top: 2px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* User Dropdown Premium */
        .user-dropdown-wrapper {
            position: relative;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.5rem 1rem 0.5rem 0.5rem;
            background: var(--racine-gray);
            border-radius: var(--radius-xl);
            cursor: pointer;
            transition: var(--transition-fast);
            border: 2px solid transparent;
        }

        .user-dropdown:hover {
            border-color: var(--racine-violet-light);
            box-shadow: var(--shadow-md);
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--racine-violet) 0%, var(--racine-violet-dark) 100%);
            color: var(--racine-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(75, 29, 242, 0.3);
            position: relative;
        }

        .user-avatar .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #22C55E;
            border: 2px solid white;
            border-radius: 50%;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--racine-black);
        }

        .user-role-badge {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 2px;
        }

        .user-role-badge.super_admin { background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); color: white; }
        .user-role-badge.admin { background: linear-gradient(135deg, var(--racine-gold) 0%, #B8860B 100%); color: var(--racine-black); }
        .user-role-badge.staff { background: linear-gradient(135deg, #0EA5E9 0%, #0369A1 100%); color: white; }
        .user-role-badge.createur { background: linear-gradient(135deg, #22C55E 0%, #15803D 100%); color: white; }
        .user-role-badge.client { background: linear-gradient(135deg, var(--racine-violet) 0%, var(--racine-violet-dark) 100%); color: white; }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-xl);
            border-radius: var(--radius-md);
            padding: 0.5rem;
            min-width: 200px;
            margin-top: 0.5rem;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            transition: var(--transition-fast);
        }

        .dropdown-item:hover {
            background: var(--racine-gray);
        }

        .dropdown-item.text-danger:hover {
            background: #FEE2E2;
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: var(--racine-gray);
        }

        /* =============================================
           📄 MAIN CONTENT
           ============================================= */
        .main-content {
            padding: 2rem;
            max-width: 1600px;
        }

        /* =============================================
           🎴 CARDS PREMIUM
           ============================================= */
        .card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            background: var(--racine-white);
            transition: var(--transition-fast);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--racine-gray);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* KPI Cards */
        .kpi-card {
            position: relative;
            overflow: hidden;
            transition: var(--transition-normal);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
        }

        .kpi-card .kpi-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .kpi-card .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            line-height: 1;
        }

        .kpi-card .kpi-label {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        /* =============================================
           📊 TABLES PREMIUM
           ============================================= */
        .table-container {
            background: var(--racine-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            background: var(--racine-white);
            border-bottom: 1px solid var(--racine-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--racine-black);
            margin: 0;
        }

        .table-search {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--racine-gray);
            border-radius: var(--radius-md);
            padding: 0.5rem 1rem;
            flex: 1;
            max-width: 300px;
        }

        .table-search input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            font-size: 0.9rem;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: var(--racine-gray);
            border: none;
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--racine-gray-dark);
        }

        .table tbody td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--racine-gray);
        }

        .table tbody tr:hover {
            background: rgba(75, 29, 242, 0.02);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =============================================
           🔘 BUTTONS PREMIUM
           ============================================= */
        .btn {
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            transition: var(--transition-fast);
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--racine-violet) 0%, var(--racine-violet-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(75, 29, 242, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--racine-violet-light) 0%, var(--racine-violet) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(75, 29, 242, 0.4);
        }

        .btn-secondary {
            background: var(--racine-gray);
            color: var(--racine-black);
        }

        .btn-secondary:hover {
            background: #E0E0E8;
        }

        .btn-success {
            background: linear-gradient(135deg, #22C55E 0%, #15803D 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--racine-gold) 0%, #B8860B 100%);
            color: var(--racine-black);
        }

        .btn-outline-primary {
            border: 2px solid var(--racine-violet);
            color: var(--racine-violet);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--racine-violet);
            color: white;
        }

        .btn-sm {
            padding: 0.4rem 0.875rem;
            font-size: 0.8rem;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
        }

        /* =============================================
           📛 BADGES PREMIUM
           ============================================= */
        .badge {
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
        }

        .badge-success { background: #DCFCE7; color: #15803D; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-danger { background: #FEE2E2; color: #DC2626; }
        .badge-info { background: #DBEAFE; color: #1D4ED8; }
        .badge-primary { background: rgba(75, 29, 242, 0.1); color: var(--racine-violet); }

        /* =============================================
           📝 FORMS PREMIUM
           ============================================= */
        .form-control {
            border: 2px solid var(--racine-gray);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition-fast);
        }

        .form-control:focus {
            border-color: var(--racine-violet);
            box-shadow: 0 0 0 3px rgba(75, 29, 242, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--racine-black);
            margin-bottom: 0.5rem;
        }

        /* =============================================
           🚨 ALERTS PREMIUM
           ============================================= */
        .alert {
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #DCFCE7 0%, #BBF7D0 100%);
            color: #15803D;
        }

        .alert-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #DC2626;
        }

        /* =============================================
           📱 RESPONSIVE
           ============================================= */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: var(--transition-normal);
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .main-content {
                padding: 1rem;
            }

            .user-info {
                display: none;
            }
        }

        /* =============================================
           ✨ ANIMATIONS
           ============================================= */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .animate-pulse {
            animation: pulse 2s infinite;
        }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Sidebar Overlay (mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('frontend.home') }}" class="sidebar-brand">
                <img src="{{ asset('racine/images/logoo.png') }}" alt="Logo">
                <span class="sidebar-brand-text">
                    RACINE BY GANDA
                    <small>Espace Pro</small>
                </span>
            </a>
        </div>

        <nav class="sidebar-nav">
            {{-- Mon Espace --}}
            <div class="sidebar-section">Mon Espace</div>
            @php
                $role = Auth::user()->role ?? 'client';
                $dashboardRoutes = [
                    'super_admin' => 'dashboard.super-admin',
                    'admin' => 'dashboard.admin',
                    'staff' => 'dashboard.staff',
                    'createur' => 'dashboard.createur',
                    'client' => 'account.dashboard',
                ];
                $dashboardRoute = $dashboardRoutes[$role] ?? 'account.dashboard';
            @endphp
            <a href="{{ route($dashboardRoute) }}" class="sidebar-link {{ request()->routeIs('dashboard.*') || request()->routeIs('account.dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span> Dashboard
            </a>
            <a href="{{ route('profile.index') }}" class="sidebar-link {{ request()->routeIs('profile.*') && !request()->routeIs('messages.*') ? 'active' : '' }}">
                <span class="icon">👤</span> Mon Profil
            </a>
            <a href="{{ route('messages.index') }}" class="sidebar-link {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <span class="icon">💬</span> Messagerie
                @php
                    $unreadCount = app(\App\Services\ConversationService::class)->getUnreadConversationsCount(auth()->id());
                @endphp
                @if($unreadCount > 0)
                    <span class="badge badge-primary ml-auto">{{ $unreadCount }}</span>
                @endif
            </a>

            @can('access-erp')
            {{-- ERP --}}
            <div class="sidebar-section">ERP</div>
            <a href="{{ route('erp.dashboard') }}" class="sidebar-link {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}">
                <span class="icon">📊</span> Vue d'ensemble
            </a>
            <a href="{{ route('erp.stocks.index') }}" class="sidebar-link {{ request()->routeIs('erp.stocks.*') ? 'active' : '' }}">
                <span class="icon">📦</span> Stocks
            </a>
            <a href="{{ route('erp.suppliers.index') }}" class="sidebar-link {{ request()->routeIs('erp.suppliers.*') ? 'active' : '' }}">
                <span class="icon">🏭</span> Fournisseurs
            </a>
            <a href="{{ route('erp.purchases.index') }}" class="sidebar-link {{ request()->routeIs('erp.purchases.*') ? 'active' : '' }}">
                <span class="icon">🛒</span> Achats
            </a>
            <a href="{{ route('erp.materials.index') }}" class="sidebar-link {{ request()->routeIs('erp.materials.*') ? 'active' : '' }}">
                <span class="icon">🧵</span> Matières
            </a>
            @endcan

            @can('access-crm')
            {{-- CRM --}}
            <div class="sidebar-section">CRM</div>
            <a href="{{ route('crm.dashboard') }}" class="sidebar-link {{ request()->routeIs('crm.dashboard') ? 'active' : '' }}">
                <span class="icon">📈</span> Pipeline
            </a>
            <a href="{{ route('crm.contacts.index') }}" class="sidebar-link {{ request()->routeIs('crm.contacts.*') ? 'active' : '' }}">
                <span class="icon">👥</span> Contacts
            </a>
            <a href="{{ route('crm.opportunities.index') }}" class="sidebar-link {{ request()->routeIs('crm.opportunities.*') ? 'active' : '' }}">
                <span class="icon">🎯</span> Opportunités
            </a>
            @endcan

            @can('access-admin')
            {{-- Administration --}}
            <div class="sidebar-section">Administration</div>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                <span class="icon">⚙️</span> Back-Office
            </a>
            <a href="{{ route('admin.orders.index') }}" class="sidebar-link">
                <span class="icon">📦</span> Commandes
            </a>
            <a href="{{ route('admin.products.index') }}" class="sidebar-link">
                <span class="icon">👗</span> Produits
            </a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-link">
                <span class="icon">👥</span> Utilisateurs
            </a>
            
            {{-- Analytics BI --}}
            <div class="sidebar-section">Business Intelligence</div>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="icon">📊</span> Analytics
                <span class="badge-count" style="background: linear-gradient(135deg, #22C55E 0%, #15803D 100%);">LIVE</span>
            </a>
            @endcan

            {{-- Site --}}
            <div class="sidebar-section">Site Public</div>
            <a href="{{ route('frontend.home') }}" class="sidebar-link" target="_blank">
                <span class="icon">🌐</span> Voir le site
            </a>
            <a href="{{ route('frontend.shop') }}" class="sidebar-link" target="_blank">
                <span class="icon">🛍️</span> Boutique
            </a>
        </nav>
    </aside>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">
        {{-- Header --}}
        <header class="main-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="icon-menu"></span>
                </button>
                <div class="page-title">
                    @yield('page-title', 'Tableau de bord')
                    @hasSection('page-subtitle')
                        <small>@yield('page-subtitle')</small>
                    @endif
                </div>
            </div>

            <div class="header-right">
                {{-- Widget Notifications --}}
                @include('components.notification-widget')
                
                <div class="dropdown user-dropdown-wrapper">
                    <div class="user-dropdown" data-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            <span class="status-dot"></span>
                        </div>
                        <div class="user-info d-none d-md-flex">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                            <span class="user-role-badge {{ Auth::user()->role }}">
                                @switch(Auth::user()->role)
                                    @case('super_admin') CEO @break
                                    @case('admin') Admin @break
                                    @case('staff') Staff @break
                                    @case('createur') Créateur @break
                                    @default Client
                                @endswitch
                            </span>
                        </div>
                        <span class="icon-chevron-down ml-2 d-none d-md-inline"></span>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                            <span class="icon-user mr-2"></span> Mon Profil
                        </a>
                        <a class="dropdown-item" href="{{ route('frontend.home') }}" target="_blank">
                            <span class="icon-external-link mr-2"></span> Voir le site
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <span class="icon-log-out mr-2"></span> Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success animate-fade-in" role="alert">
                    <span class="icon-check"></span>
                    {{ session('success') }}
                    <button type="button" class="close ml-auto" data-dismiss="alert" style="background:none;border:none;font-size:1.25rem;">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger animate-fade-in" role="alert">
                    <span class="icon-alert-circle"></span>
                    {{ session('error') }}
                    <button type="button" class="close ml-auto" data-dismiss="alert" style="background:none;border:none;font-size:1.25rem;">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- JS --}}
    <script src="{{ asset('racine/js/jquery.min.js') }}"></script>
    <script src="{{ asset('racine/js/popper.min.js') }}"></script>
    <script src="{{ asset('racine/js/bootstrap.min.js') }}"></script>

    <script>
        // Mobile sidebar toggle
        document.getElementById('mobileToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        });

        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>

    {{-- SCROLL TO TOP BUTTON --}}
    @include('components.scroll-to-top')
    
    @stack('scripts')
</body>
</html>
