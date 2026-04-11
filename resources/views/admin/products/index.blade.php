@extends('layouts.admin')

@section('title', 'Produits')
@section('page-title', 'Gestion des Produits')
@section('page-subtitle', 'Gérer tous les produits de la plateforme')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-box text-racine-orange me-2"></i>
            Gestion des Produits
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            {{ $products->total() }} produit(s) au total
        </p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouveau Produit
    </a>
</div>

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('admin.products.index'),
    'search' => true,
    'filters' => [
        [
            'name' => 'category_id',
            'label' => 'Catégorie',
            'type' => 'select',
            'icon' => 'fas fa-tags',
            'width' => 3,
            'options' => array_merge(
                [['value' => '', 'label' => 'Toutes les catégories']],
                $categories->map(function($cat) {
                    return ['value' => $cat->id, 'label' => $cat->name];
                })->toArray()
            )
        ],
        [
            'name' => 'is_active',
            'label' => 'Statut',
            'type' => 'select',
            'icon' => 'fas fa-toggle-on',
            'width' => 2,
            'options' => [
                ['value' => '', 'label' => 'Tous les statuts'],
                ['value' => '1', 'label' => 'Actifs'],
                ['value' => '0', 'label' => 'Inactifs']
            ]
        ]
    ]
])

{{-- Tableau des produits --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">
                            <i class="fas fa-image me-2"></i>Image
                        </th>
                        <th>
                            <i class="fas fa-tag me-2"></i>Nom
                        </th>
                        <th>
                            <i class="fas fa-barcode me-2"></i>SKU
                        </th>
                        <th>
                            <i class="fas fa-folder me-2"></i>Catégorie
                        </th>
                        <th>
                            <i class="fas fa-money-bill me-2"></i>Prix
                        </th>
                        <th>
                            <i class="fas fa-warehouse me-2"></i>Stock
                        </th>
                        <th>
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-end" style="width: 120px;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            @if($product->main_image)
                                <img src="{{ asset('storage/' . $product->main_image) }}" 
                                     alt="{{ $product->title }}"
                                     class="rounded"
                                     style="width: 64px; height: 64px; object-fit: cover; border: 2px solid var(--racine-beige);">
                            @else
                                <div class="rounded d-flex align-items-center justify-content-center bg-light"
                                     style="width: 64px; height: 64px; border: 2px solid var(--racine-beige);">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-racine-black">{{ $product->title }}</div>
                            @if($product->description)
                                <div class="small text-muted mt-1">
                                    {{ Str::limit($product->description, 50) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($product->sku)
                                <div class="d-flex align-items-center gap-2">
                                    <code class="text-racine-orange small">{{ $product->sku }}</code>
                                    <button onclick="copyToClipboard('{{ $product->sku }}')" 
                                            class="btn btn-sm btn-link text-racine-orange p-0" 
                                            title="Copier SKU">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                @if($product->barcode)
                                    <div class="small text-muted mt-1">
                                        <code>{{ $product->barcode }}</code>
                                    </div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Non généré</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ $product->category?->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-racine-orange">
                                {{ number_format($product->price, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td>
                            @if($product->stock > 10)
                                <span class="badge bg-success">{{ $product->stock }}</span>
                            @elseif($product->stock > 0)
                                <span class="badge bg-warning text-dark">{{ $product->stock }}</span>
                            @else
                                <span class="badge bg-danger">0</span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-pause-circle me-1"></i>Inactif
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.products.edit', $product) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucun produit trouvé</p>
                                <a href="{{ route('admin.products.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Créer votre premier produit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $products->firstItem() ?? 0 }} à {{ $products->lastItem() ?? 0 }} sur {{ $products->total() }} résultats
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Notification de succès
        const notification = document.createElement('div');
        notification.className = 'alert alert-success position-fixed top-0 end-0 m-3';
        notification.style.zIndex = '9999';
        notification.innerHTML = '<i class="fas fa-check me-2"></i>Copié : ' + text;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }).catch(function(err) {
        console.error('Erreur lors de la copie:', err);
        alert('Erreur lors de la copie');
    });
}
</script>
@endpush

