@extends('layouts.admin')

@section('title', 'Détail de la Commande #' . str_pad($order->id, 6, '0', STR_PAD_LEFT))
@section('page-title', 'Détail Commande #' . str_pad($order->id, 6, '0', STR_PAD_LEFT))

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        margin-bottom: 1.5rem;
    }
    
    .premium-table {
        width: 100%;
    }
    
    .premium-table thead {
        background: linear-gradient(135deg, rgba(18, 8, 6, 0.8) 0%, rgba(22, 13, 12, 0.6) 100%);
    }
    
    .premium-table th {
        padding: 1.25rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-bottom: 2px solid rgba(237, 95, 30, 0.2);
    }
    
    .premium-table td {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(212, 165, 116, 0.1);
        color: #e2e8f0;
    }
    
    .premium-table tbody tr:hover {
        background: rgba(237, 95, 30, 0.05);
    }
    
    .premium-select {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
        transition: all 0.3s;
    }
    
    .premium-select:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .premium-btn-secondary {
        background: rgba(51, 65, 85, 0.6);
        color: #e2e8f0;
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .premium-btn-secondary:hover {
        background: rgba(51, 65, 85, 0.8);
        border-color: rgba(212, 165, 116, 0.4);
        color: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-shopping-bag text-racine-orange me-2"></i>
            Commande #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
        </h2>
        <a href="{{ route('admin.orders.index') }}"
           class="premium-btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Détails de la commande -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Articles -->
            <div class="premium-card">
                <h3 class="text-xl font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-box text-racine-orange me-2"></i>
                    Articles commandés
                </h3>
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        @php
                            $adminStatuses = ['confirmed', 'shipped', 'delivered', 'refunded'];
                            $statusLabels = [
                                'active'           => 'Actif',
                                'confirmed'        => 'Confirmé',
                                'shipped'          => 'Expédié',
                                'delivered'        => 'Livré',
                                'cancelled'        => 'Annulé',
                                'restored'         => 'Restauré',
                                'disputed'         => 'Litige',
                                'return_requested' => 'Retour demandé',
                                'refunded'         => 'Remboursé',
                            ];
                        @endphp
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Statut</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            @php
                                $allowed = array_intersect(
                                    \App\Models\OrderItem::TRANSITIONS[$item->status] ?? [],
                                    $adminStatuses
                                );
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-4">
                                        @if($item->product && $item->product->main_image)
                                            <img class="h-16 w-16 rounded-xl object-cover border border-slate-700"
                                                 src="{{ asset('storage/' . $item->product->main_image) }}" alt="">
                                        @else
                                            <div class="h-16 w-16 rounded-xl bg-[#160D0C] border border-slate-700 flex items-center justify-center text-slate-600">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="font-semibold text-white">{{ $item->product ? $item->product->title : 'Produit supprimé' }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-slate-300">{{ number_format($item->price, 0, ',', ' ') }} F</td>
                                <td class="text-slate-300 font-semibold">{{ $item->quantity }}</td>
                                <td>
                                    @if(count($allowed) > 0)
                                        <select
                                            id="status-select-{{ $item->id }}"
                                            data-transition-url="{{ route('admin.orders.items.transition', [$order, $item]) }}"
                                            data-item-id="{{ $item->id }}"
                                            onchange="adminTransitionItem(this)"
                                            class="premium-select"
                                            style="padding:.4rem .7rem;font-size:.8rem;border-radius:8px;min-width:140px;">
                                            <option value="">{{ $statusLabels[$item->status] ?? $item->status }}</option>
                                            @foreach($allowed as $to)
                                                <option value="{{ $to }}">→ {{ $statusLabels[$to] ?? $to }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span id="status-badge-{{ $item->id }}"
                                              style="display:inline-block;padding:.25rem .6rem;border-radius:99px;font-size:.75rem;font-weight:600;background:rgba(148,163,184,.1);color:#94a3b8;border:1px solid rgba(148,163,184,.2);">
                                            {{ $statusLabels[$item->status] ?? $item->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <p class="font-bold text-racine-orange">{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} F</p>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-racine-orange/20">
                                <td colspan="4" class="py-4 text-end font-bold text-white text-lg">
                                    Total
                                </td>
                                <td class="py-4 text-end">
                                    <p class="text-2xl font-bold text-racine-orange" style="font-family: 'Playfair Display', serif;">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} F</p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Informations Client et Statut -->
        <div class="space-y-6">
            <!-- Statut -->
            <div class="premium-card">
                <h3 class="text-xl font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-sync-alt text-racine-orange me-2"></i>
                    Statut de la commande
                </h3>
                <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <select name="status" id="status"
                                class="premium-select w-full">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Payée</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Expédiée</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Terminée</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                        </select>
                    </div>
                    <button type="submit" class="premium-btn w-full">
                        <i class="fas fa-save"></i>
                        Mettre à jour
                    </button>
                </form>
            </div>

            <!-- Paiements -->
            @if($order->payments && $order->payments->count() > 0)
            <div class="premium-card">
                <h3 class="text-xl font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-credit-card text-green-400 me-2"></i>
                    Paiements
                </h3>
                <div class="space-y-4">
                    @foreach($order->payments as $payment)
                    <div class="p-4 bg-[#160D0C]/40 rounded-xl border border-slate-700/50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                @if($payment->channel === 'card')
                                    <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold">
                                        <i class="fas fa-credit-card me-1"></i>
                                        CB - {{ ucfirst($payment->provider) }}
                                    </span>
                                @elseif($payment->channel === 'mobile_money')
                                    <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                                        <i class="fas fa-mobile-alt me-1"></i>
                                        Mobile Money
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">
                                        {{ ucfirst($payment->channel ?? 'Autre') }}
                                    </span>
                                @endif

                                @if($payment->status === 'paid')
                                    <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                                        Payé
                                    </span>
                                @elseif($payment->status === 'pending' || $payment->status === 'initiated')
                                    <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-semibold">
                                        En attente
                                    </span>
                                @elseif($payment->status === 'failed')
                                    <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">
                                        Échoué
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="text-sm text-slate-300 mb-2">
                            <span class="font-semibold text-white">Montant:</span> {{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
                        </div>

                        @if($payment->external_reference)
                        <div class="text-xs text-slate-400 font-mono">
                            Réf: {{ $payment->external_reference }}
                        </div>
                        @endif

                        @if($payment->customer_phone)
                        <div class="text-xs text-slate-400 mt-1">
                            Tél: {{ $payment->customer_phone }}
                        </div>
                        @endif

                        <div class="text-xs text-slate-500 mt-2">
                            {{ $payment->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Infos Client -->
            <div class="premium-card">
                <h3 class="text-xl font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-user text-racine-orange me-2"></i>
                    Informations Client
                </h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-semibold text-slate-400 mb-1">Nom</dt>
                        <dd class="text-white font-semibold">{{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-400 mb-1">Email</dt>
                        <dd class="text-slate-300">{{ $order->customer_email ?? $order->user?->email ?? 'N/A' }}</dd>
                    </div>
                    @if($order->customer_phone)
                    <div>
                        <dt class="text-sm font-semibold text-slate-400 mb-1">Téléphone</dt>
                        <dd class="text-slate-300">{{ $order->customer_phone }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-semibold text-slate-400 mb-1">Adresse</dt>
                        <dd class="text-slate-300 whitespace-pre-line">{{ $order->customer_address ?? 'Non renseignée' }}</dd>
                    </div>
                    @if($order->user)
                    <div class="pt-4 border-t border-slate-700">
                        <dt class="text-sm font-semibold text-slate-400 mb-1">Compte Utilisateur</dt>
                        <dd class="text-racine-orange">
                            <a href="{{ route('admin.users.edit', $order->user) }}" class="hover:text-racine-yellow transition">
                                {{ $order->user->name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- QR Code -->
            <div class="premium-card">
                <h3 class="text-xl font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-qrcode text-racine-orange me-2"></i>
                    QR Code de la commande
                </h3>
                <div class="flex flex-col items-center">
                    <div class="bg-white p-4 rounded-xl border border-slate-700 mb-4">
                        {!! QrCode::size(140)->margin(1)->generate(route('admin.orders.show', $order)) !!}
                    </div>
                    <a href="{{ route('admin.orders.qr', $order) }}"
                       class="premium-btn">
                        <i class="fas fa-expand"></i>
                        Voir en grand / Imprimer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function() {
    const statusLabels = {
        active: 'Actif', confirmed: 'Confirmé', shipped: 'Expédié', delivered: 'Livré',
        cancelled: 'Annulé', restored: 'Restauré', disputed: 'Litige',
        return_requested: 'Retour demandé', refunded: 'Remboursé',
    };

    function showFlash(msg, ok) {
        let el = document.getElementById('admin-show-flash');
        if (!el) {
            el = document.createElement('div');
            el.id = 'admin-show-flash';
            el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.3);transition:opacity .3s;';
            document.body.appendChild(el);
        }
        el.style.background = ok ? '#15803d' : '#b91c1c';
        el.style.color      = 'white';
        el.style.opacity    = '1';
        el.textContent      = msg;
        clearTimeout(el._t);
        el._t = setTimeout(function() { el.style.opacity = '0'; }, 3000);
    }

    window.adminTransitionItem = function(select) {
        var to = select.value;
        if (!to) return;
        var url = select.dataset.transitionUrl;
        select.disabled = true;
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ to: to }),
        })
        .then(function(res) { return res.json().then(function(d) { return { ok: res.ok, data: d }; }); })
        .then(function(result) {
            if (!result.ok) {
                select.value = '';
                select.disabled = false;
                showFlash(result.data.message || 'Erreur lors de la transition.', false);
                return;
            }
            var newStatus = result.data.status;
            var label = statusLabels[newStatus] || newStatus;
            showFlash('Statut mis à jour : ' + label, true);
            // Replace select with a static option (no more transitions from this status in admin)
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = label;
            while (select.options.length > 0) { select.remove(0); }
            select.appendChild(opt);
            select.disabled = false;
        })
        .catch(function() {
            select.value = '';
            select.disabled = false;
            showFlash('Erreur réseau. Réessayez.', false);
        });
    };
})();
</script>
@endpush
@endsection
