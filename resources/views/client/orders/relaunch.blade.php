@extends('layouts.internal')

@section('title', 'Relancer la Commande #' . $order->id . ' - RACINE BY GANDA')
@section('page-title', 'Relancer la Commande')
@section('page-subtitle', 'Commande #' . $order->id . ' — Vérifiez les quantités avant de relancer')

@section('content')

@if(session('success'))
    <div class="alert-flash alert-flash--success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('client.orders.relaunch.submit', $order) }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header py-3" style="background: #160D0C; border-radius: 12px 12px 0 0;">
            <h5 class="mb-0 text-white fw-semibold">Articles de la commande</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: rgba(22,13,12,0.04);">
                        <tr>
                            <th class="ps-4" style="color: #160D0C;">Produit</th>
                            <th class="text-center" style="color: #160D0C;">Prix unitaire</th>
                            <th class="text-center" style="color: #160D0C; width: 140px;">Quantit&eacute;</th>
                            <th class="text-end pe-4" style="color: #160D0C;">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            @if(!in_array($item->status, ['cancelled', 'refunded']))
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        @if($item->product && $item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}"
                                                 alt="{{ $item->product->title ?? '' }}"
                                                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                                        @endif
                                        <div>
                                            <strong style="color: #160D0C;">{{ $item->product->title ?? 'Produit supprim&eacute;' }}</strong>
                                            @if($item->product && $item->product->stock > 0)
                                                <br><small class="text-muted">Stock disponible : {{ $item->product->stock }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" style="color: #160D0C;">
                                    {{ number_format($item->price, 0, ',', ' ') }} XAF
                                </td>
                                <td class="text-center">
                                    <input type="number"
                                           name="quantities[{{ $item->id }}]"
                                           value="{{ $item->quantity }}"
                                           min="1"
                                           max="{{ $item->product ? min(100, $item->product->stock) : 100 }}"
                                           class="form-control form-control-sm text-center mx-auto"
                                           style="width: 80px; border-color: #ED5F1E;"
                                           required>
                                </td>
                                <td class="text-end pe-4 fw-semibold" style="color: #ED5F1E;">
                                    {{ number_format($item->price * $item->quantity, 0, ',', ' ') }} XAF
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="color: #160D0C;">Frais de livraison</span>
                <span style="color: #160D0C;">{{ number_format($order->shipping_cost ?? 0, 0, ',', ' ') }} XAF</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="color: #160D0C;">R&eacute;duction</span>
                <span style="color: #22C55E;">-{{ number_format($order->discount_amount, 0, ',', ' ') }} XAF</span>
            </div>
            @endif
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <strong style="color: #160D0C; font-size: 1.1rem;">Total actuel</strong>
                <strong style="color: #ED5F1E; font-size: 1.25rem;">{{ number_format($order->total_amount, 0, ',', ' ') }} XAF</strong>
            </div>
            <small class="text-muted">Le total sera recalcul&eacute; avec les nouvelles quantit&eacute;s.</small>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('profile.orders.show', $order) }}"
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
        <button type="submit"
                class="btn btn-lg px-5 text-white fw-semibold"
                style="background: #ED5F1E; border-color: #ED5F1E; border-radius: 8px;">
            <i class="fas fa-redo me-2"></i> Relancer la commande
        </button>
    </div>
</form>

@endsection
