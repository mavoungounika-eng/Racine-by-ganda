@extends('layouts.creator')

@section('title', 'Mon Profil Public - RACINE BY GANDA')
@section('page-title', 'Mon Profil Public')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
<style>
    .profile-preview-banner {
        background: linear-gradient(135deg, #2C1810 0%, #8B5A2B 100%);
        padding: 2rem;
        border-radius: 24px 24px 0 0;
        position: relative;
        overflow: hidden;
    }
    
    .profile-preview-banner::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(212, 165, 116, 0.2) 0%, transparent 70%);
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        object-fit: cover;
    }
    
    .social-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        background: rgba(212, 165, 116, 0.1);
        border: 1px solid rgba(212, 165, 116, 0.3);
        color: #8B5A2B;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .social-link:hover {
        background: rgba(212, 165, 116, 0.2);
        transform: translateY(-2px);
        color: #8B5A2B;
    }
    
    .edit-notice {
        background: linear-gradient(135deg, #FFF7ED 0%, #FFFBF5 100%);
        border: 2px dashed #FFB800;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    
    {{-- Notice d'édition --}}
    <div class="edit-notice">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-[#ED5F1E] text-2xl mt-1"></i>
            <div class="flex-1">
                <h3 class="font-bold text-[#2C1810] mb-2">Aperçu de votre profil public</h3>
                <p class="text-[#8B7355] text-sm mb-3">
                    C'est ainsi que les clients voient votre boutique. Pour modifier ces informations, 
                    rendez-vous dans <strong>Paramètres → Ma Vitrine</strong>.
                </p>
                <a href="{{ route('creator.settings.shop') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-all"
                   style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color: white;">
                    <i class="fas fa-edit"></i>
                    Modifier mon profil
                </a>
            </div>
        </div>
    </div>

    {{-- Profil Public --}}
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        
        {{-- Bannière --}}
        <div class="profile-preview-banner">
            @if($profile->banner_path)
                <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière" 
                     class="w-full h-48 object-cover rounded-xl">
            @else
                <div class="h-48 flex items-center justify-center">
                    <i class="fas fa-image text-white/30 text-6xl"></i>
                </div>
            @endif
        </div>

        {{-- Informations principales --}}
        <div class="p-8">
            <div class="flex items-start gap-6 mb-6" style="margin-top: -80px;">
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    @if($profile->logo_path)
                        <img src="{{ Storage::url($profile->logo_path) }}" alt="{{ $profile->brand_name }}" 
                             class="profile-avatar">
                    @else
                        <div class="profile-avatar flex items-center justify-center bg-gradient-to-br from-[#D4A574] to-[#8B5A2B]">
                            <span class="text-white text-4xl font-bold">
                                {{ strtoupper(substr($profile->brand_name ?? 'C', 0, 1)) }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Nom et bio --}}
                <div class="flex-1 mt-16">
                    <h1 class="text-3xl font-bold text-[#2C1810] mb-2" style="font-family: 'Libre Baskerville', serif;">
                        {{ $profile->brand_name ?? 'Ma Boutique' }}
                    </h1>
                    
                    @if($profile->bio)
                        <p class="text-[#8B7355] text-lg leading-relaxed mb-4">
                            {{ $profile->bio }}
                        </p>
                    @else
                        <p class="text-[#8B7355] italic mb-4">
                            Aucune description pour le moment
                        </p>
                    @endif

                    {{-- Statistiques --}}
                    <div class="flex items-center gap-6 mb-4">
                        <div>
                            <span class="text-2xl font-bold text-[#ED5F1E]">{{ $profile->products()->where('is_active', true)->count() }}</span>
                            <span class="text-sm text-[#8B7355] ml-1">Produits</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold text-[#ED5F1E]">{{ $profile->user->collections()->count() }}</span>
                            <span class="text-sm text-[#8B7355] ml-1">Collections</span>
                        </div>
                    </div>

                    {{-- Réseaux sociaux --}}
                    @if($profile->website || $profile->instagram_url || $profile->tiktok_url)
                        <div class="flex items-center gap-3 flex-wrap">
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
            </div>

            {{-- Produits récents --}}
            <div class="mt-8 pt-8 border-t-2 border-[#E5DDD3]">
                <h2 class="text-2xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
                    Mes Produits Récents
                </h2>
                
                @php
                    $recentProducts = $profile->products()->where('is_active', true)->latest()->take(6)->get();
                @endphp

                @if($recentProducts->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($recentProducts as $product)
                            <div class="group">
                                <div class="aspect-square rounded-xl overflow-hidden bg-[#F8F6F3] mb-3">
                                    @if($product->main_image)
                                        <img src="{{ asset('storage/' . $product->main_image) }}" 
                                             alt="{{ $product->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-image text-[#8B7355] text-4xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-[#2C1810] mb-1">{{ $product->title }}</h3>
                                <p class="text-[#ED5F1E] font-bold">{{ number_format($product->price, 0, ',', ' ') }} FCFA</p>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="text-center mt-6">
                        <a href="{{ route('creator.products.index') }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border-2 border-[#E5DDD3] text-[#2C1810] font-semibold hover:bg-[#F8F6F3] transition-colors">
                            Voir tous mes produits
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <div class="text-center py-12 bg-[#F8F6F3] rounded-xl">
                        <i class="fas fa-box-open text-5xl text-[#8B7355] mb-4"></i>
                        <p class="text-[#8B7355] mb-4">Vous n'avez pas encore de produits</p>
                        <a href="{{ route('creator.products.create') }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all"
                           style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color: white;">
                            <i class="fas fa-plus"></i>
                            Créer mon premier produit
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
