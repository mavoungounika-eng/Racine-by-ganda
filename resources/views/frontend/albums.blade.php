@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Albums Photos - RACINE BY GANDA')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .albums-hero {
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
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
        background: rgba(22,13,12,0.05);
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
        background: linear-gradient(135deg, #ED5F1E 0%, #ED5F1E 100%);
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
        color: #160D0C;
        margin-bottom: 0.5rem;
    }

    .album-content p {
        color: rgba(22,13,12,0.5);
        font-size: 0.9rem;
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    .album-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid rgba(22,13,12,0.1);
    }

    .album-meta .date {
        color: rgba(22,13,12,0.5);
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
        background: linear-gradient(135deg, #FFB800 0%, #ED5F1E 100%);
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
        color: #160D0C;
        margin-bottom: 1rem;
    }

    .featured-album-content p {
        color: rgba(22,13,12,0.6);
        margin-bottom: 1.5rem;
        line-height: 1.7;
    }

    .btn-album {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
        color: white;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        width: fit-content;
    }

    .btn-album:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #ED5F1E 100%);
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

@php
    /* ── HERO ─────────────────────────────────────────────── */
    $heroSection = $cmsPage?->section('hero');
    $heroData    = $heroSection?->data ?? [];

    /* ── FEATURED ALBUM ───────────────────────────────────── */
    $featuredSection = $cmsPage?->section('featured_album');
    $featured        = $featuredSection?->data ?? [];

    $featuredMainImage     = $featured['main_image']       ?? 'showroom/gallery/gallery-05.jpeg';
    $featuredGalleryImages = $featured['gallery_images']   ?? [
        'catalogue/vetements/soiree-02.jpeg',
        'catalogue/vetements/kimono-02.jpeg',
    ];

    /* ── ALBUMS GRID ──────────────────────────────────────── */
    $albumsSection = $cmsPage?->section('albums');
    $albumItems    = $albumsSection?->data['items'] ?? [
        [
            'title'          => 'En coulisses - Hiver 2024',
            'description'    => 'Les moments exclusifs des préparatifs avant le défilé.',
            'category'       => 'Backstage',
            'date'           => 'Déc 2024',
            'photos_count'   => 24,
            'cover_image'    => 'showroom/gallery/showroom-09.jpeg',
            'preview_images' => [
                'showroom/gallery/gallery-06.jpeg',
                'showroom/gallery/gallery-07.jpeg',
            ],
            'more_count'     => 22,
            'album_url'      => '#',
        ],
        [
            'title'          => 'Exposition "Racines & Modernité"',
            'description'    => 'Notre exposition à la Galerie d\'Art de Brazzaville.',
            'category'       => 'Événement',
            'date'           => 'Nov 2024',
            'photos_count'   => 37,
            'cover_image'    => 'showroom/gallery/showroom-10.jpeg',
            'preview_images' => [
                'showroom/gallery/gallery-08.jpeg',
                'showroom/gallery/gallery-09.jpeg',
            ],
            'more_count'     => 35,
            'album_url'      => '#',
        ],
        [
            'title'          => 'Lookbook Été 2024',
            'description'    => 'Les plus belles pièces de notre collection estivale.',
            'category'       => 'Collection',
            'date'           => 'Juin 2024',
            'photos_count'   => 18,
            'cover_image'    => 'showroom/collections/collection-04.jpeg',
            'preview_images' => [
                'catalogue/accessoires/kit-voyage-bleu-02.jpeg',
                'catalogue/accessoires/kit-voyage-noir-02.jpeg',
            ],
            'more_count'     => 16,
            'album_url'      => '#',
        ],
        [
            'title'          => 'Atelier de Création',
            'description'    => 'Découvrez notre atelier et nos artisans talentueux.',
            'category'       => 'Atelier',
            'date'           => 'Oct 2024',
            'photos_count'   => 15,
            'cover_image'    => 'showroom/gallery/showroom-11.jpeg',
            'preview_images' => [
                'catalogue/vetements/bazin-02.jpeg',
                'catalogue/vetements/kimono-03.jpeg',
            ],
            'more_count'     => 12,
            'album_url'      => '#',
        ],
        [
            'title'          => 'Shooting Campagne 2024',
            'description'    => 'Les coulisses de notre campagne publicitaire.',
            'category'       => 'Shooting',
            'date'           => 'Sept 2024',
            'photos_count'   => 32,
            'cover_image'    => 'showroom/gallery/showroom-12.jpeg',
            'preview_images' => [
                'catalogue/vetements/soiree-03.jpeg',
                'catalogue/vetements/soiree-04.jpeg',
            ],
            'more_count'     => 28,
            'album_url'      => '#',
        ],
        [
            'title'          => 'Fashion Week Pointe-Noire',
            'description'    => 'Notre participation à la Fashion Week locale.',
            'category'       => 'Défilé',
            'date'           => 'Avril 2024',
            'photos_count'   => 48,
            'cover_image'    => 'showroom/gallery/showroom-13.jpeg',
            'preview_images' => [
                'showroom/gallery/showroom-14.jpeg',
                'showroom/gallery/showroom-15.jpeg',
            ],
            'more_count'     => 45,
            'album_url'      => '#',
        ],
    ];
@endphp

<!-- HERO -->
<section class="albums-hero">
    <div class="container">
        <h1>{!! $heroData['title'] ?? '📸 Albums Photos' !!}</h1>
        <p>{{ $heroData['description'] ?? 'Revivez les moments forts de RACINE BY GANDA à travers nos albums photos' }}</p>
    </div>
</section>

<!-- FEATURED ALBUM -->
<section class="albums-section">
    <div class="container">
        <div class="featured-album">
            <div class="featured-album-gallery">
                <div class="main">
                    <img src="{{ asset('storage/' . $featuredMainImage) }}"
                         alt="{{ $featured['title'] ?? 'Album à la une' }}">
                </div>
                @foreach(array_slice($featuredGalleryImages, 0, 2) as $galleryImg)
                <img src="{{ asset('storage/' . $galleryImg) }}" alt="">
                @endforeach
            </div>
            <div class="featured-album-content">
                <span class="featured-badge"><i class="fas fa-star"></i> Album à la une</span>
                <h2>{{ $featured['title'] ?? 'Défilé Collection Printemps 2024' }}</h2>
                <p>{{ $featured['description'] ?? 'Retour en images sur notre défilé exceptionnel présentant la collection Printemps 2024. Une soirée magique au cœur de Pointe-Noire, célébrant l\'élégance africaine contemporaine.' }}</p>
                <div class="album-meta album-meta--flush">
                    <span class="date">
                        <i class="fas fa-calendar"></i>
                        {{ $featured['date'] ?? 'Mars 2024' }}
                    </span>
                    @if(!empty($featured['photos_count']))
                    <span class="count">
                        <i class="fas fa-images"></i>
                        {{ $featured['photos_count'] }} photos
                    </span>
                    @endif
                </div>
                <a href="{{ $featured['button_url'] ?? route('frontend.contact') }}" class="btn-album">
                    <i class="fas fa-eye"></i>
                    {{ $featured['button_label'] ?? 'Voir l\'album complet' }}
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ALL ALBUMS -->
<section class="albums-section">
    <div class="container">
        <div class="albums-grid">
            @foreach($albumItems as $album)
            <div class="album-card" @if(!empty($album['album_url']) && $album['album_url'] !== '#') onclick="window.location='{{ $album['album_url'] }}'" @endif>
                <div class="album-cover">
                    <img src="{{ asset('storage/' . $album['cover_image']) }}"
                         alt="{{ $album['title'] }}"
                         loading="lazy">
                    <span class="album-category">{{ $album['category'] }}</span>
                    <div class="album-preview">
                        @foreach(array_slice($album['preview_images'] ?? [], 0, 2) as $thumb)
                        <div class="album-preview-thumb">
                            <img src="{{ asset('storage/' . $thumb) }}" alt="" loading="lazy">
                        </div>
                        @endforeach
                        @if(!empty($album['more_count']))
                        <div class="album-preview-more">+{{ $album['more_count'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="album-content">
                    <h3>{{ $album['title'] }}</h3>
                    <p>{{ $album['description'] }}</p>
                    <div class="album-meta">
                        <span class="date">
                            <i class="fas fa-calendar"></i>
                            {{ $album['date'] }}
                        </span>
                        @if(!empty($album['photos_count']))
                        <span class="count">
                            <i class="fas fa-images"></i>
                            {{ $album['photos_count'] }} photos
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
