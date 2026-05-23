@extends('layouts.admin')

@section('title', 'Commandes à traiter')
@section('page-title', 'Commandes à traiter')
@section('page-subtitle', 'Litiges, retours et remboursements en attente d\'action')

@section('content')
@php
$statusLabels = [
    'disputed'         => 'Litige',
    'return_requested' => 'Retour demandé',
    'refunded'         => 'Remboursé',
];
$statusColors = [
    'disputed'         => '#ef4444',
    'return_requested' => '#f59e0b',
    'refunded'         => '#6b7280',
];
@endphp

<div class="al-card mb-4">
  {{-- Onglets --}}
  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.orders.to-handle') }}"
       class="al-tab-btn {{ !$filterStatus ? 'active' : '' }}">
      Tous
      <span class="al-badge-count {{ !$filterStatus ? 'active' : '' }}">{{ $counts['tous'] }}</span>
    </a>
    <a href="{{ route('admin.orders.to-handle', ['statut' => 'disputed']) }}"
       class="al-tab-btn {{ $filterStatus === 'disputed' ? 'active' : '' }}">
      Litiges
      <span class="al-badge-count disputed {{ $filterStatus === 'disputed' ? 'active' : '' }}">{{ $counts['disputed'] }}</span>
    </a>
    <a href="{{ route('admin.orders.to-handle', ['statut' => 'return_requested']) }}"
       class="al-tab-btn {{ $filterStatus === 'return_requested' ? 'active' : '' }}">
      Retours demandés
      <span class="al-badge-count return {{ $filterStatus === 'return_requested' ? 'active' : '' }}">{{ $counts['return_requested'] }}</span>
    </a>
    <a href="{{ route('admin.orders.to-handle', ['statut' => 'refunded']) }}"
       class="al-tab-btn {{ $filterStatus === 'refunded' ? 'active' : '' }}">
      Remboursés
      <span class="al-badge-count refunded {{ $filterStatus === 'refunded' ? 'active' : '' }}">{{ $counts['refunded'] }}</span>
    </a>
  </div>

  @if($orders->isEmpty())
    <div class="text-center py-5" style="color:#6b7280;">
      <i class="fas fa-check-circle fa-3x mb-3" style="color:#4ade80;"></i>
      <p class="mb-0">Aucune commande à traiter.</p>
    </div>
  @else
  <div class="al-table-wrap">
    <table class="al-table" id="to-handle-table">
      <thead>
        <tr>
          <th>N° commande</th>
          <th>Client</th>
          <th>Date</th>
          <th>Article(s) concerné(s)</th>
          <th>Montant</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($orders as $order)
          @php
            $flaggedItems = $order->items->whereIn('status', ['disputed', 'return_requested', 'refunded']);
            if ($filterStatus) {
              $flaggedItems = $order->items->where('status', $filterStatus);
            }
          @endphp
          @foreach($flaggedItems as $item)
          <tr id="row-{{ $item->id }}">
            <td>
              <a href="{{ route('admin.orders.show', $order) }}"
                 style="color:#ED5F1E;font-weight:600;">
                {{ $order->order_number ?? '#'.$order->id }}
              </a>
            </td>
            <td>
              <div style="font-weight:500;">{{ $order->user?->name ?? $order->customer_name }}</div>
              <small style="color:#6b7280;">{{ $order->user?->email ?? $order->customer_email }}</small>
            </td>
            <td>
              <span style="white-space:nowrap;">{{ $order->created_at->format('d/m/Y') }}</span>
              <br><small style="color:#6b7280;">{{ $order->created_at->format('H:i') }}</small>
            </td>
            <td>
              <div class="d-flex align-items-start gap-2 flex-wrap">
                <span class="al-status-badge"
                      id="badge-{{ $item->id }}"
                      style="background:{{ $statusColors[$item->status] ?? '#6b7280' }}1a;color:{{ $statusColors[$item->status] ?? '#6b7280' }};border:1px solid {{ $statusColors[$item->status] ?? '#6b7280' }}40;">
                  {{ $statusLabels[$item->status] ?? $item->status }}
                </span>
                <span style="font-size:.875rem;">
                  {{ $item->product?->name ?? 'Produit #'.$item->product_id }}
                  <small style="color:#6b7280;">(×{{ $item->quantity }})</small>
                </span>
              </div>
            </td>
            <td style="white-space:nowrap;font-weight:600;">
              {{ number_format($order->total_amount / 100, 0, ',', ' ') }} XAF
            </td>
            <td id="actions-{{ $item->id }}">
              @if($item->status === 'disputed')
                <button class="al-btn-action al-btn-danger"
                        onclick="transitionItem({{ $order->id }}, {{ $item->id }}, 'refunded', this)">
                  Rembourser
                </button>
              @elseif($item->status === 'return_requested')
                <div class="d-flex gap-1 flex-wrap">
                  <button class="al-btn-action al-btn-success"
                          onclick="transitionItem({{ $order->id }}, {{ $item->id }}, 'refunded', this)">
                    Valider le retour
                  </button>
                  <button class="al-btn-action al-btn-secondary"
                          onclick="transitionItem({{ $order->id }}, {{ $item->id }}, 'delivered', this)">
                    Rejeter
                  </button>
                </div>
              @elseif($item->status === 'refunded')
                <button class="al-btn-action" disabled style="opacity:.5;cursor:not-allowed;">
                  Clôturé
                </button>
              @endif
            </td>
          </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($orders->hasPages())
  <div class="d-flex justify-content-center mt-3">
    {{ $orders->links() }}
  </div>
  @endif
  @endif
