@extends('layouts.frontend')

@section('title', $profile->brand_name . ' - Aperçu Boutique - RACINE BY GANDA')

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
        @if($profile->banner_path)
            <img src="{{ Storage::url($profile->banner_path) }}" alt="{{ $profile->brand_name }}">
        @endif
    </div>
    
    {{-- Creator Info --}}
    <div class="bg-white shadow-lg">
        <div class="container">
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 py-6 -mt-20 md:-mt-16">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    <div class="w-32 h-32 rounded-full border-4 border-white bg-white shadow-2xl overflow-hidden">
                        @if($profile->logo_path)
                            <img src="{{ Storage::url($profile->logo_path) }}" alt="{{ $profile->brand_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-[#F8F6F3] text-[#8B7355] text-3xl font-serif">
                                {{ strtoupper(substr($profile->brand_name ?? 'C', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Info --}}
                <div class="flex-1 text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-center gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-bold text-[#2C1810]" style="font-family: 'Cormorant Garamond', serif;">
                            {{ $profile->brand_name }}
                        </h1>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold w-fit mx-auto md:mx-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Mode Aperçu
                        </span>
                    </div>
                    
                    @if($profile->creator_title)
                        <p class="text-[#8B7355] mb-3 flex items-center gap-2 justify-center md:justify-start">
                            <i class="fas fa-paint-brush"></i>
                            {{ $profile->creator_title }}
                        </p>
                    @endif
                    
                    @if($profile->bio)
                        <p class="text-gray-600 max-w-2xl">
                            {{ $profile->bio }}
                        </p>
                    @endif
                </div>
                
                {{-- Stats & Social --}}
                <div class="flex-shrink-0 text-center">
                    <div class="bg-[#F8F6F3] rounded-2xl px-6 py-4 mb-3">
                        <div class="text-3xl font-bold text-[#ED5F1E] mb-1">
                            {{ $profile->products()->count() }}
                        </div>
                        <div class="text-sm text-[#8B7355]">
                            Produit(s)
                        </div>
                    </div>
                    
                    {{-- Social Links --}}
                    <div class="flex gap-2 justify-center">
                        @if($profile->instagram_url)
                            <a href="{{ $profile->instagram_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-instagram"></i>
                            </a>
                        @endif
                        @if($profile->facebook_url)
                            <a href="{{ $profile->facebook_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        @endif
                        @if($profile->tiktok_url)
                            <a href="{{ $profile->tiktok_url }}" target="_blank" 
                               class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center hover:shadow-lg transition">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        @endif
                        @if($profile->website)
                            <a href="{{ $profile->website }}" target="_blank" 
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
        
        {{-- Floating Edit Button --}}
        <div class="fixed bottom-8 right-8 z-50">
            <a href="{{ route('creator.profile.show') }}" class="flex items-center gap-2 px-6 py-3 bg-[#ED5F1E] text-white rounded-full font-bold shadow-lg hover:bg-[#d64e12] transition-colors">
                <i class="fas fa-edit"></i>
                Modifier mon profil
            </a>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-[#2C1810]" style="font-family: 'Cormorant Garamond', serif;">
                Vos Produits
            </h2>
        </div>

        @php
            $products = $profile->products()->latest()->paginate(12);
        @endphp

        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <div class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                        {{-- Image --}}
                        <div class="relative h-64 overflow-hidden">
                            @if($product->main_image)
                                <img src="{{ Storage::url($product->main_image) }}" 
                                     alt="{{ $product->title }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <i class="fas fa-image text-4xl text-gray-300"></i>
                                </div>
                            @endif
                            
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

                            {{-- Active Badge --}}
                            <div class="absolute top-3 right-3">
                                @if($product->is_active)
                                    <span class="px-2 py-1 rounded-md bg-green-500 text-white text-xs font-bold">
                                        ACTIF
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-md bg-red-500 text-white text-xs font-bold">
                                        INACTIF
                                    </span>
                                @endif
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
                    </div>
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
                <h3 class="text-2xl font-semibold text-gray-600 mb-2">Aucun produit</h3>
                <p class="text-gray-500 mb-6">Vous n'avez pas encore ajouté de produits.</p>
                <a href="{{ route('creator.products.create') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white rounded-full font-semibold hover:shadow-lg transition">
                    <i class="fas fa-plus"></i>
                    Créer mon premier produit
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
