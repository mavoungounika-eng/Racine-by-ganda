@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clock fa-4x text-warning mb-4"></i>
                    <h2 class="h3 font-weight-bold mb-3">Trop de requêtes</h2>
                    <p class="text-muted mb-2">
                        Vous avez effectué trop de requêtes en peu de temps.
                    </p>
                    <p class="text-muted mb-4">
                        Veuillez patienter <span id="countdown" class="fw-bold text-warning">60</span> secondes avant de réessayer.
                    </p>
                    <a href="{{ url()->previous('/') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var seconds = 60;
        var el = document.getElementById('countdown');
        if (!el) return;
        var timer = setInterval(function () {
            seconds--;
            el.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    })();
</script>
@endsection
