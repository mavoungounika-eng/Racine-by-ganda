@extends('layouts.frontend')

@section('title', $creatorProfile->brand_name . ' - Boutique Créateur - RACINE BY GANDA')

@push('styles')
<style>
    .creator-header {
        position: relative;
        margin-top: -70px;
        padding-top: 70px;
    }
    
    .creator-banner {
        height: 300px;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        position: relative;
        overflow: hidden;
    }
    
    .creator-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.7;
    }
</style>
@endpush

@section('content')
{{-- Creator Header --}}
<section class="creator-header">
    {{-- Banner --}}
    <div class="creator-banner">
        @if($creatorProfile->banner_path)
            <img src="{{ $creatorProfile->banner_path }}" alt="{{ $creatorProfile->brand_name }}">
        @endif
    </div>
    
    {{-- Creator Info --}}
    <div class="bg-white shadow-lg">
        <div class="container">
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 py-6 -mt-20 md:-mt-16">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    <div class="w-32 h-32 rounded-full border-4 border-white bg-white shadow-2xl overflow-hidden">
                        <img src="{{ $creatorProfile->logo_path ?? asset('images/default-creator.png') }}" 
                             alt="{{ $creatorProfile->brand_name }}"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                
                {{-- Info --}}
                <div class="flex-1 text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-center gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-bold text-[#2C1810]" style="font-family: 'Cormorant Garamond', serif;">
                            {{ $creatorProfile->brand_name }}
                        </h1>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold w-fit mx-auto md:mx-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Créateur Vérifié
                        </span>
                    </div>
                    
                    @if($creatorProfile->location)
                        <p class="text-[#8B7355] mb-3 flex items-center gap-2 justify-center md:justify-start">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $creatorProfile->location }}
                        </p>
                    @endif
                    
                    @if($creatorProfile->bio)
                        <p class="text-gray-600 max-w-2xl">
                            {{ $creatorProfile->bio }}
                        </p>
                    @endif
                </div>
                
                {{-- Stats & Social --}}
                <div class="flex-shrink-0 text-center">
                    <div class="bg-[#F8F6F3] rounded-2xl px-6 py-4 mb-3">
                        <div class="text-3xl font-bold text-[#ED5F1E] mb-1">
                            {{ $products->total() }}
                        </div>
                        <div class="text-sm text-[#8B7355]">
                            Produit(s)
                        </div>
                    </div>
                    
                    {{-- Social Links --}}
                    <div class="flex gap-2 justify-center">
                        @if($creatorProfile->instagram_url)
                            <a href="{{ $creatorProfile->instagram_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-instagram"></i>
                            </a>
                        @endif
                        @if($creatorProfile->facebook_url)
                            <a href="{{ $creatorProfile->facebook_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        @endif
                        @if($creatorProfile->tiktok_url)
                            <a href="{{ $creatorProfile->tiktok_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        @endif
                        @if($creatorProfile->website)
                            <a href="{{ $creatorProfile->website }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-[#8B5A2B] text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fas fa-globe"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Products Section --}}
<section class="py-12 bg-[#F8F6F3]">
    <div class="container">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-[#2C1810]" style="font-family: 'Cormorant Garamond', serif;">
                Produits de {{ $creatorProfile->brand_name }}
            </h2>
            <a href="{{ route('frontend.shop', ['product_type' => 'marketplace']) }}" 
               class="text-[#8B5A2B] hover:text-[#ED5F1E] transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour au marketplace
            </a>
        </div>

        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <a href="{{ route('frontend.product', $product->id) }}" 
                       class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                        {{-- Image --}}
                        <div class="relative h-64 overflow-hidden">
                            <img src="{{ $product->main_image ?? 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop' }}" 
                                 alt="{{ $product->title }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            
                            {{-- Stock Badge --}}
                            @if($product->stock <= 0)
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 rounded-full bg-gray-600 text-white text-xs font-semibold">
                                        Stock épuisé
                                    </span>
                                </div>
                            @elseif($product->stock < 5)
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 rounded-full bg-orange-500 text-white text-xs font-semibold">
                                        Stock limité
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Info --}}
                        <div class="p-4">
                            <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-1">
                                {{ $product->category?->name }}
                            </p>
                            <h3 class="text-lg font-semibold text-[#2C1810] mb-2 line-clamp-2 group-hover:text-[#ED5F1E] transition">
                                {{ $product->title }}
                            </h3>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-bold text-[#8B5A2B]">
                                    {{ number_format($product->price, 0, ',', ' ') }} FCFA
                                </p>
                                @if($product->stock > 0)
                                    <span class="text-xs text-gray-500">
                                        {{ $product->stock }} en stock
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-16 bg-white rounded-2xl">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-600 mb-2">Aucun produit disponible</h3>
                <p class="text-gray-500 mb-6">Ce créateur n'a pas encore de produits actifs</p>
                <a href="{{ route('frontend.marketplace') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white rounded-full font-semibold hover:shadow-lg transition">
                    Découvrir d'autres créateurs
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
