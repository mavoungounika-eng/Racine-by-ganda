@extends('layouts.creator')

@section('title', 'Analytique & Conformité - RACINE BY GANDA')
@section('page-title', 'Mes Finances (Analytique)')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .finance-stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid #F0EBE5;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .finance-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--racine-orange);
    }

    .finance-stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--stat-gradient, var(--racine-orange));
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8B7355;
        margin-bottom: 0.5rem;
        display: block;
    }

    .stat-value {
        font-family: 'Libre Baskerville', serif;
        font-weight: 700;
        color: var(--racine-black);
        margin-bottom: 0;
    }

    .stat-currency {
        font-size: 0.9rem;
        color: #8B7355;
        font-weight: 600;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .bg-ca { --stat-gradient: linear-gradient(90deg, #ED5F1E, #FFB800); }
    .bg-sales { --stat-gradient: linear-gradient(90deg, #160D0C, #4A3B39); }
    .bg-kyc { --stat-gradient: linear-gradient(90deg, #D4A574, #A67C52); }

    .kyc-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    
    .kyc-verified { background: #DCFCE7; color: #15803D; }
    .kyc-pending { background: #FEF3C7; color: #D97706; }
    .kyc-incomplete { background: #FEE2E2; color: #B91C1C; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    {{-- Navigation Unifiée --}}
    @include('creator.partials.settings-nav')

    {{-- Header d'information SaaS --}}
    <div class="alert alert-info border-0 shadow-sm rounded-lg mb-4" style="background: #F0F7FF; color: #005691;">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <h6 class="font-weight-bold mb-1">Modèle SaaS Pur : Pas de gestion de fonds tiers</h6>
                <p class="mb-0 small">RACINE ne prélève aucune commission. Ces chiffres représentent votre volume d'affaires direct encaissé via vos propres passerelles (Stripe/MoMo).</p>
            </div>
        </div>
    </div>

    {{-- Cartes de Statistiques Analytiques --}}
    <div class="row mb-5">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="finance-stat-card bg-ca">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="stat-label">Volume d'Affaire (Brut)</span>
                        <h2 class="stat-value h1">{{ number_format($metrics['gross_revenue'], 0, ',', ' ') }}</h2>
                        <span class="stat-currency">FCFA</span>
                    </div>
                    <div class="stat-icon" style="background: #FFF7ED; color: #ED5F1E;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="finance-stat-card bg-sales">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="stat-label">Nombre de Ventes</span>
                        <h2 class="stat-value h1">{{ $metrics['sales_count'] }}</h2>
                        <span class="stat-currency">Transactions Fulfilled</span>
                    </div>
                    <div class="stat-icon" style="background: #F3F4F6; color: #111827;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="finance-stat-card bg-kyc">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="stat-label">Statut Conformité</span>
                        <div class="mt-2">
                            <span class="kyc-badge kyc-{{ $kycStatus['status'] }}">
                                {{ strtoupper($kycStatus['status']) }}
                            </span>
                        </div>
                        <small class="text-muted d-block mt-2">Droit d'usage plateforme</small>
                    </div>
                    <div class="stat-icon" style="background: #FFFBEB; color: #D4A574;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Section KYC Contractuel --}}
        <div class="col-lg-5 mb-4">
            <div class="creator-card h-100 border-0 shadow-lg">
                <div class="card-header bg-dark py-3 px-4">
                    <h4 class="h5 font-weight-bold text-white mb-0">
                        <i class="fas fa-file-contract me-2"></i> Documents Contractuels (KYC)
                    </h4>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-4">Pour maintenir votre boutique active, vous devez soumettre vos documents d'identité et fiscaux.</p>
                    
                    <ul class="list-group list-group-flush mb-4">
                        @foreach(['identity_card' => 'Pièce d\'identité (CNI/Passeport)', 'registration_certificate' => 'RCCM / Certificat', 'tax_id' => 'NIU (Numéro Fiscal)'] as $key => $label)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-file-alt text-muted me-2"></i> {{ $label }}
                            </div>
                            @if(in_array($key, $kycStatus['missing_documents']))
                                <span class="badge bg-danger">Manquant</span>
                            @else
                                <span class="badge bg-success">Soumis</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>

                    <hr>

                    <h6 class="font-weight-bold mb-3">Soumettre un nouveau document</h6>
                    <form action="{{ route('creator.finances.kyc-submit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <select name="document_type" class="form-control" required>
                                <option value="">Type de document...</option>
                                <option value="identity_card">Pièce d'Identité</option>
                                <option value="registration_certificate">RCCM / Enregistrement</option>
                                <option value="tax_id">NIU / Fiscalité</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-file">
                                <input type="file" name="file" class="custom-file-input" id="kycFile" required>
                                <label class="custom-file-label" for="kycFile">Choisir le fichier (PDF, JPG, PNG)</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark btn-block font-weight-bold">
                            <i class="fas fa-upload me-2"></i> ENVOYER POUR VÉRIFICATION
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Top Produits (Analytique) --}}
        <div class="col-lg-7 mb-4">
            <div class="creator-card h-100 border-0 shadow-lg">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h4 class="h5 font-weight-bold mb-0" style="color: var(--racine-black); font-family: 'Libre Baskerville', serif;">
                        <i class="fas fa-trophy text-orange me-2"></i> Top Produits (Volume Ventes)
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th>Produit</th>
                                    <th class="text-end">Volume</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($metrics['top_products'] as $product)
                                <tr class="border-bottom">
                                    <td class="py-3 font-weight-bold">{{ $product->product_name }}</td>
                                    <td class="py-3 text-end">
                                        <span class="badge badge-pill bg-light text-dark px-3 py-2 font-weight-bold">
                                            {{ $product->qty }} ventes
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted mt-4">
                                        Pas encore de données de vente suffisantes.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Historique Analytique --}}
    <div class="creator-card border-0 shadow-lg overflow-hidden mt-4">
        <div class="card-header bg-dark py-3 px-4">
            <h4 class="h5 font-weight-bold text-white mb-0">
                <i class="fas fa-history me-2"></i> Dernières Ventes Fulfilled (Analytique)
            </h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Commande</th>
                        <th>Date de remise</th>
                        <th>Lieu (POS)</th>
                        <th>Méthode Paiement</th>
                        <th class="text-end">Montant Brut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td class="font-weight-bold">#{{ str_pad($sale->order_id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $sale->fulfilled_at ? $sale->fulfilled_at->format('d/m/Y H:i') : 'En attente' }}</td>
                        <td>{{ $sale->pickup_location }}</td>
                        <td><span class="badge bg-info">{{ $sale->payment_method }}</span></td>
                        <td class="text-end font-weight-bold">{{ number_format($sale->gross_amount, 0, ',', ' ') }} F</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun enregistrement de vente trouvé</h5>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
