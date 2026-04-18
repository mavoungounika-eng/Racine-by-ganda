@extends('layouts.admin')

@section('title', 'Dashboard Admin - RACINE BY GANDA')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Vue d\'ensemble de l\'activité')

@section('content')
<div class="dashboard-container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
        </div>
    @endif

    {{-- Bouton Rafraîchir --}}
    <div class="dashboard-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">Dernière mise à jour: {{ $last_updated ?? 'N/A' }}</small>
            </div>
            <form action="{{ route('admin.dashboard.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt"></i> Rafraîchir
                </button>
            </form>
        </div>
    </div>

    {{-- BLOC 1: État Global --}}
    @include('admin.dashboard.partials.global-state', ['data' => $global_state])

    {{-- BLOC 2: Alertes --}}
    @if(!empty($alerts))
        @include('admin.dashboard.partials.alerts', ['data' => $alerts])
    @endif

    {{-- BLOCS 3-4: Commercial + Marketplace --}}
    <div class="row mb-4">
        <div class="col-md-6">
            @include('admin.dashboard.partials.commercial-activity', ['data' => $commercial_activity])
        </div>
        <div class="col-md-6">
            @include('admin.dashboard.partials.marketplace', ['data' => $marketplace])
        </div>
    </div>

    {{-- BLOCS 5-6: Opérations + Tendances --}}
    <div class="row">
        <div class="col-md-6">
            @include('admin.dashboard.partials.operations', ['data' => $operations])
        </div>
        <div class="col-md-6">
            @include('admin.dashboard.partials.trends', ['data' => $trends])
        </div>
    </div>
</div>
@endsection

@push('styles')
<style nonce="{{ csp_nonce() }}">
    :root {
        --racine-black: #160D0C;
        --racine-orange: #ED5F1E;
        --racine-yellow: #FFB800;
        --racine-white: #FFFFFF;
        --racine-font-heading: 'Aleppo', 'Aileron', serif;
        --racine-font-body: 'Coco Gothic', 'Aileron', 'Helvetica Neue', sans-serif;
        --racine-font-accent: 'Aileron', 'Helvetica Neue', sans-serif;
    }

    .dashboard-kpi-card {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.2s;
        font-family: var(--racine-font-body);
    }
    .dashboard-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .dashboard-kpi-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0.5rem 0;
        color: #160D0C;
        font-family: var(--racine-font-heading);
    }
    .dashboard-kpi-label {
        font-size: 0.85rem;
        color: #160D0C;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: var(--racine-font-accent);
    }
    .dashboard-kpi-variation {
        font-size: 0.9rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }
    .dashboard-kpi-variation.positive { color: #FFB800; }
    .dashboard-kpi-variation.negative { color: #ED5F1E; }
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }
    .status-indicator.green { background: #FFB800; }
    .status-indicator.orange { background: #ED5F1E; }
    .status-indicator.red { background: #160D0C; }
    .status-indicator.neutral { background: #FFFFFF; border: 1px solid #160D0C; }
    .dashboard-section {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        font-family: var(--racine-font-body);
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        color: #160D0C;
        font-family: var(--racine-font-heading);
    }
</style>
@endpush
