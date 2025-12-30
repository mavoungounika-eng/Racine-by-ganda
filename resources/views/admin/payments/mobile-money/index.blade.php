@extends('layouts.admin')

@section('title', 'Transactions Mobile Money - Admin')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Transactions Mobile Money</h1>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Transactions</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-left-success">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Actives</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-left-info">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Montant Total</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['total_amount'], 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Filtrer :</label>
                <select name="status" class="form-control mr-2" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Annulés</option>
                </select>
                
                <input type="text" name="search" class="form-control mr-2" 
                       placeholder="Rechercher..." value="{{ request('search') }}">
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Liste des transactions --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Créateur</th>
                            <th>Plan</th>
                            <th>Montant</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            @php
                                $creator = $transaction->creatorProfile->user;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $creator->name }}</strong>
                                    <br><small class="text-muted">{{ $creator->email }}</small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->plan->name }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($transaction->plan->price, 0, ',', ' ') }} FCFA</strong>
                                </td>
                                <td>
                                    <small>
                                        Du {{ $transaction->current_period_start?->format('d/m/Y') }}
                                        <br>Au {{ $transaction->current_period_end?->format('d/m/Y') }}
                                    </small>
                                </td>
                                <td>
                                    @if($transaction->status === 'active')
                                        <span class="badge badge-success">Actif</span>
                                    @elseif($transaction->status === 'canceled')
                                        <span class="badge badge-danger">Annulé</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($transaction->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.mobile-money.show', $transaction) }}" 
                                           class="btn btn-outline-primary" title="Détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($transaction->status !== 'active')
                                            <form action="{{ route('admin.mobile-money.validate', $transaction) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Valider">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($transaction->status === 'active')
                                            <form action="{{ route('admin.mobile-money.reject', $transaction) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette transaction ?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" title="Rejeter">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune transaction Mobile Money</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
