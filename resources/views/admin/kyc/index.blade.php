@extends('layouts.admin')

@section('title', 'Dashboard KYC - Admin')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Dashboard KYC Créateurs</h1>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Complets</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['complete'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En Attente</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Incomplets</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['incomplete'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Filtrer par statut :</label>
                <select name="status" class="form-control mr-2" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="complete" {{ request('status') === 'complete' ? 'selected' : '' }}>Complets</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Incomplets</option>
                </select>
            </form>
        </div>
    </div>

    {{-- Liste des comptes --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Créateur</th>
                            <th>Email</th>
                            <th>Onboarding</th>
                            <th>Documents</th>
                            <th>Paiements</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            @php
                                $creator = $account->creatorProfile->user;
                                $isComplete = $account->onboarding_status === 'complete' && $account->payouts_enabled;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $creator->name }}</strong>
                                    <br><small class="text-muted">{{ $account->creatorProfile->brand_name }}</small>
                                </td>
                                <td>{{ $creator->email }}</td>
                                <td>
                                    @if($account->onboarding_status === 'complete')
                                        <span class="badge badge-success">Complet</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($account->onboarding_status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->details_submitted)
                                        <i class="fas fa-check text-success"></i> Soumis
                                    @else
                                        <i class="fas fa-times text-danger"></i> Manquants
                                    @endif
                                </td>
                                <td>
                                    @if($account->payouts_enabled)
                                        <i class="fas fa-check text-success"></i> Activés
                                    @else
                                        <i class="fas fa-times text-danger"></i> Désactivés
                                    @endif
                                </td>
                                <td>
                                    @if($isComplete)
                                        <span class="badge badge-success">✓ Vérifié</span>
                                    @elseif($account->details_submitted)
                                        <span class="badge badge-warning">⏳ En cours</span>
                                    @else
                                        <span class="badge badge-danger">✗ Incomplet</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.kyc.show', $creator) }}" 
                                           class="btn btn-outline-primary" title="Détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.kyc.sync', $creator) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-info" title="Synchroniser">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun compte Stripe Connect</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $accounts->links() }}
        </div>
    </div>
</div>
@endsection
