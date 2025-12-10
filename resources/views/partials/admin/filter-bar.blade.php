{{-- Composant Barre de Filtres --}}
@props(['route', 'search' => true, 'filters' => []])

<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $route }}" class="row g-3 align-items-end">
            @if($search)
                <div class="col-md-4">
                    <label for="search" class="form-label small text-muted mb-1">
                        <i class="fas fa-search me-1"></i> Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Rechercher..." 
                           class="form-control form-control-lg">
                </div>
            @endif

            @foreach($filters as $filter)
                <div class="col-md-{{ $filter['width'] ?? 3 }}">
                    <label for="{{ $filter['name'] }}" class="form-label small text-muted mb-1">
                        @if(isset($filter['icon']))
                            <i class="{{ $filter['icon'] }} me-1"></i>
                        @endif
                        {{ $filter['label'] }}
                    </label>
                    @if($filter['type'] === 'select')
                        <select name="{{ $filter['name'] }}" 
                                id="{{ $filter['name'] }}" 
                                class="form-select form-select-lg">
                            @foreach($filter['options'] as $option)
                                <option value="{{ $option['value'] }}" 
                                        {{ request($filter['name']) == $option['value'] ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($filter['type'] === 'date')
                        <input type="date" 
                               name="{{ $filter['name'] }}" 
                               id="{{ $filter['name'] }}"
                               value="{{ request($filter['name']) }}"
                               class="form-control form-control-lg">
                    @endif
                </div>
            @endforeach

            <div class="col-md-auto">
                <button type="submit" class="btn btn-racine-orange btn-lg w-100">
                    <i class="fas fa-filter me-2"></i>
                    Filtrer
                </button>
            </div>

            @if(request()->hasAny(array_merge(['search'], array_column($filters, 'name'))))
                <div class="col-md-auto">
                    <a href="{{ $route }}" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="fas fa-redo me-2"></i>
                        Réinitialiser
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

