@extends('layouts.admin-master')

@section('title', 'Réception Marchandise — ' . $purchase->reference)
@section('page-title', 'Réception Marchandise')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📦 Réception — {{ $purchase->reference }}</h1>
            <p class="text-muted mb-0">
                Fournisseur : <strong>{{ $purchase->supplier->name }}</strong>
                &nbsp;·&nbsp; Date commande : {{ $purchase->purchase_date->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('erp.purchases.show', $purchase) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('erp.purchases.reception.store', $purchase) }}" method="POST" id="receptionForm">
        @csrf

        <div class="row">

            {{-- Informations générales --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">Informations Réception</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="reception_date" class="form-label">Date de réception <span class="text-danger">*</span></label>
                            <input type="date" name="reception_date" id="reception_date"
                                class="form-control @error('reception_date') is-invalid @enderror"
                                value="{{ old('reception_date', date('Y-m-d')) }}" required>
                            @error('reception_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="bl_number" class="form-label">N° Bon de Livraison</label>
                            <input type="text" name="bl_number" id="bl_number"
                                class="form-control @error('bl_number') is-invalid @enderror"
                                value="{{ old('bl_number') }}"
                                placeholder="BL-2026-XXXX">
                            @error('bl_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes"
                                class="form-control @error('notes') is-invalid @enderror"
                                rows="3"
                                placeholder="Observations, conditions de livraison...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Résumé rapide --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Résumé</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Articles commandés</span>
                            <strong>{{ $purchase->items->count() }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total commande</span>
                            <strong>{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tableau articles --}}
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">Articles à Réceptionner</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="receptionTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Matière Première</th>
                                        <th class="text-center">Unité</th>
                                        <th class="text-end">Qté Commandée</th>
                                        <th class="text-end" style="min-width:110px">Qté Reçue</th>
                                        <th class="text-end" style="min-width:110px">Qté Refusée</th>
                                        <th class="text-end" style="min-width:130px">Prix Réel</th>
                                        <th style="min-width:150px">Motif Refus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items as $idx => $item)
                                    <tr data-item-idx="{{ $idx }}">
                                        <input type="hidden" name="items[{{ $idx }}][purchase_item_id]" value="{{ $item->id }}">
                                        <td class="align-middle">
                                            <strong>{{ $item->purchasable ? $item->purchasable->name : 'Article supprimé' }}</strong>
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ $item->purchasable ? $item->purchasable->unit : '—' }}
                                        </td>
                                        <td class="align-middle text-end fw-bold">
                                            {{ number_format($item->quantity, 2, ',', ' ') }}
                                        </td>
                                        <td class="align-middle">
                                            <input type="number"
                                                name="items[{{ $idx }}][quantity_received]"
                                                class="form-control form-control-sm text-end qty-received @error('items.'.$idx.'.quantity_received') is-invalid @enderror"
                                                value="{{ old('items.'.$idx.'.quantity_received', $item->quantity) }}"
                                                step="0.01" min="0" max="{{ $item->quantity }}" required>
                                            @error('items.'.$idx.'.quantity_received')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="align-middle">
                                            <input type="number"
                                                name="items[{{ $idx }}][quantity_refused]"
                                                class="form-control form-control-sm text-end qty-refused @error('items.'.$idx.'.quantity_refused') is-invalid @enderror"
                                                value="{{ old('items.'.$idx.'.quantity_refused', 0) }}"
                                                step="0.01" min="0" max="{{ $item->quantity }}">
                                            @error('items.'.$idx.'.quantity_refused')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="align-middle">
                                            <input type="number"
                                                name="items[{{ $idx }}][unit_price_received]"
                                                class="form-control form-control-sm text-end @error('items.'.$idx.'.unit_price_received') is-invalid @enderror"
                                                value="{{ old('items.'.$idx.'.unit_price_received', $item->unit_price) }}"
                                                step="0.01" min="0" required>
                                            @error('items.'.$idx.'.unit_price_received')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="align-middle refuse-reason-cell" style="display:none">
                                            <input type="text"
                                                name="items[{{ $idx }}][refuse_reason]"
                                                class="form-control form-control-sm @error('items.'.$idx.'.refuse_reason') is-invalid @enderror"
                                                value="{{ old('items.'.$idx.'.refuse_reason') }}"
                                                placeholder="Motif...">
                                            @error('items.'.$idx.'.refuse_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="align-middle refuse-reason-cell-placeholder">
                                            <span class="text-muted small">—</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <a href="{{ route('erp.purchases.show', $purchase) }}" class="btn btn-outline-secondary me-2">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle me-2"></i>Enregistrer la Réception
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('receptionTable');
    if (!table) return;

    function toggleRefuseReason(row) {
        const refusedInput = row.querySelector('.qty-refused');
        const reasonCell  = row.querySelector('.refuse-reason-cell');
        const placeholder = row.querySelector('.refuse-reason-cell-placeholder');
        if (!refusedInput || !reasonCell) return;

        const val = parseFloat(refusedInput.value) || 0;
        if (val > 0) {
            reasonCell.style.display = '';
            if (placeholder) placeholder.style.display = 'none';
        } else {
            reasonCell.style.display = 'none';
            if (placeholder) placeholder.style.display = '';
        }
    }

    // Initial state — show reason cell if old input had refused > 0
    table.querySelectorAll('tbody tr').forEach(function (row) {
        toggleRefuseReason(row);

        const refusedInput = row.querySelector('.qty-refused');
        const receivedInput = row.querySelector('.qty-received');

        if (refusedInput) {
            refusedInput.addEventListener('input', function () {
                toggleRefuseReason(row);
            });
        }

        // Auto-adjust: if received changes, cap refused so total ≤ ordered
        if (receivedInput && refusedInput) {
            const orderedCell = row.cells[2];
            const ordered = parseFloat(orderedCell ? orderedCell.textContent.replace(',', '.') : 0);

            receivedInput.addEventListener('input', function () {
                const received = parseFloat(receivedInput.value) || 0;
                const refused  = parseFloat(refusedInput.value) || 0;
                if ((received + refused) > ordered) {
                    refusedInput.value = Math.max(0, ordered - received).toFixed(2);
                    toggleRefuseReason(row);
                }
            });

            refusedInput.addEventListener('input', function () {
                const received = parseFloat(receivedInput.value) || 0;
                const refused  = parseFloat(refusedInput.value) || 0;
                if ((received + refused) > ordered) {
                    receivedInput.value = Math.max(0, ordered - refused).toFixed(2);
                }
            });
        }
    });
});
</script>
@endpush
@endsection
