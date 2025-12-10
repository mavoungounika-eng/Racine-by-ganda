@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Charte Graphique - RACINE BY GANDA')

@push('styles')
<style>
    .brand-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .brand-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .brand-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .brand-section {
        padding: 5rem 0;
    }
    
    .brand-section:nth-child(even) {
        background: #F8F6F3;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-header h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .section-header .line {
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #D4A574, #ED5F1E);
        margin: 0 auto 1rem;
    }
    
    .section-header p {
        color: #8B7355;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* LOGO */
    .logo-showcase {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .logo-card {
        background: white;
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    }
    
    .logo-card.dark {
        background: #1a0f09;
    }
    
    .logo-card img {
        max-width: 200px;
        height: auto;
        margin-bottom: 1.5rem;
    }
    
    .logo-card h4 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .logo-card.dark h4 {
        color: white;
    }
    
    .logo-card p {
        font-size: 0.85rem;
        color: #8B7355;
    }
    
    .logo-card.dark p {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .logo-rules {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
    }
    
    .rule-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .rule-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .rule-icon.do {
        background: #ECFDF5;
        color: #22C55E;
    }
    
    .rule-icon.dont {
        background: #FEF2F2;
        color: #EF4444;
    }
    
    .rule-text h5 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .rule-text p {
        font-size: 0.85rem;
        color: #8B7355;
        margin: 0;
    }
    
    /* COLORS */
    .colors-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.5rem;
    }
    
    .color-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .color-swatch {
        height: 120px;
    }
    
    .color-info {
        padding: 1.25rem;
    }
    
    .color-info h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .color-info code {
        display: block;
        font-size: 0.8rem;
        color: #8B7355;
        margin-bottom: 0.25rem;
    }
    
    /* TYPOGRAPHY */
    .typography-showcase {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
    }
    
    .font-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    }
    
    .font-preview {
        margin-bottom: 2rem;
    }
    
    .font-preview.heading {
        font-family: 'Cormorant Garamond', serif;
    }
    
    .font-preview h3 {
        font-size: 3rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .font-preview .sample {
        font-size: 1.5rem;
        color: #5C4A3D;
        margin-bottom: 1rem;
    }
    
    .font-preview .alphabet {
        font-size: 1rem;
        color: #8B7355;
        letter-spacing: 2px;
    }
    
    .font-meta {
        padding-top: 1.5rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .font-meta h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .font-meta p {
        font-size: 0.9rem;
        color: #8B7355;
        margin-bottom: 1rem;
    }
    
    .font-weights {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .weight-tag {
        padding: 0.35rem 0.75rem;
        background: #F8F6F3;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #5C4A3D;
    }
    
    /* PATTERNS */
    .patterns-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    
    .pattern-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .pattern-preview {
        height: 150px;
        background-size: 50px;
    }
    
    .pattern-info {
        padding: 1.25rem;
        text-align: center;
    }
    
    .pattern-info h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    /* DOWNLOAD */
    .download-section {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 4rem 0;
        text-align: center;
    }
    
    .download-section h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .download-section p {
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 2rem;
    }
    
    .download-btns {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: white;
        color: #2C1810;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-download:hover {
        background: #ED5F1E;
        color: white;
        transform: translateY(-2px);
    }
    
    @media (max-width: 992px) {
        .logo-showcase,
        .typography-showcase {
            grid-template-columns: 1fr;
        }
        
        .colors-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .patterns-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .colors-grid,
        .logo-rules {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="brand-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{!! $heroData['title'] ?? '📐 Charte Graphique' !!}</h1>
        <p>{{ $heroData['description'] ?? 'L\'identité visuelle de RACINE BY GANDA - Guidelines pour une utilisation cohérente de notre marque' }}</p>
    </div>
</section>

<!-- LOGO -->
<section class="brand-section">
    <div class="container">
        <div class="section-header">
            <h2>Notre Logo</h2>
            <div class="line"></div>
            <p>Le logo RACINE BY GANDA symbolise l'élégance africaine et l'ancrage dans nos traditions.</p>
        </div>
        
        <div class="logo-showcase">
            <div class="logo-card">
                <img src="{{ asset('images/logo-racine.png') }}" alt="Logo principal" onerror="this.src='https://via.placeholder.com/200x100?text=RACINE'">
                <h4>Logo Principal</h4>
                <p>Version couleur sur fond clair</p>
            </div>
            <div class="logo-card dark">
                <img src="{{ asset('images/logo-racine-white.png') }}" alt="Logo blanc" onerror="this.src='https://via.placeholder.com/200x100?text=RACINE'">
                <h4>Logo Inversé</h4>
                <p>Version blanche sur fond sombre</p>
            </div>
            <div class="logo-card">
                <img src="{{ asset('images/logo-racine-mono.png') }}" alt="Logo monochrome" onerror="this.src='https://via.placeholder.com/200x100?text=RACINE'">
                <h4>Logo Monochrome</h4>
                <p>Version noir pour impressions</p>
            </div>
        </div>
        
        <div class="logo-rules">
            <div class="rule-card">
                <div class="rule-icon do"><i class="fas fa-check"></i></div>
                <div class="rule-text">
                    <h5>Respecter les proportions</h5>
                    <p>Toujours redimensionner le logo de manière proportionnelle</p>
                </div>
            </div>
            <div class="rule-card">
                <div class="rule-icon do"><i class="fas fa-check"></i></div>
                <div class="rule-text">
                    <h5>Zone de protection</h5>
                    <p>Laisser un espace minimum autour du logo égal à la hauteur du "R"</p>
                </div>
            </div>
            <div class="rule-card">
                <div class="rule-icon dont"><i class="fas fa-times"></i></div>
                <div class="rule-text">
                    <h5>Ne pas déformer</h5>
                    <p>Ne jamais étirer ou comprimer le logo</p>
                </div>
            </div>
            <div class="rule-card">
                <div class="rule-icon dont"><i class="fas fa-times"></i></div>
                <div class="rule-text">
                    <h5>Ne pas modifier les couleurs</h5>
                    <p>Utiliser uniquement les versions officielles</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COLORS -->
<section class="brand-section">
    <div class="container">
        <div class="section-header">
            <h2>Palette de Couleurs</h2>
            <div class="line"></div>
            <p>Nos couleurs évoquent la richesse de la terre africaine et l'élégance intemporelle.</p>
        </div>
        
        <div class="colors-grid">
            <div class="color-card">
                <div class="color-swatch" style="background: #ED5F1E;"></div>
                <div class="color-info">
                    <h4>Orange RACINE</h4>
                    <code>HEX: #ED5F1E</code>
                    <code>RGB: 237, 95, 30</code>
                </div>
            </div>
            <div class="color-card">
                <div class="color-swatch" style="background: #D4A574;"></div>
                <div class="color-info">
                    <h4>Or Sable</h4>
                    <code>HEX: #D4A574</code>
                    <code>RGB: 212, 165, 116</code>
                </div>
            </div>
            <div class="color-card">
                <div class="color-swatch" style="background: #2C1810;"></div>
                <div class="color-info">
                    <h4>Marron Terre</h4>
                    <code>HEX: #2C1810</code>
                    <code>RGB: 44, 24, 16</code>
                </div>
            </div>
            <div class="color-card">
                <div class="color-swatch" style="background: #8B5A2B;"></div>
                <div class="color-info">
                    <h4>Bronze</h4>
                    <code>HEX: #8B5A2B</code>
                    <code>RGB: 139, 90, 43</code>
                </div>
            </div>
            <div class="color-card">
                <div class="color-swatch" style="background: #F8F6F3;"></div>
                <div class="color-info">
                    <h4>Crème</h4>
                    <code>HEX: #F8F6F3</code>
                    <code>RGB: 248, 246, 243</code>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TYPOGRAPHY -->
<section class="brand-section">
    <div class="container">
        <div class="section-header">
            <h2>Typographie</h2>
            <div class="line"></div>
            <p>Nos polices reflètent l'élégance et la lisibilité de notre marque.</p>
        </div>
        
        <div class="typography-showcase">
            <div class="font-card">
                <div class="font-preview heading">
                    <h3>Cormorant Garamond</h3>
                    <p class="sample">L'élégance de nos racines</p>
                    <p class="alphabet">ABCDEFGHIJKLMNOPQRSTUVWXYZ</p>
                    <p class="alphabet">abcdefghijklmnopqrstuvwxyz</p>
                    <p class="alphabet">0123456789</p>
                </div>
                <div class="font-meta">
                    <h4>Police de titrage</h4>
                    <p>Utilisée pour les titres, headlines et accroches. Évoque l'élégance et le raffinement.</p>
                    <div class="font-weights">
                        <span class="weight-tag">Light 300</span>
                        <span class="weight-tag">Regular 400</span>
                        <span class="weight-tag">SemiBold 600</span>
                        <span class="weight-tag">Bold 700</span>
                    </div>
                </div>
            </div>
            
            <div class="font-card">
                <div class="font-preview">
                    <h3 style="font-family: system-ui, sans-serif;">System UI / Sans-serif</h3>
                    <p class="sample" style="font-family: system-ui, sans-serif;">Mode africaine premium</p>
                    <p class="alphabet" style="font-family: system-ui, sans-serif;">ABCDEFGHIJKLMNOPQRSTUVWXYZ</p>
                    <p class="alphabet" style="font-family: system-ui, sans-serif;">abcdefghijklmnopqrstuvwxyz</p>
                    <p class="alphabet" style="font-family: system-ui, sans-serif;">0123456789</p>
                </div>
                <div class="font-meta">
                    <h4>Police de corps</h4>
                    <p>Utilisée pour le texte courant, les descriptions et les informations. Assure une lecture confortable.</p>
                    <div class="font-weights">
                        <span class="weight-tag">Regular 400</span>
                        <span class="weight-tag">Medium 500</span>
                        <span class="weight-tag">SemiBold 600</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PATTERNS -->
<section class="brand-section">
    <div class="container">
        <div class="section-header">
            <h2>Motifs & Textures</h2>
            <div class="line"></div>
            <p>Nos motifs s'inspirent des tissus traditionnels africains.</p>
        </div>
        
        <div class="patterns-grid">
            <div class="pattern-card">
                <div class="pattern-preview" style="background: repeating-linear-gradient(45deg, #D4A574 0, #D4A574 1px, transparent 0, transparent 50%);"></div>
                <div class="pattern-info">
                    <h4>Lignes Kente</h4>
                </div>
            </div>
            <div class="pattern-card">
                <div class="pattern-preview" style="background: radial-gradient(circle, #ED5F1E 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="pattern-info">
                    <h4>Points Wax</h4>
                </div>
            </div>
            <div class="pattern-card">
                <div class="pattern-preview" style="background: linear-gradient(90deg, #2C1810 25%, transparent 25%), linear-gradient(90deg, transparent 75%, #2C1810 75%), linear-gradient(0deg, #2C1810 25%, transparent 25%), linear-gradient(0deg, transparent 75%, #2C1810 75%); background-size: 20px 20px; background-color: #D4A574;"></div>
                <div class="pattern-info">
                    <h4>Damier Africain</h4>
                </div>
            </div>
            <div class="pattern-card">
                <div class="pattern-preview" style="background: repeating-conic-gradient(#ED5F1E 0% 25%, transparent 0% 50%) 50% / 20px 20px;"></div>
                <div class="pattern-info">
                    <h4>Géométrique</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DOWNLOAD -->
<section class="download-section">
    <div class="container">
        <h2>Télécharger les ressources</h2>
        <p>Accédez à notre kit média complet pour une utilisation professionnelle</p>
        <div class="download-btns">
            <a href="#" class="btn-download">
                <i class="fas fa-file-archive"></i> Kit Logos (ZIP)
            </a>
            <a href="#" class="btn-download">
                <i class="fas fa-file-pdf"></i> Brand Guidelines (PDF)
            </a>
            <a href="#" class="btn-download">
                <i class="fas fa-palette"></i> Palette Couleurs
            </a>
        </div>
    </div>
</section>
@endsection

