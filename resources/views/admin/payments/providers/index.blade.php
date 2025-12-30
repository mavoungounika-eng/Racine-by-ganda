@extends('layouts.admin-master')

@section('title', 'Providers - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Gestion des Providers')
@section('page-subtitle', 'Configuration et pilotage des fournisseurs de paiement')

@section('content')

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-server text-racine-orange me-2"></i>
                    Providers de paiement
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Code</th>
                                <th>Actif</th>
                                <th>Configuration</th>
                                <th>Santé</th>
                                <th>Priorité</th>
                                <th>Devise</th>
                                <th>Dernier événement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $provider)
                                @php
                                    $configStatus = app(\App\Services\Payments\ProviderConfigStatusService::class)->checkConfigStatus($provider->code);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $provider->name }}</strong>
                                    </td>
                                    <td>
                                        <code>{{ $provider->code }}</code>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.payments.providers.update', $provider) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_enabled" value="{{ $provider->is_enabled ? '0' : '1' }}">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input" 
                                                       id="toggle-{{ $provider->id }}"
                                                       {{ $provider->is_enabled ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                                <label class="custom-control-label" for="toggle-{{ $provider->id }}"></label>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        @if($configStatus['status'] === 'ok')
                                            <span class="badge badge-success">OK</span>
                                        @else
                                            <span class="badge badge-danger">KO</span>
                                            @if(!empty($configStatus['missing_keys']))
                                                <small class="d-block text-muted mt-1">
                                                    Manque: {{ implode(', ', $configStatus['missing_keys']) }}
                                                </small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($provider->health_status === 'ok')
                                            <span class="badge badge-success">OK</span>
                                        @elseif($provider->health_status === 'degraded')
                                            <span class="badge badge-warning">Dégradé</span>
                                        @elseif($provider->health_status === 'down')
                                            <span class="badge badge-danger">Down</span>
                                        @else
                                            <span class="badge badge-secondary">Inconnu</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.payments.providers.update', $provider) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" 
                                                   name="priority" 
                                                   value="{{ $provider->priority }}" 
                                                   min="0"
                                                   class="form-control form-control-sm d-inline-block" 
                                                   style="width: 80px;"
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $provider->currency }}</span>
                                    </td>
                                    <td>
                                        @if($provider->last_event_at)
                                            <small>{{ $provider->last_event_at->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Jamais</small>
                                        @endif
                                    </td>
                                    <td>
                                        @can('payments.config')
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editProvider({{ $provider->id }})"
                                                    title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Aucun provider configuré</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal d'édition (si nécessaire) --}}
<div class="modal fade" id="editProviderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le provider</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editProviderForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_currency">Devise</label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_currency" 
                               name="currency" 
                               maxlength="3"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function editProvider(providerId) {
        // Récupérer les données du provider via AJAX si nécessaire
        // Pour l'instant, on peut simplement ouvrir le modal avec les données existantes
        $('#editProviderForm').attr('action', '{{ route("admin.payments.providers.update", ":id") }}'.replace(':id', providerId));
        $('#editProviderModal').modal('show');
    }
</script>
@endpush

@endsection




