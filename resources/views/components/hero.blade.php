{{--
    Composant Hero Section Premium
    
    Usage:
    <x-hero 
        title="RACINE BY GANDA" 
        subtitle="Mode Africaine Premium" 
        cta_text="Découvrir la Boutique" 
        cta_link="{{ route('frontend.shop') }}"
        background_image="/images/hero-bg.jpg"
    />
    
    Props:
    - title: string (required)
    - subtitle: string (optional)
    - cta_text: string (optional)
    - cta_link: string (optional)
    - background_image: string (optional)
    - height: string (optional) - sm, md, lg, xl (default: lg)
--}}

@props([
    'title',
    'subtitle' => '',
    'cta_text' => '',
    'cta_link' => '#',
    'background_image' => '',
    'height' => 'lg'
])

@php
$heightClasses = [
    'sm' => 'h-64',
    'md' => 'h-96',
    'lg' => 'h-[500px]',
    'xl' => 'h-screen',
];
@endphp

<section class="relative {{ $heightClasses[$height] }} flex items-center justify-center overflow-hidden">
    {{-- Background Image --}}
    @if($background_image)
    <div 
        class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ $background_image }}');"
    >
        <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-br from-racine-black via-racine-orange/20 to-racine-black"></div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 container mx-auto px-4 text-center">
        <div class="max-w-4xl mx-auto">
            {{-- Title --}}
            <h1 class="font-display text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-6 animate-fade-in">
                {{ $title }}
            </h1>

            {{-- Subtitle --}}
            @if($subtitle)
            <p class="text-xl md:text-2xl text-white/90 mb-8 font-light tracking-wide animate-slide-up">
                {{ $subtitle }}
            </p>
            @endif

            {{-- CTA Button --}}
            @if($cta_text)
            <div class="animate-slide-up" style="animation-delay: 0.2s;">
                <a href="{{ $cta_link }}" class="btn btn-accent btn-lg inline-flex items-center gap-2 shadow-2xl hover:shadow-accent/50 transition-all duration-300">
                    {{ $cta_text }}
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            @endif

            {{-- Slot pour contenu additionnel --}}
            @if($slot->isNotEmpty())
            <div class="mt-8">
                {{ $slot }}
            </div>
            @endif
        </div>
    </div>

    {{-- Decorative Elements --}}
    <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-white to-transparent"></div>
</section>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 1s ease-out;
}

.animate-slide-up {
    animation: slideUp 1s ease-out;
}
</style>
