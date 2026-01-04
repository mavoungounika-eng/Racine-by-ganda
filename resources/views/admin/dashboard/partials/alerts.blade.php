<div class="dashboard-section mb-4">
    <h5 class="section-title mb-3">
        <span class="status-indicator orange"></span> Alertes & Priorités
    </h5>

    @if(empty($data) || (
        ($data['late_orders'] ?? 0) == 0 && 
        ($data['critical_stock'] ?? 0) == 0 && 
        ($data['failed_payments'] ?? 0) == 0 && 
        ($data['at_risk_creators'] ?? 0) == 0 && 
        !($data['low_conversion'] ?? false)
    ))
        <div class="alert alert-success mb-0">
            <i class="fas fa-check-circle"></i> Aucune alerte active
        </div>
    @else
        <div class="list-group">
            @if(($data['late_orders'] ?? 0) > 0)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <strong>{{ $data['late_orders'] }}</strong> commandes en retard de livraison
                    </div>
                    <a href="{{ route('admin.orders.index', ['filter' => 'late']) }}" class="btn btn-sm btn-outline-primary">
                        Voir détails
                    </a>
                </div>
            @endif

            @if(($data['critical_stock'] ?? 0) > 0)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-box text-danger"></i>
                        <strong>{{ $data['critical_stock'] }}</strong> produits en stock critique
                    </div>
                    <a href="{{ route('admin.products.index', ['filter' => 'low_stock']) }}" class="btn btn-sm btn-outline-primary">
                        Voir produits
                    </a>
                </div>
            @endif

            @if(($data['failed_payments'] ?? 0) > 0)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-credit-card text-danger"></i>
                        <strong>{{ $data['failed_payments'] }}</strong> paiements échoués aujourd'hui
                    </div>
                    <a href="{{ route('admin.payments.transactions.index', ['status' => 'failed']) }}" class="btn btn-sm btn-outline-primary">
                        Voir transactions
                    </a>
                </div>
            @endif

            @if(($data['at_risk_creators'] ?? 0) > 0)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-user-slash text-warning"></i>
                        <strong>{{ $data['at_risk_creators'] }}</strong> créateurs à surveiller
                    </div>
                    <a href="{{ route('admin.creators.index', ['filter' => 'at_risk']) }}" class="btn btn-sm btn-outline-primary">
                        Voir créateurs
                    </a>
                </div>
            @endif

            @if($data['low_conversion'] ?? false)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-chart-line text-danger"></i>
                        Conversion anormalement basse
                    </div>
                    <a href="{{ route('admin.analytics.funnel') }}" class="btn btn-sm btn-outline-primary">
                        Analyser funnel
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
