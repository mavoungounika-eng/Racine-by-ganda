@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Politique de Confidentialité - RACINE BY GANDA')

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
    
    .legal-body {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 3rem;
    }
    
    .legal-intro {
        padding: 1.5rem;
        background: rgba(237, 95, 30, 0.08);
        border-radius: 16px;
        margin-bottom: 2.5rem;
    }
    
    .legal-intro p {
        color: rgba(22,13,12,0.6);
        line-height: 1.8;
        margin: 0;
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
    
    .legal-section h2 i {
        color: #FFB800;
    }
    
    .legal-section h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #160D0C;
        margin: 1.5rem 0 0.75rem;
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
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
    }
    
    .data-table th, .data-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(22,13,12,0.1);
    }
    
    .data-table th {
        background: rgba(22,13,12,0.05);
        font-weight: 600;
        color: #160D0C;
    }
    
    .data-table td {
        color: rgba(22,13,12,0.6);
    }
    
    .rights-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
    }
    
    .right-card {
        background: rgba(22,13,12,0.05);
        border-radius: 12px;
        padding: 1.25rem;
    }
    
    .right-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #160D0C;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .right-card h4 i {
        color: #FFB800;
    }
    
    .right-card p {
        font-size: 0.9rem;
        margin: 0;
    }
    
    .contact-box {
        background: linear-gradient(135deg, #160D0C 0%, #160D0C 100%);
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
        color: #FFB800;
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
            <p style="text-align:right;margin-bottom:2rem;color:#666;"><strong>Dernière mise à jour :</strong> Juin 2026</p>

            <div class="legal-intro">
                <p>
                    Chez {{ config('company.name') }}, nous accordons une importance primordiale à la protection de vos données personnelles.
                    Cette politique de confidentialité vous informe sur la manière dont nous collectons, utilisons, stockons et protégeons
                    vos informations conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.
                </p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-building"></i> Responsable du traitement</h2>
                <p>Le responsable du traitement des données personnelles est :</p>
                <p>
                    <strong>{{ config('company.name') }} SAS</strong><br>
                    @if(config('company.address'))
                        {{ config('company.address') }}<br>
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner adresse siège social avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[ADRESSE_A_RENSEIGNER]</span><em>(adresse à renseigner)</em><br>
                    @endif
                    Email : {{ config('company.dpo_email') }}<br>
                    Téléphone :
                    @if(config('company.phone'))
                        {{ config('company.phone') }}
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner téléphone support avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[TELEPHONE_A_RENSEIGNER]</span><em>(à renseigner)</em>
                    @endif
                </p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-database"></i> Données collectées</h2>
                <p>Nous collectons les catégories de données personnelles suivantes :</p>

                <h3>Lors de l'inscription</h3>
                <ul>
                    <li>Nom et prénom</li>
                    <li>Adresse email</li>
                    <li>Mot de passe (stocké sous forme chiffrée)</li>
                    <li>Date de naissance (optionnel)</li>
                </ul>

                <h3>Lors d'une commande</h3>
                <ul>
                    <li>Adresse de livraison et de facturation</li>
                    <li>Numéro de téléphone</li>
                    <li>Informations de paiement (traitées par nos prestataires certifiés, jamais stockées sur nos serveurs)</li>
                    <li>Historique des commandes et préférences d'achat</li>
                </ul>

                <h3>Lors de la navigation</h3>
                <ul>
                    <li>Données de connexion (adresse IP, type de navigateur, système d'exploitation)</li>
                    <li>Pages visitées et durée de consultation</li>
                    <li>Données de cookies (voir section dédiée)</li>
                </ul>

                <h3>Communications</h3>
                <ul>
                    <li>Contenu des messages envoyés via le formulaire de contact</li>
                    <li>Échanges avec le service client</li>
                    <li>Préférences de communication (newsletter, SMS, notifications)</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-balance-scale"></i> Finalités et bases légales</h2>
                <p>Nous traitons vos données personnelles pour les finalités et sur les bases légales suivantes :</p>

                <h3>Exécution du contrat</h3>
                <ul>
                    <li>Traitement et suivi de vos commandes</li>
                    <li>Gestion des livraisons et de la logistique</li>
                    <li>Traitement des paiements</li>
                    <li>Gestion du service après-vente et des retours</li>
                    <li>Gestion de votre compte utilisateur</li>
                </ul>

                <h3>Consentement</h3>
                <ul>
                    <li>Envoi de newsletters et offres promotionnelles (vous pouvez retirer votre consentement à tout moment)</li>
                    <li>Cookies non essentiels (analytics, marketing)</li>
                    <li>Notifications push sur mobile</li>
                </ul>

                <h3>Intérêt légitime</h3>
                <ul>
                    <li>Amélioration de nos services et de l'expérience utilisateur</li>
                    <li>Prévention de la fraude et sécurisation des transactions</li>
                    <li>Statistiques et analyses d'audience (données anonymisées)</li>
                    <li>Gestion des réclamations et litiges</li>
                </ul>

                <h3>Obligation légale</h3>
                <ul>
                    <li>Conservation des données de facturation (10 ans)</li>
                    <li>Déclarations fiscales et comptables</li>
                    <li>Réponse aux réquisitions judiciaires</li>
                </ul>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-clock"></i> Durée de conservation</h2>
                <p>Vos données sont conservées pendant les durées suivantes :</p>
                <ul>
                    <li><strong>Compte actif :</strong> Tant que votre compte reste actif</li>
                    <li><strong>Données clients :</strong> 3 ans après le dernier achat ou contact</li>
                    <li><strong>Données de facturation :</strong> 10 ans (obligation légale comptable et fiscale)</li>
                    <li><strong>Données de prospection :</strong> 3 ans après le dernier contact pour les prospects, 3 ans après la fin de la relation commerciale pour les clients</li>
                    <li><strong>Cookies :</strong> 13 mois maximum</li>
                    <li><strong>Logs de connexion :</strong> 12 mois maximum</li>
                </ul>
                <p>À l'issue de ces durées, vos données sont soit supprimées, soit anonymisées à des fins statistiques.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-user-shield"></i> Vos droits</h2>
                <p>Conformément au RGPD et à la loi Informatique et Libertés, vous disposez des droits suivants sur vos données personnelles :</p>

                <div class="rights-grid">
                    <div class="right-card">
                        <h4><i class="fas fa-eye"></i> Droit d'accès</h4>
                        <p>Vous pouvez obtenir une copie de l'ensemble des données personnelles que nous détenons sur vous.</p>
                    </div>
                    <div class="right-card">
                        <h4><i class="fas fa-edit"></i> Droit de rectification</h4>
                        <p>Vous pouvez demander la correction de données inexactes ou incomplètes vous concernant.</p>
                    </div>
                    <div class="right-card">
                        <h4><i class="fas fa-trash"></i> Droit à l'effacement</h4>
                        <p>Vous pouvez demander la suppression de vos données dans certaines conditions (droit à l'oubli).</p>
                    </div>
                    <div class="right-card">
                        <h4><i class="fas fa-hand-paper"></i> Droit d'opposition</h4>
                        <p>Vous pouvez vous opposer au traitement de vos données pour des raisons tenant à votre situation particulière.</p>
                    </div>
                    <div class="right-card">
                        <h4><i class="fas fa-pause"></i> Droit à la limitation</h4>
                        <p>Vous pouvez demander la limitation du traitement de vos données dans certaines situations.</p>
                    </div>
                    <div class="right-card">
                        <h4><i class="fas fa-exchange-alt"></i> Droit à la portabilité</h4>
                        <p>Vous pouvez récupérer vos données dans un format structuré et les transférer à un autre responsable de traitement.</p>
                    </div>
                </div>

                <p style="margin-top:1.5rem;">Pour exercer ces droits, contactez-nous à l'adresse <a href="mailto:{{ config('company.dpo_email') }}" style="color:#ED5F1E;">{{ config('company.dpo_email') }}</a> en précisant votre demande. Nous vous répondrons dans un délai d'un mois à compter de la réception de votre demande.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-share-alt"></i> Destinataires des données</h2>
                <p>Vos données personnelles peuvent être transmises aux catégories de destinataires suivants :</p>

                <h3>Services internes</h3>
                <ul>
                    <li>Équipes commerciales et marketing (accès restreint)</li>
                    <li>Service client et support technique</li>
                    <li>Service logistique et gestion des commandes</li>
                    <li>Service comptabilité et finance</li>
                </ul>

                <h3>Prestataires externes</h3>
                <ul>
                    <li><strong>Paiement :</strong> Stripe, PayPal, Monetbil (paiements sécurisés certifiés PCI-DSS)</li>
                    <li><strong>Livraison :</strong> Transporteurs partenaires (Colissimo, Chronopost, DHL)</li>
                    <li><strong>Emailing :</strong> Services d'envoi de newsletters (avec votre consentement)</li>
                    <li><strong>Hébergement :</strong> Hébergeurs certifiés en Europe pour garantir la sécurité de vos données</li>
                    <li><strong>Analytics :</strong> Outils d'analyse d'audience (données anonymisées)</li>
                </ul>

                <p><strong>Engagement de confidentialité :</strong> Nous ne vendons jamais vos données à des tiers. Tous nos partenaires sont soumis à des obligations contractuelles strictes de confidentialité et ne peuvent utiliser vos données que pour les finalités définies.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-globe"></i> Transferts internationaux</h2>
                <p>Vos données personnelles sont principalement stockées et traitées au sein de l'Union Européenne. Dans certains cas limités, elles peuvent être transférées vers des pays situés hors de l'UE, notamment :</p>
                <ul>
                    <li>Prestataires de services cloud (garanties appropriées via clauses contractuelles types de la Commission Européenne)</li>
                    <li>Outils d'analyse et de support client (soumis à des mécanismes de protection validés par la CNIL)</li>
                </ul>
                <p>Tout transfert hors UE est encadré par des garanties appropriées conformément au RGPD (clauses contractuelles types, décisions d'adéquation, Privacy Shield le cas échéant).</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-cookie-bite"></i> Cookies et technologies similaires</h2>
                <p>Notre site utilise des cookies pour améliorer votre expérience de navigation. Un cookie est un petit fichier texte stocké sur votre appareil lors de votre visite.</p>

                <h3>Types de cookies utilisés</h3>
                <ul>
                    <li><strong>Cookies essentiels :</strong> Nécessaires au fonctionnement du site (panier, session, authentification). Ils ne peuvent être désactivés.</li>
                    <li><strong>Cookies de performance :</strong> Collectent des informations anonymes sur l'utilisation du site pour nous aider à l'améliorer.</li>
                    <li><strong>Cookies fonctionnels :</strong> Mémorisent vos préférences (langue, devise, liste de souhaits).</li>
                    <li><strong>Cookies marketing :</strong> Permettent l'affichage de publicités personnalisées (nécessitent votre consentement).</li>
                </ul>

                <h3>Gestion des cookies</h3>
                <p>Vous pouvez à tout moment modifier vos préférences de cookies via notre bandeau de consentement ou paramétrer votre navigateur pour bloquer les cookies. Attention : la désactivation de certains cookies peut limiter l'accès à certaines fonctionnalités du site.</p>
                <p>Pour plus d'informations, consultez notre <a href="{{ route('frontend.cookies') }}" style="color:#ED5F1E;">Politique Cookies</a>.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-shield-alt"></i> Mesures de sécurité</h2>
                <p>Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, perte, destruction ou divulgation :</p>
                <ul>
                    <li><strong>Chiffrement :</strong> Protocole SSL/TLS pour toutes les communications et transmissions de données</li>
                    <li><strong>Authentification forte :</strong> Authentification à deux facteurs (2FA) disponible pour sécuriser votre compte</li>
                    <li><strong>Contrôle d'accès :</strong> Accès restreint aux données personnelles selon le principe du "besoin d'en connaître"</li>
                    <li><strong>Surveillance :</strong> Monitoring 24/7 des systèmes et détection des intrusions</li>
                    <li><strong>Sauvegardes :</strong> Sauvegardes régulières et chiffrées des données</li>
                    <li><strong>Formation :</strong> Sensibilisation régulière de nos équipes aux enjeux de protection des données</li>
                    <li><strong>Audits :</strong> Tests de sécurité et audits réguliers de nos infrastructures</li>
                </ul>
                <p>En cas de violation de données personnelles susceptible d'engendrer un risque pour vos droits et libertés, nous vous en informerons dans les meilleurs délais conformément au RGPD.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-child"></i> Mineurs</h2>
                <p>Notre site n'est pas destiné aux personnes de moins de 16 ans. Nous ne collectons pas sciemment de données personnelles auprès de mineurs. Si vous êtes parent ou tuteur légal et que vous découvrez que votre enfant nous a fourni des informations personnelles, contactez-nous immédiatement à {{ config('company.dpo_email') }} pour que nous puissions supprimer ces données.</p>
            </div>

            <div class="legal-section">
                <h2><i class="fas fa-sync"></i> Modifications de la politique</h2>
                <p>Nous nous réservons le droit de modifier cette politique de confidentialité à tout moment pour refléter les évolutions légales, réglementaires ou de nos pratiques. Toute modification substantielle vous sera notifiée par email ou via un bandeau d'information sur le site. Nous vous invitons à consulter régulièrement cette page pour rester informé.</p>
            </div>

            <div class="contact-box">
                <h3><i class="fas fa-envelope"></i> Nous contacter</h3>
                <p>Pour toute question concernant cette politique de confidentialité ou l'exercice de vos droits :</p>
                <p>
                    <strong>Délégué à la Protection des Données (DPO) :</strong><br>
                    <strong>Email :</strong> <a href="mailto:{{ config('company.dpo_email') }}">{{ config('company.dpo_email') }}</a><br>
                    <strong>Courrier :</strong> {{ config('company.name') }} - DPO,
                    @if(config('company.address'))
                        {{ config('company.address') }}
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner adresse siège social avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[ADRESSE_A_RENSEIGNER]</span><em>(à renseigner)</em>
                    @endif
                </p>
                <p style="margin-top:1rem;"><strong>Réclamation auprès de la CNIL :</strong><br>
                Si vous estimez que vos droits ne sont pas respectés, vous pouvez déposer une réclamation auprès de la Commission Nationale de l'Informatique et des Libertés (CNIL) :<br>
                <a href="https://www.cnil.fr" target="_blank" style="color:#FFB800;">www.cnil.fr</a> | 3 Place de Fontenoy, TSA 80715, 75334 Paris Cedex 07</p>
            </div>
        </div>
    </div>
</section>
@endsection
