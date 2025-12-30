@extends('layouts.frontend')

@section('title', 'Connexion - RACINE BY GANDA')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #FAF7F4 0%, #FFF9F5 100%);
    }
</style>
@endpush

@section('content')
<div class="hero-wrap hero-bread" style="background-image: url('{{ asset('racine/images/bg_6.jpg') }}');">
    <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center">
                <p class="breadcrumbs"><span class="mr-2"><a href="{{ route('frontend.home') }}">Accueil</a></span> <span>Connexion</span></p>
                <h1 class="mb-0 bread">Connexion Femme</h1>
            </div>
        </div>
    </div>
</div>

<section class="ftco-section contact-section bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 ftco-animate">
                <div class="bg-white p-5 contact-form border border-warning">
                    <div class="text-center mb-4">
                        <h2 class="h3 text-dark">Bienvenue Reine</h2>
                        <p class="text-muted">Votre Espace Beauté RACINE</p>
                    </div>

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf
                        <input type="hidden" name="visual_style" value="female">

                        <div class="form-group">
                            <label for="email">Adresse Email</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de Passe</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="text-warning">Mot de passe oublié ?</a>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-warning py-3 px-5 w-100 text-white">Se Connecter</button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p>Pas encore de compte ? <a href="{{ route('register') }}" class="text-warning font-weight-bold">Créer un compte</a></p>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('frontend.home') }}" class="text-muted"><i class="ion-ios-arrow-back"></i> Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
