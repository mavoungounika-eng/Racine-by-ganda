<div class="dashboard-section">
    <h5 class="section-title mb-3">
        <span class="status-indicator" style="background: #22c55e;"></span> Marketplace
    </h5>

    <div class="row g-3">
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">CA Marketplace</div>
                <div class="h4 mb-0">{{ number_format($data['revenue'] ?? 0, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">Commandes</div>
                <div class="h4 mb-0">{{ $data['orders_count'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">Créateurs Actifs</div>
                <div class="h4 mb-0">{{ $data['active_creators'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">À Surveiller</div>
                <div class="h4 mb-0">{{ $data['at_risk_creators'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>
