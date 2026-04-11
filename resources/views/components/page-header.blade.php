@props([
    'title' => 'Page',
    'subtitle' => null,
    'breadcrumbs' => [],
    'actions' => null
])

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap: 1rem;">
    <div>
        @if(count($breadcrumbs) > 0)
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-2" style="font-size: 0.85rem;">
                @foreach($breadcrumbs as $label => $url)
                    @if($loop->last)
                        <li class="breadcrumb-item active" style="color: #6B7280;">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ $url }}" style="color: #4B1DF2;">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
        @endif
        <h1 style="font-family: 'Playfair Display', serif; font-size: 1.75rem; color: #11001F; margin: 0;">
            {{ $title }}
        </h1>
        @if($subtitle)
        <p style="color: #6B7280; margin: 0.25rem 0 0; font-size: 0.95rem;">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actions)
    <div class="d-flex align-items-center" style="gap: 0.75rem;">
        {{ $actions }}
    </div>
    @endif
</div>

