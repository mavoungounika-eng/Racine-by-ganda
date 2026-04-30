@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Portfolio - RACINE BY GANDA')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .portfolio-hero {
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .portfolio-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .portfolio-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .portfolio-filters {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 2rem 0;
        background: white;
        border-bottom: 1px solid rgba(22,13,12,0.1);
        position: sticky;
        top: 70px;
        z-index: 100;
    }
    
    .filter-btn {
        padding: 0.65rem 1.5rem;
        border: 2px solid rgba(22,13,12,0.1);
        border-radius: 30px;
        background: white;
        color: rgba(22,13,12,0.6);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        background: linear-gradient(135deg, #ED5F1E 0%, #ED5F1E 100%);
        border-color: #ED5F1E;
        color: white;
    }
    
    .portfolio-section {
        padding: 4rem 0;
        background: rgba(22,13,12,0.05);
    }
    
    .portfolio-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    .portfolio-item {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        aspect-ratio: 1;
    }
    
    .portfolio-item.wide {
        grid-column: span 2;
        aspect-ratio: 2/1;
    }
    
    .portfolio-item.tall {
        grid-row: span 2;
        aspect-ratio: 1/2;
    }
    
    .portfolio-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .portfolio-item:hover img {
        transform: scale(1.1);
    }
    
    .portfolio-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(28, 24, 16, 0.95) 0%, rgba(28, 24, 16, 0) 60%);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 1.5rem;
        opacity: 0;
        transition: opacity 0.4s;
    }
    
    .portfolio-item:hover .portfolio-overlay {
        opacity: 1;
    }
    
    .portfolio-overlay h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 0.35rem;
        transform: translateY(20px);
        transition: transform 0.4s;
    }
    
    .portfolio-item:hover .portfolio-overlay h3 {
        transform: translateY(0);
    }
    
    .portfolio-overlay span {
        color: #FFB800;
        font-size: 0.9rem;
        transform: translateY(20px);
        transition: transform 0.4s 0.1s;
    }
    
    .portfolio-item:hover .portfolio-overlay span {
        transform: translateY(0);
    }
    
    .portfolio-overlay .view-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        transition: all 0.4s;
    }
    
    .portfolio-item:hover .portfolio-overlay .view-btn {
        transform: translate(-50%, -50%) scale(1);
    }
    
    /* Stats */
    .portfolio-stats {
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
        padding: 4rem 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
        text-align: center;
    }
    
    .stat-item {
        color: white;
    }
    
    .stat-item .number {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        font-weight: 700;
        color: #FFB800;
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    
    .stat-item .label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.95rem;
    }
    
    /* Lightbox */
    .lightbox {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    
    .lightbox.active {
        display: flex;
    }
    
    .lightbox-content {
        max-width: 90%;
        max-height: 90%;
    }
    
    .lightbox-content img {
        max-width: 100%;
        max-height: 85vh;
        border-radius: 12px;
    }
    
    .lightbox-close {
        position: absolute;
        top: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .lightbox-close:hover {
        background: #ED5F1E;
    }
    
    .lightbox-info {
        text-align: center;
        margin-top: 1.5rem;
        color: white;
    }
    
    .lightbox-info h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    
    .lightbox-info span {
        color: #FFB800;
    }
    
    @media (max-width: 992px) {
        .portfolio-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .portfolio-item.wide,
        .portfolio-item.tall {
            grid-column: auto;
            grid-row: auto;
            aspect-ratio: 1;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .portfolio-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="portfolio-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{!! $heroData['title'] ?? '🎨 Portfolio' !!}</h1>
        <p>{{ $heroData['description'] ?? 'Découvrez nos créations, collections et collaborations. L\'excellence de la mode africaine.' }}</p>
    </div>
</section>

<!-- FILTERS -->
<div class="portfolio-filters">
    <button class="filter-btn active" data-filter="all">Tout</button>
    <button class="filter-btn" data-filter="collection">Collections</button>
    <button class="filter-btn" data-filter="fashion">Mode</button>
    <button class="filter-btn" data-filter="accessory">Accessoires</button>
    <button class="filter-btn" data-filter="event">Événements</button>
    <button class="filter-btn" data-filter="backstage">Backstage</button>
</div>

<!-- PORTFOLIO GRID -->
<section class="portfolio-section">
    <div class="container">
        <div class="portfolio-grid">
            <div class="portfolio-item wide" data-category="collection" data-title="Collection Été 2024" data-img="{{ asset('storage/showroom/gallery/gallery-01.jpeg') }}">
                <img src="{{ asset('storage/showroom/gallery/gallery-01.jpeg') }}" alt="Collection Été 2024">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Collection Été 2024</h3>
                    <span>Collection</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="fashion" data-title="Robe Ankara" data-img="{{ asset('storage/catalogue/vetements/soiree-01.jpeg') }}">
                <img src="{{ asset('storage/catalogue/vetements/soiree-01.jpeg') }}" alt="Robe Ankara">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Robe Ankara</h3>
                    <span>Mode Femme</span>
                </div>
            </div>

            <div class="portfolio-item tall" data-category="fashion" data-title="Ensemble Kente" data-img="{{ asset('storage/catalogue/vetements/kimono-01.jpeg') }}">
                <img src="{{ asset('storage/catalogue/vetements/kimono-01.jpeg') }}" alt="Ensemble Kente">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Ensemble Kente</h3>
                    <span>Mode</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="accessory" data-title="Bijoux Ethniques" data-img="{{ asset('storage/catalogue/accessoires/kit-voyage-bleu-01.jpeg') }}">
                <img src="{{ asset('storage/catalogue/accessoires/kit-voyage-bleu-01.jpeg') }}" alt="Kit Voyage Bleu">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Bijoux Ethniques</h3>
                    <span>Accessoires</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="event" data-title="Défilé Brazzaville" data-img="{{ asset('storage/showroom/gallery/showroom-06.jpeg') }}">
                <img src="{{ asset('storage/showroom/gallery/showroom-06.jpeg') }}" alt="Défilé Brazzaville">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Défilé Brazzaville</h3>
                    <span>Événement</span>
                </div>
            </div>

            <div class="portfolio-item wide" data-category="backstage" data-title="En coulisses" data-img="{{ asset('storage/showroom/gallery/gallery-02.jpeg') }}">
                <img src="{{ asset('storage/showroom/gallery/gallery-02.jpeg') }}" alt="En coulisses">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>En coulisses</h3>
                    <span>Backstage</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="collection" data-title="Hiver 2024" data-img="{{ asset('storage/catalogue/vetements/soiree-05.jpeg') }}">
                <img src="{{ asset('storage/catalogue/vetements/soiree-05.jpeg') }}" alt="Collection Hiver 2024">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Collection Hiver 2024</h3>
                    <span>Collection</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="fashion" data-title="Costume Homme" data-img="{{ asset('storage/catalogue/vetements/bazin-01.jpeg') }}">
                <img src="{{ asset('storage/catalogue/vetements/bazin-01.jpeg') }}" alt="Costume Homme Bazin">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Costume Homme</h3>
                    <span>Mode Homme</span>
                </div>
            </div>

            <div class="portfolio-item" data-category="accessory" data-title="Sacs Artisanaux" data-img="{{ asset('storage/catalogue/accessoires/kit-voyage-noir-01.jpeg') }}">
                <img src="{{ asset('storage/catalogue/accessoires/kit-voyage-noir-01.jpeg') }}" alt="Kit Voyage Noir">
                <div class="portfolio-overlay">
                    <div class="view-btn"><i class="fas fa-expand"></i></div>
                    <h3>Sacs Artisanaux</h3>
                    <span>Accessoires</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="portfolio-stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="number">12+</div>
                <div class="label">Collections créées</div>
            </div>
            <div class="stat-item">
                <div class="number">500+</div>
                <div class="label">Pièces uniques</div>
            </div>
            <div class="stat-item">
                <div class="number">25+</div>
                <div class="label">Événements</div>
            </div>
            <div class="stat-item">
                <div class="number">15+</div>
                <div class="label">Pays touchés</div>
            </div>
        </div>
    </div>
</section>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </button>
    <div class="lightbox-content">
        <img id="lightbox-img" src="" alt="">
        <div class="lightbox-info">
            <h3 id="lightbox-title"></h3>
            <span id="lightbox-category"></span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
// Filters
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        document.querySelectorAll('.portfolio-item').forEach(item => {
            if (filter === 'all' || item.dataset.category === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Lightbox
document.querySelectorAll('.portfolio-item').forEach(item => {
    item.addEventListener('click', () => {
        document.getElementById('lightbox-img').src = item.dataset.img;
        document.getElementById('lightbox-title').textContent = item.dataset.title;
        document.getElementById('lightbox-category').textContent = item.querySelector('.portfolio-overlay span').textContent;
        document.getElementById('lightbox').classList.add('active');
        document.body.style.overflow = 'hidden';
    });
});

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('lightbox').addEventListener('click', (e) => {
    if (e.target.id === 'lightbox') closeLightbox();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endpush

