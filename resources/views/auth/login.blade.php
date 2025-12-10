@extends('layouts.frontend')

@section('title', 'Connexion')

@section('content')
{{-- BACKGROUND MOTIF ANIMÉ -- Désactivé --}}
{{-- @include('components.racine-logo-animation', ['variant' => 'background', 'theme' => 'dark']) --}}

{{-- HEADER SECTION --}}
<div class="bg-racine-black py-5" style="position: relative; z-index: 1;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <h1 class="text-white font-weight-bold display-4" style="font-family: var(--font-heading);">Connexion</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent justify-content-center p-0">
                        <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}" class="text-white-50">Accueil</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Connexion</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h2 class="h3 font-weight-bold" style="font-family: var(--font-heading);">Bon retour</h2>
                            <p class="text-muted">Accédez à votre espace personnel</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger mb-4 border-0 shadow-sm">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label for="email" class="font-weight-bold small text-uppercase text-muted">Adresse email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control input-racine py-4" required autofocus placeholder="exemple@email.com">
                            </div>

                            <div class="form-group mb-4">
                                <label for="password" class="font-weight-bold small text-uppercase text-muted">Mot de passe</label>
                                <input type="password" id="password" name="password" class="form-control input-racine py-4" required placeholder="Votre mot de passe">
                            </div>

                            <div class="form-group d-flex justify-content-between align-items-center mb-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                                    <label class="custom-control-label" for="remember">Se souvenir de moi</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="text-racine-orange small font-weight-bold">Mot de passe oublié ?</a>
                            </div>

                            <button type="submit" class="btn btn-racine-primary btn-block py-3 font-weight-bold shadow-sm mb-4">
                                Se connecter
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-3">Vous n'avez pas de compte ? <a href="{{ route('register') }}" class="text-racine-orange font-weight-bold">Créer un compte</a></p>
                            <hr class="my-4">
                            <a href="{{ route('auth.hub') }}" class="text-muted small">
                                <i class="fas fa-arrow-left mr-1"></i> Retour au choix de connexion
                            </a>
                        </div>

                        {{-- DISTINCTION CLIENT / CRÉATEUR --}}
                        <div class="mt-5 pt-4 border-top text-center">
                            <p class="text-muted small mb-3">
                                <i class="fas fa-palette text-racine-orange"></i>
                                Vous êtes créateur, styliste ou artisan partenaire ?
                            </p>
                            <a href="{{ route('creator.login') }}" 
                               class="btn btn-outline-racine-orange btn-sm px-4 py-2 rounded-pill">
                                <i class="fas fa-palette mr-2"></i>
                                Accéder à l'espace créateur
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
