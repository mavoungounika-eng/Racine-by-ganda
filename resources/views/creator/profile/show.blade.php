@extends('layouts.creator')

@section('title', 'Mon Profil Public - RACINE BY GANDA')
@section('page-title', 'Mon Profil Public')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
<style>
    .profile-preview-banner {
        background: linear-gradient(135deg, #2C1810 0%, #8B5A2B 100%);
        padding: 0;
        border-radius: 24px 24px 0 0;
        position: relative;
        overflow: hidden;
        height: 240px;
    }
    
    .profile-avatar-container {
        margin-top: -80px;
        position: relative;
        z-index: 5;
    }
    
    .profile-avatar {
        width: 160px;
        height: 160px;
        border-radius: 80px;
        border: 6px solid white;
        box-shadow: var(--shadow-lg);
        object-fit: cover;
        background: white;
    }
    
    .social-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        border-radius: 999px;
        background: #F8F6F3;
        border: 1px solid #E5DDD3;
        color: var(--racine-black-soft);
        text-decoration: none !important;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 0.9rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .social-link:hover {
        background: var(--racine-orange-ultra-light);
        border-color: var(--racine-orange);
        color: var(--racine-orange);
        transform: translateY(-2px);
    }
    
    .edit-notice-card {
        background: #FFF9E6;
        border: 2px dashed #FFB800;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .edit-notice-card h3 {
        color: #92400E !important; /* Dark orange/brown for contrast */
        font-weight: 800;
    }

    .edit-notice-card p {
        color: #B45309 !important;
        font-weight: 500;
    }

    .stat-box {
        padding: 1rem 1.5rem;
        background: #F8F6F3;
        border-radius: 16px;
        text-align: center;
        min-width: 120px;
    }

    .stat-number {
        display: block;
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--racine-orange);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #8B7355;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .product-preview-card {
        background: white;
        border-radius: 20px;
        padding: 1rem;
        border: 1px solid #F0EBE5;
        transition: all 0.3s ease;
        height: 100%;
    }

    .product-preview-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--racine-gold);
    }

    .product-img-box {
        aspect-ratio: 1/1;
        border-radius: 14px;
        overflow: hidden;
        background: #F8F6F3;
        margin-bottom: 1rem;
    }

    .product-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            
            {{-- Navigation --}}
            @include('creator.partials.settings-nav')
            
            {{-- Notice d'édition --}}
            <div class="edit-notice-card shadow-sm">
                <div class="d-flex align-items-start">
                    <div class="mr-3">
                        <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-info-circle fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 mb-2">Aperçu de votre profil public</h3>
                        <p class="mb-3 lead small">
                            C'est ainsi que les clients voient votre boutique. Pour modifier ces informations, 
                            rendez-vous dans les <strong>Paramètres → Ma Vitrine</strong>.
                        </p>
                        <a href="{{ route('creator.settings.shop') }}" class="btn btn-warning btn-sm font-weight-bold px-4 py-2 rounded-pill">
                            <i class="fas fa-edit mr-2"></i>
                            Modifier mon profil
                        </a>
                    </div>
                </div>
            </div>

            {{-- Profil Public --}}
            <div class="creator-card p-0 overflow-hidden border-0 shadow-lg">
                
                {{-- Bannière --}}
                <div class="profile-preview-banner">
                    @if($profile->banner_path)
                        <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière" 
                             class="w-full h-100 object-cover">
                    @else
                        <div class="h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-image text-white opacity-25" style="font-size: 5rem;"></i>
                        </div>
                    @endif
                </div>

                {{-- Informations principales --}}
                <div class="px-5 pb-5">
                    <div class="row profile-avatar-container">
                        {{-- Avatar --}}
                        <div class="col-auto">
                            @if($profile->logo_path)
                                <img src="{{ Storage::url($profile->logo_path) }}" alt="{{ $profile->brand_name }}" 
                                     class="profile-avatar shadow">
                            @else
                                <div class="profile-avatar d-flex align-items-center justify-content-center bg-gradient-to-br from-[#D4A574] to-[#8B5A2B] text-white">
                                    <span class="h1 font-weight-bold text-white mb-0">
                                        {{ strtoupper(substr($profile->brand_name ?? 'C', 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-8">
                            <h1 class="display-4 font-weight-bold text-dark mb-2" style="font-family: 'Libre Baskerville', serif;">
                                {{ $profile->brand_name ?? 'Ma Boutique' }}
                            </h1>
                            
                            @if($profile->bio)
                                <p class="lead text-muted mb-4">
                                    {{ $profile->bio }}
                                </p>
                            @else
                                <p class="text-muted font-italic mb-4">
                                    Aucune description pour le moment
                                </p>
                            @endif

                            {{-- Réseaux sociaux --}}
                            @if($profile->website || $profile->instagram_url || $profile->tiktok_url)
                                <div class="d-flex flex-wrap mb-4">
                                    @if($profile->website)
                                        <a href="{{ $profile->website }}" target="_blank" class="social-link">
                                            <i class="fas fa-globe"></i>
                                            Site web
                                        </a>
                                    @endif
                                    @if($profile->instagram_url)
                                        <a href="{{ $profile->instagram_url }}" target="_blank" class="social-link">
                                            <i class="fab fa-instagram"></i>
                                            Instagram
                                        </a>
                                    @endif
                                    @if($profile->tiktok_url)
                                        <a href="{{ $profile->tiktok_url }}" target="_blank" class="social-link">
                                            <i class="fab fa-tiktok"></i>
                                            TikTok
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-4">
                            {{-- Statistiques --}}
                            <div class="d-flex justify-content-lg-end gap-3">
                                <div class="stat-box mr-3">
                                    <span class="stat-number">{{ $profile->products()->where('is_active', true)->count() }}</span>
                                    <span class="stat-label">Produits</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-number">{{ $profile->user->collections()->count() }}</span>
                                    <span class="stat-label">Collections</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Produits récents --}}
                    <div class="mt-5 pt-5 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h2 class="h3 font-weight-bold text-dark mb-0" style="font-family: 'Libre Baskerville', serif;">
                                Mes Produits Récents
                            </h2>
                            @if($profile->products()->count() > 6)
                                <a href="{{ route('creator.products.index') }}" class="btn btn-link text-orange font-weight-bold p-0">
                                    Tout voir <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            @endif
                        </div>
                        
                        @php
                            $recentProducts = $profile->products()->where('is_active', true)->latest()->take(6)->get();
                        @endphp

                        @if($recentProducts->count() > 0)
                            <div class="row">
                                @foreach($recentProducts as $product)
                                    <div class="col-6 col-md-4 col-lg-2 mb-4">
                                        <div class="product-preview-card">
                                            <div class="product-img-box">
                                                @if($product->main_image)
                                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}">
                                                @else
                                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted opacity-50 fa-2x"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <h4 class="h6 font-weight-bold text-dark mb-1 text-truncate">{{ $product->title }}</h4>
                                            <p class="text-orange font-weight-bold mb-0 small">{{ number_format($product->price, 0, ',', ' ') }} FCFA</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5 bg-light rounded-xl border-dashed">
                                <div class="mb-3">
                                    <i class="fas fa-box-open text-muted fa-3x"></i>
                                </div>
                                <h4 class="h5 text-muted mb-3">Vous n'avez pas encore de produits</h4>
                                <a href="{{ route('creator.products.create') }}" class="btn btn-orange font-weight-bold px-4 py-2 rounded-pill shadow-sm">
                                    <i class="fas fa-plus mr-2"></i>
                                    Créer mon premier produit
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Back Button --}}
            <div class="text-center mt-5">
                <a href="{{ route('creator.dashboard') }}" class="btn btn-link text-muted font-weight-bold text-decoration-none">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

