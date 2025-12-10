@extends('layouts.admin-master')

@section('title', 'CRM - Tableau de Bord')
@section('page-title', 'CRM - Tableau de Bord')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h1 class="h2 mb-1">📈 Module CRM</h1>
                        <p class="text-muted mb-0">Gestion de la relation client et des opportunités</p>
                    </div>
                    <div>
                        <span class="badge bg-primary py-2 px-3">v1.1</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row 1 --}}
        <div class="row mb-4">
            {{-- Pipeline Value --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card bg-primary text-white">
                    <div class="card-body">
                        <div class="kpi-icon">💰</div>
                        <div class="kpi-value">{{ number_format($stats['pipeline_value'], 0, ',', ' ') }} <small style="font-size: 1rem">XAF</small></div>
                        <div class="kpi-label">Valeur Pipeline (En cours)</div>
                    </div>
                </div>
            </div>

            {{-- Opportunités Ouvertes --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon text-info">🎯</div>
                        <div class="kpi-value">{{ $stats['opportunities_open'] }}</div>
                        <div class="kpi-label">Opportunités Ouvertes</div>
                    </div>
                </div>
            </div>

            {{-- Gagnées ce mois --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon text-success">🏆</div>
                        <div class="kpi-value text-success">+{{ $stats['opportunities_won_month'] }}</div>
                        <div class="kpi-label">Gagnées (Ce mois)</div>
                    </div>
                </div>
            </div>

            {{-- Perdues ce mois --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon text-danger">📉</div>
                        <div class="kpi-value text-danger">-{{ $stats['opportunities_lost_month'] }}</div>
                        <div class="kpi-label">Perdues (Ce mois)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row 2 (Contacts) --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ $stats['contacts_total'] }}</h3>
                        <small class="text-muted">Total Contacts</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-0 text-warning">{{ $stats['contacts_leads'] }}</h3>
                        <small class="text-muted">Leads</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-0 text-success">{{ $stats['contacts_clients'] }}</h3>
                        <small class="text-muted">Clients</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-0 text-info">{{ $stats['contacts_partners'] }}</h3>
                        <small class="text-muted">Partenaires</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Activités Récentes --}}
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📅 Activités Récentes</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($recent_activities->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recent_activities as $activity)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                @switch($activity->type)
                                                    @case('call') 📞 @break
                                                    @case('email') 📧 @break
                                                    @case('meeting') 🤝 @break
                                                    @default 📌
                                                @endswitch
                                                {{ $activity->summary }}
                                            </h6>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            Avec <a href="{{ route('crm.contacts.show', $activity->contact) }}">{{ $activity->contact->first_name }} {{ $activity->contact->last_name }}</a>
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <p class="text-muted mb-0">Aucune activité récente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Top Clients & Opportunités --}}
            <div class="col-lg-6 mb-4">
                {{-- Top Clients --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">🏆 Top Clients (Valeur Gagnée)</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($top_clients->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($top_clients as $client)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="{{ route('crm.contacts.show', $client) }}">
                                            {{ $client->first_name }} {{ $client->last_name }}
                                        </a>
                                        <span class="badge bg-success badge-pill">{{ number_format($client->total_won_value, 0, ',', ' ') }} XAF</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-4 text-center text-muted">Pas assez de données</div>
                        @endif
                    </div>
                </div>

                {{-- Opportunités Actives --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">🔥 Opportunités Chaudes</h5>
                        <a href="{{ route('crm.opportunities.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        @if($active_opportunities->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        @foreach($active_opportunities as $opp)
                                        <tr>
                                            <td>
                                                <strong>{{ Str::limit($opp->title, 20) }}</strong><br>
                                                <small class="text-muted">{{ $opp->contact->first_name }} {{ $opp->contact->last_name }}</small>
                                            </td>
                                            <td class="text-right">
                                                <span class="font-weight-bold">{{ number_format($opp->value, 0, ',', ' ') }}</span><br>
                                                <span class="badge bg-light">{{ $opp->stage }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">Aucune opportunité en cours</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
