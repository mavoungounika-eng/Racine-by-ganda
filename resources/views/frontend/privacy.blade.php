@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Politique de Confidentialité - RACINE BY GANDA')

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
    
    .legal-body {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 3rem;
    }
    
    .legal-intro {
        padding: 1.5rem;
        background: rgba(212, 165, 116, 0.1);
        border-radius: 16px;
        margin-bottom: 2.5rem;
    }
    
    .legal-intro p {
        color: #5C4A3D;
        line-height: 1.8;
        margin: 0;
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
    
    .legal-section h2 i {
        color: #D4A574;
    }
    
    .legal-section h3 {
        font-size: 1.1rem;
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
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
    }
    
    .data-table th, .data-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .data-table th {
        background: #F8F6F3;
        font-weight: 600;
        color: #2C1810;
    }
    
    .data-table td {
        color: #5C4A3D;
    }
    
    .rights-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
    }
    
    .right-card {
        background: #F8F6F3;
        border-radius: 12px;
        padding: 1.25rem;
    }
    
    .right-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .right-card h4 i {
        color: #D4A574;
    }
    
    .right-card p {
        font-size: 0.9rem;
        margin: 0;
    }
    
    .contact-box {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-top: 2rem;
    }
    
    .contact-box h3 {
        color: white;
        margin-top: 0;
    }
    
    .contact-box p {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .contact-box a {
        color: #D4A574;
    }
    
    @media (max-width: 768px) {
        .legal-hero h1 { font-size: 2rem; }
        .legal-body { padding: 1.5rem; }
        .rights-grid { grid-template-columns: 1fr; }
        .data-table { font-size: 0.9rem; }
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
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Politique de Confidentialité' }}</h1>
        <p>{{ $heroData['description'] ?? 'Dernière mise à jour : Janvier 2025' }}</p>
    </div>
</section>

<!-- CONTENT -->
<section class="legal-content">
    <div class="container">
        <div class="legal-body">
            <div class="legal-intro">
                <p>
                    Chez RACINE BY GANDA, nous accordons une importance primordiale à la protection de vos données personnelles. 
                    Cette politique explique comment nous collectons, utilisons et protégeons vos informations conformément au 
                    Règlement Général sur la Protection des Données (RGPD).
                </p>
            </div>
            
            <div class="legal-section">
                <h2><i class="fas fa-building"></i> Responsable du traitement</h2>
                <p>Le responsable du traitement des données est :</p>
                <p>
                    <strong>RACINE BY GANDA SAS</strong><br>
                    15 Rue de la Mode, 75003 Paris<br>
                    Email : dpo@racine-ganda.com<br>
                    Téléphone : +33 1 23 45 67 89
                </p>
            </div>
            

            <div class="legal-section">
                <h2><i class="fas fa-clock"></i> Durée de conservation</h2>
                <ul>
                    <li><strong>Données clients :</strong> 3 ans après le dernier achat</li>
                    <li><strong>Données de facturation :</strong> 10 ans (obligation légale)</li>
                    <li><strong>Données de prospection :</strong> 3 ans après le dernier contact</li>
                    <li><strong>Cookies :</strong> 13 mois maximum</li>
                </ul>
            </div>
            


            <div class="legal-section">
                <h2><i class="fas fa-share-alt"></i> Partage des données</h2>
                <p>Vos données peuvent être partagées avec :</p>
                <ul>
                    <li><strong>Prestataires de livraison :</strong> Pour l'acheminement de vos commandes</li>
                    <li><strong>Prestataires de paiement :</strong> Pour le traitement sécurisé des transactions</li>
                    <li><strong>Services d'emailing :</strong> Pour l'envoi de communications (avec votre consentement)</li>
                </ul>
                <p>Nous ne vendons jamais vos données à des tiers. Nos partenaires sont soumis à des obligations contractuelles strictes de confidentialité.</p>
            </div>
            
            <div class="legal-section">
                <h2><i class="fas fa-shield-alt"></i> Sécurité</h2>
                <p>Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données :</p>
                <ul>
                    <li>Chiffrement SSL/TLS pour toutes les transmissions</li>
                    <li>Stockage sécurisé des données en Europe</li>
                    <li>Accès restreint aux données personnelles</li>
                    <li>Formation régulière de notre personnel</li>
                    <li>Audits de sécurité périodiques</li>
                </ul>
            </div>
            
            <div class="contact-box">
                <h3><i class="fas fa-envelope"></i> Nous contacter</h3>
                <p>Pour toute question concernant cette politique ou vos données personnelles :</p>
                <p>
                    <strong>Email :</strong> <a href="mailto:dpo@racine-ganda.com">dpo@racine-ganda.com</a><br>
                    <strong>Courrier :</strong> RACINE BY GANDA - DPO, 15 Rue de la Mode, 75003 Paris
                </p>
                <p>Vous pouvez également déposer une réclamation auprès de la CNIL : <a href="https://www.cnil.fr" target="_blank">www.cnil.fr</a></p>
            </div>
        </div>
    </div>
</section>
@endsection
