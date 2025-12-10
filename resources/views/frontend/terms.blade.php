@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Conditions Générales de Vente - RACINE BY GANDA')

@push('styles')
<style>
    .legal-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
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
        background: #F8F6F3;
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
        color: #2C1810;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #E5DDD3;
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
        color: #8B7355;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .legal-nav a:hover, .legal-nav a.active {
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
    }
    
    .legal-body {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
    }
    
    .legal-section {
        margin-bottom: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid #E5DDD3;
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
        color: #2C1810;
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
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 10px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.1rem;
    }
    
    .legal-section h3 {
        font-size: 1.15rem;
        font-weight: 600;
        color: #2C1810;
        margin: 1.5rem 0 0.75rem;
    }
    
    .legal-section p {
        color: #5C4A3D;
        line-height: 1.8;
        margin-bottom: 1rem;
    }
    
    .legal-section ul {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }
    
    .legal-section li {
        color: #5C4A3D;
        line-height: 1.8;
        margin-bottom: 0.5rem;
    }
    
    .highlight-box {
        background: rgba(212, 165, 116, 0.1);
        border-left: 4px solid #D4A574;
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
        color: #8B7355;
        font-size: 0.9rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #E5DDD3;
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
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Conditions Générales de Vente' }}</h1>
        <p>{{ $heroData['description'] ?? 'Dernière mise à jour : Janvier 2025' }}</p>
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
                    <li><a href="#article1">1. Objet</a></li>
                    <li><a href="#article2">2. Produits</a></li>
                    <li><a href="#article3">3. Prix</a></li>
                    <li><a href="#article4">4. Commande</a></li>
                    <li><a href="#article5">5. Paiement</a></li>
                    <li><a href="#article6">6. Livraison</a></li>
                    <li><a href="#article7">7. Retours</a></li>
                    <li><a href="#article8">8. Garanties</a></li>
                    <li><a href="#article9">9. Responsabilité</a></li>
                    <li><a href="#article10">10. Données personnelles</a></li>
                </ul>
            </nav>
            
            <!-- BODY -->
            <div class="legal-body">
                <div class="legal-section" id="article1">
                    <h2><span class="number">1</span> Objet</h2>
                    <p>Les présentes Conditions Générales de Vente (CGV) régissent les relations contractuelles entre RACINE BY GANDA, société par actions simplifiée au capital de 10 000 €, dont le siège social est situé au 15 Rue de la Mode, 75003 Paris, immatriculée au RCS de Paris sous le numéro XXX XXX XXX, et toute personne physique ou morale (le "Client") effectuant un achat sur le site www.racine-ganda.com.</p>
                    <div class="highlight-box">
                        <p>En passant commande, le Client accepte sans réserve les présentes CGV.</p>
                    </div>
                </div>
                
                <div class="legal-section" id="article2">
                    <h2><span class="number">2</span> Produits</h2>
                    <p>Les produits proposés à la vente sont ceux présentés sur le site au jour de la consultation. Les photographies et descriptions sont aussi fidèles que possible mais ne sauraient engager la responsabilité du Vendeur.</p>
                    <h3>2.1 Disponibilité</h3>
                    <p>Les offres de produits sont valables dans la limite des stocks disponibles. En cas d'indisponibilité d'un produit commandé, le Client en sera informé dans les plus brefs délais et pourra choisir entre un remboursement ou un échange.</p>
                    <h3>2.2 Caractéristiques</h3>
                    <p>Les produits RACINE BY GANDA sont des créations artisanales. De légères variations de couleur ou de motif peuvent exister entre les différentes pièces, faisant de chaque article une pièce unique.</p>
                </div>
                
                <div class="legal-section" id="article3">
                    <h2><span class="number">3</span> Prix</h2>
                    <p>Les prix sont indiqués en euros, toutes taxes comprises (TTC). Ils ne comprennent pas les frais de livraison, qui sont facturés en supplément et indiqués avant la validation de la commande.</p>
                    <p>RACINE BY GANDA se réserve le droit de modifier ses prix à tout moment. Les produits sont facturés sur la base des tarifs en vigueur au moment de la validation de la commande.</p>
                </div>
                
                <div class="legal-section" id="article4">
                    <h2><span class="number">4</span> Commande</h2>
                    <p>Pour passer commande, le Client doit :</p>
                    <ul>
                        <li>Sélectionner les produits souhaités et les ajouter au panier</li>
                        <li>Valider le contenu de son panier</li>
                        <li>Renseigner ses coordonnées de livraison et de facturation</li>
                        <li>Choisir le mode de livraison</li>
                        <li>Choisir le mode de paiement et régler la commande</li>
                    </ul>
                    <p>La commande n'est définitive qu'après confirmation du paiement. Un email de confirmation récapitulatif est envoyé au Client.</p>
                </div>
                
                <div class="legal-section" id="article5">
                    <h2><span class="number">5</span> Paiement</h2>
                    <p>Le règlement des achats s'effectue par :</p>
                    <ul>
                        <li>Carte bancaire (Visa, Mastercard, American Express)</li>
                        <li>PayPal</li>
                        <li>Paiement en 3 ou 4 fois sans frais (via Alma, pour les commandes de 100€ à 2000€)</li>
                    </ul>
                    <p>Les transactions sont sécurisées par le protocole SSL. Les données bancaires ne sont jamais stockées sur nos serveurs.</p>
                </div>
                
                <div class="legal-section" id="article6">
                    <h2><span class="number">6</span> Livraison</h2>
                    <h3>6.1 Zones et délais</h3>
                    <ul>
                        <li>France métropolitaine : 3-5 jours ouvrés</li>
                        <li>DOM-TOM : 7-14 jours ouvrés</li>
                        <li>Union Européenne : 5-10 jours ouvrés</li>
                        <li>International : 10-21 jours ouvrés</li>
                    </ul>
                    <h3>6.2 Frais de port</h3>
                    <ul>
                        <li>France métropolitaine : Gratuit dès 100€, sinon 5,90€</li>
                        <li>DOM-TOM : À partir de 12,90€</li>
                        <li>Union Européenne : À partir de 9,90€</li>
                        <li>International : Sur devis</li>
                    </ul>
                </div>
                
                <div class="legal-section" id="article7">
                    <h2><span class="number">7</span> Droit de Rétractation et Retours</h2>
                    <p>Conformément à l'article L.221-18 du Code de la consommation, le Client dispose d'un délai de 14 jours à compter de la réception de sa commande pour exercer son droit de rétractation, sans avoir à justifier de motifs.</p>
                    <div class="highlight-box">
                        <p>RACINE BY GANDA étend ce délai à 30 jours pour offrir plus de flexibilité à ses clients.</p>
                    </div>
                    <h3>7.1 Conditions de retour</h3>
                    <ul>
                        <li>L'article doit être retourné dans son état d'origine, non porté, non lavé</li>
                        <li>L'emballage d'origine doit être intact</li>
                        <li>Les étiquettes doivent être attachées</li>
                    </ul>
                    <h3>7.2 Procédure</h3>
                    <p>Le Client doit initier sa demande de retour depuis son espace client ou en contactant le service client. Une étiquette de retour prépayée sera fournie pour les retours en France métropolitaine.</p>
                </div>
                
                <div class="legal-section" id="article8">
                    <h2><span class="number">8</span> Garanties</h2>
                    <p>Tous les produits bénéficient de la garantie légale de conformité (articles L.217-4 et suivants du Code de la consommation) et de la garantie contre les vices cachés (articles 1641 et suivants du Code civil).</p>
                </div>
                
                <div class="legal-section" id="article9">
                    <h2><span class="number">9</span> Responsabilité</h2>
                    <p>RACINE BY GANDA ne saurait être tenue responsable des dommages résultant d'une mauvaise utilisation des produits achetés, ni des retards ou défaillances dus à des cas de force majeure.</p>
                </div>
                
                <div class="legal-section" id="article10">
                    <h2><span class="number">10</span> Données Personnelles</h2>
                    <p>Les données personnelles collectées sont traitées conformément à notre Politique de Confidentialité et au RGPD. Pour plus d'informations, consultez notre <a href="{{ route('frontend.privacy') }}">Politique de Confidentialité</a>.</p>
                </div>
                
                <p class="update-date">Ces CGV sont soumises au droit français. Tout litige sera de la compétence exclusive des tribunaux de Paris.</p>
            </div>
        </div>
    </div>
</section>
@endsection
