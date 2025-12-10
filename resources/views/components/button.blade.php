@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, accent, danger, outline
    'size' => 'md', // sm, md, lg
    'href' => null,
    'icon' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2';

$sizeClasses = [
    'sm' => 'px-4 py-2 text-sm rounded-lg',
    'md' => 'px-6 py-3 text-base rounded-full',
    'lg' => 'px-8 py-4 text-lg rounded-full',
];

$variantClasses = [
    'primary' => 'bg-racine-orange text-racine-black hover:bg-racine-yellow shadow-lg hover:shadow-xl focus:ring-racine-orange',
    'secondary' => 'border-2 border-white/20 text-white hover:bg-white hover:text-racine-black focus:ring-white',
    'accent' => 'bg-racine-orange text-racine-black hover:bg-racine-yellow shadow-lg hover:shadow-xl focus:ring-racine-orange',
    'danger' => 'bg-red-500 text-white hover:bg-red-600 shadow-lg hover:shadow-xl focus:ring-red-500',
    'outline' => 'border-2 border-racine-orange text-racine-orange hover:bg-racine-orange hover:text-racine-black focus:ring-racine-orange',
];

$classes = $baseClasses . ' ' . $sizeClasses[$size] . ' ' . $variantClasses[$variant];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="{{ $icon }} mr-2"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="{{ $icon }} mr-2"></i>
        @endif
        {{ $slot }}
    </button>
@endif
