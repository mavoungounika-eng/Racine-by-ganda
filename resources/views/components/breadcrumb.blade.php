@props(['items' => []])
@if(count($items) > 0)
<nav aria-label="Fil d'Ariane" class="mb-4" style="margin-bottom:1rem;">
    <ol style="display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem; list-style:none; padding:0; margin:0; font-size:0.875rem;">
        @foreach($items as $index => $item)
            <li style="display:flex; align-items:center;">
                @if($item['url'])
                    <a href="{{ $item['url'] }}" style="color:#6b7280; text-decoration:none; display:flex; align-items:center; gap:0.25rem;">
                        @if($index === 0)
                            <i class="fas fa-home"></i>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </a>
                @else
                    <span style="color:#ED5F1E; font-weight:500; display:flex; align-items:center; gap:0.25rem;">
                        @if($index === 0)
                            <i class="fas fa-home"></i>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </span>
                @endif
                @if($index < count($items) - 1)
                    <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.65rem; margin:0 0.5rem;"></i>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
