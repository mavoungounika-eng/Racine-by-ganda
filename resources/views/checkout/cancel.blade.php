@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body text-center py-5">
                    {{-- Icône --}}
                    <div class="mb-4">
                        <i class="fas fa-times-circle fa-4x text-danger"></i>
                    </div>

                    {{-- Titre --}}
                    <h2 class="h3 font-weight-bold mb-3">Paiement annulé</h2>

                    {{-- Message --}}
                    <p class="text-muted mb-4">
                        Vous avez annulé le processus de paiement. Votre commande est toujours enregistrée mais n'a pas été validée.
                    </p>

                    {{-- Informations commande --}}
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <p class="mb-1"><strong>Commande #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong></p>
                            <p class="mb-0 text-muted">Montant : {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>

                    {{-- Bouton réessayer selon payment_method --}}
                    @if($paymentMethod === 'card')
                        <form action="{{ route('checkout.card.pay') }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-credit-card mr-2"></i>
                                Réessayer le paiement par carte
                            </button>
                        </form>
                    @elseif($paymentMethod === 'mobile_money')
                        <a href="{{ route('checkout.mobile-money.form', $order) }}" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Réessayer le paiement Mobile Money
                        </a>
                    @else
                        {{-- Fallback générique --}}
                        <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fas fa-redo mr-2"></i>
                            Retour au checkout
                        </a>
                    @endif

                    {{-- Bouton retour accueil --}}
                    <a href="{{ route('frontend.home') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-home mr-2"></i>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
