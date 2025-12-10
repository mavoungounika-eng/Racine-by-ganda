@extends('layouts.admin-master')

@section('title', 'Ajustement de Stock : ' . $product->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ajustement de Stock</h1>
        <a href="{{ route('erp.stocks.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Produit : {{ $product->title }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Stock Actuel :</strong> {{ $product->stock }} unités
                    </div>

                    <form action="{{ route('erp.stocks.store-adjustment', $product) }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label>Type de Mouvement</label>
                            <select name="type" class="form-control" required id="typeSelect">
                                <option value="in">➕ Entrée (Ajout)</option>
                                <option value="out">➖ Sortie (Retrait)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Quantité</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>

                        <div class="form-group">
                            <label>Raison</label>
                            <select name="reason" class="form-control" required id="reasonSelect">
                                <optgroup label="Entrées">
                                    <option value="Inventaire (Correction +)">Correction Inventaire (+)</option>
                                    <option value="Retour Client">Retour Client</option>
                                    <option value="Don (Entrée)">Don Reçu</option>
                                    <option value="Autre (Entrée)">Autre Entrée</option>
                                </optgroup>
                                <optgroup label="Sorties">
                                    <option value="Inventaire (Correction -)">Correction Inventaire (-)</option>
                                    <option value="Casse / Détérioration">Casse / Détérioration</option>
                                    <option value="Vol / Perte">Vol / Perte</option>
                                    <option value="Don / Cadeau">Don / Cadeau Marketing</option>
                                    <option value="Autre (Sortie)">Autre Sortie</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Note (Optionnel)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Détails supplémentaires..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Enregistrer l'ajustement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Petit script pour filtrer les raisons selon le type (UX)
    const typeSelect = document.getElementById('typeSelect');
    const reasonSelect = document.getElementById('reasonSelect');
    const optgroups = reasonSelect.getElementsByTagName('optgroup');

    function updateReasons() {
        const type = typeSelect.value;
        if (type === 'in') {
            optgroups[0].style.display = '';
            optgroups[1].style.display = 'none';
            reasonSelect.value = optgroups[0].children[0].value;
        } else {
            optgroups[0].style.display = 'none';
            optgroups[1].style.display = '';
            reasonSelect.value = optgroups[1].children[0].value;
        }
    }

    typeSelect.addEventListener('change', updateReasons);
    updateReasons(); // Init
</script>
@endpush
@endsection
