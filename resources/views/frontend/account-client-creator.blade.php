@extends('layouts.frontend')

@section('title', 'Comment ça marche ? - Compte Client & Créateur')

@section('content')
{{-- HEADER SECTION --}}
<div class="bg-racine-black py-5" style="position: relative; z-index: 1;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-10">
                <h1 class="text-white font-weight-bold display-4" style="font-family: var(--font-heading);">
                    Comment ça marche ?
                </h1>
                <p class="text-white-50 mt-3" style="font-size: 1.1rem;">
                    Comprendre votre compte RACINE BY GANDA
                </p>
            </div>
        </div>
    </div>
</div>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                {{-- MESSAGE CENTRAL (IMPORTANT) --}}
                <div class="alert alert-info border-0 shadow-sm mb-5" style="background: linear-gradient(135deg, rgba(212, 165, 116, 0.15) 0%, rgba(139, 90, 43, 0.1) 100%); border-left: 4px solid #D4A574 !important;">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle text-racine-orange mr-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <h3 class="text-racine-orange font-weight-bold mb-3">
                                Un seul compte suffit.
                            </h3>
                            <p class="mb-0 text-dark" style="font-size: 1.1rem; line-height: 1.8;">
                                Vous pouvez acheter et vendre avec le même compte, sans jamais perdre vos données.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- SCHÉMA SIMPLE --}}
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body p-5">
                        <h2 class="h4 font-weight-bold mb-4 text-center" style="font-family: var(--font-heading);">
                            Structure de votre compte
                        </h2>
                        
                        <div class="text-center mb-4">
                            <div class="account-structure" style="font-size: 1.1rem; line-height: 2.5;">
                                <div class="mb-3">
                                    <strong style="color: #D4A574; font-size: 1.3rem;">UN UTILISATEUR</strong>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-arrow-down text-racine-orange" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="mb-3">
                                    <strong style="color: #8B5A2B; font-size: 1.2rem;">UN COMPTE</strong><br>
                                    <span class="text-muted">(email / Google / Apple / Facebook)</span>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-arrow-down text-racine-orange" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="p-3 border rounded" style="background: rgba(212, 165, 116, 0.1);">
                                            <i class="fas fa-shopping-bag text-racine-orange mb-2" style="font-size: 2rem;"></i><br>
                                            <strong>Acheter</strong><br>
                                            <span class="text-muted small">(CLIENT)</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="p-3 border rounded" style="background: rgba(212, 165, 116, 0.1);">
                                            <i class="fas fa-palette text-racine-orange mb-2" style="font-size: 2rem;"></i><br>
                                            <strong>Vendre</strong><br>
                                            <span class="text-muted small">(CRÉATEUR)</span><br>
                                            <span class="badge badge-warning mt-2">En attente</span>
                                            <span class="badge badge-success mt-2">Actif</span>
                                            <span class="badge badge-danger mt-2">Suspendu</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FAQ --}}
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body p-5">
                        <h2 class="h4 font-weight-bold mb-4" style="font-family: var(--font-heading);">
                            Questions fréquentes
                        </h2>

                        {{-- QUESTION 1 --}}
                        <div class="faq-item mb-4 pb-4 border-bottom">
                            <h3 class="h5 font-weight-bold mb-3" style="color: #8B5A2B;">
                                ❓ Ai-je besoin de créer deux comptes ?
                            </h3>
                            <div class="alert alert-danger border-0 mb-3" style="background: rgba(239, 68, 68, 0.1);">
                                <strong class="text-danger">❌ Non.</strong>
                            </div>
                            <p class="mb-3" style="font-size: 1.05rem; line-height: 1.8;">
                                Vous utilisez <strong>un seul compte</strong> pour tout faire :
                            </p>
                            <ul class="list-unstyled ml-4" style="font-size: 1.05rem; line-height: 2;">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success mr-2"></i>
                                    Acheter des produits
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success mr-2"></i>
                                    Suivre vos commandes
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success mr-2"></i>
                                    Devenir créateur
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success mr-2"></i>
                                    Vendre vos produits
                                </li>
                            </ul>
                            <p class="mt-3 mb-0" style="font-size: 1.05rem; line-height: 1.8;">
                                <strong>👉 Votre email et votre compte restent les mêmes.</strong>
                            </p>
                        </div>

                        {{-- QUESTION 2 --}}
                        <div class="faq-item mb-4 pb-4 border-bottom">
                            <h3 class="h5 font-weight-bold mb-3" style="color: #8B5A2B;">
                                ❓ Que se passe-t-il si je deviens créateur ?
                            </h3>
                            <div class="alert alert-success border-0 mb-3" style="background: rgba(34, 197, 94, 0.1);">
                                <strong class="text-success">✅ Rien n'est perdu.</strong>
                            </div>
                            <p class="mb-3" style="font-size: 1.05rem; line-height: 1.8;">
                                Quand vous devenez créateur :
                            </p>
                            <ul class="list-unstyled ml-4" style="font-size: 1.05rem; line-height: 2;">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    <strong>Vos commandes passées restent visibles</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    <strong>Votre panier reste intact</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    <strong>Vos adresses, paiements et favoris sont conservés</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-plus-circle text-racine-orange mr-2"></i>
                                    <strong>Un espace créateur s'ajoute à votre compte</strong>
                                </li>
                            </ul>
                        </div>

                        {{-- QUESTION 3 --}}
                        <div class="faq-item mb-4 pb-4 border-bottom">
                            <h3 class="h5 font-weight-bold mb-3" style="color: #8B5A2B;">
                                ❓ Puis-je continuer à acheter même si je suis créateur ?
                            </h3>
                            <div class="alert alert-success border-0 mb-3" style="background: rgba(34, 197, 94, 0.1);">
                                <strong class="text-success">✅ Oui, toujours.</strong>
                            </div>
                            <p class="mb-3" style="font-size: 1.05rem; line-height: 1.8;">
                                Même en tant que créateur :
                            </p>
                            <ul class="list-unstyled ml-4" style="font-size: 1.05rem; line-height: 2;">
                                <li class="mb-2">
                                    <i class="fas fa-shopping-bag text-racine-orange mr-2"></i>
                                    Vous pouvez acheter vos propres produits
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-shopping-bag text-racine-orange mr-2"></i>
                                    Vous pouvez acheter chez d'autres créateurs
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-shopping-bag text-racine-orange mr-2"></i>
                                    Vous gardez toutes les fonctionnalités client
                                </li>
                            </ul>
                        </div>

                        {{-- QUESTION 4 --}}
                        <div class="faq-item mb-4">
                            <h3 class="h5 font-weight-bold mb-3" style="color: #8B5A2B;">
                                ❓ Pourquoi mon compte créateur est "en attente" ?
                            </h3>
                            <p class="mb-3" style="font-size: 1.05rem; line-height: 1.8;">
                                <strong>Explication simple :</strong>
                            </p>
                            <div class="alert alert-warning border-0 mb-3" style="background: rgba(245, 158, 11, 0.1);">
                                <p class="mb-2" style="font-size: 1.05rem; line-height: 1.8;">
                                    <strong>Lorsque vous demandez à devenir créateur :</strong>
                                </p>
                                <ul class="mb-2 ml-4" style="font-size: 1.05rem; line-height: 2;">
                                    <li>Votre compte est créé immédiatement</li>
                                    <li>Votre demande est vérifiée par l'équipe RACINE</li>
                                </ul>
                                <p class="mb-2 mt-3" style="font-size: 1.05rem; line-height: 1.8;">
                                    <strong>Pendant ce temps :</strong>
                                </p>
                                <ul class="mb-0 ml-4" style="font-size: 1.05rem; line-height: 2;">
                                    <li>
                                        <i class="fas fa-check text-success mr-2"></i>
                                        <strong>Vous pouvez acheter</strong>
                                    </li>
                                    <li>
                                        <i class="fas fa-times text-danger mr-2"></i>
                                        <strong>Vous ne pouvez pas encore vendre</strong>
                                    </li>
                                </ul>
                                <p class="mt-3 mb-0" style="font-size: 1.05rem; line-height: 1.8;">
                                    <strong>Dès validation, vous pouvez vendre sans autre action.</strong>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- LIENS UTILES --}}
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body p-5">
                        <h2 class="h4 font-weight-bold mb-4" style="font-family: var(--font-heading);">
                            Liens utiles
                        </h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('login') }}" class="btn btn-outline-racine-orange btn-block py-3">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Se connecter
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('register') }}" class="btn btn-racine-primary btn-block py-3">
                                    <i class="fas fa-user-plus mr-2"></i>
                                    Créer un compte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection



