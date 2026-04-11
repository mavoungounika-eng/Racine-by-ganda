{{--
    Composant Section Title Premium
    
    Usage:
    <x-section-title 
        title="Nos Créateurs" 
        subtitle="Découvrez les talents qui font RACINE BY GANDA" 
        align="center" 
    />
    
    Props:
    - title: string (required)
    - subtitle: string (optional)
    - align: string (optional) - left, center, right (default: left)
--}}

@props([
    'title',
    'subtitle' => '',
    'align' => 'left'
])

@php
$alignClasses = [
    'left' => 'text-left',
    'center' => 'text-center',
    'right' => 'text-right',
];
@endphp

<div class="{{ $alignClasses[$align] }} mb-12">
    {{-- Decorative Line (si center) --}}
    @if($align === 'center')
    <div class="flex items-center justify-center mb-4">
        <div class="h-px w-12 bg-accent"></div>
        <div class="h-1.5 w-1.5 rounded-full bg-accent mx-2"></div>
        <div class="h-px w-12 bg-accent"></div>
    </div>
    @endif

    {{-- Title --}}
    <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-primary mb-4">
        {{ $title }}
    </h2>

    {{-- Subtitle --}}
    @if($subtitle)
    <p class="text-lg md:text-xl text-gray-600 max-w-3xl {{ $align === 'center' ? 'mx-auto' : '' }}">
        {{ $subtitle }}
    </p>
    @endif

    {{-- Slot pour contenu additionnel --}}
    @if($slot->isNotEmpty())
    <div class="mt-4">
        {{ $slot }}
    </div>
    @endif
</div>
