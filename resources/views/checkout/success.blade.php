@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    {{-- Message de succès --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 2rem; border-left: 4px solid #28a745; background: #f8f9fa; border-radius: 8px;">
            <i class="fas fa-check-circle mr-2" style="color: #28a745; font-size: 1.2rem;"></i>
            <strong>{{ session('success') }}</strong>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Message d'erreur (au cas où) --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 2rem; border-left: 4px solid #dc3545; background: #f8f9fa; border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2" style="color: #dc3545; font-size: 1.2rem;"></i>
            <strong>{{ session('error') }}</strong>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Card principale --}}
    <div class="card shadow-lg">
        {{-- Header --}}
        <div class="card-header bg-dark text-white text-center py-4">
            <div class="mb-3">
                <i class="fas fa-check-circle fa-3x text-success"></i>
            </div>
            <h1 class="h3 mb-2">Commande confirmée !</h1>
            <p class="mb-0">Merci pour votre achat. Votre commande a été enregistrée avec succès.</p>
        </div>

        {{-- Contenu --}}
        <div class="card-body p-4">
            {{-- Numéro de commande --}}
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Numéro de commande</p>
                            <h4 class="font-weight-bold">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h4>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <p class="text-muted mb-1">Date</p>
                            <h5 class="font-weight-bold">{{ $order->created_at->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statut du paiement --}}
            @if($order->payment_status === 'paid')
                <div class="alert alert-success">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x mr-3"></i>
                        <div>
                            <strong>Paiement confirmé</strong>
                            <p class="mb-0">Votre paiement a été traité avec succès. Merci !</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                        <div class="flex-grow-1">
                            <strong>Paiement en attente</strong>
                            <p class="mb-0">Votre commande est enregistrée mais le paiement est en attente.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Résumé de la commande --}}
            <div class="border-top pt-4 mb-4">
                <h3 class="h5 font-weight-bold mb-3">Résumé de la commande</h3>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Quantité</th>
                                <th class="text-right">Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product->title ?? $item->product->name ?? 'Produit' }}</strong>
                                    </td>
                                    <td class="text-right">{{ $item->quantity }}</td>
                                    <td class="text-right">{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total</th>
                                <th class="text-right text-primary">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Informations de livraison --}}
            @if($order->customer_address)
                <div class="border-top pt-4 mb-4">
                    <h3 class="h5 font-weight-bold mb-3">Adresse de livraison</h3>
                    <div class="card bg-light">
                        <div class="card-body">
                            <p class="mb-1"><strong>{{ $order->customer_name }}</strong></p>
                            @if($order->customer_phone)
                                <p class="mb-1 text-muted">{{ $order->customer_phone }}</p>
                            @endif
                            <p class="mb-0 text-muted">{{ $order->customer_address }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Message selon le mode de paiement --}}
            @php
                $paymentMethod = $order->payment_method ?? 'card';
            @endphp
            
            @if($order->payment_status !== 'paid')
                @if($paymentMethod === 'cash_on_delivery')
                    <div class="alert alert-info border-left-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-truck fa-2x mr-3"></i>
                            <div>
                                <strong class="d-block mb-1">Paiement à la livraison</strong>
                                <p class="mb-0">Votre commande est confirmée. Vous paierez le montant de <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong> lors de la réception de votre commande.</p>
                            </div>
                        </div>
                    </div>
                @elseif($paymentMethod === 'card')
                    <form action="{{ route('checkout.card.pay') }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-credit-card mr-2"></i>
                            Payer {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA maintenant
                        </button>
                    </form>
                @elseif($paymentMethod === 'mobile_money')
                    <a href="{{ route('checkout.mobile-money.form', $order) }}" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-mobile-alt mr-2"></i>
                        Payer avec Mobile Money
                    </a>
                @endif
            @endif

            {{-- Actions --}}
            <div class="border-top pt-4">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('frontend.shop') }}" class="btn btn-primary btn-block">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Continuer mes achats
                        </a>
                    </div>
                    @auth
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('profile.orders') }}" class="btn btn-outline-dark btn-block">
                            <i class="fas fa-list mr-2"></i>
                            Voir mes commandes
                        </a>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="alert alert-info mt-4">
        <div class="d-flex">
            <i class="fas fa-info-circle fa-2x mr-3"></i>
            <div>
                <strong>Prochaines étapes :</strong>
                <p class="mb-0">
                    Vous recevrez un email de confirmation avec les détails de votre commande.
                    @if($order->payment_status !== 'paid')
                        @if($paymentMethod === 'cash_on_delivery')
                            Vous paierez à la livraison.
                        @else
                            Vous pouvez compléter le paiement ci-dessus.
                        @endif
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
