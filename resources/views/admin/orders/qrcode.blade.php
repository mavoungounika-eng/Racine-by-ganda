@extends('layouts.admin')

@section('title', 'QR Code Commande #' . $order->id)
@section('page-title', 'QR Code Commande #' . str_pad($order->id, 6, '0', STR_PAD_LEFT))

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .info-item {
        padding: 1.25rem;
        background: rgba(18, 8, 6, 0.4);
        border-radius: 12px;
        border: 1px solid rgba(212, 165, 116, 0.1);
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #e2e8f0;
    }
    
    @media print {
        body {
            background: white;
        }
        .premium-card {
            background: white;
            border: 1px solid #ddd;
            box-shadow: none;
        }
        .no-print {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="premium-card">
        <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-qrcode text-racine-orange mr-2"></i>
            QR Code - Commande #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
        </h2>
        <p class="text-slate-400 mb-6">Scannez ce code pour accéder rapidement à la commande</p>

        <!-- QR Code Display -->
        <div class="flex justify-center mb-8">
            <div class="bg-white p-6 rounded-xl border-2 border-slate-700 shadow-xl">
                {!! QrCode::size(250)->margin(2)->generate($url) !!}
            </div>
        </div>

        <!-- Order Information -->
        <div class="pt-6 border-t border-slate-700">
            <h3 class="text-lg font-bold text-white mb-6" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-info-circle text-racine-orange mr-2"></i>
                Informations de la commande
            </h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Numéro de commande</div>
                    <div class="info-value">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>

                @if($order->user)
                <div class="info-item">
                    <div class="info-label">Client</div>
                    <div class="info-value">{{ $order->user->name }}</div>
                </div>
                @elseif($order->customer_name)
                <div class="info-item">
                    <div class="info-label">Client</div>
                    <div class="info-value">{{ $order->customer_name }}</div>
                </div>
                @endif

                <div class="info-item">
                    <div class="info-label">Montant total</div>
                    <div class="info-value text-racine-orange">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Statut</div>
                    <div class="info-value">
                        @php
                            $statusColors = [
                                'completed' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-400'],
                                'paid' => ['bg' => 'bg-blue-500/20', 'text' => 'text-blue-400'],
                                'shipped' => ['bg' => 'bg-purple-500/20', 'text' => 'text-purple-400'],
                                'cancelled' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-400'],
                                'pending' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-400'],
                            ];
                            $status = $statusColors[$order->status] ?? $statusColors['pending'];
                        @endphp
                        <span class="px-3 py-1 {{ $status['bg'] }} {{ $status['text'] }} rounded-full text-xs font-semibold">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">QR Token</div>
                    <div class="info-value text-xs font-mono text-slate-400 break-all">{{ $order->qr_token }}</div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-blue-400 mb-2">Utilisation au Showroom / Caisse</h3>
                    <p class="text-sm text-slate-300">
                        Ce QR Code peut être scanné avec un lecteur de code-barres pour accéder rapidement à cette commande depuis l'interface de scan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-between items-center no-print pt-6 border-t border-slate-700">
            <a href="{{ route('admin.orders.show', $order) }}"
               class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Retour à la commande
            </a>

            <button onclick="window.print()"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center gap-2">
                <i class="fas fa-print"></i>
                Imprimer
            </button>
        </div>
    </div>
</div>
@endsection
