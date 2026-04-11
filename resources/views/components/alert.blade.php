@props([
    'type' => 'info', // success, error, warning, info
    'dismissible' => false,
])

@php
$typeConfig = [
    'success' => [
        'bg' => 'bg-green-50',
        'border' => 'border-green-200',
        'icon' => 'fas fa-check-circle text-green-500',
        'text' => 'text-green-700',
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'icon' => 'fas fa-exclamation-circle text-red-500',
        'text' => 'text-red-700',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50',
        'border' => 'border-yellow-200',
        'icon' => 'fas fa-exclamation-triangle text-yellow-500',
        'text' => 'text-yellow-700',
    ],
    'info' => [
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-200',
        'icon' => 'fas fa-info-circle text-blue-500',
        'text' => 'text-blue-700',
    ],
];

$config = $typeConfig[$type];
$classes = 'p-4 border rounded-lg ' . $config['bg'] . ' ' . $config['border'];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} x-data="{ show: true }" x-show="show">
    <div class="flex items-start">
        <i class="{{ $config['icon'] }} mt-0.5 mr-3"></i>
        <div class="flex-1 {{ $config['text'] }}">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button @click="show = false" class="ml-3 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>
</div>
