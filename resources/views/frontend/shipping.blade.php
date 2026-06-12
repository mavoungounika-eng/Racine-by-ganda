@extends('layouts.frontend')
@section('title', 'Livraison & Délais — Racine by Ganda')

@section('content')
<div class="container py-5">

    {{-- Hero --}}
    <div class="text-center mb-5">
        <h1 class="fw-bold" style="color:var(--color-noir,#160D0C)">Livraison & Délais</h1>
        <p class="text-muted">Nous expédions partout en Afrique et dans le monde depuis Pointe-Noire, Congo.</p>
    </div>

    {{-- Zones --}}
    <div class="row g-4 mb-5">
        @php
            $shippingService = app(\App\Services\ShippingService::class);
            $zones = $shippingService->allZones();
        @endphp

        @foreach($zones as $key => $zone)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:42px;height:42px;border-radius:50%;background:var(--color-orange,#ED5F1E);display:flex;align-items:center;justify-content:center;">
                            @if($key === 'local')
                                <i class="fas fa-map-marker-alt text-white"></i>
                            @elseif($key === 'cemac')
                                <i class="fas fa-globe-africa text-white"></i>
                            @elseif($key === 'afrique')
                                <i class="fas fa-globe-africa text-white"></i>
                            @elseif($key === 'europe')
                                <i class="fas fa-globe-europe text-white"></i>
                            @else
                                <i class="fas fa-plane text-white"></i>
                            @endif
                        </div>
                        <h5 class="mb-0 fw-bold">{{ $zone['label'] }}</h5>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="fas fa-truck me-2 text-muted"></i>
                            <strong>{{ format_price($zone['cost']) }}</strong>
                            <span class="text-muted"> / commande</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-gift me-2 text-muted"></i>
                            Gratuite dès <strong>{{ format_price($zone['free_above']) }}</strong>
                        </li>
                        <li>
                            <i class="fas fa-clock me-2 text-muted"></i>
                            {{ $zone['delay'] }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Retrait --}}
    <div class="card border-0 mb-5" style="background:var(--color-jaune,#FFB800)20;">
        <div class="card-body p-4 d-flex gap-4 align-items-center flex-wrap">
            <div style="font-size:2.5rem;"><i class="fas fa-store"></i></div>
            <div>
                <h5 class="fw-bold mb-1">Retrait en showroom — Gratuit</h5>
                <p class="mb-0 text-muted small">Récupérez votre commande directement à notre showroom à Pointe-Noire. Nous vous préviendrons par e-mail dès qu'elle est prête.</p>
            </div>
        </div>
    </div>

    {{-- Tableau récapitulatif --}}
    <h4 class="fw-bold mb-3">Récapitulatif des tarifs</h4>
    <div class="table-responsive mb-5">
        <table class="table table-bordered align-middle">
            <thead style="background:var(--color-noir,#160D0C);color:#fff;">
                <tr>
                    <th>Zone</th>
                    <th>Délai estimé</th>
                    <th>Frais</th>
                    <th>Livraison gratuite dès</th>
                </tr>
            </thead>
            <tbody>
                @foreach($zones as $key => $zone)
                <tr>
                    <td class="fw-semibold">{{ $zone['label'] }}</td>
                    <td>{{ $zone['delay'] }}</td>
                    <td>{{ format_price($zone['cost']) }}</td>
                    <td><span class="badge" style="background:var(--color-orange,#ED5F1E)">{{ format_price($zone['free_above']) }}</span></td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2" class="fw-semibold">Retrait showroom (Pointe-Noire)</td>
                    <td colspan="2"><span class="badge bg-success">Gratuit</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Note info --}}
    <div class="alert alert-info border-0">
        <i class="fas fa-info-circle me-2"></i>
        Les frais de livraison affichés sont calculés automatiquement à partir de votre adresse de livraison lors du paiement.
        Les prix s'affichent dans la devise sélectionnée en haut de page.
    </div>

</div>
@endsection
