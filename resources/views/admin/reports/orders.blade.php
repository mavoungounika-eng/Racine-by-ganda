@extends('layouts.admin-master')

@section('title', 'Rapport Commandes - Admin')
@section('page-title', 'Rapport Commandes')
@section('page-subtitle', 'Liste détaillée des commandes filtrées')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <strong>{{ number_format($totalOrders, 0, ',', ' ') }}</strong> commande(s) —
        CA payé : <strong class="text-success">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</strong>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-outline-success">
            <i class="fas fa-download me-1"></i>CSV
        </a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour commandes
        </a>
    </div>
</div>

@if(!empty($filters) && array_filter($filters))
<div class="alert alert-light border mb-3 small">
    Filtres actifs :
    @foreach(array_filter($filters) as $key => $val)
        <span class="badge bg-secondary ms-1">{{ $key }} : {{ $val }}</span>
    @endforeach
</div>
@endif

<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th class="text-end">Total</th>
                        <th>Articles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                    #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>
                                <div>{{ $order->user?->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $order->user?->email }}</small>
                            </td>
                            <td class="small">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge bg-secondary small">{{ $order->status }}</span>
                            </td>
                            <td>
                                @if($order->payment_status === 'paid')
                                    <span class="badge bg-success small">Payé</span>
                                @elseif($order->payment_status === 'pending')
                                    <span class="badge bg-warning small">En attente</span>
                                @else
                                    <span class="badge bg-danger small">{{ $order->payment_status }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td class="small text-muted">{{ $order->items->count() }} article(s)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune commande pour ces filtres</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($orders->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Total payé :</td>
                        <td class="text-end text-success">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
