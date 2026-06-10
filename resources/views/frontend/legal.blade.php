@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Mentions Légales - RACINE BY GANDA')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .legal-hero {
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
        text-align: center;
    }

    .legal-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        color: white;
        margin-bottom: 0.5rem;
    }

    .legal-hero p {
        color: rgba(255, 255, 255, 0.6);
    }

    .legal-content {
        padding: 4rem 0;
        background: rgba(22,13,12,0.05);
    }

    .legal-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 3rem;
        max-width: 1100px;
        margin: 0 auto;
    }

    .legal-nav {
        position: sticky;
        top: 100px;
        height: fit-content;
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
    }

    .legal-nav h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #160D0C;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(22,13,12,0.1);
    }

    .legal-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .legal-nav li {
        margin-bottom: 0.5rem;
    }

    .legal-nav a {
        display: block;
        padding: 0.5rem 0.75rem;
        color: rgba(22,13,12,0.5);
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .legal-nav a:hover, .legal-nav a.active {
        background: rgba(237, 95, 30, 0.08);
        color: #160D0C;
    }

    .legal-body {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
    }

    .legal-section {
        margin-bottom: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid rgba(22,13,12,0.1);
    }

    .legal-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .legal-section h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        font-weight: 600;
        color: #160D0C;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .legal-section h2 .number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #FFB800 0%, #ED5F1E 100%);
        color: white;
        border-radius: 10px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.1rem;
    }

    .legal-section p {
        color: rgba(22,13,12,0.6);
        line-height: 1.8;
        margin-bottom: 1rem;
    }

    .legal-section ul {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }

    .legal-section li {
        color: rgba(22,13,12,0.6);
        line-height: 1.8;
        margin-bottom: 0.5rem;
    }

    .highlight-box {
        background: rgba(237, 95, 30, 0.08);
        border-start: 4px solid #FFB800;
        padding: 1.25rem;
        border-radius: 0 12px 12px 0;
        margin: 1.5rem 0;
    }

    .highlight-box p {
        margin: 0;
        font-weight: 500;
    }

    .update-date {
        text-align: center;
        color: rgba(22,13,12,0.5);
        font-size: 0.9rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(22,13,12,0.1);
    }

    @media (max-width: 1024px) {
        .legal-container { grid-template-columns: 1fr; }
        .legal-nav { position: static; }
    }

    @media (max-width: 768px) {
        .legal-hero h1 { font-size: 2rem; }
        .legal-body { padding: 1.5rem; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="legal-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Mentions Légales' }}</h1>
        <p>{{ $heroData['description'] ?? 'Informations légales obligatoires' }}</p>
    </div>
</section>

<!-- CONTENT -->
<section class="legal-content">
    <div class="container">
        <div class="legal-container">
            <!-- NAV -->
            <nav class="legal-nav">
                <h3>Sommaire</h3>
                <ul>
                    <li><a href="#article1">1. Éditeur du site</a></li>
                    <li><a href="#article2">2. Hébergement</a></li>
                    <li><a href="#article3">3. Propriété intellectuelle</a></li>
                    <li><a href="#article4">4. Données personnelles</a></li>
                    <li><a href="#article5">5. Cookies</a></li>
                    <li><a href="#article6">6. Responsabilité</a></li>
                    <li><a href="#article7">7. Droit applicable</a></li>
                </ul>
            </nav>

            <!-- BODY -->
            <div class="legal-body">
                <div class="legal-section" id="article1">
                    <h2><span class="number">1</span> Éditeur du site</h2>
                    <p>Le site www.racinebyganda.com est édité par RACINE BY GANDA.</p>
                    <ul>
                        <li>Raison sociale : {{ config('company.name') }}</li>
                        @if(config('company.address'))
                        <li>Siège social : {{ config('company.address') }}</li>
                        @endif
                        <li>Email : {{ config('company.email') }}</li>
                        <li>Directeur de la publication : {{ config('company.ceo') }}</li>
                    </ul>
                </div>

                <div class="legal-section" id="article2">
                    <h2><span class="number">2</span> Hébergement</h2>
                    <p>Le site est hébergé par :</p>
                    <ul>
                        <li>Hostinger International Ltd</li>
                        <li>61 Lordou Vironos Street, 6023 Larnaca, Chypre</li>
                        <li>Site web : www.hostinger.com</li>
                    </ul>
                </div>

                <div class="legal-section" id="article3">
                    <h2><span class="number">3</span> Propriété intellectuelle</h2>
                    <p>L'ensemble des contenus présents sur le site (textes, images, photographies, logos, marques, vidéos, sons, plans, noms de domaine, logiciels, etc.) sont protégés par le droit d'auteur, le droit des marques et/ou tout autre droit de propriété intellectuelle.</p>
                    <div class="highlight-box">
                        <p>Toute reproduction, représentation, modification ou exploitation non autorisée est interdite et constitue une contrefaçon sanctionnée par le Code de la propriété intellectuelle.</p>
                    </div>
                </div>

                <div class="legal-section" id="article4">
                    <h2><span class="number">4</span> Données personnelles</h2>
                    <p>Les données personnelles collectées sur ce site sont traitées conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi relative à l'informatique, aux fichiers et aux libertés.</p>
                    <p>Pour en savoir plus sur la gestion de vos données, consultez notre <a href="{{ route('frontend.privacy') }}">Politique de Confidentialité</a>.</p>
                    <p>Vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Pour exercer ces droits, contactez-nous à : {{ config('company.email') }}.</p>
                </div>

                <div class="legal-section" id="article5">
                    <h2><span class="number">5</span> Cookies</h2>
                    <p>Le site utilise des cookies pour améliorer l'expérience utilisateur, réaliser des statistiques de visites et assurer le bon fonctionnement des services proposés.</p>
                    <p>Vous pouvez paramétrer l'utilisation des cookies via les paramètres de votre navigateur.</p>
                </div>

                <div class="legal-section" id="article6">
                    <h2><span class="number">6</span> Responsabilité</h2>
                    <p>RACINE BY GANDA s'efforce d'assurer l'exactitude des informations diffusées sur le site, mais ne saurait garantir l'exhaustivité ou l'absence d'erreurs. L'utilisation des informations et contenus disponibles sur le site se fait sous la seule responsabilité de l'utilisateur.</p>
                    <p>RACINE BY GANDA ne pourra être tenue responsable des dommages directs ou indirects résultant de l'accès ou de l'utilisation du site, y compris l'inaccessibilité, les pertes de données ou les virus.</p>
                </div>

                <div class="legal-section" id="article7">
                    <h2><span class="number">7</span> Droit applicable</h2>
                    <p>Les présentes mentions légales sont régies par le droit camerounais. Tout litige relatif à l'utilisation du site sera soumis à la compétence des tribunaux de Douala.</p>
                </div>

                <p class="update-date">Dernière mise à jour : Juin 2026</p>
            </div>
        </div>
    </div>
</section>
@endsection
