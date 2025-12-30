@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Livraison - RACINE BY GANDA')

@push('styles')
<style>
    .shipping-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .shipping-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .shipping-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .shipping-content {
        padding: 4rem 0;
        background: #F8F6F3;
    }
    
    /* OPTIONS */
    .options-section {
        margin-bottom: 4rem;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-tag {
        display: inline-block;
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    .options-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .option-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .option-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
    }
    
    .option-card.popular {
        border: 2px solid #D4A574;
    }
    
    .popular-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .option-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2rem;
        color: #D4A574;
    }
    
    .option-card h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .option-price {
        font-size: 2rem;
        font-weight: 700;
        color: #8B5A2B;
        margin-bottom: 0.5rem;
    }
    
    .option-price span {
        font-size: 0.9rem;
        font-weight: 400;
        color: #8B7355;
    }
    
    .option-delay {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #F8F6F3;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #5C4A3D;
        margin-bottom: 1rem;
    }
    
    .option-features {
        text-align: left;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .option-features li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #5C4A3D;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }
    
    .option-features i {
        color: #22C55E;
    }
    
    /* ZONES */
    .zones-section {
        background: white;
        border-radius: 24px;
        padding: 3rem;
        margin-bottom: 4rem;
    }
    
    .zones-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .zones-table th {
        background: #F8F6F3;
        padding: 1.25rem;
        text-align: left;
        font-weight: 600;
        color: #2C1810;
        border-bottom: 2px solid #E5DDD3;
    }
    
    .zones-table td {
        padding: 1.25rem;
        border-bottom: 1px solid #E5DDD3;
        color: #5C4A3D;
    }
    
    .zones-table tr:last-child td {
        border-bottom: none;
    }
    
    .free-badge {
        background: rgba(34, 197, 94, 0.1);
        color: #22C55E;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    /* FAQ */
    .faq-section {
        background: white;
        border-radius: 24px;
        padding: 3rem;
    }
    
    .faq-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .faq-item {
        background: #F8F6F3;
        border-radius: 16px;
        padding: 1.5rem;
    }
    
    .faq-item h4 {
        font-size: 1.05rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .faq-item h4 i {
        color: #D4A574;
    }
    
    .faq-item p {
        color: #5C4A3D;
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    /* CTA */
    .cta-section {
        margin-top: 4rem;
        text-align: center;
    }
    
    .cta-box {
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 24px;
        padding: 3rem;
        color: white;
    }
    
    .cta-box h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }
    
    .cta-box p {
        opacity: 0.9;
        margin-bottom: 1.5rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: white;
        color: #2C1810;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        color: #2C1810;
    }
    
    @media (max-width: 1024px) {
        .options-grid { grid-template-columns: 1fr; }
        .faq-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .shipping-hero h1 { font-size: 2.5rem; }
        .zones-section, .faq-section { padding: 1.5rem; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="shipping-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Livraison' }}</h1>
        <p>{{ $heroData['description'] ?? 'Découvrez nos options de livraison rapides et sécurisées pour recevoir vos créations africaines.' }}</p>
    </div>
</section>

<!-- CONTENT -->
<section class="shipping-content">
    <div class="container">
        <!-- OPTIONS -->
        <div class="options-section">
            <div class="section-header">
                <span class="section-tag">Options</span>
                <h2 class="section-title">Choisissez votre livraison</h2>
            </div>
            
            <div class="options-grid">
                <div class="option-card">
                    <div class="option-icon"><i class="fas fa-box"></i></div>
                    <h3>Livraison Standard</h3>
                    <div class="option-price">5,90€</div>
                    <div class="option-delay"><i class="fas fa-clock"></i> 3-5 jours ouvrés</div>
                    <ul class="option-features">
                        <li><i class="fas fa-check"></i> Suivi en temps réel</li>
                        <li><i class="fas fa-check"></i> Notification SMS</li>
                        <li><i class="fas fa-check"></i> Livraison à domicile</li>
                    </ul>
                </div>
                
                <div class="option-card popular">
                    <span class="popular-badge">Populaire</span>
                    <div class="option-icon"><i class="fas fa-truck"></i></div>
                    <h3>Livraison Express</h3>
                    <div class="option-price">9,90€</div>
                    <div class="option-delay"><i class="fas fa-clock"></i> 24-48h</div>
                    <ul class="option-features">
                        <li><i class="fas fa-check"></i> Livraison prioritaire</li>
                        <li><i class="fas fa-check"></i> Suivi en temps réel</li>
                        <li><i class="fas fa-check"></i> Créneau horaire au choix</li>
                        <li><i class="fas fa-check"></i> Signature requise</li>
                    </ul>
                </div>
                
                <div class="option-card">
                    <div class="option-icon"><i class="fas fa-store"></i></div>
                    <h3>Point Relais</h3>
                    <div class="option-price">3,90€</div>
                    <div class="option-delay"><i class="fas fa-clock"></i> 4-6 jours ouvrés</div>
                    <ul class="option-features">
                        <li><i class="fas fa-check"></i> +6000 points relais</li>
                        <li><i class="fas fa-check"></i> Disponible 14 jours</li>
                        <li><i class="fas fa-check"></i> Horaires flexibles</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- ZONES -->
        <div class="zones-section">
            <div class="section-header">
                <span class="section-tag">Destinations</span>
                <h2 class="section-title">Zones de livraison</h2>
            </div>
            
            <table class="zones-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Délai</th>
                        <th>Tarif Standard</th>
                        <th>Gratuit dès</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>France métropolitaine</strong></td>
                        <td>3-5 jours ouvrés</td>
                        <td>5,90€</td>
                        <td><span class="free-badge">100€ d'achat</span></td>
                    </tr>
                    <tr>
                        <td><strong>Belgique, Luxembourg</strong></td>
                        <td>4-6 jours ouvrés</td>
                        <td>7,90€</td>
                        <td><span class="free-badge">150€ d'achat</span></td>
                    </tr>
                    <tr>
                        <td><strong>Union Européenne</strong></td>
                        <td>5-10 jours ouvrés</td>
                        <td>9,90€</td>
                        <td><span class="free-badge">200€ d'achat</span></td>
                    </tr>
                    <tr>
                        <td><strong>DOM-TOM</strong></td>
                        <td>7-14 jours ouvrés</td>
                        <td>12,90€</td>
                        <td>—</td>
                    </tr>
                    <tr>
                        <td><strong>International</strong></td>
                        <td>10-21 jours</td>
                        <td>Sur devis</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- FAQ -->
        <div class="faq-section">
            <div class="section-header">
                <span class="section-tag">FAQ</span>
                <h2 class="section-title">Questions fréquentes</h2>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <h4><i class="fas fa-map-marker-alt"></i> Comment suivre ma commande ?</h4>
                    <p>Un email avec le lien de suivi vous est envoyé dès l'expédition. Vous pouvez aussi suivre votre commande depuis votre espace client.</p>
                </div>
                <div class="faq-item">
                    <h4><i class="fas fa-calendar-alt"></i> Quand ma commande sera-t-elle expédiée ?</h4>
                    <p>Les commandes passées avant 14h (hors week-end) sont expédiées le jour même. Les autres sont expédiées le jour ouvré suivant.</p>
                </div>
                <div class="faq-item">
                    <h4><i class="fas fa-home"></i> Que faire si je suis absent ?</h4>
                    <p>Le transporteur effectuera une seconde présentation ou déposera votre colis en point relais. Vous serez notifié par SMS et email.</p>
                </div>
                <div class="faq-item">
                    <h4><i class="fas fa-box-open"></i> Mon colis est endommagé, que faire ?</h4>
                    <p>Refusez le colis et contactez-nous immédiatement. Nous vous enverrons un nouveau colis sans frais supplémentaires.</p>
                </div>
            </div>
        </div>
        
        <!-- CTA -->
        <div class="cta-section">
            <div class="cta-box">
                <h2>Livraison offerte dès 100€</h2>
                <p>Profitez de la livraison gratuite en France métropolitaine sur toutes vos commandes de plus de 100€.</p>
                <a href="{{ route('frontend.shop') }}" class="btn-cta">
                    <i class="fas fa-shopping-bag"></i> Découvrir la boutique
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
