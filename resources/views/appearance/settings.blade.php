@extends('layouts.frontend')

@section('title', 'Réglages d\'Apparence - RACINE BY GANDA')

@section('content')
<div class="hero-wrap hero-bread" style="background-image: url('{{ asset('racine/images/bg_6.jpg') }}');">
    <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center">
                <p class="breadcrumbs"><span class="mr-2"><a href="{{ route('frontend.home') }}">Accueil</a></span> <span>Paramètres</span></p>
                <h1 class="mb-0 bread">Réglages d'Apparence</h1>
            </div>
        </div>
    </div>
</div>

<section class="ftco-section bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 ftco-animate">
                <div class="bg-white p-5">
                    <div class="mb-5">
                        <h2 class="h3 mb-3"><i class="ion-ios-color-palette mr-2"></i> Personnalisation</h2>
                        <p class="text-muted">Personnalisez l'apparence du site selon vos préférences.</p>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success mb-4">
                        <i class="ion-ios-checkmark-circle mr-2"></i> {{ session('success') }}
                    </div>
                    @endif

                    <form action="{{ route('appearance.update') }}" method="POST" id="appearance-form">
                        @csrf

                        {{-- Mode d'affichage --}}
                        <div class="card mb-4">
                            <div class="card-header bg-white font-weight-bold">
                                <i class="ion-ios-sunny mr-2"></i> Mode d'Affichage
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="display_mode" value="light" {{ $settings->display_mode === 'light' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-sunny h2 text-warning d-block"></i>
                                                <span class="font-weight-bold">Clair</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="display_mode" value="dark" {{ $settings->display_mode === 'dark' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-moon h2 text-dark d-block"></i>
                                                <span class="font-weight-bold">Sombre</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="display_mode" value="auto" {{ $settings->display_mode === 'auto' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-time h2 text-primary d-block"></i>
                                                <span class="font-weight-bold">Auto</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Palette d'accent --}}
                        <div class="card mb-4">
                            <div class="card-header bg-white font-weight-bold">
                                <i class="ion-ios-brush mr-2"></i> Palette d'Accent
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 col-md-3 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="accent_palette" value="orange" {{ $settings->accent_palette === 'orange' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <div class="rounded-circle mx-auto mb-2" style="width: 40px; height: 40px; background-color: #ED5F1E;"></div>
                                                <span>Orange</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="accent_palette" value="yellow" {{ $settings->accent_palette === 'yellow' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <div class="rounded-circle mx-auto mb-2" style="width: 40px; height: 40px; background-color: #FFB800;"></div>
                                                <span>Jaune</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="accent_palette" value="gold" {{ $settings->accent_palette === 'gold' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <div class="rounded-circle mx-auto mb-2" style="width: 40px; height: 40px; background-color: #D4AF37;"></div>
                                                <span>Or</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="accent_palette" value="red" {{ $settings->accent_palette === 'red' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <div class="rounded-circle mx-auto mb-2" style="width: 40px; height: 40px; background-color: #DC2626;"></div>
                                                <span>Rouge</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Style visuel --}}
                        <div class="card mb-4">
                            <div class="card-header bg-white font-weight-bold">
                                <i class="ion-ios-person mr-2"></i> Style Visuel
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="visual_style" value="female" {{ $settings->visual_style === 'female' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-heart h2 text-warning d-block"></i>
                                                <span class="font-weight-bold">Femme</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="visual_style" value="male" {{ $settings->visual_style === 'male' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-briefcase h2 text-dark d-block"></i>
                                                <span class="font-weight-bold">Homme</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" name="visual_style" value="neutral" {{ $settings->visual_style === 'neutral' ? 'checked' : '' }} class="d-none peer">
                                            <div class="border rounded p-3 peer-checked-active">
                                                <i class="ion-ios-contrast h2 text-secondary d-block"></i>
                                                <span class="font-weight-bold">Neutre</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-primary py-3 px-5">Enregistrer</button>
                            <button type="button" onclick="if(confirm('Réinitialiser toutes les préférences ?')) window.appearanceManager.reset()" class="btn btn-outline-secondary py-3 px-4">Réinitialiser</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .peer-checked-active {
        transition: all 0.3s ease;
    }
    .peer:checked + .peer-checked-active {
        border-color: #D4AF37 !important;
        background-color: #fffdf5;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.2);
    }
</style>
@endsection

@push('scripts')
<script src="{{ asset('js/appearance.js') }}"></script>
@endpush
