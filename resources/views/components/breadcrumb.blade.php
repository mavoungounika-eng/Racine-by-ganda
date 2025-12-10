{{--
    Composant Breadcrumb (Fil d'Ariane) Premium
    
    Usage:
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Créateurs', 'url' => route('frontend.creators')],
        ['label' => 'Profil Créateur', 'url' => null]
    ]" />
    
    Props:
    - items: array (required) - Format: [['label' => 'Text', 'url' => 'https://...'], ...]
      Si url est null, l'élément est considéré comme actif (dernier élément)
--}}

@props(['items' => []])

@if(count($items) > 0)
<nav aria-label="Fil d'Ariane" class="mb-6">
    <ol class="flex items-center flex-wrap gap-2 text-sm">
        @foreach($items as $index => $item)
            <li class="flex items-center">
                @if($item['url'])
                    {{-- Lien cliquable --}}
                    <a href="{{ $item['url'] }}" class="text-gray-600 hover:text-accent transition flex items-center gap-2">
                        @if($index === 0)
                            <i class="fas fa-home"></i>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </a>
                @else
                    {{-- Élément actif (dernier) --}}
                    <span class="text-primary font-medium flex items-center gap-2">
                        @if($index === 0)
                            <i class="fas fa-home"></i>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </span>
                @endif

                {{-- Séparateur (sauf pour le dernier élément) --}}
                @if($index < count($items) - 1)
                    <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
