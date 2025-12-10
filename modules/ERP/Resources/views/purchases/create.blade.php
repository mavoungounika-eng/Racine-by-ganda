@extends('layouts.admin-master')

@section('title', 'Nouvelle Commande Fournisseur')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nouvelle Commande</h1>
        <a href="{{ route('erp.purchases.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <form action="{{ route('erp.purchases.store') }}" method="POST" id="purchaseForm">
        @csrf
        
        <div class="row">
            <!-- Informations Générales -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Informations</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="supplier_id">Fournisseur <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="form-control" required>
                                <option value="">Sélectionner un fournisseur</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="purchase_date">Date de commande <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="expected_delivery_date">Date livraison prévue</label>
                            <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Articles à commander</h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                            <i class="fas fa-plus"></i> Ajouter Article
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Matière Première</th>
                                        <th width="20%">Quantité</th>
                                        <th width="20%">Prix Unitaire</th>
                                        <th width="15%">Total</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Rows will be added here -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">Total Commande :</td>
                                        <td colspan="2" class="font-weight-bold" id="grandTotal">0 XAF</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Enregistrer la Commande
            </button>
        </div>
    </form>
</div>

<script>
    let itemIndex = 0;
    const materials = @json($materials);

    function addItem() {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        
        let options = '<option value="">Choisir...</option>';
        materials.forEach(m => {
            options += `<option value="${m.id}">${m.name} (${m.unit || 'unité'})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="items[${itemIndex}][material_id]" class="form-control" required onchange="updateRow(${itemIndex})">
                    ${options}
                </select>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0" required oninput="updateRow(${itemIndex})">
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control" step="0.01" min="0" required oninput="updateRow(${itemIndex})">
            </td>
            <td class="text-right row-total">0 XAF</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); calculateGrandTotal()">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        itemIndex++;
    }

    function updateRow(index) {
        // Simple calculation logic would go here, but since we use dynamic names, 
        // we need to traverse the DOM relative to the changed input or use IDs.
        // For simplicity in this generated code, let's recalculate everything.
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;
        const rows = document.querySelectorAll('#itemsBody tr');
        
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
            const rowTotal = qty * price;
            
            row.querySelector('.row-total').textContent = new Intl.NumberFormat('fr-FR').format(rowTotal) + ' XAF';
            total += rowTotal;
        });

        document.getElementById('grandTotal').textContent = new Intl.NumberFormat('fr-FR').format(total) + ' XAF';
    }

    // Add one empty row by default
    document.addEventListener('DOMContentLoaded', () => {
        addItem();
    });
</script>
@endsection
