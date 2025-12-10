@extends('layouts.internal')

@section('title', 'Points de Fidélité - RACINE BY GANDA')
@section('page-title', 'Programme de Fidélité')
@section('page-subtitle', 'Gérez vos points de fidélité')

@section('content')
<div class="row">
    <div class="col-md-4">
        @if($loyaltyPoint)
        <div class="card mb-4">
            <div class="card-body text-center">
                <h2 class="mb-3">{{ number_format($loyaltyPoint->points) }}</h2>
                <p class="text-muted mb-3">Points disponibles</p>
                
                @php
                    $tierColors = [
                        'bronze' => '#cd7f32',
                        'silver' => '#c0c0c0',
                        'gold' => '#ffd700',
                    ];
                    $tierNames = [
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                    ];
                @endphp
                
                <span class="badge mb-3" style="background: {{ $tierColors[$loyaltyPoint->tier] }}; color: white; padding: 0.75rem 1.5rem; font-size: 1rem;">
                    Niveau {{ $tierNames[$loyaltyPoint->tier] }}
                </span>

                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small>Total gagné</small>
                        <strong>{{ number_format($loyaltyPoint->total_earned) }} pts</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small>Total dépensé</small>
                        <strong>{{ number_format($loyaltyPoint->total_spent) }} pts</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Comment gagner des points ?</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="icon-check text-success mr-2"></i>
                        <strong>1%</strong> du montant de chaque commande payée
                    </li>
                    <li class="mb-2">
                        <i class="icon-check text-success mr-2"></i>
                        Points convertibles en réductions
                    </li>
                    <li>
                        <i class="icon-check text-success mr-2"></i>
                        Niveaux : Bronze → Silver → Gold
                    </li>
                </ul>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center">
                <i class="icon-star" style="font-size: 3rem; color: #ccc;"></i>
                <h5 class="mt-3">Aucun point</h5>
                <p class="text-muted">Commencez à acheter pour gagner des points !</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historique des transactions</h5>
            </div>
            <div class="card-body">
                @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Points</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    <strong class="{{ $transaction->points > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->points > 0 ? '+' : '' }}{{ $transaction->points }}
                                    </strong>
                                </td>
                                <td>
                                    @if($transaction->type === 'earned')
                                        <span class="badge badge-success">Gagné</span>
                                    @elseif($transaction->type === 'spent')
                                        <span class="badge badge-warning">Dépensé</span>
                                    @else
                                        <span class="badge badge-secondary">Expiré</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="icon-list" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3">Aucune transaction</h5>
                    <p class="text-muted">Vos transactions de points apparaîtront ici.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Fidélité', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection

