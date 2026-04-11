@props([
    'title' => 'Données',
    'searchable' => true,
    'searchPlaceholder' => 'Rechercher...',
    'createRoute' => null,
    'createLabel' => 'Nouveau'
])

<div class="table-container">
    <div class="table-header">
        <h5 class="table-title">{{ $title }}</h5>
        <div class="d-flex align-items-center gap-3" style="gap: 1rem;">
            @if($searchable)
            <div class="table-search">
                <span class="icon-search" style="color: #6B7280;"></span>
                <input type="text" placeholder="{{ $searchPlaceholder }}" class="table-search-input">
            </div>
            @endif
            @if($createRoute)
            <a href="{{ $createRoute }}" class="btn btn-primary btn-sm">
                + {{ $createLabel }}
            </a>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        {{ $slot }}
    </div>
</div>

