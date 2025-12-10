@extends('layouts.internal')

@section('title', 'Détail de la Commande #' . $order->id . ' - RACINE BY GANDA')
@section('page-title', 'Détail de la Commande')
@section('page-subtitle', 'Commande #' . $order->id . ' - Passée le ' . $order->created_at->format('d/m/Y'))

@section('content')
<div class="row">
    <div class="col-12">
        <!-- HEADER COMMANDE -->
        <div class="card-racine"
            <div class="card-body p-4 text-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2" style="font-weight: 700; font-size: 1.75rem;">
                            <i class="fas fa-receipt me-3"></i>
                            Commande #{{ $order->id }}
                        </h3>
                        <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">
                            Passée le {{ $order->created_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                    <div>
                        @php
                            $statusConfig = [
                                'pending' => ['label' => 'En attente', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.2)'],
                                'processing' => ['label' => 'En traitement', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.2)'],
                                'paid' => ['label' => 'Payée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.2)'],
                                'shipped' => ['label' => 'Expédiée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.2)'],
                                'completed' => ['label' => 'Complétée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.2)'],
                                'delivered' => ['label' => 'Livrée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.2)'],
                                'cancelled' => ['label' => 'Annulée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.2)'],
                                'failed' => ['label' => 'Échouée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.2)'],
                            ];
                            $status = $statusConfig[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#6c757d', 'bg' => 'rgba(108, 117, 125, 0.2)'];
                        @endphp
                        <span class="badge" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }}; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 1rem; border: 2px solid {{ $status['color'] }}40;">
                            {{ $status['label'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- COLONNE GAUCHE : Informations -->
            <div class="col-lg-6 mb-4">
                <!-- LIVRAISON -->
                <div class="card-racine"
                    <div class="card-header bg-white border-0 py-4" style="border-bottom: 2px solid #f0f0f0;">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-truck me-2" style="color: #ED5F1E;"></i>
                            Informations de Livraison
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if($order->address)
                            <div class="mb-3">
                                <strong style="color: #160D0C; font-size: 1.1rem;">{{ $order->address->first_name }} {{ $order->address->last_name }}</strong>
                            </div>
                            <p class="mb-2" style="color: #6c757d;">
                                {{ $order->address->address_line_1 }}
                            </p>
                            @if($order->address->address_line_2)
                                <p class="mb-2" style="color: #6c757d;">
                                    {{ $order->address->address_line_2 }}
                                </p>
                            @endif
                            <p class="mb-2" style="color: #6c757d;">
                                {{ $order->address->city }}{{ $order->address->postal_code ? ', ' . $order->address->postal_code : '' }}
                            </p>
                            <p class="mb-3" style="color: #6c757d;">
                                {{ $order->address->country }}
                            </p>
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

                <!-- PAIEMENT -->
                <div class="card-racine"
                    <div class="card-header bg-white border-0 py-4" style="border-bottom: 2px solid #f0f0f0;">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-credit-card me-2" style="color: #ED5F1E;"></i>
                            Informations de Paiement
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <strong style="color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Statut :</strong>
                            <div class="mt-2">
                                @if($order->payment_status === 'paid')
                                    <span class="badge" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(34, 197, 94, 0.2);">
                                        <i class="fas fa-check-circle me-1"></i> Payé
                                    </span>
                                @elseif($order->payment_status === 'pending')
                                    <span class="badge" style="background: rgba(255, 184, 0, 0.1); color: #FFB800; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(255, 184, 0, 0.2);">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </span>
                                @else
                                    <span class="badge" style="background: rgba(220, 38, 38, 0.1); color: #DC2626; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid rgba(220, 38, 38, 0.2);">
                                        <i class="fas fa-times-circle me-1"></i> Échoué
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong style="color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Méthode :</strong>
                            <p class="mb-0 mt-2" style="color: #160D0C; font-weight: 500;">
                                {{ $order->payment_method ?? 'Non spécifiée' }}
                            </p>
                        </div>
                        <div>
                            <strong style="color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Montant total :</strong>
                            <p class="mb-0 mt-2" style="color: #ED5F1E; font-size: 1.75rem; font-weight: 700;">
                                {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : Articles -->
            <div class="col-lg-6 mb-4">
                <div class="card-racine"
                    <div class="card-header bg-white border-0 py-4" style="border-bottom: 2px solid #f0f0f0;">
                        <h5 class="mb-0" style="font-weight: 600; color: #160D0C;">
                            <i class="fas fa-box me-2" style="color: #ED5F1E;"></i>
                            Articles Commandés
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 1rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Produit</th>
                                        <th style="padding: 1rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef; text-align: center;">Qté</th>
                                        <th style="padding: 1rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef; text-align: right;">Prix unit.</th>
                                        <th style="padding: 1rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 1rem;">
                                            <strong style="color: #160D0C;">{{ $item->product->title ?? 'Produit' }}</strong>
                                            @if($item->product && $item->product->sku)
                                                <br>
                                                <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                            @endif
                                        </td>
                                        <td style="padding: 1rem; text-align: center; vertical-align: middle;">
                                            <span style="font-weight: 500; color: #160D0C;">{{ $item->quantity }}</span>
                                        </td>
                                        <td style="padding: 1rem; text-align: right; vertical-align: middle;">
                                            <span style="color: #6c757d;">{{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA</span>
                                        </td>
                                        <td style="padding: 1rem; text-align: right; vertical-align: middle;">
                                            <strong style="color: #ED5F1E;">{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', ' ') }} FCFA</strong>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot style="background: #f8f9fa;">
                                    <tr>
                                        <td colspan="3" style="padding: 1.25rem; text-align: right; font-weight: 600; color: #160D0C; border-top: 2px solid #e9ecef;">
                                            <strong>Total :</strong>
                                        </td>
                                        <td style="padding: 1.25rem; text-align: right; border-top: 2px solid #e9ecef;">
                                            <strong style="color: #ED5F1E; font-size: 1.25rem;">
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
        </div>

        <!-- ACTIONS -->
        <div class="card-racine"
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 1rem;">
                    <a href="{{ route('profile.orders') }}" class="btn" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); border-radius: 12px; padding: 0.75rem 2rem; font-weight: 500; transition: all 0.3s;">
                        <i class="fas fa-arrow-left me-2"></i> Retour aux commandes
                    </a>
                    <div class="d-flex gap-2 flex-wrap">
                        @php
                            $conversationService = app(\App\Services\ConversationService::class);
                            $existingThread = \App\Models\Conversation::forOrder($order->id)->first();
                        @endphp
                        @if($existingThread)
                            <a href="{{ route('messages.show', $existingThread->id) }}" class="btn" style="background: rgba(75, 29, 242, 0.1); color: #4B1DF2; border: 1px solid #4B1DF2; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-comments me-2"></i> Voir la discussion
                            </a>
                        @else
                            <form action="{{ route('messages.create-order-thread', $order) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn" style="background: rgba(75, 29, 242, 0.1); color: #4B1DF2; border: 1px solid #4B1DF2; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s;">
                                    <i class="fas fa-comments me-2"></i> Contacter le support
                                </button>
                            </form>
                        @endif
                        @if(in_array($order->status, ['completed', 'delivered']) && $order->payment_status === 'paid')
                        <a href="{{ route('profile.reviews.create', $order) }}" class="btn" style="background: rgba(255, 184, 0, 0.1); color: #FFB800; border: 1px solid #FFB800; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s;">
                            <i class="fas fa-star me-2"></i> Laisser un avis
                        </a>
                        @endif
                        <a href="{{ route('profile.invoice.show', $order) }}" class="btn" style="background: rgba(14, 165, 233, 0.1); color: #0EA5E9; border: 1px solid #0EA5E9; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s;" target="_blank">
                            <i class="fas fa-file-invoice me-2"></i> Voir la facture
                        </a>
                        <a href="{{ route('profile.invoice.download', $order) }}" class="btn" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid #22C55E; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s;">
                            <i class="fas fa-download me-2"></i> Télécharger
                        </a>
                        <a href="{{ route('frontend.shop') }}" class="btn" style="background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%); color: white; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3); transition: all 0.3s;">
                            <i class="fas fa-store me-2"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    tbody tr:hover {
        background: rgba(237, 95, 30, 0.02) !important;
    }
</style>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
        ['label' => 'Détail Commande #' . $order->id, 'url' => null],
    ],
    'backUrl' => route('profile.orders'),
    'backText' => 'Retour aux commandes',
    'position' => 'bottom',
])
@endsection
