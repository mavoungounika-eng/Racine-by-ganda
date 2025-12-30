@extends('layouts.internal')

@section('title', 'Mes Commandes - RACINE BY GANDA')
@section('page-title', 'Mes Commandes')
@section('page-subtitle', 'Historique et suivi de vos commandes')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- HEADER -->
        <div class="card-racine"
            <div class="card-body p-4 text-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2" style="font-weight: 700; font-size: 1.75rem;">
                            <i class="fas fa-shopping-bag me-3"></i>
                            Mes Commandes
                        </h3>
                        <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">
                            Suivez l'état de vos commandes et consultez votre historique d'achats
                        </p>
                    </div>
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-receipt" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS FILTRES -->
        <div class="card-racine"
            <div class="card-body p-0">
                <ul class="nav nav-tabs border-0" style="background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 16px 16px 0 0;">
                    <li class="nav-item">
                        <a class="nav-link {{ $statusFilter === 'toutes' ? 'active' : '' }}" 
                           href="{{ route('profile.orders') }}"
                           style="border: none; color: {{ $statusFilter === 'toutes' ? '#ED5F1E' : '#6c757d' }}; font-weight: {{ $statusFilter === 'toutes' ? '600' : '400' }}; padding: 1rem 1.5rem;">
                            <i class="fas fa-list me-2"></i>
                            Toutes
                            @if($statusFilter === 'toutes')
                                <span class="badge ms-2" style="background: #ED5F1E; color: white;">{{ $orders->total() }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $statusFilter === 'en-cours' ? 'active' : '' }}" 
                           href="{{ route('profile.orders', ['status' => 'en-cours']) }}"
                           style="border: none; color: {{ $statusFilter === 'en-cours' ? '#ED5F1E' : '#6c757d' }}; font-weight: {{ $statusFilter === 'en-cours' ? '600' : '400' }}; padding: 1rem 1.5rem;">
                            <i class="fas fa-clock me-2"></i>
                            En cours
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $statusFilter === 'terminees' ? 'active' : '' }}" 
                           href="{{ route('profile.orders', ['status' => 'terminees']) }}"
                           style="border: none; color: {{ $statusFilter === 'terminees' ? '#ED5F1E' : '#6c757d' }}; font-weight: {{ $statusFilter === 'terminees' ? '600' : '400' }}; padding: 1rem 1.5rem;">
                            <i class="fas fa-check-circle me-2"></i>
                            Terminées
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- TABLEAU DES COMMANDES -->
        @if($orders->count() > 0)
        <div class="card-racine"
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">N° Commande</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Date</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Articles</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Montant</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Statut</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Paiement</th>
                                <th style="padding: 1.25rem; font-weight: 600; color: #160D0C; border-bottom: 2px solid #e9ecef;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    <strong style="color: #160D0C; font-size: 1.1rem;">#{{ $order->id }}</strong>
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    <div>
                                        <div style="font-weight: 500; color: #160D0C;">{{ $order->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    <div>
                                        <span style="font-weight: 500; color: #160D0C;">{{ $order->items->count() }} article(s)</span>
                                        @if($order->items->count() > 0 && $order->items->first()->product)
                                            <br>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($order->items->first()->product->title ?? 'Produit', 30) }}</small>
                                            @if($order->items->count() > 1)
                                                <br>
                                                <small class="text-muted">+ {{ $order->items->count() - 1 }} autre(s)</small>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    <strong style="color: #ED5F1E; font-size: 1.1rem;">
                                        {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA
                                    </strong>
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    @php
                                        $statusConfig = [
                                            'pending' => ['label' => 'En attente', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.1)'],
                                            'processing' => ['label' => 'En traitement', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.1)'],
                                            'paid' => ['label' => 'Payée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.1)'],
                                            'shipped' => ['label' => 'Expédiée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.1)'],
                                            'completed' => ['label' => 'Complétée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.1)'],
                                            'delivered' => ['label' => 'Livrée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.1)'],
                                            'cancelled' => ['label' => 'Annulée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.1)'],
                                            'failed' => ['label' => 'Échouée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.1)'],
                                        ];
                                        $status = $statusConfig[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#6c757d', 'bg' => 'rgba(108, 117, 125, 0.1)'];
                                    @endphp
                                    <span class="badge" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }}; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; border: 1px solid {{ $status['color'] }}20;">
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
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
                                </td>
                                <td style="padding: 1.25rem; vertical-align: middle;">
                                    <a href="{{ route('profile.orders.show', $order) }}" 
                                       class="btn btn-sm" 
                                       style="background: rgba(237, 95, 30, 0.1); color: #ED5F1E; border: 1px solid rgba(237, 95, 30, 0.3); border-radius: 8px; padding: 0.5rem 1rem; font-weight: 500; transition: all 0.3s;">
                                        <i class="fas fa-eye me-1"></i> Voir
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div class="card-footer bg-white border-0 py-4" style="border-top: 2px solid #f0f0f0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Affichage de {{ $orders->firstItem() ?? 0 }} à {{ $orders->lastItem() ?? 0 }} sur {{ $orders->total() }} commande(s)
                            </small>
                        </div>
                        <div>
                            {{ $orders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- ÉTAT VIDE -->
        <div class="card-racine"
            <div class="card-body text-center py-5">
                <div style="width: 120px; height: 120px; background: linear-gradient(135deg, rgba(237, 95, 30, 0.1) 0%, rgba(255, 184, 0, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                    <i class="fas fa-shopping-bag" style="font-size: 4rem; color: #ED5F1E;"></i>
                </div>
                <h4 class="mb-3" style="color: #160D0C; font-weight: 600;">Aucune commande</h4>
                <p class="text-muted mb-4" style="font-size: 1.1rem;">
                    @if($statusFilter === 'en-cours')
                        Vous n'avez aucune commande en cours pour le moment.
                    @elseif($statusFilter === 'terminees')
                        Vous n'avez aucune commande terminée pour le moment.
                    @else
                        Vous n'avez pas encore passé de commande.
                    @endif
                </p>
                <a href="{{ route('frontend.shop') }}" class="btn" style="background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%); color: white; border-radius: 12px; padding: 0.75rem 2.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);">
                    <i class="fas fa-store me-2"></i> Découvrir la boutique
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    .nav-link {
        transition: all 0.3s;
    }
    .nav-link:hover {
        color: #ED5F1E !important;
        background: rgba(237, 95, 30, 0.05);
    }
    .nav-link.active {
        background: white !important;
        border-bottom: 3px solid #ED5F1E !important;
    }
    tbody tr:hover {
        background: rgba(237, 95, 30, 0.02) !important;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.2);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .table-responsive {
            display: block;
        }
        thead {
            display: none;
        }
        tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
        }
        tbody td {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0 !important;
            border: none;
        }
        tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            margin-right: 1rem;
        }
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
@endsection
