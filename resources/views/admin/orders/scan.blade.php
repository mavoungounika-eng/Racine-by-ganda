@extends('layouts.admin')

@section('title', 'Scanner une commande')
@section('page-title', 'Scanner une Commande')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .premium-input {
        background: rgba(22, 13, 12, 0.6);
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 1rem 1.25rem 1rem 3rem;
        color: #e2e8f0;
        font-size: 1.1rem;
        transition: all 0.3s;
    }
    
    .premium-input:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
        background: rgba(22, 13, 12, 0.8);
    }
    
    .premium-input::placeholder {
        color: #64748B;
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }

    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 800px;">
    <div class="premium-card mb-4">
        <h2 class="h3 mb-2" style="font-family: 'Libre Baskerville', serif; color: white; font-weight: 700;">
            <i class="fas fa-qrcode text-warning mr-2"></i>
            Scanner une commande
        </h2>
        <p class="text-muted mb-4">Utilisez un lecteur de code-barres ou entrez le code manuellement</p>

        <!-- Instructions -->
        <div class="info-box mb-4">
            <div class="d-flex align-items-start">
                <div class="flex-shrink-0 mr-3">
                    <i class="fas fa-info-circle text-info" style="font-size: 1.25rem;"></i>
                </div>
                <div>
                    <h5 class="text-info font-weight-bold mb-2">Comment utiliser le scanner</h5>
                    <ul class="mb-0 pl-3">
                        <li class="mb-1">Placez le curseur dans le champ ci-dessous</li>
                        <li class="mb-1">Scannez le QR Code avec votre lecteur</li>
                        <li class="mb-1">Le code sera automatiquement saisi et la recherche lancée</li>
                        <li>Vous pouvez aussi taper manuellement un code ou un numéro de commande</li>
                    </ul>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->has('code'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ $errors->first('code') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Scan Form -->
        <form method="POST" action="{{ route('admin.orders.scan.handle') }}">
            @csrf

            <div class="mb-4">
                <label for="code" class="form-label font-weight-bold" style="color: #e2e8f0;">
                    Code QR ou Numéro de commande
                </label>
                <div class="position-relative">
                    <div class="position-absolute" style="left: 1rem; top: 50%; transform: translateY(-50%); z-index: 10;">
                        <i class="fas fa-qrcode text-muted" style="font-size: 1.25rem;"></i>
                    </div>
                    <input 
                        type="text" 
                        name="code" 
                        id="code" 
                        autofocus 
                        autocomplete="off"
                        value="{{ old('code') }}"
                        class="premium-input form-control @error('code') is-invalid @enderror"
                        placeholder="Scannez ici ou tapez le code"
                        style="width: 100%;"
                    >
                </div>
                <small class="form-text text-muted mt-2">
                    Le champ est en focus automatique pour faciliter le scan
                </small>
            </div>

            <div class="d-flex justify-content-end pt-3 border-top" style="border-color: rgba(139, 115, 85, 0.3) !important;">
                <button type="submit" class="premium-btn">
                    <i class="fas fa-search mr-2"></i>
                    Rechercher la commande
                </button>
            </div>
        </form>

        <!-- Quick Tips -->
        <div class="mt-5 pt-4 border-top" style="border-color: rgba(139, 115, 85, 0.3) !important;">
            <h5 class="font-weight-bold mb-3" style="color: white;">Astuces rapides</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-start p-3" style="background: rgba(22, 13, 12, 0.4); border: 1px solid rgba(139, 115, 85, 0.3); border-radius: 12px;">
                        <i class="fas fa-check-circle text-success mr-3 mt-1"></i>
                        <p class="mb-0 small" style="color: #e2e8f0;">Recherche par QR token unique</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-start p-3" style="background: rgba(22, 13, 12, 0.4); border: 1px solid rgba(139, 115, 85, 0.3); border-radius: 12px;">
                        <i class="fas fa-check-circle text-success mr-3 mt-1"></i>
                        <p class="mb-0 small" style="color: #e2e8f0;">Recherche par numéro de commande</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Link -->
    <div class="text-center">
        <a href="{{ route('admin.orders.index') }}" class="text-warning" style="text-decoration: none;">
            <i class="fas fa-arrow-left mr-1"></i>
            Voir toutes les commandes
        </a>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const codeInput = document.getElementById('code');
        let scanTimeout;

        codeInput.addEventListener('input', function() {
            clearTimeout(scanTimeout);
            if (this.value.length > 30) {
                scanTimeout = setTimeout(() => {
                    this.form.submit();
                }, 300);
            }
        });
    });
</script>
@endpush
@endsection
