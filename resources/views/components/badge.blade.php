@props([
    'variant' => 'default', // default, success, warning, danger, info, accent
    'icon' => null,
])

@php
$variantClasses = [
    'default' => 'bg-white/10 text-white',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800',
    'info' => 'bg-blue-100 text-blue-800',
    'accent' => 'bg-racine-orange text-racine-black',
    'racine' => 'bg-racine-orange text-racine-black',
    'orange' => 'bg-racine-orange text-racine-black',
    'yellow' => 'bg-racine-yellow text-racine-black',
];

$classes = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . $variantClasses[$variant];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="{{ $icon }} mr-1.5"></i>
    @endif
    {{ $slot }}
</span>
