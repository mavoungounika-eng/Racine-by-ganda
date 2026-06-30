<form action="{{ route('admin.settings.update', 'appearance') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- IDENTITÉ VISUELLE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-image me-2" style="color: #ED5F1E;"></i>Identité visuelle</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">URL du logo principal</label>
                    <input type="url" name="logo_url" class="form-control"
                           value="{{ old('logo_url', $settings['logo_url'] ?? '') }}"
                           placeholder="https://racinebyganda.com/storage/logo.png">
                    <small class="text-muted">Format recommandé : SVG ou PNG transparent, 200x60px</small>
                    @error('logo_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if(!empty(old('logo_url', $settings['logo_url'] ?? '')))
                        <div class="mt-2 p-2 border rounded" style="background: #f8f9fa;">
                            <small class="text-muted d-block mb-1">Prévisualisation :</small>
                            <img src="{{ old('logo_url', $settings['logo_url']) }}" alt="Logo" style="max-height: 60px; max-width: 100%;">
                        </div>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">URL du logo mode sombre</label>
                    <input type="url" name="logo_dark_url" class="form-control"
                           value="{{ old('logo_dark_url', $settings['logo_dark_url'] ?? '') }}"
                           placeholder="https://racinebyganda.com/storage/logo-dark.png">
                    <small class="text-muted">Logo alternatif pour le mode sombre</small>
                    @error('logo_dark_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">URL du favicon</label>
                    <input type="url" name="favicon_url" class="form-control"
                           value="{{ old('favicon_url', $settings['favicon_url'] ?? '') }}"
                           placeholder="https://racinebyganda.com/storage/favicon.ico">
                    <small class="text-muted">Format : ICO ou PNG 32x32px</small>
                    @error('favicon_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- COULEURS DE LA CHARTE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-palette me-2" style="color: #ED5F1E;"></i>Couleurs de la charte</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3" style="border-left: 4px solid #0dcaf0;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Charte RACINE BY GANDA :</strong> Ces couleurs sont l'identité visuelle de la marque. Elles sont fixes et ne doivent pas être modifiées pour préserver la cohérence de l'expérience utilisateur.
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Couleur primaire (orange)</label>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 50px; height: 50px; background: #ED5F1E; border-radius: 8px; border: 1px solid #ddd;"></div>
                        <div class="flex-grow-1">
                            <input type="text" class="form-control" value="#ED5F1E" readonly style="background: #f8f9fa;">
                            <small class="text-muted">Charte fixe</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Couleur secondaire (jaune)</label>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 50px; height: 50px; background: #FFB800; border-radius: 8px; border: 1px solid #ddd;"></div>
                        <div class="flex-grow-1">
                            <input type="text" class="form-control" value="#FFB800" readonly style="background: #f8f9fa;">
                            <small class="text-muted">Charte fixe</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Couleur sombre (noir)</label>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 50px; height: 50px; background: #160D0C; border-radius: 8px; border: 1px solid #ddd;"></div>
                        <div class="flex-grow-1">
                            <input type="text" class="form-control" value="#160D0C" readonly style="background: #f8f9fa;">
                            <small class="text-muted">Charte fixe</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hidden inputs pour soumettre les couleurs fixes --}}
            <input type="hidden" name="primary_color" value="#ED5F1E">
            <input type="hidden" name="secondary_color" value="#FFB800">
            <input type="hidden" name="dark_color" value="#160D0C">
        </div>
    </div>

    {{-- THÈME & ANIMATIONS --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-moon me-2" style="color: #ED5F1E;"></i>Thème & Animations</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Thème par défaut <span class="text-danger">*</span></label>
                    <select name="default_theme" class="form-select" required>
                        <option value="light" {{ old('default_theme', $settings['default_theme'] ?? 'dark') === 'light' ? 'selected' : '' }}>Clair</option>
                        <option value="dark" {{ old('default_theme', $settings['default_theme'] ?? 'dark') === 'dark' ? 'selected' : '' }}>Sombre</option>
                        <option value="auto" {{ old('default_theme', $settings['default_theme'] ?? 'dark') === 'auto' ? 'selected' : '' }}>Automatique</option>
                    </select>
                    <small class="text-muted">Thème par défaut pour nouveaux utilisateurs</small>
                    @error('default_theme')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Palette accent par défaut <span class="text-danger">*</span></label>
                    <select name="default_accent" class="form-select" required>
                        <option value="orange" {{ old('default_accent', $settings['default_accent'] ?? 'orange') === 'orange' ? 'selected' : '' }}>Orange (#ED5F1E)</option>
                        <option value="yellow" {{ old('default_accent', $settings['default_accent'] ?? 'orange') === 'yellow' ? 'selected' : '' }}>Jaune (#FFB800)</option>
                        <option value="gold" {{ old('default_accent', $settings['default_accent'] ?? 'orange') === 'gold' ? 'selected' : '' }}>Or (#DAA520)</option>
                        <option value="red" {{ old('default_accent', $settings['default_accent'] ?? 'orange') === 'red' ? 'selected' : '' }}>Rouge (#DC3545)</option>
                    </select>
                    <small class="text-muted">Palette accent par défaut</small>
                    @error('default_accent')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Intensité animations <span class="text-danger">*</span></label>
                    <select name="animation_intensity" class="form-select" required>
                        <option value="none" {{ old('animation_intensity', $settings['animation_intensity'] ?? 'standard') === 'none' ? 'selected' : '' }}>Aucune</option>
                        <option value="soft" {{ old('animation_intensity', $settings['animation_intensity'] ?? 'standard') === 'soft' ? 'selected' : '' }}>Douce</option>
                        <option value="standard" {{ old('animation_intensity', $settings['animation_intensity'] ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="luxury" {{ old('animation_intensity', $settings['animation_intensity'] ?? 'standard') === 'luxury' ? 'selected' : '' }}>Luxe</option>
                    </select>
                    <small class="text-muted">Intensité animations par défaut</small>
                    @error('animation_intensity')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- CSS PERSONNALISÉ --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-code me-2" style="color: #ED5F1E;"></i>CSS Personnalisé</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>⚠️ Attention :</strong> Le CSS personnalisé est appliqué directement sans validation. Tester en environnement de développement d'abord.
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">CSS global personnalisé</label>
                    <textarea name="custom_css" class="form-control" rows="8" style="font-family: 'Courier New', monospace; font-size: 14px;"
                              placeholder="/* Votre CSS personnalisé ici */&#10;.custom-class {&#10;    color: #ED5F1E;&#10;}">{{ old('custom_css', $settings['custom_css'] ?? '') }}</textarea>
                    <small class="text-muted">Appliqué sur toutes les pages admin après le CSS principal</small>
                    @error('custom_css')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-palette me-2"></i>Enregistrer Apparence
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>
