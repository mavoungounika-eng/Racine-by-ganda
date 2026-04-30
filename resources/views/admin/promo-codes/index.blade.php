@extends('layouts.admin-master')

@section('title', 'Codes Promo')
@section('page-title', 'Codes Promo')
@section('page-subtitle', 'Gérez les codes de réduction appliqués au checkout')

@section('content')

{{-- En-tête --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-tags text-racine-orange me-2"></i>
            Codes Promo
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Pourcentage, montant fixe ou livraison gratuite
        </p>
    </div>
    <a href="{{ route('admin.promo-codes.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouveau code promo
    </a>
</div>

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('admin.promo-codes.index'),
    'search' => true,
    'filters' => [
        [
            'name' => 'type',
            'label' => 'Type',
            'type' => 'select',
            'icon' => 'fas fa-tag',
            'width' => 3,
            'options' => [
                ['value' => '', 'label' => 'Tous les types'],
                ['value' => 'percentage', 'label' => 'Pourcentage (%)'],
                ['value' => 'fixed', 'label' => 'Montant fixe (FCFA)'],
                ['value' => 'free_shipping', 'label' => 'Livraison gratuite'],
            ]
        ],
        [
            'name' => 'status',
            'label' => 'Statut',
            'type' => 'select',
            'icon' => 'fas fa-toggle-on',
            'width' => 3,
            'options' => [
                ['value' => '', 'label' => 'Tous'],
                ['value' => 'active', 'label' => 'Actif & valide'],
                ['value' => 'upcoming', 'label' => 'À venir'],
                ['value' => 'expired', 'label' => 'Expiré'],
            ]
        ]
    ]
])

{{-- Tableau --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-barcode me-2"></i>Code
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-tag me-2"></i>Nom
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-percent me-2"></i>Type / Valeur
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-chart-bar me-2"></i>Utilisations
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-calendar me-2"></i>Période
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promoCodes as $promo)
                    @php
                        $now = now();
                        $isExpired = $promo->expires_at && $now->gt($promo->expires_at);
                        $isUpcoming = $promo->starts_at && $now->lt($promo->starts_at);
                        $isExhausted = $promo->max_uses && $promo->used_count >= $promo->max_uses;
                    @endphp
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <code class="text-racine-orange fw-bold" style="font-size: 0.95rem;">{{ $promo->code }}</code>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ $promo->name }}</div>
                            @if($promo->description)
                                <div class="small text-muted mt-1" style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $promo->description }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($promo->type === 'percentage')
                                <span class="badge bg-info text-white">
                                    <i class="fas fa-percent me-1"></i>{{ number_format($promo->value, 0) }}%
                                </span>
                            @elseif($promo->type === 'fixed')
                                <span class="badge bg-primary text-white">
                                    <i class="fas fa-coins me-1"></i>{{ number_format($promo->value, 0, ',', ' ') }} FCFA
                                </span>
                            @else
                                <span class="badge bg-success text-white">
                                    <i class="fas fa-shipping-fast me-1"></i>Livraison gratuite
                                </span>
                            @endif
                            @if($promo->min_amount)
                                <div class="small text-muted mt-1">
                                    min {{ number_format($promo->min_amount, 0, ',', ' ') }} FCFA
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="badge bg-light text-dark">
                                {{ $promo->used_count }}{{ $promo->max_uses ? ' / '.$promo->max_uses : '' }}
                            </span>
                            @if($promo->max_uses_per_user)
                                <div class="small text-muted mt-1">
                                    max {{ $promo->max_uses_per_user }}/client
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="small">
                                @if($promo->starts_at)
                                    <div class="text-muted">Dès {{ $promo->starts_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($promo->expires_at)
                                    <div class="text-muted">Jusqu au {{ $promo->expires_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if(!$promo->starts_at && !$promo->expires_at)
                                    <span class="text-muted">Permanent</span>
                                @endif
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if(!$promo->is_active)
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-pause-circle me-1"></i>Désactivé
                                </span>
                            @elseif($isExpired)
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fas fa-clock me-1"></i>Expiré
                                </span>
                            @elseif($isUpcoming)
                                <span class="badge bg-warning text-dark rounded-pill">
                                    <i class="fas fa-hourglass-start me-1"></i>À venir
                                </span>
                            @elseif($isExhausted)
                                <span class="badge bg-dark rounded-pill">
                                    <i class="fas fa-ban me-1"></i>Épuisé
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @endif
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.promo-codes.edit', $promo) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-md-inline ms-1">Modifier</span>
                                </a>
                                <button type="button"
                                        onclick="openDeletePromoModal({{ $promo->id }}, '{{ addslashes($promo->code) }}', {{ $promo->usages_count ?? 0 }})"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                    <span class="d-none d-md-inline ms-1">Supprimer</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-tags fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucun code promo trouvé</p>
                                <a href="{{ route('admin.promo-codes.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Créer votre premier code
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($promoCodes->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $promoCodes->firstItem() ?? 0 }} à {{ $promoCodes->lastItem() ?? 0 }} sur {{ $promoCodes->total() }} résultats
                </div>
                <div>
                    {{ $promoCodes->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal suppression --}}
<div class="modal fade" id="deletePromoModal" tabindex="-1" aria-labelledby="deletePromoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="deletePromoModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deletePromoForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-0">
                        Supprimer le code promo <strong id="promoCodeLabel" class="text-racine-black"></strong> ?
                    </p>
                    <div id="promoUsagesWarning" class="alert alert-warning mt-3 mb-0 d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> ce code a déjà été utilisé. La suppression sera refusée par le serveur ; désactivez-le plutôt.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
function openDeletePromoModal(id, code, usages) {
    document.getElementById('promoCodeLabel').textContent = code;
    document.getElementById('deletePromoForm').action =
        '{{ route('admin.promo-codes.destroy', ':id') }}'.replace(':id', id);
    const warn = document.getElementById('promoUsagesWarning');
    if (usages && usages > 0) {
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
    const modal = new bootstrap.Modal(document.getElementById('deletePromoModal'));
    modal.show();
}
</script>
@endpush
