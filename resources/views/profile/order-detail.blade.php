@extends('layouts.internal')

@section('title', 'Détail de la Commande #' . $order->id . ' - RACINE BY GANDA')
@section('page-title', 'Détail de la Commande')
@section('page-subtitle', 'Commande #' . $order->id . ' — Passée le ' . $order->created_at->format('d/m/Y'))

@section('content')

@php
    $statusConfig = [
        'pending'    => ['label' => 'En attente',    'color' => '#FFB800', 'bg' => 'rgba(255,184,0,0.15)',    'explanation' => 'Votre commande a été reçue et est en attente de traitement.'],
        'processing' => ['label' => 'En traitement', 'color' => '#FFB800', 'bg' => 'rgba(255,184,0,0.15)',    'explanation' => 'Votre commande est en cours de préparation.'],
        'paid'       => ['label' => 'Payée',          'color' => '#ED5F1E', 'bg' => 'rgba(237,95,30,0.15)',   'explanation' => 'Votre paiement a été validé, la commande sera bientôt expédiée.'],
        'shipped'    => ['label' => 'Expédiée',       'color' => '#ED5F1E', 'bg' => 'rgba(237,95,30,0.15)',   'explanation' => 'Votre colis est en route. Surveillez vos emails pour le numéro de suivi.'],
        'completed'  => ['label' => 'Complétée',      'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.15)',   'explanation' => 'Votre commande a été livrée avec succès. Merci pour votre achat !'],
        'delivered'  => ['label' => 'Livrée',         'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.15)',   'explanation' => 'Votre commande a été livrée avec succès. Merci pour votre achat !'],
        'cancelled'  => ['label' => 'Annulée',        'color' => '#DC2626', 'bg' => 'rgba(220,38,38,0.15)',   'explanation' => 'Cette commande a été annulée.'],
        'failed'     => ['label' => 'Échouée',        'color' => '#DC2626', 'bg' => 'rgba(220,38,38,0.15)',   'explanation' => 'Un problème est survenu avec cette commande. Contactez le support.'],
    ];
    $status = $statusConfig[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#160D0C', 'bg' => 'rgba(22,13,12,0.1)', 'explanation' => ''];

    $paymentLabels = [
        'cash_on_delivery' => 'Paiement à la livraison',
        'stripe'           => 'Carte bancaire (Stripe)',
        'card'             => 'Carte bancaire',
        'monetbil'         => 'Mobile Money (Monetbil)',
        'mobile_money'     => 'Mobile Money',
        'free'             => 'Gratuit',
    ];
    $paymentLabel = $paymentLabels[$order->payment_method ?? ''] ?? ($order->payment_method ?? 'Non spécifiée');

    // Timeline: steps in order
    $timelineSteps = [
        'commandé'  => ['label' => 'Commandé',  'icon' => 'fa-cart-plus',    'statuses' => ['pending','processing','paid','shipped','completed','delivered','cancelled','failed']],
        'confirmé'  => ['label' => 'Confirmé',  'icon' => 'fa-check',        'statuses' => ['processing','paid','shipped','completed','delivered']],
        'expédié'   => ['label' => 'Expédié',   'icon' => 'fa-truck',        'statuses' => ['shipped','completed','delivered']],
        'livré'     => ['label' => 'Livré',      'icon' => 'fa-box-open',     'statuses' => ['completed','delivered']],
    ];
    if ($order->status === 'cancelled') {
        $timelineSteps['annulé'] = ['label' => 'Annulé', 'icon' => 'fa-times-circle', 'statuses' => ['cancelled']];
    }

    $isPending   = $order->status === 'pending';
    $isCompleted = in_array($order->status, ['completed', 'delivered']);
    $needsPayment = $order->payment_status !== 'paid'
        && in_array($order->status, ['pending', 'processing'])
        && $order->payment_method !== 'cash_on_delivery';
@endphp

<div class="row">
    <div class="col-12">

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert-flash alert-flash--success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="float:right;"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert-flash alert-flash--error alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="float:right;"></button>
            </div>
        @endif

        {{-- HEADER COMMANDE --}}
        <div class="al-card mb-4">
            <div class="p-4" style="background: linear-gradient(135deg, #160D0C 0%, #2d1a19 100%); border-radius: 12px 12px 0 0;">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <h3 class="mb-1" style="font-weight: 700; font-size: 1.75rem; color: #fff;">
                            <i class="fas fa-receipt me-3" style="color: #ED5F1E;"></i>
                            Commande #{{ $order->id }}
                        </h3>
                        <p class="mb-0" style="opacity: 0.7; font-size: 0.95rem; color: #fff;">
                            Passée le {{ $order->created_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }}; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 1rem; border: 2px solid {{ $status['color'] }}50;">
                            {{ $status['label'] }}
                        </span>
                        @if($status['explanation'])
                            <div class="mt-2" style="font-size: 0.8rem; color: rgba(255,255,255,0.65); max-width: 260px;">
                                {{ $status['explanation'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TIMELINE --}}
            <div class="px-4 py-3" style="background: rgba(22,13,12,0.03); border-bottom: 1px solid rgba(22,13,12,0.08);">
                <div class="d-flex align-items-center justify-content-between order-timeline">
                    @foreach($timelineSteps as $key => $step)
                        @php
                            $isActive   = in_array($order->status, $step['statuses']);
                            $isCancelled = $key === 'annulé';
                            $stepColor   = $isCancelled ? '#DC2626' : ($isActive ? '#ED5F1E' : 'rgba(22,13,12,0.2)');
                            $labelColor  = $isCancelled ? '#DC2626' : ($isActive ? '#160D0C' : 'rgba(22,13,12,0.35)');
                        @endphp
                        <div class="timeline-step text-center">
                            <div class="timeline-icon mx-auto mb-1" style="width: 48px; height: 48px; border-radius: 50%; background: {{ $isActive ? $stepColor : 'rgba(22,13,12,0.06)' }}; display: flex; align-items: center; justify-content: center; border: 2px solid {{ $stepColor }}; transition: all 0.3s; box-shadow: {{ $isActive ? '0 2px 8px ' . $stepColor . '40' : 'none' }};">
                                <i class="fas {{ $step['icon'] }}" style="color: {{ $isActive ? '#fff' : $stepColor }}; font-size: 0.9rem;"></i>
                            </div>
                            <div style="font-size: 0.75rem; font-weight: {{ $isActive ? '600' : '400' }}; color: {{ $labelColor }};">
                                {{ $step['label'] }}
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="timeline-line flex-grow-1 mx-2" style="height: 3px; background: {{ $isActive ? '#ED5F1E' : 'rgba(22,13,12,0.1)' }}; margin-bottom: 1.75rem; border-radius: 2px;"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            {{-- COLONNE GAUCHE --}}
            <div class="col-lg-6 mb-4">

                {{-- LIVRAISON --}}
                <div class="al-card mb-4">
                    <div class="px-4 py-3" style="border-bottom: 2px solid rgba(22,13,12,0.08);">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-truck me-2" style="color: #ED5F1E;"></i>
                            Informations de Livraison
                        </h5>
                    </div>
                    <div class="p-4">
                        @if($order->address)
                            <strong style="color: #160D0C; font-size: 1.05rem;">{{ $order->address->first_name }} {{ $order->address->last_name }}</strong>
                            <p class="mt-2 mb-1" style="color: rgba(22,13,12,0.6);">{{ $order->address->address_line_1 }}</p>
                            @if($order->address->address_line_2)
                                <p class="mb-1" style="color: rgba(22,13,12,0.6);">{{ $order->address->address_line_2 }}</p>
                            @endif
                            <p class="mb-1" style="color: rgba(22,13,12,0.6);">
                                {{ $order->address->city }}{{ $order->address->postal_code ? ', ' . $order->address->postal_code : '' }}
                            </p>
                            <p class="mb-2" style="color: rgba(22,13,12,0.6);">{{ $order->address->country }}</p>
                            @if($order->address->phone)
                                <p class="mb-0">
                                    <i class="fas fa-phone me-2" style="color: #ED5F1E;"></i>
                                    <strong style="color: #160D0C;">{{ $order->address->phone }}</strong>
                                </p>
                            @endif
                        @else
                            <p class="text-muted mb-0">Adresse non disponible</p>
                        @endif
                    </div>
                </div>

                {{-- PAIEMENT --}}
                <div class="al-card {{ $needsPayment ? 'payment-card--needs-payment' : ($order->status === 'cancelled' ? 'payment-card--cancelled' : ($order->payment_status === 'paid' ? 'payment-card--paid' : '')) }}">
                    <div class="px-4 py-3" style="border-bottom: 2px solid rgba(22,13,12,0.08);">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-credit-card me-2" style="color: #ED5F1E;"></i>
                            Paiement
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.45); font-weight: 600; margin-bottom: 0.5rem;">Statut</div>
                            @if($order->status === 'cancelled')
                                <span class="pmt-badge pmt-badge--red">
                                    <i class="fas fa-ban me-1"></i> Annulée
                                </span>
                            @elseif($order->payment_status === 'paid')
                                <span class="pmt-badge pmt-badge--green">
                                    <i class="fas fa-check-circle me-1"></i> Payé
                                </span>
                            @elseif($order->payment_status === 'pending')
                                <span class="pmt-badge pmt-badge--yellow">
                                    <i class="fas fa-clock me-1"></i> En attente
                                </span>
                            @else
                                <span class="pmt-badge pmt-badge--red">
                                    <i class="fas fa-times-circle me-1"></i> Échoué
                                </span>
                            @endif

                            @if($needsPayment)
                            <div class="mt-3 p-3 payment-cta-box">
                                <p class="mb-2" style="font-size: 0.83rem; color: rgba(22,13,12,0.65);">
                                    <i class="fas fa-info-circle me-1" style="color: #22C55E;"></i>
                                    Votre commande est en attente de paiement.
                                </p>
                                @if($order->payment_method === 'card')
                                    <a href="#" onclick="document.querySelector('form[action*=\'card/pay\']').submit(); return false;"
                                        style="font-size: 0.83rem; color: #16a34a; font-weight: 600; text-decoration: none;">
                                        <i class="fas fa-credit-card me-1"></i> Finaliser le paiement par carte →
                                    </a>
                                @elseif(in_array($order->payment_method, ['mobile_money', 'monetbil']))
                                    <a href="{{ route('checkout.mobile-money.form', $order) }}"
                                        style="font-size: 0.83rem; color: #16a34a; font-weight: 600; text-decoration: none;">
                                        <i class="fas fa-mobile-alt me-1"></i> Finaliser le paiement Mobile Money →
                                    </a>
                                @endif
                            </div>
                            @elseif($order->payment_status === 'paid')
                            <div class="mt-3 p-3" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.18); border-radius: 10px; display:flex; align-items:center; gap:0.5rem;">
                                <i class="fas fa-check-circle" style="color: #22C55E; font-size: 1.1rem;"></i>
                                <span style="font-size: 0.83rem; color: #15803d; font-weight: 600;">Paiement confirmé</span>
                            </div>
                            @elseif($order->status === 'cancelled')
                            <div class="mt-3 p-3" style="background: rgba(220,38,38,0.05); border: 1px solid rgba(220,38,38,0.15); border-radius: 10px; display:flex; align-items:center; gap:0.5rem;">
                                <i class="fas fa-ban" style="color: #DC2626; font-size: 1rem;"></i>
                                <span style="font-size: 0.83rem; color: #b91c1c; font-weight: 600;">Commande annulée</span>
                            </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.45); font-weight: 600; margin-bottom: 0.5rem;">Méthode</div>
                            <p class="mb-0" style="color: #160D0C; font-weight: 500;">{{ $paymentLabel }}</p>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.45); font-weight: 600; margin-bottom: 0.5rem;">Montant total</div>
                            <p class="mb-0" style="color: #ED5F1E; font-size: 1.75rem; font-weight: 700;">
                                {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLONNE DROITE : Articles --}}
            <div class="col-lg-6 mb-4">
                <div class="al-card" id="items-card">
                    <div class="px-4 py-3" style="border-bottom: 2px solid rgba(22,13,12,0.08); display:flex; align-items:center; justify-content:space-between;">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-box me-2" style="color: #ED5F1E;"></i>
                            Articles Commandés
                        </h5>
                        <span style="font-size:0.8rem;font-weight:700;color:rgba(22,13,12,0.4);background:rgba(22,13,12,0.05);border-radius:20px;padding:0.2rem 0.65rem;">
                            {{ $order->items->where('status', \App\Models\OrderItem::STATUS_ACTIVE)->count() }}
                        </span>
                    </div>
                    <div class="al-table-wrap">
                        @if($isPending)
                            <p class="px-4 pt-3 pb-0 mb-0" style="font-size: 0.82rem; color: rgba(22,13,12,0.5);">
                                <i class="fas fa-info-circle me-1" style="color: #FFB800;"></i>
                                Commande en attente — vous pouvez encore ajuster les quantités ou retirer des articles.
                            </p>
                        @endif
                        @if($order->status === 'cancelled')
                        <div style="position:relative;">
                            <div style="position:absolute;inset:0;background:rgba(255,255,255,0.6);z-index:5;display:flex;align-items:center;justify-content:center;border-radius:0;">
                                <div style="background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.2);border-radius:10px;padding:0.6rem 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                                    <i class="fas fa-ban" style="color:#DC2626;"></i>
                                    <span style="font-size:0.88rem;font-weight:600;color:#DC2626;">Commande annulée</span>
                                </div>
                            </div>
                        @endif
                        <table class="al-table w-100" id="items-table" style="{{ $order->status === 'cancelled' ? 'opacity:0.45;pointer-events:none;' : '' }}">
                            <thead>
                                <tr>
                                    <th style="width: 36px; padding-left: 1rem;">
                                        <input type="checkbox" id="select-all" class="al-cb" title="Tout sélectionner">
                                    </th>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">Prix unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr class="item-row"
                                    data-item-id="{{ $item->id }}"
                                    data-product-id="{{ $item->product_id }}"
                                    data-product-slug="{{ $item->product->slug ?? '' }}"
                                    data-product-title="{{ $item->product->title ?? 'Produit' }}"
                                    data-cancel-url="{{ route('orders.items.cancel', [$order, $item]) }}">
                                    <td style="padding-left: 1rem; vertical-align: middle;">
                                        <input type="checkbox" class="al-cb item-cb" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <strong style="color: #160D0C;">{{ $item->product->title ?? 'Produit supprimé' }}</strong>
                                    </td>
                                    <td class="text-center" style="vertical-align: middle;">
                                        @if($isPending)
                                            <form action="{{ route('profile.orders.item.quantity', [$order, $item]) }}" method="POST" class="d-inline-flex align-items-center gap-1">
                                                @csrf
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="99"
                                                    class="form-control form-control-sm text-center"
                                                    style="width: 60px; border-radius: 8px; border: 1px solid rgba(22,13,12,0.2);">
                                                <button type="submit" class="btn btn-sm" title="Enregistrer"
                                                    style="background: #ED5F1E; color: #fff; border-radius: 8px; padding: 0.25rem 0.5rem; border: none;">
                                                    <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-weight: 500; color: #160D0C;">{{ $item->quantity }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="vertical-align: middle; color: rgba(22,13,12,0.55);">
                                        {{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-end" style="vertical-align: middle;">
                                        <strong style="color: #ED5F1E;">{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', ' ') }} FCFA</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td colspan="3" class="text-end" style="font-weight: 600; color: #160D0C; border-top: 2px solid rgba(22,13,12,0.1); padding: 1rem;">
                                        Total
                                    </td>
                                    <td class="text-end" style="border-top: 2px solid rgba(22,13,12,0.1); padding: 1rem;">
                                        <strong style="color: #ED5F1E; font-size: 1.2rem;">
                                            {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        @if($order->status === 'cancelled')
                        </div>
                        @endif
                    </div>

                    {{-- BARRE D'ACTIONS FLOTTANTE --}}
                    <div id="items-action-bar" style="display:none; position: sticky; bottom: 0; background: #160D0C; border-top: 2px solid rgba(237,95,30,0.4); padding: 0.75rem 1rem; border-radius: 0 0 12px 12px; z-index: 10;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span id="selection-count" style="color: rgba(255,255,255,0.75); font-size: 0.85rem; font-weight: 500; margin-right: 0.5rem;"></span>

                            <button type="button" id="btn-view-product"
                                onclick="itemBarViewProduct()"
                                style="display:none; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                                <i class="fas fa-eye me-1"></i> Voir la fiche
                            </button>

                            @if($isCompleted)
                            <button type="button" id="btn-report-item"
                                onclick="itemBarReport()"
                                style="background: rgba(255,184,0,0.15); color: #FFB800; border: 1px solid rgba(255,184,0,0.3); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Signaler un problème
                            </button>
                            @endif

                            @if($isPending)
                            <button type="button" id="btn-remove-item"
                                onclick="itemBarRemove()"
                                style="background: rgba(220,38,38,0.15); color: #FCA5A5; border: 1px solid rgba(220,38,38,0.35); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                                <i class="fas fa-trash me-1"></i> Retirer de la commande
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION ARTICLES ANNULÉS --}}
        @php
            $cancelledItems = $order->items->where('status', \App\Models\OrderItem::STATUS_CANCELLED);
        @endphp
        @if($cancelledItems->isNotEmpty())
        <div class="al-card mb-4" id="cancelled-items-card">
            <div class="px-4 py-3" style="border-bottom: 2px solid rgba(220,38,38,0.15); background: rgba(220,38,38,0.03);">
                <h5 class="mb-0" style="font-weight: 600; color: #DC2626;">
                    <i class="fas fa-ban me-2"></i>
                    Articles annulés
                    <span class="ms-2" style="font-size: 0.8rem; font-weight: 400; color: rgba(22,13,12,0.45);">({{ $cancelledItems->count() }} article{{ $cancelledItems->count() > 1 ? 's' : '' }})</span>
                </h5>
            </div>
            <div class="al-table-wrap">
                <table class="al-table w-100" id="cancelled-items-table">
                    <thead>
                        <tr style="background: rgba(220,38,38,0.03);">
                            <th style="width: 36px; padding-left: 1rem;">
                                <input type="checkbox" id="cancelled-select-all" class="al-cb" title="Tout sélectionner">
                            </th>
                            <th>Produit</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Prix unit.</th>
                            <th class="text-end">Annulé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cancelledItems as $item)
                        <tr class="cancelled-item-row"
                            data-item-id="{{ $item->id }}"
                            data-product-id="{{ $item->product_id }}"
                            data-product-slug="{{ $item->product->slug ?? '' }}"
                            data-product-title="{{ $item->product->title ?? 'Produit' }}"
                            data-restore-url="{{ route('orders.items.restore', [$order, $item]) }}">
                            <td style="padding-left: 1rem; vertical-align: middle;">
                                <input type="checkbox" class="al-cb cancelled-item-cb" value="{{ $item->id }}">
                            </td>
                            <td>
                                <strong style="color: #160D0C;">{{ $item->product->title ?? 'Produit supprimé' }}</strong>
                            </td>
                            <td class="text-center" style="vertical-align: middle;">
                                <span style="font-weight: 500; color: rgba(22,13,12,0.6);">{{ $item->quantity }}</span>
                            </td>
                            <td class="text-end" style="vertical-align: middle; color: rgba(22,13,12,0.5);">
                                {{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="text-end" style="vertical-align: middle; color: rgba(22,13,12,0.45); font-size: 0.85rem;">
                                {{ $item->cancelled_at ? $item->cancelled_at->format('d/m/Y H:i') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- BARRE D'ACTIONS ARTICLES ANNULÉS --}}
            <div id="cancelled-action-bar" style="display:none; position: sticky; bottom: 0; background: #1c0e0d; border-top: 2px solid rgba(220,38,38,0.4); padding: 0.75rem 1rem; border-radius: 0 0 12px 12px; z-index: 10;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="cancelled-selection-count" style="color: rgba(255,255,255,0.75); font-size: 0.85rem; font-weight: 500; margin-right: 0.5rem;"></span>

                    <button type="button" id="btn-cancelled-view"
                        onclick="cancelledBarView()"
                        style="display:none; background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                        <i class="fas fa-eye me-1"></i> Voir la fiche
                    </button>

                    @if($isPending)
                    <button type="button" id="btn-restore-item"
                        onclick="cancelledBarRestore()"
                        style="background: rgba(34,197,94,0.15); color: #86EFAC; border: 1px solid rgba(34,197,94,0.35); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                        <i class="fas fa-undo me-1"></i> Restaurer dans la commande
                    </button>
                    @endif

                    <button type="button" id="btn-reorder-item"
                        onclick="cancelledBarReorder()"
                        style="background: rgba(237,95,30,0.15); color: #FCA572; border: 1px solid rgba(237,95,30,0.35); border-radius: 8px; padding: 0.4rem 1rem; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                        <i class="fas fa-redo me-1"></i> Recommander
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- ACTIONS --}}
        <div class="al-card mb-4">
            <div class="p-4">

                {{-- NIVEAU 1 : Paiement (priorité absolue) --}}
                @if($needsPayment)
                <div class="mb-4">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #ED5F1E; margin-bottom: 0.75rem;"><i class="fas fa-exclamation-circle me-1"></i> Action requise</div>
                    @if($order->payment_method === 'card')
                        <form action="{{ route('checkout.card.pay') }}" method="POST">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="btn-action btn-action--pay">
                                <i class="fas fa-credit-card me-2"></i> Payer par carte maintenant
                            </button>
                        </form>
                    @elseif(in_array($order->payment_method, ['mobile_money', 'monetbil']))
                        <a href="{{ route('checkout.mobile-money.form', $order) }}" class="btn-action btn-action--pay">
                            <i class="fas fa-mobile-alt me-2"></i> Payer via Mobile Money maintenant
                        </a>
                    @endif
                </div>
                @endif

                {{-- NIVEAU 2 : Actions secondaires --}}
                <div style="border-top: 1px solid rgba(22,13,12,0.07); margin-top: 1rem; padding-top: 1rem;">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.35); margin-bottom: 0.75rem;">Gérer la commande</div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @php $existingThread = \App\Models\Conversation::forOrder($order->id)->first(); @endphp
                    @if($existingThread)
                        <a href="{{ route('messages.show', $existingThread->id) }}" class="btn-action btn-action--secondary">
                            <i class="fas fa-comments me-2"></i> Voir la discussion
                        </a>
                    @else
                        <form action="{{ route('messages.create-order-thread', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn-action btn-action--secondary">
                                <i class="fas fa-comments me-2"></i> Contacter le support
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('profile.invoice.show', $order) }}" class="btn-action btn-action--secondary" target="_blank">
                        <i class="fas fa-file-invoice me-2"></i> Voir la facture
                    </a>
                    <a href="{{ route('profile.invoice.download', $order) }}" class="btn-action btn-action--secondary">
                        <i class="fas fa-download me-2"></i> Télécharger
                    </a>
                    <a href="{{ route('frontend.shop') }}" class="btn-action btn-action--secondary">
                        <i class="fas fa-store me-2"></i> Continuer mes achats
                    </a>
                </div>

                {{-- NIVEAU 3 : Actions contextuelles (commandes livrées) --}}
                @if($isCompleted)
                <div style="border-top: 1px solid rgba(22,13,12,0.07); margin-top: 0.75rem; padding-top: 0.75rem;">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.35); margin-bottom: 0.75rem;">Après livraison</div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @if($order->payment_status === 'paid')
                        <a href="{{ route('profile.reviews.create', $order) }}" class="btn-action btn-action--subtle">
                            <i class="fas fa-star me-2"></i> Laisser un avis
                        </a>
                        <button type="button" class="btn-action btn-action--subtle" data-bs-toggle="modal" data-bs-target="#returnModal">
                            <i class="fas fa-undo me-2"></i> Retourner le produit
                        </button>
                    @endif
                    <button type="button" class="btn-action btn-action--subtle" data-bs-toggle="modal" data-bs-target="#reportProblemModal">
                        <i class="fas fa-exclamation-triangle me-2"></i> Signaler un problème
                    </button>
                </div>
                </div>
                @endif

                {{-- NIVEAU 4 : Zone destructive --}}
                @if($isPending)
                <div style="border-top: 1px solid rgba(22,13,12,0.08); padding-top: 1rem; margin-top: 0.25rem;">
                    <button type="button" class="btn-action btn-action--danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="fas fa-times me-2"></i> Annuler la commande
                    </button>
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- MODAL : Annuler la commande --}}
@if($isPending)
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(22,13,12,0.1);">
                <h5 class="modal-title" id="cancelOrderModalLabel" style="font-weight: 700; color: #DC2626;">
                    <i class="fas fa-exclamation-triangle me-2"></i> Annuler la commande
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p style="color: rgba(22,13,12,0.7);">
                    Êtes-vous sûr de vouloir annuler la commande <strong>#{{ $order->id }}</strong> ?
                </p>
                <p class="mb-0" style="font-size: 0.88rem; color: rgba(22,13,12,0.5);">
                    Cette action est irréversible. Si vous avez déjà effectué un paiement, le remboursement sera traité sous 5 à 10 jours ouvrés.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(22,13,12,0.1);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Garder ma commande</button>
                <form action="{{ route('profile.orders.cancel', $order) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn" style="background: #DC2626; color: #fff; border-radius: 10px; border: none; font-weight: 600;">
                        <i class="fas fa-times me-1"></i> Confirmer l'annulation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL : Signaler un problème --}}
