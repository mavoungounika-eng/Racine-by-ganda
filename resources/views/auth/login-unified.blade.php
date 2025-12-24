@extends('layouts.frontend')

@section('title', 'Connexion')

@section('content')
{{-- HEADER SECTION --}}
<div class="bg-racine-black py-5" style="position: relative; z-index: 1;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <h1 class="text-white font-weight-bold display-4" style="font-family: var(--font-heading);">Connexion</h1>
                <p class="text-white-50 mt-3">Accédez à votre espace personnel</p>
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
                        {{-- MESSAGE RASSURANT (IMPORTANT) --}}
                        <div class="alert alert-info border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%); border-left: 4px solid #D4A574 !important;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle text-racine-orange mr-3 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <strong class="text-racine-orange">Un seul compte suffit.</strong>
                                    <p class="mb-0 mt-1 text-dark" style="font-size: 0.9rem;">
                                        Vous pouvez acheter et vendre avec le même compte, sans jamais perdre vos données.
                                    </p>
                                </div>
                            </div>
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

                        {{-- FORMULAIRE DE CONNEXION --}}
                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label for="email" class="font-weight-bold small text-uppercase text-muted">Adresse email</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       class="form-control input-racine py-4" 
                                       required 
                                       autofocus 
                                       placeholder="exemple@email.com">
                            </div>

                            <div class="form-group mb-4">
                                <label for="password" class="font-weight-bold small text-uppercase text-muted">Mot de passe</label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control input-racine py-4" 
                                       required 
                                       placeholder="Votre mot de passe">
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

                        {{-- SÉPARATEUR --}}
                        <div class="divider text-center my-4">
                            <span class="text-muted small" style="position: relative; padding: 0 1rem; background: white;">
                                ou continuer avec
                            </span>
                            <hr class="my-0" style="margin-top: -10px;">
                        </div>

                        {{-- BOUTONS OAUTH (UNIFIÉS - SANS PARAMÈTRE ROLE) --}}
                        <div class="oauth-buttons">
                            <a href="{{ url('/auth/google/redirect') }}" 
                               class="btn btn-outline-secondary btn-block py-3 mb-3 d-flex align-items-center justify-content-center"
                               style="border-color: #ddd; transition: all 0.3s;">
                                <i class="fab fa-google mr-2" style="color: #4285F4;"></i>
                                <span>Continuer avec Google</span>
                            </a>

                            <a href="{{ url('/auth/apple/redirect') }}" 
                               class="btn btn-outline-dark btn-block py-3 mb-3 d-flex align-items-center justify-content-center"
                               style="background: #000; color: #fff; border-color: #000; transition: all 0.3s;">
                                <i class="fab fa-apple mr-2"></i>
                                <span>Continuer avec Apple</span>
                            </a>

                            <a href="{{ url('/auth/facebook/redirect') }}" 
                               class="btn btn-outline-primary btn-block py-3 mb-3 d-flex align-items-center justify-content-center"
                               style="background: #1877F2; color: #fff; border-color: #1877F2; transition: all 0.3s;">
                                <i class="fab fa-facebook-f mr-2"></i>
                                <span>Continuer avec Facebook</span>
                            </a>
                        </div>

                        {{-- LIEN INSCRIPTION --}}
                        <div class="text-center mt-4 pt-4 border-top">
                            <p class="mb-0">
                                Vous n'avez pas de compte ? 
                                <a href="{{ route('register') }}" class="text-racine-orange font-weight-bold">Créer un compte</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .divider {
        position: relative;
        margin: 1.5rem 0;
    }
    
    .divider hr {
        border-top: 1px solid #dee2e6;
    }
    
    .divider span {
        position: relative;
        z-index: 1;
    }
    
    .oauth-buttons a:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>
@endsection



