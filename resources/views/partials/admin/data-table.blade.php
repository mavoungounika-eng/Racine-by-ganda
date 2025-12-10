{{-- Composant Tableau de Données --}}
@props(['items', 'columns', 'actions' => [], 'emptyMessage' => 'Aucun élément trouvé', 'emptyIcon' => 'fa-inbox'])

<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        @foreach($columns as $column)
                            <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem;">
                                @if(isset($column['icon']))
                                    <i class="{{ $column['icon'] }} me-2"></i>
                                @endif
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                        @if(count($actions) > 0)
                            <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem;">
                                Actions
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr style="transition: all 0.2s;">
                            @foreach($columns as $column)
                                <td style="padding: 1.25rem 1rem; vertical-align: middle;">
                                    @if(isset($column['slot']))
                                        {{ ${$column['slot']} }}
                                    @elseif(isset($column['render']))
                                        {!! $column['render']($item) !!}
                                    @else
                                        {{ $item->{$column['field']} ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                            @if(count($actions) > 0)
                                <td class="text-end" style="padding: 1.25rem 1rem;">
                                    <div class="btn-group" role="group">
                                        @foreach($actions as $action)
                                            @if(isset($action['condition']) && !$action['condition']($item))
                                                @continue
                                            @endif
                                            
                                            @if($action['type'] === 'link')
                                                <a href="{{ $action['url']($item) }}" 
                                                   class="btn btn-sm {{ $action['class'] ?? 'btn-outline-primary' }}"
                                                   title="{{ $action['title'] ?? '' }}">
                                                    <i class="{{ $action['icon'] ?? '' }}"></i>
                                                    @if(isset($action['label']))
                                                        <span class="d-none d-md-inline ms-1">{{ $action['label'] }}</span>
                                                    @endif
                                                </a>
                                            @elseif($action['type'] === 'form')
                                                <form action="{{ $action['url']($item) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ $action['confirm'] ?? 'Êtes-vous sûr ?' }}');">
                                                    @csrf
                                                    @method($action['method'] ?? 'DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $action['class'] ?? 'btn-outline-danger' }}"
                                                            title="{{ $action['title'] ?? '' }}">
                                                        <i class="{{ $action['icon'] ?? '' }}"></i>
                                                        @if(isset($action['label']))
                                                            <span class="d-none d-md-inline ms-1">{{ $action['label'] }}</span>
                                                        @endif
                                                    </button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + (count($actions) > 0 ? 1 : 0) }}" class="text-center py-5">
                                <div class="py-4">
                                    <i class="fas {{ $emptyIcon }} fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">{{ $emptyMessage }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(method_exists($items, 'hasPages') && $items->hasPages())
            <div class="card-footer bg-transparent border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Affichage de {{ $items->firstItem() ?? 0 }} à {{ $items->lastItem() ?? 0 }} sur {{ $items->total() }} résultats
                    </div>
                    <div>
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