</div>

<style>
.al-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .45rem .85rem;
  border-radius: 6px;
  background: #f1f5f9;
  color: #475569;
  font-size: .875rem;
  font-weight: 500;
  text-decoration: none;
  border: 1px solid transparent;
  transition: background .15s, color .15s, border-color .15s;
}
.al-tab-btn:hover { background: #e2e8f0; color: #0f172a; }
.al-tab-btn.active { background: #fff7f4; color: #ED5F1E; border-color: #ED5F1E40; }

.al-badge-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 99px;
  background: #e2e8f0;
  color: #475569;
  font-size: .7rem;
  font-weight: 700;
}
.al-badge-count.disputed { background: #fee2e2; color: #ef4444; }
.al-badge-count.return   { background: #fef3c7; color: #d97706; }
.al-badge-count.refunded { background: #f1f5f9; color: #6b7280; }

.al-status-badge {
  display: inline-block;
  padding: .2rem .55rem;
  border-radius: 99px;
  font-size: .75rem;
  font-weight: 600;
  white-space: nowrap;
}

.al-btn-action {
  padding: .3rem .7rem;
  border-radius: 5px;
  border: none;
  font-size: .8rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s;
  white-space: nowrap;
}
.al-btn-action:hover { opacity: .85; }
.al-btn-danger    { background: #fee2e2; color: #b91c1c; }
.al-btn-success   { background: #dcfce7; color: #15803d; }
.al-btn-secondary { background: #f1f5f9; color: #475569; }
</style>

<script>
function transitionItem(orderId, itemId, to, triggerBtn) {
  const actionsCell = document.getElementById('actions-' + itemId);
  const allBtns = actionsCell.querySelectorAll('button');
  allBtns.forEach(b => { b.disabled = true; b.style.opacity = '.5'; });
  triggerBtn.textContent = '…';

  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.content : '';

  fetch('/admin/commandes/' + orderId + '/articles/' + itemId + '/transition', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
    body: JSON.stringify({ to: to }),
  })
  .then(function(res) {
    return res.json().then(function(data) {
      return { ok: res.ok, data: data };
    });
  })
  .then(function(result) {
    if (!result.ok) {
      alert(result.data.message || 'Erreur lors de la transition.');
      allBtns.forEach(function(b) { b.disabled = false; b.style.opacity = '1'; });
      return;
    }
    var badge = document.getElementById('badge-' + itemId);
    var labels = { refunded: 'Remboursé', delivered: 'Livré', shipped: 'Expédié', confirmed: 'Confirmé' };
    var colors = { refunded: '#6b7280', delivered: '#4ade80', shipped: '#60a5fa', confirmed: '#a78bfa' };
    if (badge) {
      badge.textContent = labels[result.data.status] || result.data.status;
      var c = colors[result.data.status] || '#6b7280';
      badge.style.background = c + '1a';
      badge.style.color = c;
      badge.style.borderColor = c + '40';
    }
    // Replace action cell with closed button (DOM API, no innerHTML)
    var closedBtn = document.createElement('button');
    closedBtn.className = 'al-btn-action';
    closedBtn.disabled = true;
    closedBtn.style.opacity = '.5';
    closedBtn.style.cursor = 'not-allowed';
    closedBtn.textContent = 'Clôturé';
    while (actionsCell.firstChild) { actionsCell.removeChild(actionsCell.firstChild); }
    actionsCell.appendChild(closedBtn);
  })
  .catch(function() {
    alert('Erreur réseau. Réessayez.');
    allBtns.forEach(function(b) { b.disabled = false; b.style.opacity = '1'; });
  });
}
</script>
@endsection
