@extends('layouts.creator')

@section('title', 'Ma Vitrine - RACINE BY GANDA')
@section('page-title', 'Ma Vitrine')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        
        {{-- Widget Onboarding --}}
        @include('creator.partials.onboarding-widget')
        
        {{-- Feedback Messages --}}
        @if(session('success'))
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4 rounded-3">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        <div class="creator-card">
            <div class="mb-4 pb-3 border-bottom">
                <h3 class="mb-0" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                    <i class="fas fa-store text-orange-500 me-2"></i>
                    Personnaliser ma vitrine
                </h3>
                <p class="text-muted mt-2 mb-0">Définissez l'identité visuelle de votre boutique pour attirer vos clients.</p>
            </div>

            <form method="POST" action="{{ route('creator.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Nom de la boutique --}}
                <div class="creator-form-group">
                    <label for="brand_name" class="creator-label">Nom de la boutique <span class="text-danger">*</span></label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name', $profile->brand_name) }}" required class="creator-input @error('brand_name') is-invalid @enderror">
                    @error('brand_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Visuel : Logo & Bannière --}}
                <div class="row g-4 mb-4">
                    {{-- Logo --}}
                    <div class="col-md-6">
                        <label class="creator-label">Logo de la boutique</label>
                        <div class="mb-3 text-center">
                            <div class="mx-auto shadow-sm position-relative overflow-hidden" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid #E5DDD3;">
                                @if($profile->logo_path)
                                    <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                        <i class="fas fa-camera fa-2x"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <input type="file" name="logo" accept="image/*" class="form-control mb-2">
                        <p class="small text-muted text-center">Format carré (JPG, PNG). Max 2MB.</p>
                        @error('logo') <div class="text-danger small text-center">{{ $message }}</div> @enderror
                    </div>

                    {{-- Bannière --}}
                    <div class="col-md-6">
                        <label class="creator-label">Bannière de couverture</label>
                        <div class="mb-3">
                            <div class="shadow-sm position-relative overflow-hidden w-100" style="height: 120px; border-radius: 12px; border: 4px solid #E5DDD3;">
                                @if($profile->banner_path)
                                    <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                        <i class="fas fa-image fa-2x"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <input type="file" name="banner" accept="image/*" class="form-control mb-2">
                        <p class="small text-muted text-center">Format paysage (1200x400px). Max 4MB.</p>
                        @error('banner') <div class="text-danger small text-center">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Bio --}}
                <div class="creator-form-group">
                    <label for="bio" class="creator-label">À propos de votre boutique</label>
                    <textarea id="bio" name="bio" rows="4" class="creator-input @error('bio') is-invalid @enderror" placeholder="Racontez votre histoire, votre savoir-faire...">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Réseaux Sociaux --}}
                <div class="mb-4 pb-2 border-bottom">
                    <h5 class="fw-bold text-dark">Réseaux Sociaux</h5>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="creator-form-group mb-0">
                            <label for="website" class="creator-label"><i class="fas fa-globe me-2 text-muted"></i> Site Web</label>
                            <input type="url" id="website" name="website" value="{{ old('website', $profile->website) }}" placeholder="https://..." class="creator-input">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="creator-form-group mb-0">
                            <label for="instagram_url" class="creator-label"><i class="fab fa-instagram me-2 text-muted"></i> Instagram</label>
                            <input type="url" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $profile->instagram_url) }}" placeholder="https://instagram.com/..." class="creator-input">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="creator-form-group mb-0">
                            <label for="tiktok_url" class="creator-label"><i class="fab fa-tiktok me-2 text-muted"></i> TikTok</label>
                            <input type="url" id="tiktok_url" name="tiktok_url" value="{{ old('tiktok_url', $profile->tiktok_url) }}" placeholder="https://tiktok.com/..." class="creator-input">
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                    <a href="{{ route('creator.dashboard') }}" class="btn btn-outline-secondary px-4 rounded-3 border-0">
                        Annuler
                    </a>
                    <button type="submit" class="creator-btn">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
