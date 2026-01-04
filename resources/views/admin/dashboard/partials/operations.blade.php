<div class="dashboard-section">
    <h5 class="section-title mb-3">
        <span class="status-indicator" style="background: #3b82f6;"></span> Opérations & Logistique
    </h5>

    <div class="row g-3">
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">À Préparer</div>
                <div class="h4 mb-0">{{ $data['to_prepare'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">Prêtes Non Expédiées</div>
                <div class="h4 mb-0">{{ $data['ready_not_shipped'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">Retours</div>
                <div class="h4 mb-0">{{ $data['returns'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center">
                <div class="text-muted small">Incidents</div>
                <div class="h4 mb-0">{{ $data['incidents'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>
