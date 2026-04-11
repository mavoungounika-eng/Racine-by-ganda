@extends('layouts.admin')

@section('title', 'Finances - RACINE BY GANDA')
@section('page_title', 'Finances')
@section('page_subtitle', 'Vue d\'ensemble financière')
@section('breadcrumb', 'Finances')

@section('content')

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card-racine">
            <div class="card-body">
                <div class="small text-muted mb-1">REVENUS TOTAUX</div>
                <div class="h4 mb-0">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-racine">
            <div class="card-body">
                <div class="small text-muted mb-1">REVENUS CE MOIS</div>
                <div class="h4 mb-0">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-racine">
            <div class="card-body">
                <div class="small text-muted mb-1">PAYOUTS EN ATTENTE</div>
                <div class="h4 mb-0 text-warning">{{ number_format($stats['pending_payouts'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-racine">
            <div class="card-body">
                <div class="small text-muted mb-1">COMMISSIONS PAYÉES</div>
                <div class="h4 mb-0 text-success">{{ number_format($stats['paid_commissions'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-6 mb-3">
        <div class="card-racine">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
                <h5 class="mb-0" style="font-family: var(--font-heading); font-size:1.1rem; font-weight:700;">Paiements récents</h5>
            </div>
            <div class="card-body p-0">
                @if($recentPayments->count())
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="small text-muted">
                            <tr>
                                <th>Date</th>
                                <th>Commande</th>
                                <th>Montant</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                                    <td>#{{ $payment->order_id }}</td>
                                    <td class="fw-bold text-success">{{ number_format($payment->amount ?? 0, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-receipt fa-2x mb-2"></i>
                        <p class="mb-0">Aucun paiement récent</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card-racine">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
                <h5 class="mb-0" style="font-family: var(--font-heading); font-size:1.1rem; font-weight:700;">Payouts en attente</h5>
            </div>
            <div class="card-body p-0">
                @if($pendingPayouts->count())
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="small text-muted">
                            <tr>
                                <th>Vendeur</th>
                                <th>Commande</th>
                                <th>Montant</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pendingPayouts as $payout)
                                <tr>
                                    <td>{{ $payout->vendor->name ?? 'Vendeur inconnu' }}</td>
                                    <td>#{{ $payout->order_id }}</td>
                                    <td class="fw-bold">{{ number_format($payout->vendor_payout ?? 0, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">Aucun payout en attente</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
