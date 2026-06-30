@props([
    'title'       => 'Page',
    'subtitle'    => null,
    'breadcrumbs' => [],
    'actions'     => null,
])

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:1rem;">
    <div>
        @if(count($breadcrumbs) > 0)
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb bg-transparent p-0 mb-2" style="font-size:.8rem;margin:0 0 .5rem;">
                @foreach($breadcrumbs as $label => $url)
                    @if($loop->last)
                        <li class="breadcrumb-item active" style="color:#ED5F1E;font-weight:600;">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ $url }}" style="color:#888;text-decoration:none;">{{ $label }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
        @endif

        <h1 style="font-size:1.5rem;font-weight:700;color:#160D0C;margin:0;line-height:1.2;">{{ $title }}</h1>
        @if($subtitle)
        <p style="color:#777;margin:.25rem 0 0;font-size:.88rem;">{{ $subtitle }}</p>
        @endif
    </div>

    @if($actions)
    <div class="d-flex align-items-center flex-wrap" style="gap:.5rem;">
        {{ $actions }}
    </div>
    @endif
</div>
