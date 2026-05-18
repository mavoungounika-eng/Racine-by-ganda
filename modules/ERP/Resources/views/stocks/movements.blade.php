@extends('layouts.admin-master')
@section('title', 'ERP — Mouvements de Stock')
@section('page-title', 'Mouvements de Stock')
@section('page-subtitle', 'Historique complet des entrées et sorties')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="erp-link-sm">ERP</a></li>
      <li class="breadcrumb-item"><a href="{{ route('erp.stocks.index') }}" class="erp-link-sm">Stocks</a></li>
      <li class="breadcrumb-item active text-muted" style="font-size:.82rem">Mouvements</li>
    </ol>
  </nav>
  <div class="d-flex gap-2">
    <a href="{{ route('erp.stocks.index') }}" class="al-action-btn al-action-btn-sm"><i class="fas fa-arrow-left me-1"></i>Retour</a>
    <a href="{{ route('erp.stocks.movements.export', request()->query()) }}" class="al-action-btn al-action-btn-sm al-action-btn-primary"><i class="fas fa-file-excel me-1"></i>Exporter</a>
  </div>
</div>

{{-- Filtres --}}
<div class="al-card mb-4">
  <form method="GET" action="{{ route('erp.stocks.movements') }}" class="d-flex flex-wrap gap-2 align-items-end">
    <div>
      <label class="al-filter-label">Date début</label>
      <input type="date" name="date_from" class="al-filter-input" value="{{ request('date_from') }}">
    </div>
    <div>
      <label class="al-filter-label">Date fin</label>
      <input type="date" name="date_to" class="al-filter-input" value="{{ request('date_to') }}">
    </div>
    <div>
      <label class="al-filter-label">Type</label>
      <select name="type" class="al-filter-select">
        <option value="">Tous</option>
        <option value="in"  {{ request('type') === 'in'  ? 'selected' : '' }}>Entrées</option>
        <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Sorties</option>
      </select>
    </div>
    <button type="submit" class="al-action-btn al-action-btn-primary"><i class="fas fa-filter me-1"></i>Filtrer</button>
  </form>
</div>

{{-- Table --}}
<div class="al-card">
  @if($movements->count() > 0)
  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th>Date</th>
        <th>Type</th>
        <th>Produit / Matière</th>
        <th>Quantité</th>
        <th>Raison</th>
        <th>De → Vers</th>
        <th>Utilisateur</th>
      </tr></thead>
      <tbody>
        @foreach($movements as $movement)
        <tr>
          <td class="al-row-muted">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
          <td>
            @if($movement->type === 'in')
              <span class="al-badge al-badge-ok">Entrée</span>
            @else
              <span class="al-badge al-badge-danger">Sortie</span>
            @endif
          </td>
          <td class="al-row-name">{{ $movement->stockable ? ($movement->stockable->title ?? $movement->stockable->name) : 'N/A' }}</td>
          <td class="fw-bold {{ $movement->type === 'in' ? 'mat-stock-ok' : 'mat-stock-out' }}">
            {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
          </td>
          <td class="al-row-muted">{{ $movement->reason ?? '—' }}</td>
          <td class="al-row-muted">{{ $movement->from_location ?? '—' }} → {{ $movement->to_location ?? '—' }}</td>
          <td class="al-row-muted">{{ $movement->user ? $movement->user->name : 'Système' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3">{{ $movements->links() }}</div>
  @else
  <div class="al-empty">
    <div class="al-empty-icon">📋</div>
    Aucun mouvement de stock
  </div>
  @endif
</div>

@push('styles')
<style nonce="{{ csp_nonce() }}">
.al-filter-label{display:block;font-size:.72rem;color:#888;margin-bottom:.3rem;font-weight:600}
.erp-link-sm{font-size:.78rem;color:#ED5F1E;text-decoration:none}
.erp-link-sm:hover{text-decoration:underline}
.mat-stock-ok{color:#4ade80}
.mat-stock-out{color:#f87171}
</style>
@endpush

@endsection
