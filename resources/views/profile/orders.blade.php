@extends('layouts.internal')

@section('title', 'Mes Commandes - RACINE BY GANDA')
@section('page-title', 'Mes Commandes')
@section('page-subtitle', 'Historique et suivi de vos commandes')

@section('content')

@php
    $uid  = auth()->id();
    $base = \App\Models\Order::where('user_id', $uid);
    $totalAllOrders = (clone $base)->count();
    $totalSpent     = (clone $base)->where('payment_status', 'paid')->sum('total_amount');
    $activeOrder    = (clone $base)->whereIn('status', ['pending', 'processing'])->latest()->first();
    $countEnCours   = (clone $base)->whereIn('status', ['pending', 'processing'])->count();
    $countTerminees = (clone $base)->whereIn('status', ['completed', 'delivered'])->count();
    $countAnnulees  = (clone $base)->where('status', 'cancelled')->count();
@endphp

<div class="row">
    <div class="col-12">

        {{-- HEADER STATS --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card stat-card--orange">
                    <div class="stat-icon stat-icon--orange">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $totalAllOrders }}</div>
                        <div class="stat-label">Commande{{ $totalAllOrders > 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card stat-card--green">
                    <div class="stat-icon stat-icon--green">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <div class="stat-value" style="font-size:1.2rem;">{{ number_format($totalSpent, 0, ',', ' ') }}</div>
                        <div class="stat-label">FCFA dépensés</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                @if($activeOrder)
                    <a href="{{ route('profile.orders.show', $activeOrder) }}" style="text-decoration:none;display:block;">
                        <div class="stat-card stat-card--yellow stat-card--link">
                            <div class="stat-icon stat-icon--yellow">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="stat-value" style="font-size:0.95rem;">Commande #{{ $activeOrder->id }}</div>
                                <div class="stat-label">En cours</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="color:#FFB800;font-size:0.75rem;flex-shrink:0;"></i>
                        </div>
                    </a>
                @else
                    <div class="stat-card stat-card--muted">
                        <div class="stat-icon stat-icon--green" style="opacity:0.5;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div style="opacity:0.55;">
                            <div class="stat-value" style="font-size:0.95rem;">Aucune en cours</div>
                            <div class="stat-label">Tout est livré</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- TABS --}}
        <div class="orders-tabs-wrap mb-3">
            <ul class="orders-tabs">
                <li>
                    <a class="orders-tab {{ $statusFilter === 'toutes' ? 'active' : '' }}"
                       href="{{ route('profile.orders') }}">
                        <i class="fas fa-list"></i>
                        <span class="tab-text">Toutes</span>
                        @if($totalAllOrders > 0)
                            <span class="tab-badge {{ $statusFilter === 'toutes' ? 'tab-badge--orange' : '' }}">{{ $totalAllOrders }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    <a class="orders-tab {{ $statusFilter === 'en-cours' ? 'active' : '' }}"
                       href="{{ route('profile.orders', ['status' => 'en-cours']) }}">
                        <i class="fas fa-clock"></i>
                        <span class="tab-text">En cours</span>
                        @if($countEnCours > 0)
                            <span class="tab-badge {{ $statusFilter === 'en-cours' ? 'tab-badge--orange' : '' }}">{{ $countEnCours }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    <a class="orders-tab {{ $statusFilter === 'terminees' ? 'active' : '' }}"
                       href="{{ route('profile.orders', ['status' => 'terminees']) }}">
                        <i class="fas fa-check-circle"></i>
                        <span class="tab-text">Terminées</span>
                        @if($countTerminees > 0)
                            <span class="tab-badge {{ $statusFilter === 'terminees' ? 'tab-badge--orange' : '' }}">{{ $countTerminees }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    <a class="orders-tab {{ $statusFilter === 'annulees' ? 'active orders-tab--cancelled' : '' }}"
                       href="{{ route('profile.orders', ['status' => 'annulees']) }}">
                        <i class="fas fa-ban"></i>
                        <span class="tab-text">Annulées</span>
                        @if($countAnnulees > 0)
                            <span class="tab-badge {{ $statusFilter === 'annulees' ? 'tab-badge--red' : '' }}">{{ $countAnnulees }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>

        {{-- TABLEAU --}}
        @if($orders->count() > 0)
        <div class="orders-card">
            <div class="table-responsive">
                <table class="table orders-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:4px;padding:0;"></th>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th class="d-none d-md-table-cell">Articles</th>
                            <th>Montant</th>
                            <th>Statut &amp; Paiement</th>
                            <th style="width:32px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        @php
                            $sc = [
                                'pending'    => ['label' => 'En attente',    'color' => '#FFB800', 'bg' => 'rgba(255,184,0,0.12)'],
                                'processing' => ['label' => 'En traitement', 'color' => '#FFB800', 'bg' => 'rgba(255,184,0,0.12)'],
                                'paid'       => ['label' => 'Payée',          'color' => '#ED5F1E', 'bg' => 'rgba(237,95,30,0.12)'],
                                'shipped'    => ['label' => 'Expédiée',       'color' => '#ED5F1E', 'bg' => 'rgba(237,95,30,0.12)'],
                                'completed'  => ['label' => 'Complétée',      'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.12)'],
                                'delivered'  => ['label' => 'Livrée',         'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.12)'],
                                'cancelled'  => ['label' => 'Annulée',        'color' => '#DC2626', 'bg' => 'rgba(220,38,38,0.12)'],
                                'failed'     => ['label' => 'Échouée',        'color' => '#DC2626', 'bg' => 'rgba(220,38,38,0.12)'],
                            ];
                            $st = $sc[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#160D0C', 'bg' => 'rgba(22,13,12,0.1)'];
                        @endphp
                        <tr class="orders-row {{ $order->status === 'cancelled' ? 'orders-row--cancelled' : '' }}"
                            @if($order->status !== 'cancelled')
                            onclick="window.location='{{ route('profile.orders.show', $order) }}'"
                            onkeydown="if(event.key==='Enter')window.location='{{ route('profile.orders.show', $order) }}'"
                            @else
                            data-order-id="{{ $order->id }}"
                            data-order-status="cancelled"
                            data-order-url="{{ route('profile.orders.show', $order) }}"
                            data-restore-url="{{ route('orders.restore', $order) }}"
                            data-archive-url="{{ route('orders.archive', $order) }}"
                            @endif
                            tabindex="0">
                            {{-- Pastille statut --}}
                            <td style="padding:0;vertical-align:middle;">
                                <div style="width:4px;min-height:60px;background:{{ $st['color'] }};border-radius:3px 0 0 3px;"></div>
                            </td>
                            <td style="vertical-align:middle;padding:1rem 1rem 1rem 0.75rem;">
                                <strong style="color:#160D0C;font-size:1rem;">#{{ $order->id }}</strong>
                            </td>
                            <td style="vertical-align:middle;padding:1rem;">
                                <div style="font-weight:500;color:#160D0C;white-space:nowrap;">{{ $order->created_at->format('d/m/Y') }}</div>
                                <small style="color:rgba(22,13,12,0.4);">{{ $order->created_at->format('H:i') }}</small>
                            </td>
                            <td class="d-none d-md-table-cell" style="vertical-align:middle;padding:1rem;">
                                <span style="font-weight:500;color:#160D0C;">{{ $order->items->count() }} article{{ $order->items->count() > 1 ? 's' : '' }}</span>
                                @if($order->items->count() > 0 && $order->items->first()->product)
                                    <br><small style="color:rgba(22,13,12,0.4);">{{ \Illuminate\Support\Str::limit($order->items->first()->product->title ?? '', 28) }}</small>
                                    @if($order->items->count() > 1)
                                        <small style="color:rgba(22,13,12,0.35);"> +{{ $order->items->count() - 1 }}</small>
                                    @endif
                                @endif
                            </td>
                            <td style="vertical-align:middle;padding:1rem;white-space:nowrap;">
                                @php $dispAmt = ($order->cancellation_type === 'global' && $order->original_total) ? $order->original_total : $order->total_amount; @endphp
                                <strong style="color:#ED5F1E;font-size:1rem;">{{ number_format($dispAmt ?? 0, 0, ',', ' ') }} FCFA</strong>
                            </td>
                            <td style="vertical-align:middle;padding:1rem;">
                                <span class="status-pill" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};border:1px solid {{ $st['color'] }}30;">
                                    {{ $st['label'] }}
                                </span>
                                <div style="margin-top:5px;">
                                    @if($order->status === 'cancelled')
                                        <span class="payment-pill payment-pill--red"><i class="fas fa-ban me-1"></i>Annulée</span>
                                    @elseif($order->payment_status === 'paid')
                                        <span class="payment-pill payment-pill--green"><i class="fas fa-check-circle me-1"></i>Payé</span>
                                    @elseif($order->payment_status === 'pending')
                                        <span class="payment-pill payment-pill--yellow"><i class="fas fa-clock me-1"></i>En attente</span>
                                    @else
                                        <span class="payment-pill payment-pill--red"><i class="fas fa-times-circle me-1"></i>Échoué</span>
                                    @endif
                                </div>
                            </td>
                            <td style="vertical-align:middle;padding:0.75rem 1rem 0.75rem 0;text-align:right;white-space:nowrap;">
                                @if($order->status === 'cancelled')
                                    <form action="{{ route('orders.restore', $order) }}" method="POST" class="d-inline" onclick="event.stopPropagation()">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-restore-inline">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                @else
                                    <i class="fas fa-chevron-right orders-arrow"></i>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div style="padding:1.25rem 1.5rem;border-top:1px solid rgba(22,13,12,0.07);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;background:#fafaf9;border-radius:0 0 16px 16px;">
                <small style="color:rgba(22,13,12,0.4);">
                    {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} sur {{ $orders->total() }} commande{{ $orders->total() > 1 ? 's' : '' }}
                </small>
                {{ $orders->links() }}
            </div>
        </div>

        @else

        {{-- ÉTAT VIDE --}}
        <div class="orders-card" style="text-align:center;padding:3rem 2rem;">
            <div style="width:96px;height:96px;background:linear-gradient(135deg,rgba(237,95,30,0.08),rgba(255,184,0,0.08));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                <i class="fas fa-shopping-bag" style="font-size:2.5rem;color:#ED5F1E;"></i>
            </div>
            <h5 style="color:#160D0C;font-weight:700;margin-bottom:0.75rem;">Aucune commande</h5>
            <p style="color:rgba(22,13,12,0.5);margin-bottom:1.75rem;font-size:0.95rem;">
                @if($statusFilter === 'en-cours') Aucune commande en cours pour le moment.
                @elseif($statusFilter === 'terminees') Aucune commande terminée pour le moment.
                @elseif($statusFilter === 'annulees') Aucune commande annulée.
                @else Vous n'avez pas encore passé de commande.
                @endif
            </p>
            <a href="{{ route('frontend.shop') }}" class="btn" style="background:linear-gradient(135deg,#ED5F1E,#d45519);color:#fff;border-radius:12px;padding:0.75rem 2rem;font-weight:600;box-shadow:0 4px 12px rgba(237,95,30,0.25);border:none;">
                <i class="fas fa-store me-2"></i>Découvrir la boutique
            </a>
        </div>

        @endif
    </div>
</div>

<style nonce="{{ csp_nonce() }}">
    /* ── Stat cards ── */
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 1.125rem 1.375rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 1px solid rgba(22,13,12,0.1);
        transition: box-shadow 0.2s;
    }
    .stat-card--orange { border-color: rgba(237,95,30,0.2); }
    .stat-card--green  { border-color: rgba(34,197,94,0.2); }
    .stat-card--yellow { border-color: rgba(255,184,0,0.25); }
    .stat-card--muted  { border-color: rgba(22,13,12,0.08); }
    .stat-card--link:hover { box-shadow: 0 4px 16px rgba(22,13,12,0.08); cursor: pointer; }
    .stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1.05rem;
    }
    .stat-icon--orange { background: rgba(237,95,30,0.08); color: #ED5F1E; }
    .stat-icon--green  { background: rgba(34,197,94,0.08);  color: #22C55E; }
    .stat-icon--yellow { background: rgba(255,184,0,0.1);   color: #FFB800; }
    .stat-value { font-size: 1.4rem; font-weight: 700; color: #160D0C; line-height: 1.1; }
    .stat-label { font-size: 0.74rem; color: rgba(22,13,12,0.45); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-top: 3px; }

    /* ── Tabs ── */
    .orders-tabs-wrap {
        background: #fff;
        border: 1px solid rgba(22,13,12,0.1);
        border-radius: 14px;
        overflow: hidden;
    }
    .orders-tabs {
        list-style: none;
        margin: 0;
        padding: 0 0.5rem;
        display: flex;
        gap: 0;
        border-bottom: 2px solid rgba(22,13,12,0.07);
    }
    .orders-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.875rem 1.125rem;
        font-size: 0.88rem;
        font-weight: 500;
        color: rgba(22,13,12,0.5);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: color 0.2s, border-color 0.2s, background 0.2s;
        white-space: nowrap;
        cursor: pointer;
    }
    .orders-tab:hover {
        color: #ED5F1E;
        background: rgba(237,95,30,0.03);
    }
    .orders-tab.active {
        color: #ED5F1E;
        font-weight: 700;
        border-bottom-color: #ED5F1E;
    }
    .orders-tab--cancelled.active {
        color: #DC2626;
        border-bottom-color: #DC2626;
    }
    .tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        background: rgba(22,13,12,0.08);
        color: rgba(22,13,12,0.45);
    }
    .tab-badge--orange { background: #ED5F1E; color: #fff; }
    .tab-badge--red    { background: #DC2626; color: #fff; }

    /* ── Table ── */
    .orders-card {
        background: #fff;
        border: 1px solid rgba(22,13,12,0.1);
        border-radius: 16px;
        overflow: hidden;
    }
    .orders-table thead tr { background: rgba(22,13,12,0.025); }
    .orders-table thead th {
        padding: 0.875rem 1rem;
        font-weight: 700;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(22,13,12,0.4);
        border-bottom: 2px solid rgba(22,13,12,0.08);
        border-top: none;
        white-space: nowrap;
    }
    .status-pill {
        display: inline-block;
        padding: 0.3rem 0.65rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .payment-pill {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 5px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .payment-pill--green  { background: rgba(34,197,94,0.08);  color: #22C55E; border: 1px solid rgba(34,197,94,0.2); }
    .payment-pill--yellow { background: rgba(255,184,0,0.08);  color: #B45309; border: 1px solid rgba(255,184,0,0.2); }
    .payment-pill--red    { background: rgba(220,38,38,0.08);  color: #DC2626; border: 1px solid rgba(220,38,38,0.2); }
    .orders-row {
        border-bottom: 1px solid rgba(22,13,12,0.06);
        cursor: pointer;
        transition: background 0.15s;
    }
    .orders-row:last-child { border-bottom: none; }
    .orders-row:hover { background: rgba(237,95,30,0.022) !important; }
    .orders-row:focus { outline: 2px solid #ED5F1E; outline-offset: -2px; }
    .orders-arrow {
        color: rgba(22,13,12,0.2);
        font-size: 0.78rem;
        transition: color 0.15s, transform 0.15s;
    }
    .orders-row:hover .orders-arrow {
        color: #ED5F1E;
        transform: translateX(2px);
    }

    .orders-row--selected {
        background: rgba(237,95,30,0.04) !important;
        outline: 2px solid #ED5F1E;
        outline-offset: -2px;
    }
    .btn-restore-inline {
        background: rgba(237,95,30,0.08);
        color: #ED5F1E;
        border: 1px solid rgba(237,95,30,0.25);
        border-radius: 8px;
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-restore-inline:hover {
        background: #ED5F1E;
        color: #fff;
    }

    /* ── Mobile ── */
    @media (max-width: 575px) {
        .orders-tab { padding: 0.75rem 0.875rem; font-size: 0.8rem; }
        .tab-text { display: none; }
        .stat-value { font-size: 1.2rem; }
    }
</style>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Commandes', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
{{-- BARRE D'ACTIONS FLOTTANTE — commandes annulées --}}
<div id="cancelled-action-bar" class="d-none position-fixed bottom-0 start-0 end-0 py-3 px-4"
     style="background:#160D0C;z-index:1050;border-top:2px solid #ED5F1E;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-white small">1 commande sélectionnée</span>
        <div class="d-flex gap-2">
            <a id="bar-btn-detail" href="#" class="btn btn-sm fw-semibold" style="background:#FFB800;color:#160D0C;">
                <i class="fas fa-eye me-1"></i>Voir le détail
            </a>
            <button id="bar-btn-restore" class="btn btn-sm btn-outline-light fw-semibold">
                <i class="fas fa-undo me-1"></i>Restaurer
            </button>
            <button id="bar-btn-archive" class="btn btn-sm fw-semibold" style="background:#ED5F1E;color:#fff;">
                <i class="fas fa-archive me-1"></i>Archiver
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bar = document.getElementById('cancelled-action-bar');
    let selectedRow = null;

    document.querySelectorAll('tr[data-order-status="cancelled"]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            if (selectedRow === row) {
                row.classList.remove('orders-row--selected');
                selectedRow = null;
                bar.classList.add('d-none');
                return;
            }
            if (selectedRow) selectedRow.classList.remove('orders-row--selected');
            row.classList.add('orders-row--selected');
            selectedRow = row;

            document.getElementById('bar-btn-detail').href = row.dataset.orderUrl;
            document.getElementById('bar-btn-restore').onclick = () => submitAction('PATCH', row.dataset.restoreUrl);
            document.getElementById('bar-btn-archive').onclick = () => submitAction('DELETE', row.dataset.archiveUrl);
            bar.classList.remove('d-none');
        });
    });

    function submitAction(method, action) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = action;
        f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                    + '<input type="hidden" name="_method" value="' + method + '">';
        document.body.appendChild(f);
        f.submit();
    }
});
</script>
@endpush

@endsection