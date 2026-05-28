@props([
    'icon'        => null,
    'emoji'       => '📭',
    'title'       => 'Aucune donnée',
    'description' => 'Commencez par ajouter des éléments.',
    'actionRoute' => null,
    'actionLabel' => 'Commencer',
    'actionIcon'  => 'fas fa-plus',
])

<div class="text-center py-5 px-3">
    <div style="margin-bottom:1rem;">
        @if($icon)
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(237,95,30,.1),rgba(255,184,0,.1));display:flex;align-items:center;justify-content:center;margin:0 auto;">
                <i class="{{ $icon }}" style="font-size:1.75rem;color:#ED5F1E;"></i>
            </div>
        @else
            <div style="font-size:3rem;opacity:.45;">{{ $emoji }}</div>
        @endif
    </div>

    <h5 style="color:#160D0C;font-weight:700;margin:0 0 .5rem;">{{ $title }}</h5>
    <p style="color:#888;font-size:.88rem;max-width:360px;margin:0 auto 1.5rem;line-height:1.6;">{{ $description }}</p>

    @if($slot->isNotEmpty())
        {{ $slot }}
    @elseif($actionRoute)
        <a href="{{ $actionRoute }}"
           style="display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.4rem;background:#ED5F1E;color:white;border-radius:.6rem;text-decoration:none;font-weight:600;font-size:.875rem;">
            <i class="{{ $actionIcon }}"></i> {{ $actionLabel }}
        </a>
    @endif
</div>