@if($isCompleted)
<div class="modal fade" id="reportProblemModal" tabindex="-1" aria-labelledby="reportProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18);">
            <form action="{{ route('messages.create-order-thread', $order) }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid rgba(22,13,12,0.1);">
                    <h5 class="modal-title" id="reportProblemModalLabel" style="font-weight: 700; color: #B45309;">
                        <i class="fas fa-exclamation-triangle me-2" style="color: #FFB800;"></i> Signaler un problème
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p style="color: rgba(22,13,12,0.7); margin-bottom: 1.25rem;">
                        Décrivez le problème rencontré avec la commande <strong>#{{ $order->id }}</strong>.
                        Notre équipe vous répondra dans les plus brefs délais.
                    </p>
                    <label class="form-label" style="font-weight: 600; color: #160D0C;">Description du problème</label>
                    <textarea name="initial_message" class="form-control" rows="4" required minlength="20"
                        placeholder="Ex: Je n'ai pas reçu mon colis, le produit est endommagé..."
                        style="border-radius: 10px; border: 1px solid rgba(22,13,12,0.2); resize: none;"></textarea>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(22,13,12,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn" style="background: #ED5F1E; color: #fff; border-radius: 10px; border: none; font-weight: 600;">
                        <i class="fas fa-paper-plane me-1"></i> Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL : Retourner le produit --}}
