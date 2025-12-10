@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Albums Photos - RACINE BY GANDA')

@push('styles')
<style>
    .albums-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .albums-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .albums-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
    }
    
    .albums-section {
        padding: 4rem 0;
        background: #F8F6F3;
        width: 100%;
    }
    
    /* S'assurer que le CTA et le footer prennent toute la largeur */
    #cta-racine {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .footer-area {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .albums-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
    }
    
    .album-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        transition: all 0.4s;
        cursor: pointer;
    }
    
    .album-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
    }
    
    .album-cover {
        position: relative;
        height: 280px;
        overflow: hidden;
    }
    
    .album-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .album-card:hover .album-cover img {
        transform: scale(1.1);
    }
    
    .album-preview {
        position: absolute;
        bottom: 1rem;
        left: 1rem;
        right: 1rem;
        display: flex;
        gap: 0.5rem;
    }
    
    .album-preview-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }
    
    .album-preview-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .album-preview-more {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .album-category {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .album-content {
        padding: 1.5rem;
    }
    
    .album-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.4rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .album-content p {
        color: #8B7355;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        line-height: 1.6;
    }
    
    .album-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .album-meta .date {
        color: #8B7355;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .album-meta .count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #ED5F1E;
        font-weight: 600;
    }
    
    /* Featured Album */
    .featured-album {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        margin-bottom: 3rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    }
    
    .featured-album-gallery {
        display: grid;
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 0.5rem;
        padding: 0.5rem;
        height: 450px;
    }
    
    .featured-album-gallery .main {
        grid-row: span 2;
    }
    
    .featured-album-gallery img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }
    
    .featured-album-content {
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .featured-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        width: fit-content;
        margin-bottom: 1rem;
    }
    
    .featured-album-content h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        color: #2C1810;
        margin-bottom: 1rem;
    }
    
    .featured-album-content p {
        color: #5C4A3D;
        margin-bottom: 1.5rem;
        line-height: 1.7;
    }
    
    .btn-album {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        width: fit-content;
    }
    
    .btn-album:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        transform: translateY(-2px);
    }
    
    @media (max-width: 992px) {
        .featured-album {
            grid-template-columns: 1fr;
        }
        
        .featured-album-gallery {
            height: 300px;
        }
    }
    
    @media (max-width: 576px) {
        .albums-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="albums-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{!! $heroData['title'] ?? '📸 Albums Photos' !!}</h1>
        <p>{{ $heroData['description'] ?? 'Revivez les moments forts de RACINE BY GANDA à travers nos albums photos' }}</p>
    </div>
</section>

<!-- FEATURED ALBUM -->
<section style="padding: 4rem 0; background: #F8F6F3;">
    <div class="container">
        <div class="featured-album">
            <div class="featured-album-gallery">
                <div class="main">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800" alt="Album à la une">
                </div>
                <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400" alt="">
                <img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400" alt="">
            </div>
            <div class="featured-album-content">
                <span class="featured-badge"><i class="fas fa-star"></i> Album à la une</span>
                <h2>Défilé Collection Printemps 2024</h2>
                <p>
                    Retour en images sur notre défilé exceptionnel présentant la collection Printemps 2024. 
                    Une soirée magique au cœur de Pointe-Noire, célébrant l'élégance africaine contemporaine.
                </p>
                <div class="album-meta" style="border: none; padding: 0; margin-bottom: 1.5rem;">
                    <span class="date"><i class="fas fa-calendar"></i> Mars 2024</span>
                    <span class="count"><i class="fas fa-images"></i> 48 photos</span>
                </div>
                <a href="#" class="btn-album">
                    <i class="fas fa-eye"></i> Voir l'album complet
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ALL ALBUMS -->
<section class="albums-section">
    <div class="container">
        <div class="albums-grid">
            <!-- Album 1 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600" alt="Backstage">
                    <span class="album-category">Backstage</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1558171813-4c088753af8f?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1544441893-675973e31985?w=100" alt=""></div>
                        <div class="album-preview-more">+22</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>En coulisses - Hiver 2024</h3>
                    <p>Les moments exclusifs des préparatifs avant le défilé.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Déc 2024</span>
                        <span class="count"><i class="fas fa-images"></i> 24 photos</span>
                    </div>
                </div>
            </div>
            
            <!-- Album 2 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600" alt="Exposition">
                    <span class="album-category">Événement</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=100" alt=""></div>
                        <div class="album-preview-more">+35</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>Exposition "Racines & Modernité"</h3>
                    <p>Notre exposition à la Galerie d'Art de Brazzaville.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Nov 2024</span>
                        <span class="count"><i class="fas fa-images"></i> 37 photos</span>
                    </div>
                </div>
            </div>
            
            <!-- Album 3 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1558618047-f8a8b7f79c5e?w=600" alt="Collection">
                    <span class="album-category">Collection</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=100" alt=""></div>
                        <div class="album-preview-more">+18</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>Lookbook Été 2024</h3>
                    <p>Les plus belles pièces de notre collection estivale.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Juin 2024</span>
                        <span class="count"><i class="fas fa-images"></span>
                    </div>
                </div>
            </div>
            
            <!-- Album 4 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=600" alt="Atelier">
                    <span class="album-category">Atelier</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=100" alt=""></div>
                        <div class="album-preview-more">+12</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>Atelier de Création</h3>
                    <p>Découvrez notre atelier et nos artisans talentueux.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Oct 2024</span>
                        <span class="count"><i class="fas fa-images"></i> 15 photos</span>
                    </div>
                </div>
            </div>
            
            <!-- Album 5 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600" alt="Shooting">
                    <span class="album-category">Shooting</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1544441893-675973e31985?w=100" alt=""></div>
                        <div class="album-preview-more">+28</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>Shooting Campagne 2024</h3>
                    <p>Les coulisses de notre campagne publicitaire.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Sept 2024</span>
                        <span class="count"><i class="fas fa-images"></i> 32 photos</span>
                    </div>
                </div>
            </div>
            
            <!-- Album 6 -->
            <div class="album-card">
                <div class="album-cover">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600" alt="Défilé">
                    <span class="album-category">Défilé</span>
                    <div class="album-preview">
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?w=100" alt=""></div>
                        <div class="album-preview-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=100" alt=""></div>
                        <div class="album-preview-more">+45</div>
                    </div>
                </div>
                <div class="album-content">
                    <h3>Fashion Week Pointe-Noire</h3>
                    <p>Notre participation à la Fashion Week locale.</p>
                    <div class="album-meta">
                        <span class="date"><i class="fas fa-calendar"></i> Avril 2024</span>
                        <span class="count"><i class="fas fa-images"></i> 48 photos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

