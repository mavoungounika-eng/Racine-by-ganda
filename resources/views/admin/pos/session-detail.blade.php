@extends('layouts.admin')
@section('title', 'Session #{{ $session->id }} — Détail')
@section('page-title', 'Détail Session POS')

@push('styles')
<style nonce="{{ csp_nonce() }}">
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:900px){.detail-grid{grid-template-columns:1fr}}
.panel{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:16px;padding:1.5rem}
.panel-title{font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:#888;margin-bottom:1rem;font-weight:600}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid rgba(212,165,116,.06);font-size:.875rem}
.info-row:last-child{border-bottom:none}
.info-label{color:#888}
.info-val{color:#e2e8f0;font-weight:500;text-align:right}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.badge-open{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.badge-closed{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)}
.badge-closing{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.badge-fantome{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.dot-live{width:6px;height:6px;border-radius:50%;background:#4ade80;box-shadow:0 0 6px #4ade80;display:inline-block;animation:blink 1.5s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
.cash-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.cash-card{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:12px;padding:1rem 1.25rem;text-align:center}
.cash-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:#888;margin-bottom:.4rem}
.cash-val{font-size:1.4rem;font-weight:800;color:#e2e8f0}
.cash-val.green{color:#4ade80}.cash-val.red{color:#f87171}.cash-val.orange{color:#ED5F1E}
.timeline{list-style:none;padding:0;margin:0;position:relative}
.timeline::before{content:'';position:absolute;left:10px;top:0;bottom:0;width:2px;background:rgba(212,165,116,.15)}
.tl-item{display:flex;gap:1rem;padding:.6rem 0;position:relative}
.tl-dot{width:20px;height:20px;border-radius:50%;background:#ED5F1E;flex-shrink:0;z-index:1;display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#fff;font-weight:700}
.tl-content{flex:1;padding-top:1px}
.tl-time{font-size:.72rem;color:#888}
.tl-label{font-size:.85rem;color:#e2e8f0;font-weight:500}
.sales-table{width:100%;border-collapse:collapse;font-size:.85rem}
.sales-table th{background:rgba(22,13,12,.9);color:#aaa;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;padding:.6rem .875rem;text-align:left;border-bottom:1px solid rgba(212,165,116,.1)}
.sales-table td{padding:.7rem .875rem;border-bottom:1px solid rgba(212,165,116,.06);color:#e2e8f0;vertical-align:middle}
.sales-table tr:hover td{background:rgba(237,95,30,.04)}
.btn-back{display:inline-flex;align-items:center;gap:.5rem;color:#888;text-decoration:none;font-size:.85rem;margin-bottom:1.25rem;transition:color .15s}
.btn-back:hover{color:#ED5F1E}
.btn-danger{background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer}
.btn-export{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3);border-radius:8px;padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
.page-actions{display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap}
.diff-pos{color:#4ade80}.diff-neg{color:#f87171}
</style>
@endpush

@section('content')
@php
  $fantome = $session->status === 'open' && !$session->last_activity_at;
  $dureeMin = $session->opened_at ? intval((($session->closed_at ?? now())->timestamp - $session->opened_at->timestamp) / 60) : 0;
  $dureeH = intdiv($dureeMin, 60); $dureeM = $dureeMin % 60;
  $dureeStr = $dureeH > 0 ? ($dureeH.'h'.($dureeM > 0 ? $dureeM.'min' : '')) : $dureeMin.'min';
  $diff = ($session->closing_cash ?? 0) - ($session->opening_cash ?? 0) - ($session->total_ventes ?? 0);
  $totalSales = $session->sales->sum('total_amount');
  $nbSales = $session->sales->count();
@endphp

<a href="{{ route('pos.interface.sessions') }}" class="btn-back">← Retour aux sessions</a>

<div class="page-actions">
  <a href="{{ route('pos.interface.sessions.export-csv', $session->id) }}" class="btn-export">↓ Export CSV session</a>
  @if($session->status === 'open' || $session->status === 'closing')
  <form method="POST" action="{{ route('pos.interface.sessions.force-close', $session->id) }}" onsubmit="return confirm('Clôturer cette session ?')">
    @csrf
    <button type="submit" class="btn-danger">⏹ Clôturer (admin)</button>
  </form>
  @endif
</div>

{{-- Récap caisse --}}
<div class="cash-grid">
  <div class="cash-card">
    <div class="cash-label">Fond de caisse</div>
    <div class="cash-val orange">{{ number_format($session->opening_cash ?? 0, 0, ',', ' ') }} FCFA</div>
  </div>
  <div class="cash-card">
    <div class="cash-label">Ventes session</div>
    <div class="cash-val green">{{ number_format($session->total_ventes ?? 0, 0, ',', ' ') }} FCFA</div>
  </div>
  <div class="cash-card">
    <div class="cash-label">Écart caisse</div>
    @if($session->closing_cash !== null)
      <div class="cash-val {{ $diff >= 0 ? 'green' : 'red' }}">{{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 0, ',', ' ') }} FCFA</div>
    @else
      <div class="cash-val" style="color:#888">—</div>
    @endif
  </div>
</div>

<div class="detail-grid">
  {{-- Infos session --}}
  <div class="panel">
    <div class="panel-title">Informations session</div>
    <div class="info-row"><span class="info-label">ID</span><span class="info-val">#{{ $session->id }}</span></div>
    <div class="info-row"><span class="info-label">Statut</span><span class="info-val">
      @if($fantome)<span class="badge badge-fantome">Fantôme</span>
      @elseif($session->status === 'open')<span class="badge badge-open"><span class="dot-live"></span>Active</span>
      @elseif($session->status === 'closing')<span class="badge badge-closing">En clôture</span>
      @else<span class="badge badge-closed">Fermée</span>@endif
    </span></div>
    <div class="info-row"><span class="info-label">Opérateur</span><span class="info-val">{{ $session->opener?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Email</span><span class="info-val" style="font-size:.8rem">{{ $session->opener?->email ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Machine</span><span class="info-val" style="font-family:monospace;font-size:.8rem">{{ $session->machine_name ?? $session->machine_id ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Ouverture</span><span class="info-val">{{ $session->opened_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Clôture</span><span class="info-val">{{ $session->closed_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Durée</span><span class="info-val">{{ $dureeStr }}</span></div>
    <div class="info-row"><span class="info-label">Clôturé par</span><span class="info-val">{{ $session->closer?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="info-label">Repris le</span><span class="info-val">{{ $session->resumed_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
    @if($session->notes)
    <div class="info-row" style="flex-direction:column;align-items:flex-start;gap:.4rem">
      <span class="info-label">Notes</span>
      <span style="color:#e2e8f0;font-size:.8rem;white-space:pre-wrap;line-height:1.5">{{ $session->notes }}</span>
    </div>
    @endif
  </div>

  {{-- Timeline --}}
  <div class="panel">
    <div class="panel-title">Timeline</div>
    <ul class="timeline">
      <li class="tl-item">
        <div class="tl-dot" style="background:#ED5F1E">O</div>
        <div class="tl-content">
          <div class="tl-label">Session ouverte par {{ $session->opener?->name ?? '—' }}</div>
          <div class="tl-time">{{ $session->opened_at?->format('d/m/Y à H:i:s') ?? '—' }}</div>
        </div>
      </li>
      @if($session->resumed_at)
      <li class="tl-item">
        <div class="tl-dot" style="background:#fbbf24">R</div>
        <div class="tl-content">
          <div class="tl-label">Session reprise{{ $session->resumedBy ? ' par '.$session->resumedBy->name : '' }}</div>
          <div class="tl-time">{{ $session->resumed_at->format('d/m/Y à H:i:s') }}</div>
        </div>
      </li>
      @endif
      @if($session->last_activity_at)
      <li class="tl-item">
        <div class="tl-dot" style="background:#4ade80">A</div>
        <div class="tl-content">
          <div class="tl-label">Dernière activité</div>
          <div class="tl-time">{{ \Carbon\Carbon::parse($session->last_activity_at)->format('d/m/Y à H:i:s') }}</div>
        </div>
      </li>
      @endif
      @if($session->closed_at)
      <li class="tl-item">
        <div class="tl-dot" style="background:#94a3b8">F</div>
        <div class="tl-content">
          <div class="tl-label">Session fermée{{ $session->closer ? ' par '.$session->closer->name : '' }}</div>
          <div class="tl-time">{{ $session->closed_at->format('d/m/Y à H:i:s') }}</div>
        </div>
      </li>
      @else
      <li class="tl-item">
        <div class="tl-dot" style="background:{{ $fantome ? '#f87171' : '#4ade80' }}">•</div>
        <div class="tl-content">
          <div class="tl-label">{{ $fantome ? 'Session fantôme (inactive)' : 'Session en cours…' }}</div>
          <div class="tl-time">Maintenant — {{ $dureeStr }} depuis l'ouverture</div>
        </div>
      </li>
      @endif
    </ul>
  </div>
</div>

{{-- Ventes --}}
<div class="panel">
  <div class="panel-title" style="display:flex;justify-content:space-between;align-items:center">
    <span>Ventes ({{ $nbSales }} ticket{{ $nbSales > 1 ? 's' : '' }} — {{ number_format($totalSales, 0, ',', ' ') }} FCFA)</span>
    <a href="{{ route('pos.interface.sessions.export-csv', $session->id) }}" class="btn-export" style="font-size:.78rem;padding:.3rem .8rem">↓ CSV</a>
  </div>
  @if($nbSales === 0)
    <div style="text-align:center;padding:2rem;color:#666">Aucune vente enregistrée pour cette session</div>
  @else
  <div style="overflow-x:auto;max-height:500px;overflow-y:auto">
    <table class="sales-table">
      <thead><tr>
        <th>#</th><th>Date</th><th>Référence</th><th>Montant</th><th>Moyen paiement</th><th>Statut</th>
      </tr></thead>
      <tbody>
        @foreach($session->sales->sortByDesc('created_at') as $sale)
        <tr>
          <td style="color:#888">{{ $sale->id }}</td>
          <td>{{ $sale->created_at?->format('d/m H:i') ?? '—' }}</td>
          <td style="font-family:monospace;font-size:.78rem;color:#aaa">{{ $sale->reference ?? $sale->idempotency_key ?? '—' }}</td>
          <td style="font-weight:600;color:#4ade80">{{ number_format($sale->total_amount ?? 0, 0, ',', ' ') }} FCFA</td>
          <td style="text-transform:capitalize">{{ $sale->payments->pluck('method')->join(', ') ?: '—' }}</td>
          <td>
            @php $st = $sale->status ?? 'unknown'; @endphp
            <span style="font-size:.78rem;padding:2px 8px;border-radius:999px;
              background:{{ $st === 'completed' ? 'rgba(34,197,94,.15)' : ($st === 'pending' ? 'rgba(251,191,36,.15)' : 'rgba(239,68,68,.15)') }};
              color:{{ $st === 'completed' ? '#4ade80' : ($st === 'pending' ? '#fbbf24' : '#f87171') }}">
              {{ $st }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endsection
