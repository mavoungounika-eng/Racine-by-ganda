{{--
    Composant Modal Premium avec Alpine.js
    
    Usage:
    <x-modal name="deleteModal" title="Confirmer la suppression">
        <p>Êtes-vous sûr de vouloir supprimer cet élément ?</p>
        
        <x-slot name="footer">
            <x-button variant="outline" @click="$dispatch('close-modal', 'deleteModal')">
                Annuler
            </x-button>
            <x-button variant="danger" type="submit">
                Supprimer
            </x-button>
        </x-slot>
    </x-modal>
    
    Pour ouvrir: @click="$dispatch('open-modal', 'deleteModal')"
    Pour fermer: @click="$dispatch('close-modal', 'deleteModal')"
    
    Props:
    - name: string (required) - Identifiant unique du modal
    - title: string (optional)
    - size: string (optional) - sm, md, lg, xl (default: md)
--}}

@props([
    'name',
    'title' => '',
    'size' => 'md'
])

@php
$sizeClasses = [
    'sm' => 'max-w-md',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
];
@endphp

<div 
    x-data="{ show: false }" 
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    {{-- Overlay --}}
    <div 
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"
    ></div>

    {{-- Modal Container --}}
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div 
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            @click.away="show = false"
            class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full {{ $sizeClasses[$size] }}"
        >
            {{-- Header --}}
            @if($title)
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <button 
                    @click="show = false"
                    class="h-8 w-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-center"
                >
                    <i class="fas fa-times text-gray-500 dark:text-gray-400"></i>
                </button>
            </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @isset($footer)
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $footer }}
            </div>
            @endisset
        </div>
    </div>
</div>
