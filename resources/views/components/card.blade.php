@props([
    'variant' => 'default', // default, header, dark, gradient
    'padding' => 'p-8',
    'hover' => true,
])

@php
$baseClasses = 'rounded-2xl transition-all duration-300';

$variantClasses = [
    'default' => 'bg-[#1f1412] border border-white/10 shadow-lg ' . ($hover ? 'hover:shadow-2xl hover:scale-105' : ''),
    'header' => 'bg-gradient-to-r from-racine-orange to-racine-yellow text-racine-black',
    'dark' => 'bg-racine-black border border-white/10 shadow-2xl',
    'gradient' => 'bg-gradient-to-br from-white to-gray-50 shadow-lg ' . ($hover ? 'hover:shadow-2xl' : ''),
    'racine' => 'bg-[#1f1412] border border-white/10 shadow-lg ' . ($hover ? 'hover:shadow-2xl hover:scale-105' : ''),
];

$classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $padding;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
