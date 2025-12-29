@extends('layouts.admin')

@section('title', 'Alertes Performance')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">⚠️ Alertes Performance</h1>
            <p class="text-muted">Routes dépassant les seuils critiques</p>
        </div>
    </div>

    {{-- Alertes critiques --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h2 class="h5 mb-0">🔴 Alertes Critiques</h2>
                    <small>Queries > 30 OU Temps réponse > 500ms</small>
                </div>
                <div class="card-body">
                    @if($criticalRoutes->isEmpty())
                        <p class="text-success mb-0">✅ Aucune alerte critique</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th class="text-end">Queries moyennes</th>
                                        <th class="text-end">Temps moyen</th>
                                        <th class="text-end">Appels</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($criticalRoutes as $route)
                                        <tr>
                                            <td><code>{{ $route->route }}</code></td>
                                            <td class="text-end">
                                                <span class="badge bg-danger">
                                                    {{ number_format($route->avg_queries, 1) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-danger">
                                                    {{ number_format($route->avg_response_time, 1) }}ms
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($route->hits) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Alertes warning --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h2 class="h5 mb-0">🟠 Alertes Modérées</h2>
                    <small>Queries > 20 (mais ≤ 30) ET Temps réponse ≤ 500ms</small>
                </div>
                <div class="card-body">
                    @if($warningRoutes->isEmpty())
                        <p class="text-success mb-0">✅ Aucune alerte modérée</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th class="text-end">Queries moyennes</th>
                                        <th class="text-end">Temps moyen</th>
                                        <th class="text-end">Appels</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($warningRoutes as $route)
                                        <tr>
                                            <td><code>{{ $route->route }}</code></td>
                                            <td class="text-end">
                                                <span class="badge bg-warning">
                                                    {{ number_format($route->avg_queries, 1) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success">
                                                    {{ number_format($route->avg_response_time, 1) }}ms
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($route->hits) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('admin.performance.index') }}" class="btn btn-secondary">← Retour au dashboard</a>
        </div>
    </div>
</div>
@endsection
