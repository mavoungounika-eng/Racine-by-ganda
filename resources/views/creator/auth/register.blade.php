<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ouverture de Compte Créateur - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            background: #111111;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 2rem 0;
        }
        
        .gradient-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(139, 90, 43, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(212, 165, 116, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 107, 0, 0.05) 0%, transparent 70%);
        }
        
        .noise {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            padding: 2rem;
            position: relative;
            z-index: 10;
            margin: 0 auto;
        }
        
        .auth-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: color 0.3s;
        }
        
        .auth-back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A574, #E5B27B);
            transform: scaleX(1);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            background: rgba(212, 165, 116, 0.15);
            color: #D4A574;
            border: 1px solid rgba(212, 165, 116, 0.3);
        }
        
        .register-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .register-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        .register-form {
            margin-top: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #ff6b6b;
        }
        
        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .form-control:focus {
            outline: none;
            border-color: rgba(212, 165, 116, 0.5);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }
        
        .form-control select {
            cursor: pointer;
        }
        
        .form-help {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.25rem;
        }
        
        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0.2rem;
            cursor: pointer;
            accent-color: #D4A574;
            flex-shrink: 0;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            line-height: 1.5;
            cursor: pointer;
        }
        
        .form-check-label a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
        }
        
        .btn-register {
            width: 100%;
            padding: 0.9rem 1.5rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 40%, #FF6B00 100%);
            color: #111;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .register-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .register-footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .register-footer a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            font-weight: 500;
        }
        
        .creator-link-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .creator-link-section p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .btn-creator-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            border: 1px solid rgba(212, 165, 116, 0.3);
            background: rgba(212, 165, 116, 0.1);
            color: #D4A574;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-creator-link:hover {
            background: rgba(212, 165, 116, 0.2);
            border-color: rgba(212, 165, 116, 0.5);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .register-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <a href="{{ route('auth.hub') }}" class="auth-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Retour au choix d'espace</span>
        </a>
        
        <div class="register-card">
            <div class="register-header">
                <span class="register-badge">
                    <i class="fas fa-palette"></i>
                    Espace Créateur
                </span>
                
                <h1 class="register-title">Devenir Créateur Partenaire</h1>
                <p class="register-subtitle">Rejoignez RACINE BY GANDA et vendez vos créations sur notre plateforme. Votre compte sera validé par notre équipe.</p>
            </div>
            
            {{-- MESSAGE INFORMATIF --}}
            <div style="background: rgba(212, 165, 116, 0.15); border: 1px solid rgba(212, 165, 116, 0.3); border-left: 4px solid #D4A574; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.9); font-size: 0.875rem;">
                <div style="display: flex; align-items: start; gap: 0.75rem;">
                    <i class="fas fa-info-circle" style="color: #D4A574; font-size: 1rem; margin-top: 0.1rem;"></i>
                    <div>
                        <strong style="color: #D4A574; display: block; margin-bottom: 0.25rem;">Un seul compte suffit.</strong>
                        <p style="margin: 0; font-size: 0.85rem; line-height: 1.5;">
                            Vous pouvez acheter et vendre avec le même compte, sans jamais perdre vos données.
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- CONNEXION SOCIALE --}}
            <div class="social-login" style="margin-bottom: 1.5rem;">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <span style="color: rgba(255, 255, 255, 0.5); font-size: 0.875rem;">Ou s'inscrire avec</span>
                </div>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'google', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.05); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif; margin-bottom: 0.75rem;">
                    <i class="fab fa-google"></i>
                    <span>S'inscrire avec Google</span>
                </a>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'apple', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.3); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif; margin-bottom: 0.75rem;">
                    <i class="fab fa-apple"></i>
                    <span>S'inscrire avec Apple</span>
                </a>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(24, 119, 242, 0.4); background: rgba(24, 119, 242, 0.2); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif; margin-bottom: 1rem;">
                    <i class="fab fa-facebook-f"></i>
                    <span>S'inscrire avec Facebook</span>
                </a>
                
                <div style="text-align: center; margin: 1.5rem 0 1rem 0;">
                    <span style="color: rgba(255, 255, 255, 0.5); font-size: 0.875rem;">Ou remplir le formulaire</span>
                </div>
            </div>
            
            @if($errors->any())
                <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid rgba(255, 107, 107, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #ff6b6b; font-size: 0.875rem;">
                    <strong>Erreurs :</strong>
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('creator.register.post') }}" class="register-form">
                @csrf
                
                <div class="form-section">
                    <h3 class="form-section-title">Informations Personnelles</h3>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nom Complet <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-control" 
                            placeholder="Votre nom complet" 
                            required
                            value="{{ old('name') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse Email <span class="required">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="votre@email.com" 
                            required
                            value="{{ old('email') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-control" 
                            placeholder="+33 6 12 34 56 78" 
                            value="{{ old('phone') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de Passe <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="••••••••" 
                            required
                        >
                        <p class="form-help">Minimum 8 caractères</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmer le Mot de Passe <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-control" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Informations de la Marque / Atelier</h3>
                    
                    <div class="form-group">
                        <label for="brand_name" class="form-label">Nom de la Marque / Atelier <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="brand_name" 
                            name="brand_name" 
                            class="form-control" 
                            placeholder="Ex: Atelier Mode Paris" 
                            required
                            value="{{ old('brand_name') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Description / Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            class="form-control" 
                            rows="4" 
                            placeholder="Présentez votre marque, votre style, votre univers..."
                            maxlength="1000"
                        >{{ old('bio') }}</textarea>
                        <p class="form-help">Maximum 1000 caractères</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Localisation</label>
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            class="form-control" 
                            placeholder="Ex: Paris, France" 
                            value="{{ old('location') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="type" class="form-label">Type de Créations</label>
                        <select id="type" name="type" class="form-control">
                            <option value="">Sélectionnez un type</option>
                            <option value="pret-a-porter" {{ old('type') === 'pret-a-porter' ? 'selected' : '' }}>Prêt-à-porter</option>
                            <option value="sur-mesure" {{ old('type') === 'sur-mesure' ? 'selected' : '' }}>Sur mesure</option>
                            <option value="accessoires" {{ old('type') === 'accessoires' ? 'selected' : '' }}>Accessoires</option>
                            <option value="bijoux" {{ old('type') === 'bijoux' ? 'selected' : '' }}>Bijoux</option>
                            <option value="autre" {{ old('type') === 'autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Réseaux Sociaux & Web (Optionnel)</h3>
                    
                    <div class="form-group">
                        <label for="website" class="form-label">Site Web</label>
                        <input 
                            type="url" 
                            id="website" 
                            name="website" 
                            class="form-control" 
                            placeholder="https://votre-site.com" 
                            value="{{ old('website') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="instagram_url" class="form-label">Instagram</label>
                        <input 
                            type="url" 
                            id="instagram_url" 
                            name="instagram_url" 
                            class="form-control" 
                            placeholder="https://instagram.com/votre-compte" 
                            value="{{ old('instagram_url') }}"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="tiktok_url" class="form-label">TikTok</label>
                        <input 
                            type="url" 
                            id="tiktok_url" 
                            name="tiktok_url" 
                            class="form-control" 
                            placeholder="https://tiktok.com/@votre-compte" 
                            value="{{ old('tiktok_url') }}"
                        >
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Informations Légales (Optionnel)</h3>
                    
                    <div class="form-group">
                        <label for="legal_status" class="form-label">Statut Légal</label>
                        <select id="legal_status" name="legal_status" class="form-control">
                            <option value="">Sélectionnez un statut</option>
                            <option value="particulier" {{ old('legal_status') === 'particulier' ? 'selected' : '' }}>Particulier</option>
                            <option value="auto-entrepreneur" {{ old('legal_status') === 'auto-entrepreneur' ? 'selected' : '' }}>Auto-entrepreneur</option>
                            <option value="sarl" {{ old('legal_status') === 'sarl' ? 'selected' : '' }}>SARL</option>
                            <option value="sas" {{ old('legal_status') === 'sas' ? 'selected' : '' }}>SAS</option>
                            <option value="autre" {{ old('legal_status') === 'autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="registration_number" class="form-label">Numéro d'Enregistrement</label>
                        <input 
                            type="text" 
                            id="registration_number" 
                            name="registration_number" 
                            class="form-control" 
                            placeholder="RCCM, NIU, SIRET..." 
                            value="{{ old('registration_number') }}"
                        >
                    </div>
                </div>
                
                {{-- ✅ C4: CGV CRÉATEUR (nouveau) --}}
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="cgv_creator" 
                        name="cgv_creator" 
                        required
                    >
                    <label class="form-check-label" for="cgv_creator">
                        J'accepte les <a href="{{ route('creator.cgv') }}" target="_blank" style="color: #D4A574; text-decoration: underline;">Conditions Générales de Vente Créateur</a> de RACINE BY GANDA. <span class="required">*</span>
                    </label>
                </div>
                
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="terms" 
                        name="terms" 
                        required
                    >
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="{{ route('frontend.terms') }}" target="_blank">conditions générales</a> et la <a href="{{ route('frontend.privacy') }}" target="_blank">politique de confidentialité</a> de RACINE BY GANDA.
                    </label>
                </div>
                
                <button type="submit" class="btn-register">
                    Envoyer ma Demande
                </button>
            </form>
            
            <div class="register-footer">
                <p>Déjà un compte créateur ? <a href="{{ route('creator.login') }}">Se connecter</a></p>
            </div>
            
            <div class="creator-link-section">
                <p>Vous souhaitez simplement acheter ?</p>
                <a href="{{ route('register', ['context' => 'boutique']) }}" class="btn-creator-link">
                    <i class="fas fa-shopping-bag"></i>
                    Créer un compte client
                </a>
            </div>
        </div>
    </div>
</body>
</html>

