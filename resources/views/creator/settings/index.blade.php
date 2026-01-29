@extends('layouts.creator')

@section('title', 'Ma Vitrine - RACINE BY GANDA')
@section('page-title', 'Ma Vitrine')

@push('styles')
<style>
    .vitrine-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid #F0EBE5;
    }

    .form-section-title {
        color: var(--racine-black);
        font-family: 'Libre Baskerville', serif;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .form-section-title i {
        color: var(--racine-orange);
    }

    .creator-label {
        font-weight: 700;
        color: var(--racine-black) !important;
        margin-bottom: 0.5rem;
        display: block;
    }

    .visual-upload-container {
        background: #F8F6F3;
        border: 2px dashed #D4A574;
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        text-align: center;
    }

    .visual-upload-container:hover {
        background: #F0EBE5;
        border-color: var(--racine-orange);
    }

    .preview-box {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logo-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
    }

    .banner-preview {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .social-input-group {
        position: relative;
    }

    .social-input-group i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #8B7355;
    }

    .social-input-group .creator-input {
        padding-left: 3rem;
    }

    .btn-save-vitrine {
        background: var(--racine-orange);
        color: white !important;
        font-weight: 800;
        padding: 0.8rem 2rem;
        border-radius: 12px;
        box-shadow: var(--shadow-orange);
        transition: all 0.3s;
    }

    .btn-save-vitrine:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(237, 95, 30, 0.4);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            
            {{-- Navigation Unifiée --}}
            @include('creator.partials.settings-nav')
            
            {{-- Widget Onboarding (Optionnel selon l'état) --}}
            @if(!$profile->logo_path || !$profile->banner_path)
                @include('creator.partials.onboarding-widget')
            @endif
            
            {{-- Messages --}}
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 rounded-pill px-4 py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle mr-3 fa-lg"></i>
                        <span class="font-weight-bold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <div class="vitrine-card">
                <div class="mb-5">
                    <h2 class="form-section-title h3 mb-2">
                        <i class="fas fa-magic"></i>
                        Personnaliser ma vitrine
                    </h2>
                    <p class="text-muted font-weight-bold">L'identité visuelle de votre boutique est la première chose que vos clients verront.</p>
                </div>

                <form method="POST" action="{{ route('creator.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Nom de la boutique --}}
                    <div class="form-group mb-5">
                        <label for="brand_name" class="creator-label">Nom de votre boutique <span class="text-danger">*</span></label>
                        <input type="text" id="brand_name" name="brand_name" 
                               value="{{ old('brand_name', $profile->brand_name) }}" 
                               class="form-control creator-input h-auto py-3 @error('brand_name') is-invalid @enderror" 
                               required placeholder="Ex: Ma Boutique Artisanale">
                        @error('brand_name') <div class="invalid-feedback font-weight-bold">{{ $message }}</div> @enderror
                    </div>

                    {{-- Visuels --}}
                    <div class="row mb-5">
                        {{-- Logo --}}
                        <div class="col-md-5 mb-4 mb-md-0">
                            <label class="creator-label">Logo de la marque</label>
                            <div class="visual-upload-container h-100 d-flex flex-column align-items-center justify-content-center">
                                <div class="preview-box border-0 mb-3" style="width: 120px; height: 120px; border-radius: 50%;">
                                    @if($profile->logo_path)
                                        <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="logo-preview shadow">
                                    @else
                                        <div class="text-muted"><i class="fas fa-camera fa-3x"></i></div>
                                    @endif
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="logo" class="custom-file-input" id="logoInput" accept="image/*">
                                    <label class="custom-file-label text-left" for="logoInput">Changer le logo</label>
                                </div>
                                <small class="text-muted mt-2 d-block">Recommandé : 500x500px (Max 2MB)</small>
                                @error('logo') <div class="text-danger small font-weight-bold mt-2">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Bannière --}}
                        <div class="col-md-7">
                            <label class="creator-label">Bannière de couverture</label>
                            <div class="visual-upload-container h-100">
                                <div class="preview-box mb-3">
                                    @if($profile->banner_path)
                                        <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière" class="banner-preview shadow">
                                    @else
                                        <div class="py-4 text-muted"><i class="fas fa-image fa-3x"></i></div>
                                    @endif
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="banner" class="custom-file-input" id="bannerInput" accept="image/*">
                                    <label class="custom-file-label text-left" for="bannerInput">Changer la bannière</label>
                                </div>
                                <small class="text-muted mt-2 d-block">Idéal : 1200x400px (Max 4MB)</small>
                                @error('banner') <div class="text-danger small font-weight-bold mt-2">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- À propos --}}
                    <div class="form-group mb-5">
                        <label for="bio" class="creator-label">Présentation de votre boutique / Biographie</label>
                        <textarea id="bio" name="bio" rows="5" 
                                  class="form-control creator-input h-auto py-3 @error('bio') is-invalid @enderror" 
                                  placeholder="Racontez votre histoire, votre passion et ce qui rend vos produits uniques...">{{ old('bio', $profile->bio) }}</textarea>
                        @error('bio') <div class="invalid-feedback font-weight-bold">{{ $message }}</div> @enderror
                    </div>

                    {{-- Réseaux Sociaux --}}
                    <div class="mb-4">
                        <h4 class="h5 font-weight-bold text-dark border-bottom pb-3 mb-4">Liens sociaux</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="creator-label">Site Web</label>
                                <div class="social-input-group">
                                    <i class="fas fa-globe"></i>
                                    <input type="url" name="website" value="{{ old('website', $profile->website) }}" 
                                           class="form-control creator-input h-auto py-3" placeholder="https://votre-site.com">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="creator-label">Instagram</label>
                                <div class="social-input-group">
                                    <i class="fab fa-instagram"></i>
                                    <input type="url" name="instagram_url" value="{{ old('instagram_url', $profile->instagram_url) }}" 
                                           class="form-control creator-input h-auto py-3" placeholder="https://instagram.com/nom">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="creator-label">TikTok</label>
                                <div class="social-input-group">
                                    <i class="fab fa-tiktok"></i>
                                    <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $profile->tiktok_url) }}" 
                                           class="form-control creator-input h-auto py-3" placeholder="https://tiktok.com/@nom">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mt-5 pt-4 border-top">
                        <a href="{{ route('creator.dashboard') }}" class="btn btn-link text-muted font-weight-bold text-decoration-none order-2 order-md-1">
                            <i class="fas fa-arrow-left mr-2"></i> Annuler les changements
                        </a>
                        <button type="submit" class="btn btn-save-vitrine btn-lg order-1 order-md-2 px-5">
                            <i class="fas fa-save mr-2"></i> Enregistrer ma vitrine
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Script simple pour afficher le nom du fichier sélectionné dans les labels Bootstrap
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', e => {
            let fileName = e.target.files[0].name;
            let nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    });
</script>
@endpush

