<form action="{{ route('admin.settings.update', 'general') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- INFORMATIONS DE L'ENTREPRISE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-building me-2" style="color: #ED5F1E;"></i>Informations de l'entreprise</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nom du site <span class="text-danger">*</span></label>
                    <input type="text" name="site_name" class="form-control"
                           value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                           placeholder="RACINE BY GANDA" required>
                    <small class="text-muted">Nom affiché sur tout le site</small>
                    @error('site_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email de contact <span class="text-danger">*</span></label>
                    <input type="email" name="site_email" class="form-control"
                           value="{{ old('site_email', $settings['site_email'] ?? '') }}"
                           placeholder="contact@racinebyganda.com" required>
                    <small class="text-muted">Email principal pour les notifications</small>
                    @error('site_email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="text" name="site_phone" class="form-control"
                           value="{{ old('site_phone', $settings['site_phone'] ?? '') }}"
                           placeholder="+237 6XX XX XX XX">
                    <small class="text-muted">Numéro de contact affiché</small>
                    @error('site_phone')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Adresse physique</label>
                    <input type="text" name="site_address" class="form-control"
                           value="{{ old('site_address', $settings['site_address'] ?? '') }}"
                           placeholder="Douala, Cameroun">
                    <small class="text-muted">Adresse du magasin/bureau</small>
                    @error('site_address')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Pays</label>
                    <select name="site_country" class="form-select">
                        <option value="">-- Sélectionner --</option>
                        <option value="Cameroun" {{ old('site_country', $settings['site_country'] ?? '') === 'Cameroun' ? 'selected' : '' }}>Cameroun</option>
                        <option value="Congo" {{ old('site_country', $settings['site_country'] ?? '') === 'Congo' ? 'selected' : '' }}>Congo</option>
                        <option value="France" {{ old('site_country', $settings['site_country'] ?? '') === 'France' ? 'selected' : '' }}>France</option>
                        <option value="Belgique" {{ old('site_country', $settings['site_country'] ?? '') === 'Belgique' ? 'selected' : '' }}>Belgique</option>
                        <option value="Autre" {{ old('site_country', $settings['site_country'] ?? '') === 'Autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                    <small class="text-muted">Pays principal d'activité</small>
                    @error('site_country')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Fuseau horaire</label>
                    <select name="site_timezone" class="form-select">
                        <option value="Africa/Douala" {{ old('site_timezone', $settings['site_timezone'] ?? '') === 'Africa/Douala' ? 'selected' : '' }}>Africa/Douala (Cameroun)</option>
                        <option value="Africa/Brazzaville" {{ old('site_timezone', $settings['site_timezone'] ?? '') === 'Africa/Brazzaville' ? 'selected' : '' }}>Africa/Brazzaville (Congo)</option>
                        <option value="Europe/Paris" {{ old('site_timezone', $settings['site_timezone'] ?? '') === 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                        <option value="UTC" {{ old('site_timezone', $settings['site_timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                    </select>
                    <small class="text-muted">Fuseau horaire du site</small>
                    @error('site_timezone')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- RÉSEAUX SOCIAUX --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-share-alt me-2" style="color: #ED5F1E;"></i>Réseaux sociaux</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-facebook text-primary me-2"></i>Facebook
                    </label>
                    <input type="url" name="social_facebook" class="form-control"
                           value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}"
                           placeholder="https://facebook.com/racinebyganda">
                    @error('social_facebook')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-instagram text-danger me-2"></i>Instagram
                    </label>
                    <input type="url" name="social_instagram" class="form-control"
                           value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}"
                           placeholder="https://instagram.com/racinebyganda">
                    @error('social_instagram')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-twitter text-info me-2"></i>Twitter / X
                    </label>
                    <input type="url" name="social_twitter" class="form-control"
                           value="{{ old('social_twitter', $settings['social_twitter'] ?? '') }}"
                           placeholder="https://twitter.com/racinebyganda">
                    @error('social_twitter')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-whatsapp text-success me-2"></i>WhatsApp Business
                    </label>
                    <input type="text" name="social_whatsapp" class="form-control"
                           value="{{ old('social_whatsapp', $settings['social_whatsapp'] ?? '') }}"
                           placeholder="+237 6XX XX XX XX">
                    <small class="text-muted">Numéro WhatsApp Business</small>
                    @error('social_whatsapp')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-tiktok me-2" style="color: #000;"></i>TikTok
                    </label>
                    <input type="url" name="social_tiktok" class="form-control"
                           value="{{ old('social_tiktok', $settings['social_tiktok'] ?? '') }}"
                           placeholder="https://tiktok.com/@racinebyganda">
                    @error('social_tiktok')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fab fa-linkedin text-primary me-2"></i>LinkedIn
                    </label>
                    <input type="url" name="social_linkedin" class="form-control"
                           value="{{ old('social_linkedin', $settings['social_linkedin'] ?? '') }}"
                           placeholder="https://linkedin.com/company/racinebyganda">
                    @error('social_linkedin')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Enregistrer les paramètres généraux
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
        </a>
    </div>
</form>