@if($order->payment_status === 'paid')
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18);">
            <form action="{{ route('profile.orders.return', $order) }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid rgba(22,13,12,0.1);">
                    <h5 class="modal-title" id="returnModalLabel" style="font-weight: 700; color: #160D0C;">
                        <i class="fas fa-undo me-2" style="color: #ED5F1E;"></i> Demande de retour
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p style="color: rgba(22,13,12,0.7); margin-bottom: 1.25rem;">
                        Expliquez la raison de votre retour pour la commande <strong>#{{ $order->id }}</strong>.
                    </p>
                    <label class="form-label" style="font-weight: 600; color: #160D0C;">Motif du retour <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required minlength="20"
                        placeholder="Ex: Le produit ne correspond pas à la description, il est défectueux..."
                        style="border-radius: 10px; border: 1px solid rgba(22,13,12,0.2); resize: none;"></textarea>
                    <p class="mt-2 mb-0" style="font-size: 0.8rem; color: rgba(22,13,12,0.45);">
                        <i class="fas fa-info-circle me-1"></i>
                        Les retours sont acceptés dans les 14 jours suivant la réception.
                    </p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(22,13,12,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn" style="background: #ED5F1E; color: #fff; border-radius: 10px; border: none; font-weight: 600;">
                        <i class="fas fa-paper-plane me-1"></i> Soumettre la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

<style nonce="{{ csp_nonce() }}">
    .al-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid rgba(22,13,12,0.1);
        box-shadow: 0 2px 12px rgba(22,13,12,0.06);
        overflow: hidden;
    }
    .al-table-wrap {
        overflow-x: auto;
    }
    .al-table {
        border-collapse: collapse;
    }
    .al-table thead tr {
        background: rgba(22,13,12,0.04);
    }
    .al-table th {
        padding: 0.875rem 1rem;
        font-weight: 600;
        color: #160D0C;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 2px solid rgba(22,13,12,0.1);
        white-space: nowrap;
    }
    .al-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(22,13,12,0.07);
        font-size: 0.92rem;
        color: #160D0C;
    }
    .al-table tbody tr:last-child td {
        border-bottom: none;
    }
    .al-table tbody tr:hover {
        background: rgba(237,95,30,0.02);
    }
    .al-table tbody tr.row-selected {
        background: rgba(237,95,30,0.06) !important;
    }
    .al-cb {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #ED5F1E;
    }
    .order-timeline {
        min-height: 60px;
    }
    .timeline-step {
        flex-shrink: 0;
    }
    /* ── Flash messages ── */
    .alert-flash {
        padding: 0.875rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        border-left: 4px solid;
        display: flex;
        align-items: center;
    }
    .alert-flash--success {
        background: rgba(34,197,94,0.06);
        border-left-color: #22C55E;
        color: #15803d;
    }
    .alert-flash--error {
        background: rgba(220,38,38,0.06);
        border-left-color: #DC2626;
        color: #b91c1c;
    }

    /* ── Payment card states ── */
    @keyframes paymentPulse {
        0%, 100% { box-shadow: 0 2px 12px rgba(22,13,12,0.06), 0 0 0 0 rgba(34,197,94,0.2); }
        50%       { box-shadow: 0 2px 12px rgba(22,13,12,0.06), 0 0 0 8px rgba(34,197,94,0.04); }
    }
    .payment-card--needs-payment { animation: paymentPulse 2.5s ease-in-out infinite; border-color: rgba(34,197,94,0.35) !important; }
    .payment-card--paid     { border-color: rgba(34,197,94,0.25) !important; }
    .payment-card--cancelled{ border-color: rgba(220,38,38,0.2) !important; }
    .payment-cta-box {
        background: rgba(34,197,94,0.06);
        border: 1px solid rgba(34,197,94,0.2);
        border-radius: 10px;
    }

    /* ── Payment badges ── */
    .pmt-badge {
        display: inline-block;
        padding: 0.4rem 0.9rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid;
    }
    .pmt-badge--green  { background: rgba(34,197,94,0.1);  color: #22C55E; border-color: rgba(34,197,94,0.25); }
    .pmt-badge--yellow { background: rgba(255,184,0,0.1);  color: #FFB800; border-color: rgba(255,184,0,0.25); }
    .pmt-badge--red    { background: rgba(220,38,38,0.1);  color: #DC2626; border-color: rgba(220,38,38,0.25); }

    /* ── Action buttons ── */
    .btn-action {
        display: inline-flex;
        align-items: center;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .btn-action--back {
        background: rgba(22,13,12,0.05);
        color: rgba(22,13,12,0.6);
        border-color: rgba(22,13,12,0.15);
        padding: 0.55rem 1.2rem;
        font-size: 0.88rem;
    }
    .btn-action--back:hover { background: rgba(22,13,12,0.09); color: #160D0C; }
    .btn-action--pay {
        background: linear-gradient(135deg, #22C55E 0%, #16a34a 100%);
        color: #fff !important;
        border-color: transparent;
        padding: 0.875rem 2.25rem;
        font-size: 1rem;
        font-weight: 700;
        box-shadow: 0 4px 16px rgba(34,197,94,0.3);
    }
    .btn-action--pay:hover { box-shadow: 0 6px 20px rgba(34,197,94,0.4); transform: translateY(-1px); color: #fff; }
    .btn-action--secondary {
        background: #fff;
        color: rgba(22,13,12,0.65);
        border-color: rgba(22,13,12,0.18);
        padding: 0.5rem 1rem;
        font-size: 0.84rem;
        font-weight: 500;
    }
    .btn-action--secondary:hover { color: #ED5F1E; border-color: #ED5F1E; background: rgba(237,95,30,0.03); }
    .btn-action--subtle {
        background: transparent;
        color: rgba(22,13,12,0.5);
        border-color: rgba(22,13,12,0.12);
        padding: 0.45rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 500;
    }
    .btn-action--subtle:hover { color: #160D0C; border-color: rgba(22,13,12,0.25); background: rgba(22,13,12,0.03); }
    .btn-action--danger {
        background: transparent;
        color: #DC2626;
        border-color: rgba(220,38,38,0.3);
        padding: 0.5rem 1rem;
        font-size: 0.84rem;
    }
    .btn-action--danger:hover { background: rgba(220,38,38,0.06); border-color: #DC2626; }

    #items-action-bar button:hover { opacity: 0.85; }
</style>

<script nonce="{{ csp_nonce() }}">
(function () {
    const selectAll = document.getElementById('select-all');
    const actionBar = document.getElementById('items-action-bar');
    const selectionCount = document.getElementById('selection-count');
    const btnViewProduct = document.getElementById('btn-view-product');

    function getChecked() {
        return Array.from(document.querySelectorAll('.item-cb:checked'));
    }

    function getSelectedRows() {
        return getChecked().map(cb => cb.closest('.item-row'));
    }

    function updateBar() {
        const checked = getChecked();
        const count = checked.length;

        if (count === 0) {
            actionBar.style.display = 'none';
            if (selectAll) selectAll.indeterminate = false;
            return;
        }

        actionBar.style.display = 'block';
        selectionCount.textContent = count === 1 ? '1 article sélectionné' : count + ' articles sélectionnés';

        if (btnViewProduct) {
            btnViewProduct.style.display = count === 1 ? 'inline-flex' : 'none';
        }

        const all = document.querySelectorAll('.item-cb');
        if (selectAll) {
            selectAll.checked = count === all.length;
            selectAll.indeterminate = count > 0 && count < all.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.item-cb').forEach(cb => {
                cb.checked = this.checked;
                cb.closest('.item-row').classList.toggle('row-selected', this.checked);
            });
            updateBar();
        });
    }

    document.querySelectorAll('.item-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            this.closest('.item-row').classList.toggle('row-selected', this.checked);
            updateBar();
        });
    });
    updateBar();

    window.itemBarViewProduct = function () {
        const rows = getSelectedRows();
        if (rows.length !== 1) return;
        const slug = rows[0].dataset.productSlug;
        if (slug) {
            window.open('{{ url('/produit') }}/' + slug, '_blank');
        } else {
            alert('Fiche produit indisponible.');
        }
    };

    window.itemBarReport = function () {
        const rows = getSelectedRows();
        const titles = rows.map(r => r.dataset.productTitle).join(', ');
        const textarea = document.querySelector('#reportProblemModal textarea[name="initial_message"]');
        if (textarea && titles) {
            textarea.value = 'Article(s) concerné(s) : ' + titles + '\n\n';
        }
        const modal = document.getElementById('reportProblemModal');
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    };

    // ─── Cancelled items action bar ────────────────────────────────────────────
    const cancelledSelectAll = document.getElementById('cancelled-select-all');
    const cancelledActionBar = document.getElementById('cancelled-action-bar');
    const cancelledSelectionCount = document.getElementById('cancelled-selection-count');

    function getCancelledChecked() {
        return Array.from(document.querySelectorAll('.cancelled-item-cb:checked'));
    }

    function getCancelledSelectedRows() {
        return getCancelledChecked().map(cb => cb.closest('.cancelled-item-row'));
    }

    function updateCancelledBar() {
        const checked = getCancelledChecked();
        const count = checked.length;
        const viewBtn = document.getElementById('btn-cancelled-view');

        if (!cancelledActionBar) return;

        if (count === 0) {
            cancelledActionBar.style.display = 'none';
            if (cancelledSelectAll) cancelledSelectAll.indeterminate = false;
            if (viewBtn) viewBtn.style.display = 'none';
            return;
        }

        cancelledActionBar.style.display = 'block';
        if (viewBtn) viewBtn.style.display = count === 1 ? 'inline-flex' : 'none';
        if (cancelledSelectionCount) {
            cancelledSelectionCount.textContent = count === 1 ? '1 article sélectionné' : count + ' articles sélectionnés';
        }

        const all = document.querySelectorAll('.cancelled-item-cb');
        if (cancelledSelectAll) {
            cancelledSelectAll.checked = count === all.length;
            cancelledSelectAll.indeterminate = count > 0 && count < all.length;
        }
    }

    if (cancelledSelectAll) {
        cancelledSelectAll.addEventListener('change', function () {
            document.querySelectorAll('.cancelled-item-cb').forEach(cb => {
                cb.checked = this.checked;
                cb.closest('.cancelled-item-row').classList.toggle('row-selected', this.checked);
            });
            updateCancelledBar();
        });
    }

    document.querySelectorAll('.cancelled-item-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            this.closest('.cancelled-item-row').classList.toggle('row-selected', this.checked);
            updateCancelledBar();
        });
    });

    window.cancelledBarRestore = function () {
        const rows = getCancelledSelectedRows();
        if (rows.length === 0) return;
        if (!confirm('Restaurer ' + (rows.length === 1 ? '"' + rows[0].dataset.productTitle + '"' : rows.length + ' articles') + ' dans la commande ?')) return;

        const csrfToken = (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=').slice(1).join('=');
        const token = decodeURIComponent(csrfToken);

        Promise.all(rows.map(row =>
            fetch(row.dataset.restoreUrl, {
                method: 'PATCH',
                headers: {
                    'X-XSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
        )).then(responses => {
            if (responses.every(r => r.ok)) {
                window.location.reload();
            } else {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        }).catch(() => alert('Erreur réseau. Veuillez réessayer.'));
    };

    window.cancelledBarView = function () {
        const rows = getCancelledSelectedRows();
        if (rows.length !== 1) return;
        const slug = rows[0].dataset.productSlug;
        if (!slug) {
            alert('Fiche produit indisponible.');
            return;
        }
        window.open('{{ url('/produit') }}/' + slug, '_blank');
    };

    window.cancelledBarReorder = function () {
        const rows = getCancelledSelectedRows();
        rows.forEach(row => {
            const slug = row.dataset.productSlug;
            if (slug) {
                window.open('{{ url('/produit') }}/' + slug + '?reorder=1', '_blank');
            }
        });
    };
    // ────────────────────────────────────────────────────────────────────────────

    window.itemBarRemove = function () {
        const rows = getSelectedRows();
        if (rows.length === 0) return;

        const names = rows.map(r => r.dataset.productTitle).join(', ');
        if (!confirm('Retirer ' + (rows.length === 1 ? '"' + names + '"' : rows.length + ' articles') + ' de la commande ?')) return;

        const csrfToken = (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=').slice(1).join('=');
        const token = decodeURIComponent(csrfToken);

        Promise.all(rows.map(row =>
            fetch(row.dataset.cancelUrl, {
                method: 'DELETE',
                headers: {
                    'X-XSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
        )).then(responses => {
            const allOk = responses.every(r => r.ok);
            if (allOk) {
                window.location.reload();
            } else {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        }).catch(() => alert('Erreur réseau. Veuillez réessayer.'));
    };
})();
</script>

{{-- MODAL : Doublon détecté (reorder_warnings) --}}
@php $reorderWarnings = session()->pull('reorder_warnings'); @endphp
@if($reorderWarnings)
<div class="modal fade" id="reorderWarningModal" tabindex="-1" aria-labelledby="reorderWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(22,13,12,0.1);">
                <h5 class="modal-title" id="reorderWarningModalLabel" style="font-weight: 700; color: #B45309;">
                    <i class="fas fa-exclamation-triangle me-2" style="color: #FFB800;"></i> Articles déjà commandés et annulés
                </h5>
            </div>
            <div class="modal-body p-4">
                <p style="color: rgba(22,13,12,0.7);">
                    Vous avez déjà commandé et annulé le(s) article(s) suivant(s) par le passé :
                </p>
                <ul class="mb-3" style="color: #160D0C; font-weight: 500;">
                    @foreach($reorderWarnings as $warning)
                        <li>{{ $warning['product_name'] }}</li>
                    @endforeach
                </ul>
                <p class="mb-0" style="font-size: 0.88rem; color: rgba(22,13,12,0.5);">
                    Votre commande a bien été enregistrée.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(22,13,12,0.1);">
                <button type="button" class="btn" data-bs-dismiss="modal"
                    style="background: #ED5F1E; color: #fff; border-radius: 10px; border: none; font-weight: 600; padding: 0.6rem 1.5rem;">
                    Compris
                </button>
            </div>
        </div>
    </div>
</div>
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('reorderWarningModal')).show();
});
</script>
@endif

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
        ['label' => 'Commande #' . $order->id, 'url' => null],
    ],
    'backUrl'  => route('profile.orders'),
    'backText' => 'Retour aux commandes',
    'position' => 'bottom',
])
@endsection
