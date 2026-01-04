<div class="dashboard-section mb-4">
    <h5 class="section-title mb-3">
        <span class="status-indicator red"></span> État Global
    </h5>

    <div class="row g-3">
        {{-- CA du jour --}}
        <div class="col-md">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-label">CA du Jour</div>
                <div class="dashboard-kpi-value">
                    {{ $data['revenue']['formatted'] ?? 'N/A' }}
                </div>
                @if(isset($data['revenue']['variation']))
                    <div class="dashboard-kpi-variation {{ $data['revenue']['variation'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $data['revenue']['variation'] >= 0 ? '+' : '' }}{{ $data['revenue']['variation'] }}% vs J-1
                    </div>
                @endif
                <span class="status-indicator {{ $data['revenue']['status'] ?? 'neutral' }}"></span>
            </div>
        </div>

        {{-- Nombre de commandes --}}
        <div class="col-md">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-label">Commandes</div>
                <div class="dashboard-kpi-value">
                    {{ $data['orders_count']['value'] ?? 'N/A' }}
                </div>
                @if(isset($data['orders_count']['variation']))
                    <div class="dashboard-kpi-variation {{ $data['orders_count']['variation'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $data['orders_count']['variation'] >= 0 ? '+' : '' }}{{ $data['orders_count']['variation'] }}%
                    </div>
                @endif
                <span class="status-indicator {{ $data['orders_count']['status'] ?? 'neutral' }}"></span>
            </div>
        </div>

        {{-- Panier moyen --}}
        <div class="col-md">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-label">Panier Moyen</div>
                <div class="dashboard-kpi-value">
                    {{ $data['average_basket']['formatted'] ?? 'N/A' }}
                </div>
                @if(isset($data['average_basket']['variation']))
                    <div class="dashboard-kpi-variation {{ $data['average_basket']['variation'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $data['average_basket']['variation'] >= 0 ? '+' : '' }}{{ $data['average_basket']['variation'] }}%
                    </div>
                @endif
            </div>
        </div>

        {{-- Taux de conversion --}}
        <div class="col-md">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-label">Conversion</div>
                <div class="dashboard-kpi-value">
                    {{ $data['conversion_rate']['formatted'] ?? 'N/A' }}
                </div>
                <span class="status-indicator {{ $data['conversion_rate']['status'] ?? 'neutral' }}"></span>
            </div>
        </div>

        {{-- Commandes en attente --}}
        <div class="col-md">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-label">En Attente</div>
                <div class="dashboard-kpi-value">
                    {{ $data['pending_orders']['value'] ?? 'N/A' }}
                </div>
                <span class="status-indicator {{ $data['pending_orders']['status'] ?? 'neutral' }}"></span>
            </div>
        </div>
    </div>
</div>
