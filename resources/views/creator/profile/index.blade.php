@extends('layouts.creator')

@section('title', 'Mon Profil - RACINE BY GANDA')
@section('page-title', 'Mon Profil')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        
        {{-- Widget de complétion --}}
        @include('creator.partials.onboarding-widget')
        
        {{-- Messages de succès --}}
        @if(session('success'))
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4 rounded-3">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- SECTION 1 : APERÇU PUBLIC --}}
        <div class="creator-card mb-4" id="apercu">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <h3 class="mb-0" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                    <i class="fas fa-eye text-orange-500 me-2"></i>
                    Aperçu Public
                </h3>
                <a href="{{ route('creator.profile.preview') }}" target="_blank" 
                   class="btn btn-outline-warning text-dark border-orange-200">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Voir comme un client
                </a>
            </div>
            
            <div class="text-center p-5 rounded-3" style="background: linear-gradient(135deg, #FFF7ED 0%, #FFFBF5 100%);">
                <div class="mb-4 text-orange-500">
                    <i class="fas fa-store fa-3x"></i>
                </div>
                <h5 class="text-dark fw-bold mb-2">Votre vitrine est votre image</h5>
                <p class="text-muted mb-4">
                    Découvrez comment les clients voient votre boutique et assurez-vous que tout est parfait.
                </p>
                <a href="{{ route('creator.profile.preview') }}" target="_blank"
                   class="creator-btn">
                    <i class="fas fa-eye me-2"></i>
                    Prévisualiser ma boutique
                </a>
            </div>
        </div>

        {{-- SECTION 2 : INFORMATIONS BOUTIQUE --}}
        <div class="creator-card mb-4" id="boutique">
            <div class="mb-4 pb-3 border-bottom">
                <h3 class="mb-0" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                    <i class="fas fa-store text-orange-500 me-2"></i>
                    Informations Boutique
                </h3>
            </div>
            
            <form action="{{ route('creator.profile.update.shop') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row g-4 mb-4">
                    {{-- Logo --}}
                    <div class="col-md-6">
                        <label class="creator-label">Logo de la boutique</label>
                        <div class="d-flex align-items-center gap-4">
                            <label for="logo" class="position-relative cursor-pointer">
                                <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 4px solid #E5DDD3;" class="shadow-sm">
                                    @if($profile->logo_path)
                                        <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small rounded-bottom-circle opacity-0 hover-opacity-100 transition-opacity">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </label>
                            <input type="file" id="logo" name="logo" accept="image/*" class="d-none" onchange="previewImage(this)">
                            <div>
                                <p class="small text-muted mb-1">Format carré recommandé (500x500px)</p>
                                <p class="extra-small text-muted mb-0">Max 2 MB - JPG, PNG</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Bannière --}}
                    <div class="col-md-6">
                        <label class="creator-label">Bannière de la boutique</label>
                        <div class="d-flex align-items-center gap-4">
                            <label for="banner" class="position-relative cursor-pointer w-100">
                                <div style="height: 100px; border-radius: 12px; overflow: hidden; border: 4px solid #E5DDD3;" class="shadow-sm w-100">
                                    @if($profile->banner_path)
                                        <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small rounded-bottom opacity-0 hover-opacity-100 transition-opacity">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </label>
                            <input type="file" id="banner" name="banner" accept="image/*" class="d-none" onchange="previewImage(this)">
                        </div>
                        <p class="small text-muted mt-2">Format paysage (1200x400px). Max 4 MB</p>
                    </div>
                </div>
                
                <div class="creator-form-group">
                    <label for="brand_name" class="creator-label">Nom de la boutique <span class="text-danger">*</span></label>
                    <input type="text" id="brand_name" name="brand_name" 
                           class="creator-input @error('brand_name') is-invalid @enderror"
                           value="{{ old('brand_name', $profile->brand_name) }}" required>
                    @error('brand_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="creator-form-group">
                    <label for="bio" class="creator-label">Description de la boutique</label>
                    <textarea id="bio" name="bio" rows="5"
                              class="creator-input @error('bio') is-invalid @enderror"
                              placeholder="Présentez votre univers créatif, votre savoir-faire, vos inspirations...">{{ old('bio', $profile->bio) }}</textarea>
                    <div class="d-flex justify-content-end mt-1">
                        <small class="text-muted"><span id="bio-counter">{{ strlen($profile->bio ?? '') }}</span>/1000 caractères</small>
                    </div>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="text-end">
                    <button type="submit" class="creator-btn">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        {{-- SECTION 3 : IDENTITÉ VENDEUR --}}
        <div class="creator-card mb-4" id="identite">
            <div class="mb-4 pb-3 border-bottom">
                <h3 class="mb-0" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                    <i class="fas fa-user text-orange-500 me-2"></i>
                    Photo & Identité
                </h3>
            </div>
            
            <form action="{{ route('creator.profile.update.identity') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="creator-label">Photo personnelle</label>
                        <div class="d-flex align-items-center gap-4">
                            <label for="avatar" class="position-relative cursor-pointer">
                                <div class="creator-avatar creator-avatar-xl shadow-sm border border-4 border-light">
                                    @if($profile->avatar_path)
                                        <img src="{{ Storage::url($profile->avatar_path) }}" alt="Avatar" class="w-100 h-100 rounded-circle object-fit-cover">
                                    @else
                                        <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small rounded-bottom-circle opacity-0 hover-opacity-100 transition-opacity" style="border-radius: 0 0 50px 50px;">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </label>
                            <input type="file" id="avatar" name="avatar" accept="image/*" class="d-none" onchange="previewImage(this)">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">{{ $user->name }}</h5>
                                <p class="small text-muted mb-1">Ajoutez une photo professionnelle pour humaniser votre boutique</p>
                                <p class="extra-small text-muted mb-0">Format carré - Max 2 MB</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="creator-form-group">
                    <label for="creator_title" class="creator-label">Titre / Fonction</label>
                    <input type="text" id="creator_title" name="creator_title" 
                           class="creator-input @error('creator_title') is-invalid @enderror"
                           value="{{ old('creator_title', $profile->creator_title) }}"
                           placeholder="Ex: Artisan maroquinier, Designer textile, Créateur bijoux...">
                    <small class="text-muted">Décrivez votre métier en quelques mots</small>
                    @error('creator_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="text-end">
                    <button type="submit" class="creator-btn">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- SECTION 4 : RÉSEAUX SOCIAUX --}}
        <div class="creator-card mb-4" id="social">
            <div class="mb-4 pb-3 border-bottom">
                <h3 class="mb-0" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                    <i class="fas fa-share-alt text-orange-500 me-2"></i>
                    Réseaux Sociaux
                </h3>
            </div>
            
            <form action="{{ route('creator.profile.update.social') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="creator-form-group">
                    <label for="website" class="creator-label">
                        <i class="fas fa-globe me-2 text-muted"></i>Site web
                    </label>
                    <input type="url" id="website" name="website" 
                           class="creator-input @error('website') is-invalid @enderror"
                           value="{{ old('website', $profile->website) }}"
                           placeholder="https://monsite.com">
                    @error('website')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="creator-form-group">
                    <label for="instagram_url" class="creator-label">
                        <i class="fab fa-instagram me-2 text-muted"></i>Instagram
                    </label>
                    <input type="url" id="instagram_url" name="instagram_url" 
                           class="creator-input @error('instagram_url') is-invalid @enderror"
                           value="{{ old('instagram_url', $profile->instagram_url) }}"
                           placeholder="https://instagram.com/votre_compte">
                    @error('instagram_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="creator-form-group">
                    <label for="tiktok_url" class="creator-label">
                        <i class="fab fa-tiktok me-2 text-muted"></i>TikTok
                    </label>
                    <input type="url" id="tiktok_url" name="tiktok_url" 
                           class="creator-input @error('tiktok_url') is-invalid @enderror"
                           value="{{ old('tiktok_url', $profile->tiktok_url) }}"
                           placeholder="https://tiktok.com/@votre_compte">
                    @error('tiktok_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="creator-form-group">
                    <label for="facebook_url" class="creator-label">
                        <i class="fab fa-facebook me-2 text-muted"></i>Facebook
                    </label>
                    <input type="url" id="facebook_url" name="facebook_url" 
                           class="creator-input @error('facebook_url') is-invalid @enderror"
                           value="{{ old('facebook_url', $profile->facebook_url) }}"
                           placeholder="https://facebook.com/votre_page">
                    @error('facebook_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="text-end">
                    <button type="submit" class="creator-btn">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .hover-opacity-100:hover { opacity: 1 !important; }
    .transition-opacity { transition: opacity 0.3s ease; }
</style>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Trouver le conteneur d'image
            const container = input.previousElementSibling.querySelector('div[style*="overflow: hidden"]');
            if (container) {
                // Créer ou mettre à jour l'image
                let img = container.querySelector('img');
                if (!img) {
                    container.innerHTML = ''; // Vider le placeholder
                    img = document.createElement('img');
                    img.className = "w-100 h-100 object-fit-cover";
                    container.appendChild(img);
                }
                img.src = e.target.result;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Compteur de caractères pour la bio
const bioTextarea = document.getElementById('bio');
if (bioTextarea) {
    bioTextarea.addEventListener('input', function() {
        const counter = document.getElementById('bio-counter');
        if (counter) {
            counter.textContent = this.value.length;
        }
    });
}
</script>
@endpush
@endsection
