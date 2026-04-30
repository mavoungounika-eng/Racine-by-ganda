<div class="dashboard-section">
    <h5 class="section-title mb-3">
        <span class="status-indicator" style="background: #fbbf24;"></span> Activité Commerciale
    </h5>

    <div class="mb-3">
        <h6 class="text-muted mb-2">Top 5 Produits RACINE (24h)</h6>
        @if(empty($data['top_products_brand']))
            <p class="text-muted small">Aucune vente aujourd'hui</p>
        @else
            <div class="list-group list-group-flush">
                @foreach($data['top_products_brand'] as $index => $product)
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>{{ $index + 1 }}. {{ $product->name ?? 'N/A' }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $product->sales_count ?? 0 }} ventes</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mb-3">
        <h6 class="text-muted mb-2">Paniers Abandonnés</h6>
        <div class="d-flex justify-content-between align-items-center">
            <span>{{ $data['abandoned_carts']['count'] ?? 0 }} paniers</span>
            <span class="text-muted">{{ number_format($data['abandoned_carts']['value'] ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>
</div>
