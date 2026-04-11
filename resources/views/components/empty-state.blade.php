@props([
    'icon' => '📭',
    'title' => 'Aucune donnée',
    'description' => 'Commencez par ajouter des éléments.',
    'actionRoute' => null,
    'actionLabel' => 'Commencer'
])

<div class="text-center py-5">
    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">
        {{ $icon }}
    </div>
    <h4 style="color: #11001F; font-family: 'Playfair Display', serif;">{{ $title }}</h4>
    <p style="color: #6B7280; max-width: 400px; margin: 0 auto 1.5rem;">{{ $description }}</p>
    @if($actionRoute)
    <a href="{{ $actionRoute }}" class="btn btn-primary">
        {{ $actionLabel }}
    </a>
    @endif
</div>

