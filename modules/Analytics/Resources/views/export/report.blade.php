<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Analytics - RACINE BY GANDA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a2e;
            line-height: 1.6;
            background: white;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Header */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #ED5F1E;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .brand-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #160D0C;
        }
        
        .brand-name small {
            display: block;
            font-size: 0.9rem;
            font-weight: 400;
            color: #6c757d;
        }
        
        .report-meta {
            text-align: right;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .report-meta strong {
            color: #160D0C;
            font-size: 1.1rem;
        }
        
        /* Section Title */
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #ED5F1E;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .kpi-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .kpi-box.highlight {
            background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
            color: white;
            border: none;
        }
        
        .kpi-box .value {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .kpi-box .label {
            font-size: 0.85rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .kpi-box .growth {
            font-size: 0.85rem;
            margin-top: 8px;
            padding: 3px 10px;
            border-radius: 15px;
            display: inline-block;
        }
        
        .kpi-box .growth.positive {
            background: rgba(40, 167, 69, 0.15);
            color: #28A745;
        }
        
        .kpi-box .growth.negative {
            background: rgba(220, 53, 69, 0.15);
            color: #DC3545;
        }
        
        /* Insights */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .insight-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid;
        }
        
        .insight-item.success { border-color: #28A745; background: rgba(40, 167, 69, 0.05); }
        .insight-item.warning { border-color: #FFC107; background: rgba(255, 193, 7, 0.05); }
        .insight-item.danger { border-color: #DC3545; background: rgba(220, 53, 69, 0.05); }
        .insight-item.info { border-color: #17A2B8; background: rgba(23, 162, 184, 0.05); }
        
        .insight-icon {
            font-size: 1.5rem;
        }
        
        .insight-content strong {
            display: block;
            margin-bottom: 3px;
        }
        
        .insight-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }
        
        /* Tables */
        .table-container {
            margin-bottom: 40px;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #160D0C;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .rank {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }
        
        .rank.gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .rank.silver { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); color: white; }
        .rank.bronze { background: linear-gradient(135deg, #CD7F32, #8B4513); color: white; }
        .rank.normal { background: #e9ecef; color: #495057; }
        
        /* Monthly Chart (simplified for print) */
        .monthly-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 40px;
        }
        
        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .bar {
            width: 40px;
            background: linear-gradient(180deg, #ED5F1E 0%, #FFB800 100%);
            border-radius: 8px 8px 0 0;
            min-height: 20px;
        }
        
        .bar-label {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Footer */
        .report-footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .container {
                padding: 20px;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(237, 95, 30, 0.3);
            transition: transform 0.2s;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        🖨️ Imprimer / PDF
    </button>

    <div class="container">
        {{-- Header --}}
        <div class="report-header">
            <div class="brand">
                <div class="brand-logo">R</div>
                <div class="brand-name">
                    RACINE BY GANDA
                    <small>Rapport Business Intelligence</small>
                </div>
            </div>
            <div class="report-meta">
                <strong>Rapport Analytics</strong><br>
                Généré le : {{ $reportDate }}<br>
                Période : {{ $periodStart }} - {{ $periodEnd }}
            </div>
        </div>

        {{-- KPIs --}}
        <h2 class="section-title">📊 Indicateurs Clés de Performance</h2>
        <div class="kpi-grid">
            <div class="kpi-box highlight">
                <div class="value">{{ $kpis['revenue']['formatted'] }}</div>
                <div class="label">Revenus du mois</div>
                <span class="growth {{ $kpis['revenue']['growth'] >= 0 ? 'positive' : 'negative' }}" style="background: rgba(255,255,255,0.2); color: white;">
                    {{ $kpis['revenue']['growth'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['revenue']['growth']) }}%
                </span>
            </div>
            <div class="kpi-box">
                <div class="value">{{ $kpis['orders']['value'] }}</div>
                <div class="label">Commandes</div>
                <span class="growth {{ $kpis['orders']['growth'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $kpis['orders']['growth'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['orders']['growth']) }}%
                </span>
            </div>
            <div class="kpi-box">
                <div class="value">{{ $kpis['avg_cart']['formatted'] }}</div>
                <div class="label">Panier Moyen</div>
            </div>
            <div class="kpi-box">
                <div class="value">{{ $kpis['conversion_rate'] }}%</div>
                <div class="label">Taux de Conversion</div>
            </div>
        </div>

        {{-- Insights --}}
        @if(count($insights) > 0)
        <h2 class="section-title">🧠 Insights Intelligents</h2>
        <div class="insights-grid">
            @foreach($insights as $insight)
            <div class="insight-item {{ $insight['type'] }}">
                <span class="insight-icon">{{ $insight['icon'] }}</span>
                <div class="insight-content">
                    <strong>{{ $insight['title'] }}</strong>
                    <p>{{ $insight['message'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Top Produits --}}
        <h2 class="section-title">🏆 Top 10 Produits</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Rang</th>
                        <th>Produit</th>
                        <th style="width: 120px;">Quantité</th>
                        <th style="width: 150px;">Revenus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $index => $product)
                    <tr>
                        <td>
                            <span class="rank {{ $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'normal')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td><strong>{{ $product['title'] }}</strong></td>
                        <td>{{ $product['total_sold'] }} unités</td>
                        <td><strong style="color: #28A745;">{{ $product['revenue_formatted'] }}</strong></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
                            Aucune donnée disponible
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Évolution Mensuelle --}}
        <h2 class="section-title">📈 Évolution sur 6 Mois</h2>
        <div class="monthly-bars">
            @php
                $maxRevenue = max(array_column($monthlyData['datasets'][0]['data'] ?? [0], null) ?: [1]);
            @endphp
            @foreach($monthlyData['labels'] ?? [] as $index => $label)
            <div class="bar-group">
                <div class="bar" style="height: {{ ($monthlyData['datasets'][0]['data'][$index] ?? 0) / $maxRevenue * 150 }}px;"></div>
                <div class="bar-label">{{ $label }}</div>
            </div>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="report-footer">
            <p>
                <strong>RACINE BY GANDA</strong> - Mode Africaine Premium<br>
                Ce rapport a été généré automatiquement par le module Analytics.<br>
                © {{ date('Y') }} Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>

