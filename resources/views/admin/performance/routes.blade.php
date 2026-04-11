@extends('layouts.admin')

@section('title', 'Performance par Route')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">📋 Performance par Route</h1>
            <p class="text-muted">Analyse détaillée par endpoint</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Routes</h2>
                    <div>
                        <a href="{{ route('admin.performance.index') }}" class="btn btn-sm btn-secondary">← Retour</a>
                    </div>
                </div>
                <div class="card-body">
                    @if($routeStats->isEmpty())
                        <p class="text-muted">Aucune donnée disponible</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="{{ route('admin.performance.routes', ['sort' => 'route', 'dir' => $sortBy === 'route' && $sortDir === 'asc' ? 'desc' : 'asc']) }}">
                                                Route
                                                @if($sortBy === 'route')
                                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="text-end">
                                            <a href="{{ route('admin.performance.routes', ['sort' => 'hits', 'dir' => $sortBy === 'hits' && $sortDir === 'desc' ? 'asc' : 'desc']) }}">
                                                Appels
                                                @if($sortBy === 'hits')
                                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="text-end">
                                            <a href="{{ route('admin.performance.routes', ['sort' => 'avg_queries', 'dir' => $sortBy === 'avg_queries' && $sortDir === 'desc' ? 'asc' : 'desc']) }}">
                                                Queries moy.
                                                @if($sortBy === 'avg_queries')
                                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="text-end">
                                            <a href="{{ route('admin.performance.routes', ['sort' => 'avg_response_time', 'dir' => $sortBy === 'avg_response_time' && $sortDir === 'desc' ? 'asc' : 'desc']) }}">
                                                Temps moy.
                                                @if($sortBy === 'avg_response_time')
                                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="text-end">Temps DB moy.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($routeStats as $stat)
                                        <tr>
                                            <td><code>{{ $stat->route }}</code></td>
                                            <td class="text-end">{{ number_format($stat->hits) }}</td>
                                            <td class="text-end">
                                                <span class="badge {{ $stat->avg_queries > 30 ? 'bg-danger' : ($stat->avg_queries > 20 ? 'bg-warning' : 'bg-success') }}">
                                                    {{ number_format($stat->avg_queries, 1) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $stat->avg_response_time > 500 ? 'bg-danger' : ($stat->avg_response_time > 300 ? 'bg-warning' : 'bg-success') }}">
                                                    {{ number_format($stat->avg_response_time, 1) }}ms
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($stat->avg_db_time, 1) }}ms</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-3">
                            {{ $routeStats->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
