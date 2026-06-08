@extends('layouts.admin')

@section('title', 'Paramètres - RACINE BY GANDA')
@section('page_title', 'Paramètres')
@section('page_subtitle', 'Configuration globale du site')
@section('breadcrumb', 'Paramètres')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Navigation par onglets --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    @foreach($tabs as $tabKey => $tabData)
        <li class="nav-item">
            <a class="nav-link {{ $currentTab === $tabKey ? 'active' : '' }}"
               href="{{ route('admin.settings.index', $tabKey) }}"
               @if(!$tabData['implemented']) style="opacity: 0.6;" @endif>
                <i class="fas {{ $tabData['icon'] }} me-2"></i>{{ $tabData['label'] }}
                @if(!$tabData['implemented'])
                    <small class="badge bg-secondary ms-1">Bientôt</small>
                @endif
            </a>
        </li>
    @endforeach
</ul>

{{-- Contenu des onglets --}}
<div class="tab-content">
    @if($currentTab === 'general')
        @include('admin.settings.tabs.general')
    @elseif($currentTab === 'marketplace')
        @include('admin.settings.tabs.marketplace')
    @elseif($currentTab === 'payments')
        @include('admin.settings.tabs.payments')
    @elseif($currentTab === 'email')
        @include('admin.settings.tabs.email')
    @elseif($currentTab === 'integrations')
        @include('admin.settings.tabs.integrations')
    @elseif($currentTab === 'security')
        @include('admin.settings.tabs.security')
    @elseif(isset($tabs[$currentTab]) && !$tabs[$currentTab]['implemented'])
        {{-- Placeholder pour onglets non implémentés --}}
        <div class="card border-0 shadow-sm" style="border-radius:18px;">
            <div class="card-body text-center py-5">
                <i class="fas {{ $tabs[$currentTab]['icon'] }} fa-3x mb-3" style="color: var(--racine-orange);"></i>
                <h4>{{ $tabs[$currentTab]['label'] }}</h4>
                <p class="text-muted">Cet onglet sera disponible dans le Sprint suivant.</p>
                <small class="text-muted">En cours de développement</small>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Onglet inconnu. Retournez à l'onglet <a href="{{ route('admin.settings.index', 'general') }}">Général</a>.
        </div>
    @endif
</div>

@endsection
