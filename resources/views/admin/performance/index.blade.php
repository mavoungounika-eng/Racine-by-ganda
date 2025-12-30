@extends('layouts.admin')

@section('title', 'Performance Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">📊 Dashboard Performance</h1>
            <p class="text-muted">Métriques de performance backend (debug mode uniquement)</p>
        </div>
    </div>

    {{-- Statistiques 24h --}}
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h5 mb-3">📅 Dernières 24 heures</h2>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Requêtes totales</h6>
                    <p class="h3 mb-0">{{ number_format($stats24h->total_requests ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Queries moyennes</h6>
                    <p class="h3 mb-0">{{ number_format($stats24h->avg_queries ?? 0, 1) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Temps DB moyen</h6>
                    <p class="h3 mb-0">{{ number_format($stats24h->avg_db_time ?? 0, 1) }}ms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Temps réponse moyen</h6>
                    <p class="h3 mb-0">{{ number_format($stats24h->avg_response_time ?? 0, 1) }}ms</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques 7 jours --}}
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h5 mb-3">📆 7 derniers jours</h2>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Requêtes totales</h6>
                    <p class="h3 mb-0">{{ number_format($stats7d->total_requests ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Queries moyennes</h6>
                    <p class="h3 mb-0">{{ number_format($stats7d->avg_queries ?? 0, 1) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Temps DB moyen</h6>
                    <p class="h3 mb-0">{{ number_format($stats7d->avg_db_time ?? 0, 1) }}ms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Temps réponse moyen</h6>
                    <p class="h3 mb-0">{{ number_format($stats7d->avg_response_time ?? 0, 1) }}ms</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Top 5 routes lentes --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">🐌 Top 5 Routes les plus lentes</h2>
                </div>
                <div class="card-body">
                    @if($slowestRoutes->isEmpty())
                        <p class="text-muted">Aucune donnée disponible</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th class="text-end">Temps moyen</th>
                                        <th class="text-end">Queries moyennes</th>
                                        <th class="text-end">Appels</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slowestRoutes as $route)
                                        <tr>
                                            <td><code>{{ $route->route }}</code></td>
                                            <td class="text-end">
                                                <span class="badge {{ $route->avg_response_time > 500 ? 'bg-danger' : ($route->avg_response_time > 300 ? 'bg-warning' : 'bg-success') }}">
                                                    {{ number_format($route->avg_response_time, 1) }}ms
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $route->avg_queries > 30 ? 'bg-danger' : ($route->avg_queries > 20 ? 'bg-warning' : 'bg-success') }}">
                                                    {{ number_format($route->avg_queries, 1) }}
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
            <a href="{{ route('admin.performance.routes') }}" class="btn btn-primary me-2">📋 Voir toutes les routes</a>
            <a href="{{ route('admin.performance.alerts') }}" class="btn btn-warning">⚠️ Voir les alertes</a>
        </div>
    </div>
</div>
@endsection
