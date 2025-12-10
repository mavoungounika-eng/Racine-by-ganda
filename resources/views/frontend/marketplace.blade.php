@extends('layouts.frontend')

@section('title', 'Marketplace Créateurs - RACINE BY GANDA')

@push('styles')
<style>
    .marketplace-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0 3rem;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
    }
    
    .marketplace-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .marketplace-hero p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
{{-- Hero Section --}}
<section class="marketplace-hero text-center">
    <div class="container">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white text-sm font-bold uppercase tracking-wide mb-4">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
            </svg>
            Marketplace
        </div>
        <h1>{{ $cmsPage?->title ?? 'Nos Créateurs Partenaires' }}</h1>
        <p>{{ $cmsPage?->meta_description ?? 'Découvrez les talents africains qui façonnent la mode de demain. Chaque créateur apporte son univers unique, sa vision et son savoir-faire.' }}</p>
    </div>
</section>

{{-- Creators Grid --}}
<section class="py-16 bg-[#F8F6F3]">
    <div class="container">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-[#2C1810] mb-4" style="font-family: 'Cormorant Garamond', serif;">
                Nos Créateurs
            </h2>
            <p class="text-[#8B7355] text-lg">
                {{ $creators->total() }} créateur(s) vérifié(s)
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($creators as $creator)
                <a href="{{ route('frontend.creator.shop', $creator->slug) }}" 
                   class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    {{-- Banner --}}
                    <div class="h-32 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] relative">
                        @if($creator->banner_path)
                            <img src="{{ $creator->banner_path }}" alt="{{ $creator->brand_name }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    
                    {{-- Logo --}}
                    <div class="relative px-6 -mt-12">
                        <div class="w-24 h-24 rounded-full border-4 border-white bg-white overflow-hidden shadow-lg">
                            <img src="{{ $creator->logo_path ?? asset('images/default-creator.png') }}" 
                                 alt="{{ $creator->brand_name }}"
                                 class="w-full h-full object-cover">
                        </div>
                    </div>
                    
                    {{-- Info --}}
                    <div class="px-6 pb-6 pt-4">
                        <h3 class="text-xl font-bold text-[#2C1810] mb-1 group-hover:text-[#ED5F1E] transition">
                            {{ $creator->brand_name }}
                        </h3>
                        
                        @if($creator->location)
                            <p class="text-sm text-[#8B7355] mb-3 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $creator->location }}
                            </p>
                        @endif
                        
                        @if($creator->bio)
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                {{ $creator->bio }}
                            </p>
                        @endif
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#8B7355]">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                                </svg>
                                {{ $creator->products_count }} produit(s)
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Vérifié
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun créateur disponible</h3>
                    <p class="text-gray-500">Revenez bientôt pour découvrir nos créateurs partenaires</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($creators->hasPages())
            <div class="mt-12">
                {{ $creators->links() }}
            </div>
        @endif
    </div>
</section>

{{-- Featured Products --}}
@if($featuredProducts->count() > 0)
<section class="py-16 bg-white">
    <div class="container">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-[#2C1810] mb-4" style="font-family: 'Cormorant Garamond', serif;">
                Produits en Vedette
            </h2>
            <p class="text-[#8B7355] text-lg">
                Découvrez les dernières créations de nos créateurs
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                <a href="{{ route('frontend.product', $product->id) }}" 
                   class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    {{-- Image --}}
                    <div class="relative h-64 overflow-hidden">
                        <img src="{{ $product->main_image ?? 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop' }}" 
                             alt="{{ $product->title }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        
                        {{-- Badge Créateur --}}
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#F8F6F3] border-2 border-[#8B5A2B] text-[#8B5A2B] text-[10px] font-semibold uppercase">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                {{ $product->creator?->creatorProfile?->brand_name ?? 'Créateur' }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- Info --}}
                    <div class="p-4">
                        <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-1">
                            {{ $product->category?->name }}
                        </p>
                        <h3 class="text-lg font-semibold text-[#2C1810] mb-2 line-clamp-2 group-hover:text-[#ED5F1E] transition">
                            {{ $product->title }}
                        </h3>
                        <p class="text-xl font-bold text-[#8B5A2B]">
                            {{ number_format($product->price, 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('frontend.shop', ['product_type' => 'marketplace']) }}" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white rounded-full font-semibold hover:shadow-lg transition">
                Voir tous les produits marketplace
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
@endif
@endsection
