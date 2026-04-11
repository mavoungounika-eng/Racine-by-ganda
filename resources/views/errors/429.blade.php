@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                    <h2 class="h3 font-weight-bold mb-3">Trop de requêtes</h2>
                    <p class="text-muted mb-4">
                        Vous avez effectué trop de requêtes en peu de temps. Veuillez patienter quelques instants avant de réessayer.
                    </p>
                    <a href="{{ route('checkout.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour au checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

