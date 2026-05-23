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
@endphp

<div class="row">
    <div class="col-12">

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                            <div class="timeline-icon mx-auto mb-1" style="width: 40px; height: 40px; border-radius: 50%; background: {{ $isActive ? $stepColor : 'rgba(22,13,12,0.06)' }}; display: flex; align-items: center; justify-content: center; border: 2px solid {{ $stepColor }}; transition: all 0.3s;">
                                <i class="fas {{ $step['icon'] }}" style="color: {{ $isActive ? '#fff' : $stepColor }}; font-size: 0.85rem;"></i>
                            </div>
                            <div style="font-size: 0.75rem; font-weight: {{ $isActive ? '600' : '400' }}; color: {{ $labelColor }};">
                                {{ $step['label'] }}
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="timeline-line flex-grow-1 mx-2" style="height: 2px; background: {{ $isActive ? '#ED5F1E' : 'rgba(22,13,12,0.1)' }}; margin-bottom: 1.5rem;"></div>
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
                <div class="al-card">
                    <div class="px-4 py-3" style="border-bottom: 2px solid rgba(22,13,12,0.08);">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-credit-card me-2" style="color: #ED5F1E;"></i>
                            Paiement
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(22,13,12,0.45); font-weight: 600; margin-bottom: 0.5rem;">Statut</div>
                            @if($order->payment_status === 'paid')
                                <span class="badge" style="background: rgba(34,197,94,0.1); color: #22C55E; padding: 0.45rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(34,197,94,0.25);">
                                    <i class="fas fa-check-circle me-1"></i> Payé
                                </span>
                            @elseif($order->payment_status === 'pending')
                                <span class="badge" style="background: rgba(255,184,0,0.1); color: #FFB800; padding: 0.45rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(255,184,0,0.25);">
                                    <i class="fas fa-clock me-1"></i> En attente
                                </span>
                            @else
                                <span class="badge" style="background: rgba(220,38,38,0.1); color: #DC2626; padding: 0.45rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(220,38,38,0.25);">
                                    <i class="fas fa-times-circle me-1"></i> Échoué
                                </span>
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
                <div class="al-card">
                    <div class="px-4 py-3" style="border-bottom: 2px solid rgba(22,13,12,0.08);">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-box me-2" style="color: #ED5F1E;"></i>
                            Articles Commandés
                        </h5>
                    </div>
                    <div class="al-table-wrap">
                        @if($isPending)
                            <p class="px-4 pt-3 pb-0 mb-0" style="font-size: 0.82rem; color: rgba(22,13,12,0.5);">
                                <i class="fas fa-info-circle me-1" style="color: #FFB800;"></i>
                                Commande en attente — vous pouvez encore ajuster les quantités.
                            </p>
                        @endif
                        <table class="al-table w-100">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">Prix unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong style="color: #160D0C;">{{ $item->product->title ?? 'Produit supprimé' }}</strong>
                                        {{-- SKU masqué côté client --}}
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
                    </div>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="al-card mb-4">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    {{-- Retour --}}
                    <a href="{{ route('profile.orders') }}" class="btn"
                        style="background: rgba(22,13,12,0.05); color: rgba(22,13,12,0.6); border: 1px solid rgba(22,13,12,0.15); border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 500; transition: all 0.3s;">
                        <i class="fas fa-arrow-left me-2"></i> Retour aux commandes
                    </a>

                    <div class="d-flex gap-2 flex-wrap">

                        {{-- Annuler (pending seulement) --}}
                        @if($isPending)
                            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#cancelOrderModal"
                                style="background: rgba(220,38,38,0.08); color: #DC2626; border: 1px solid rgba(220,38,38,0.35); border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-times me-2"></i> Annuler la commande
                            </button>
                        @endif

                        {{-- Discussion / support --}}
                        @php $existingThread = \App\Models\Conversation::forOrder($order->id)->first(); @endphp
                        @if($existingThread)
                            <a href="{{ route('messages.show', $existingThread->id) }}" class="btn"
                                style="background: rgba(237,95,30,0.1); color: #ED5F1E; border: 1px solid #ED5F1E; border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-comments me-2"></i> Voir la discussion
                            </a>
                        @else
                            <form action="{{ route('messages.create-order-thread', $order) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn"
                                    style="background: rgba(237,95,30,0.1); color: #ED5F1E; border: 1px solid #ED5F1E; border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                    <i class="fas fa-comments me-2"></i> Contacter le support
                                </button>
                            </form>
                        @endif

                        {{-- Signaler un problème (completed/delivered) --}}
                        @if($isCompleted)
                            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#reportProblemModal"
                                style="background: rgba(255,184,0,0.1); color: #B45309; border: 1px solid rgba(180,83,9,0.35); border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-exclamation-triangle me-2"></i> Signaler un problème
                            </button>
                        @endif

                        {{-- Avis (completed/delivered + paid) --}}
                        @if($isCompleted && $order->payment_status === 'paid')
                            <a href="{{ route('profile.reviews.create', $order) }}" class="btn"
                                style="background: rgba(255,184,0,0.1); color: #FFB800; border: 1px solid #FFB800; border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-star me-2"></i> Laisser un avis
                            </a>
                        @endif

                        {{-- Retourner le produit (completed/delivered + paid) --}}
                        @if($isCompleted && $order->payment_status === 'paid')
                            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#returnModal"
                                style="background: rgba(22,13,12,0.05); color: rgba(22,13,12,0.65); border: 1px solid rgba(22,13,12,0.2); border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-undo me-2"></i> Retourner le produit
                            </button>
                        @endif

                        {{-- Facture --}}
                        <a href="{{ route('profile.invoice.show', $order) }}" class="btn"
                            style="background: rgba(22,13,12,0.05); color: #160D0C; border: 1px solid rgba(22,13,12,0.2); border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;" target="_blank">
                            <i class="fas fa-file-invoice me-2"></i> Voir la facture
                        </a>
                        <a href="{{ route('profile.invoice.download', $order) }}" class="btn"
                            style="background: rgba(34,197,94,0.1); color: #22C55E; border: 1px solid #22C55E; border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; transition: all 0.3s;">
                            <i class="fas fa-download me-2"></i> Télécharger la facture
                        </a>

                        {{-- Continuer achats --}}
                        <a href="{{ route('frontend.shop') }}" class="btn"
                            style="background: linear-gradient(135deg, #ED5F1E 0%, #d45519 100%); color: white; border-radius: 12px; padding: 0.75rem 1.75rem; font-weight: 600; box-shadow: 0 4px 12px rgba(237,95,30,0.3); transition: all 0.3s; border: none;">
                            <i class="fas fa-store me-2"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
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
    .order-timeline {
        min-height: 60px;
    }
    .timeline-step {
        flex-shrink: 0;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
</style>

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
