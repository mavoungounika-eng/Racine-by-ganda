@extends('layouts.admin')

@section('title', 'Gestion des Plans - Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Plans d'Abonnement Créateur</h1>
        <a href="{{ route('admin.subscriptions.plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Nouveau Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code</th>
                            <th>Prix</th>
                            <th>Abonnés</th>
                            <th>Stripe</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <strong>{{ $plan->name }}</strong>
                                    <br><small class="text-muted">{{ $plan->description }}</small>
                                </td>
                                <td><code>{{ $plan->code }}</code></td>
                                <td><strong>{{ number_format($plan->price, 0, ',', ' ') }} FCFA</strong></td>
                                <td>
                                    {{ $plan->subscriptions()->where('status', 'active')->count() }}
                                    <small class="text-muted">actifs</small>
                                </td>
                                <td>
                                    @if($plan->stripe_product_id)
                                        <i class="fas fa-check text-success" title="Configuré"></i>
                                    @else
                                        <i class="fas fa-times text-danger" title="Non configuré"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($plan->is_active)
                                        <span class="badge badge-success">Actif</span>
                                    @else
                                        <span class="badge badge-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.subscriptions.plans.edit', $plan) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.subscriptions.plans.toggle', $plan) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning" 
                                                    title="{{ $plan->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="fas fa-{{ $plan->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.subscriptions.plans.destroy', $plan) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce plan ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun plan d'abonnement</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
