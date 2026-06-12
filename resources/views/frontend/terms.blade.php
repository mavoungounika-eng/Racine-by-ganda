@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Conditions Générales de Vente - RACINE BY GANDA')

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
    
    .legal-section h3 {
        font-size: 1.15rem;
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
                    <li><a href="#article1">1. Objet et champ d'application</a></li>
                    <li><a href="#article2">2. Définitions</a></li>
                    <li><a href="#article3">3. Inscription et compte utilisateur</a></li>
                    <li><a href="#article4">4. Produits et commandes</a></li>
                    <li><a href="#article5">5. Prix et paiement</a></li>
                    <li><a href="#article6">6. Livraison</a></li>
                    <li><a href="#article7">7. Droit de rétractation et retours</a></li>
                    <li><a href="#article8">8. Responsabilités</a></li>
                    <li><a href="#article9">9. Propriété intellectuelle</a></li>
                    <li><a href="#article10">10. Données personnelles</a></li>
                    <li><a href="#article11">11. Litiges et droit applicable</a></li>
                </ul>
            </nav>
            
            <!-- BODY -->
            <div class="legal-body">
                <p class="update-date" style="text-align:right;margin-bottom:2rem;"><strong>Pointe-Noire, juin 2026</strong></p>

                <div class="legal-section" id="article1">
                    <h2><span class="number">1</span> Objet et champ d'application</h2>
                    <p>Les présentes Conditions Générales de Vente (CGV) régissent les relations contractuelles entre {{ config('company.name') }}, société par actions simplifiée au capital de
                    @if(config('company.capital'))
                        {{ number_format(config('company.capital'), 0, ',', ' ') }} €
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner capital social avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[CAPITAL_A_RENSEIGNER]</span><em>(à renseigner)</em>
                    @endif
                    , dont le siège social est situé au
                    @if(config('company.address'))
                        {{ config('company.address') }}
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner adresse siège social avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[ADRESSE_A_RENSEIGNER]</span><em>(à renseigner)</em>
                    @endif
                    , immatriculée au RCCM sous le numéro
                    @if(config('company.rccm'))
                        {{ config('company.rccm') }}
                    @else
                        <!-- TODO PROD BLOQUANT: Renseigner RCCM réel avant mise en ligne -->
                        <span class="todo-prod" style="display:none">[RCCM_A_RENSEIGNER]</span><em>(à renseigner)</em>
                    @endif
                    , ci-après dénommée "la Plateforme", et toute personne physique ou morale effectuant un achat sur le site racinebyganda.com.</p>
                    <p>{{ config('company.name') }} opère une marketplace de mode africaine contemporaine mettant en relation des créateurs indépendants avec des clients.</p>
                    <div class="highlight-box">
                        <p><strong>Acceptation :</strong> En passant commande, le Client reconnaît avoir pris connaissance des présentes CGV et les accepte sans réserve. L'acceptation des CGV est matérialisée par le clic sur le bouton de validation de commande.</p>
                    </div>
                </div>

                <div class="legal-section" id="article2">
                    <h2><span class="number">2</span> Définitions</h2>
                    <p>Les termes suivants ont la signification indiquée ci-après :</p>
                    <ul>
                        <li><strong>Plateforme :</strong> {{ config('company.name') }}, éditeur et exploitant du site racinebyganda.com</li>
                        <li><strong>Créateur :</strong> Artisan, styliste ou créateur indépendant proposant ses produits sur la Plateforme</li>
                        <li><strong>Client :</strong> Toute personne physique ou morale procédant à un achat sur la Plateforme</li>
                        <li><strong>Commande :</strong> Achat de produits effectué par le Client sur la Plateforme</li>
                        <li><strong>Produit :</strong> Article de mode, accessoire ou création artisanale proposé à la vente</li>
                        <li><strong>Compte utilisateur :</strong> Espace personnel accessible après inscription permettant de gérer ses commandes</li>
                    </ul>
                </div>

                <div class="legal-section" id="article3">
                    <h2><span class="number">3</span> Inscription et compte utilisateur</h2>
                    <h3>3.1 Création de compte</h3>
                    <p>L'achat sur la Plateforme nécessite la création d'un compte utilisateur. Le Client garantit l'exactitude des informations fournies lors de son inscription et s'engage à les mettre à jour en cas de modification.</p>
                    <p>Les identifiants de connexion sont personnels et confidentiels. Le Client est responsable de leur conservation et de toute utilisation qui en serait faite.</p>
                    <h3>3.2 Accès au compte</h3>
                    <p>Le compte permet au Client de :</p>
                    <ul>
                        <li>Consulter l'historique de ses commandes</li>
                        <li>Suivre l'état de ses livraisons</li>
                        <li>Gérer ses adresses de livraison</li>
                        <li>Modifier ses informations personnelles</li>
                        <li>Initier des retours ou réclamations</li>
                    </ul>
                    <h3>3.3 Suppression de compte</h3>
                    <p>Le Client peut demander la suppression de son compte à tout moment depuis son espace personnel ou en contactant {{ config('company.email') }}. Les données liées aux commandes passées seront conservées conformément aux obligations légales.</p>
                </div>

                <div class="legal-section" id="article4">
                    <h2><span class="number">4</span> Produits et commandes</h2>
                    <h3>4.1 Nature des produits</h3>
                    <p>Les produits proposés sur la Plateforme sont des créations artisanales réalisées par des créateurs indépendants basés en Afrique et dans la diaspora. Chaque pièce est unique et peut présenter de légères variations par rapport aux photographies présentées.</p>
                    <p>Les descriptions, photographies et informations produits sont fournies par les Créateurs et validées par la Plateforme. Elles sont aussi précises que possible mais ne sauraient constituer un engagement contractuel sur des détails mineurs.</p>
                    <h3>4.2 Disponibilité</h3>
                    <p>Les offres sont valables dans la limite des stocks disponibles. En cas d'indisponibilité d'un article après validation de la commande, le Client en sera informé par email dans un délai de 48 heures et pourra choisir entre :</p>
                    <ul>
                        <li>Un remboursement intégral de l'article indisponible</li>
                        <li>Un échange contre un produit équivalent (sous réserve d'acceptation)</li>
                        <li>L'annulation totale de la commande si l'article était le seul commandé</li>
                    </ul>
                    <h3>4.3 Processus de commande</h3>
                    <p>Pour passer commande, le Client doit :</p>
                    <ol>
                        <li>Sélectionner les produits souhaités et les ajouter au panier</li>
                        <li>Valider le contenu du panier et vérifier les quantités</li>
                        <li>Renseigner ou confirmer l'adresse de livraison</li>
                        <li>Choisir le mode de livraison parmi les options proposées</li>
                        <li>Sélectionner le mode de paiement</li>
                        <li>Vérifier le récapitulatif de la commande</li>
                        <li>Accepter les CGV et valider la commande</li>
                        <li>Procéder au paiement</li>
                    </ol>
                    <p>La commande est définitivement enregistrée après validation du paiement. Un email de confirmation est envoyé au Client récapitulant le détail de la commande, le montant total et l'adresse de livraison.</p>
                </div>

                <div class="legal-section" id="article5">
                    <h2><span class="number">5</span> Prix et paiement</h2>
                    <h3>5.1 Prix</h3>
                    <p>Les prix sont affichés en francs CFA (XAF), devise principale de la Plateforme, ou en euros (EUR) pour la clientèle de la diaspora, selon la localisation du Client, toutes taxes comprises (TTC). La fiscalité applicable est celle en vigueur en République du Congo au jour de la commande.</p>
                    <p>Les frais de livraison sont indiqués distinctement avant la validation finale de la commande et s'ajoutent au montant total.</p>
                    <p>La Plateforme se réserve le droit de modifier ses prix à tout moment, étant entendu que les produits seront facturés sur la base des tarifs en vigueur au moment de la validation de la commande.</p>
                    <h3>5.2 Moyens de paiement</h3>
                    <p>Le règlement des commandes s'effectue par :</p>
                    <ul>
                        <li><strong>Mobile Money (XAF) :</strong> MTN Mobile Money Congo, Airtel Money Congo (moyens de paiement privilégiés en République du Congo)</li>
                        <li><strong>Carte bancaire :</strong> Visa, Mastercard, American Express (paiement sécurisé via Stripe), notamment pour les règlements en EUR</li>
                        <li><strong>Mobile Money :</strong> MTN Mobile Money, Airtel Money (via Monetbil)</li>
                    </ul>
                    <p>Les transactions sont sécurisées par le protocole SSL. Les données bancaires ne sont jamais stockées sur les serveurs de la Plateforme et sont traitées uniquement par les prestataires de paiement certifiés PCI-DSS.</p>
                    <h3>5.3 Validation du paiement</h3>
                    <p>Le Client garantit à la Plateforme qu'il dispose des autorisations nécessaires pour utiliser le mode de paiement choisi. La Plateforme se réserve le droit de refuser ou d'annuler toute commande en cas de refus d'autorisation de paiement, de fraude avérée ou de litige relatif au paiement d'une commande antérieure.</p>
                </div>

                <div class="legal-section" id="article6">
                    <h2><span class="number">6</span> Livraison</h2>
                    <h3>6.1 Zones de livraison</h3>
                    <p>{{ config('company.name') }} livre dans les zones géographiques suivantes :</p>
                    <ul>
                        <li>Congo-Brazzaville (Pointe-Noire, Brazzaville et principales villes)</li>
                        <li>Afrique centrale (CEMAC : Gabon, Cameroun, Tchad, RCA, Guinée équatoriale)</li>
                        <li>Reste de l'Afrique (principales villes et capitales)</li>
                        <li>Diaspora — Union Européenne</li>
                        <li>International (sur demande)</li>
                    </ul>
                    <h3>6.2 Délais de livraison</h3>
                    <p>Les délais de livraison sont donnés à titre indicatif et peuvent varier selon la localisation du Créateur et la destination :</p>
                    <ul>
                        <li><strong>Congo-Brazzaville :</strong> 2 à 7 jours ouvrés</li>
                        <li><strong>Afrique centrale (CEMAC) :</strong> 5 à 12 jours ouvrés</li>
                        <li><strong>Reste de l'Afrique :</strong> 7 à 14 jours ouvrés</li>
                        <li><strong>Diaspora — Union Européenne :</strong> 7 à 15 jours ouvrés</li>
                        <li><strong>International :</strong> 10 à 21 jours ouvrés</li>
                    </ul>
                    <p>Ces délais courent à compter de la confirmation de la commande. Les retards de livraison ne peuvent donner lieu à l'annulation de la commande ou à l'octroi de dommages et intérêts.</p>
                    <h3>6.3 Frais de livraison</h3>
                    <p>Les frais de livraison varient selon la destination et le poids du colis :</p>
                    <ul>
                        <li>Congo-Brazzaville : Gratuit dès 100 000 XAF d'achat, sinon à partir de 2 000 XAF</li>
                        <li>Afrique centrale (CEMAC) : À partir de 10 000 XAF</li>
                        <li>Reste de l'Afrique : À partir de 15 000 XAF</li>
                        <li>Diaspora — Union Européenne : À partir de 15€</li>
                        <li>International : Sur devis</li>
                    </ul>
                    <h3>6.4 Réception de la commande</h3>
                    <p>Le Client est tenu de vérifier l'état du colis en présence du transporteur. Toute anomalie apparente (colis endommagé, ouvert, mouillé) doit être signalée immédiatement au transporteur et faire l'objet de réserves écrites sur le bon de livraison.</p>
                    <p>Le Client dispose ensuite de 48 heures pour signaler tout dommage ou non-conformité à {{ config('company.support_email') }}.</p>
                </div>

                <div class="legal-section" id="article7">
                    <h2><span class="number">7</span> Droit de rétractation et retours</h2>
                    <h3>7.1 Droit de rétractation légal</h3>
                    <p>Conformément aux articles L.221-18 et suivants du Code de la consommation, le Client dispose d'un délai de 14 jours à compter de la réception de sa commande pour exercer son droit de rétractation, sans avoir à justifier de motifs ni à payer de pénalité.</p>
                    <div class="highlight-box">
                        <p><strong>Garantie satisfaction :</strong> {{ config('company.name') }} étend ce délai à 30 jours pour offrir une plus grande flexibilité à ses clients.</p>
                    </div>
                    <h3>7.2 Conditions de retour</h3>
                    <p>Pour être accepté, un retour doit respecter les conditions suivantes :</p>
                    <ul>
                        <li>L'article doit être retourné dans son état d'origine, non porté, non lavé et non modifié</li>
                        <li>Les étiquettes d'origine doivent être intactes et attachées</li>
                        <li>L'emballage d'origine doit être présent et non détérioré</li>
                        <li>La demande de retour doit être initiée avant l'expiration du délai de 30 jours</li>
                    </ul>
                    <p>Les articles personnalisés ou sur-mesure ne peuvent faire l'objet d'une rétractation, sauf en cas de défaut de conformité ou vice caché.</p>
                    <h3>7.3 Procédure de retour</h3>
                    <p>Le Client doit initier sa demande de retour depuis son espace client ou en contactant {{ config('company.support_email') }}. Une étiquette de retour prépayée sera fournie pour les retours en France métropolitaine. Pour les autres destinations, les frais de retour sont à la charge du Client.</p>
                    <p>Le remboursement interviendra dans un délai de 14 jours à compter de la réception du produit retourné, après vérification de sa conformité aux conditions de retour. Le remboursement sera effectué par le même moyen de paiement que celui utilisé pour la commande.</p>
                </div>

                <div class="legal-section" id="article8">
                    <h2><span class="number">8</span> Responsabilités</h2>
                    <h3>8.1 Responsabilité de la Plateforme</h3>
                    <p>{{ config('company.name') }} agit en qualité d'intermédiaire entre les Créateurs et les Clients. La Plateforme s'engage à :</p>
                    <ul>
                        <li>Sélectionner des Créateurs de qualité et vérifier leur conformité aux standards de la marque</li>
                        <li>Assurer la sécurité des transactions et la protection des données personnelles</li>
                        <li>Traiter les commandes et coordonner la logistique</li>
                        <li>Fournir un service client réactif et accessible</li>
                    </ul>
                    <p>La Plateforme ne saurait être tenue responsable des retards de livraison dus à des cas de force majeure, à des informations de livraison erronées fournies par le Client, ou à l'absence du destinataire lors de la livraison.</p>
                    <h3>8.2 Garantie légale de conformité</h3>
                    <p>Tous les produits bénéficient de la garantie légale de conformité prévue aux articles L.217-4 et suivants du Code de la consommation, ainsi que de la garantie contre les vices cachés prévue aux articles 1641 et suivants du Code civil.</p>
                    <p>En cas de défaut de conformité, le Client peut obtenir le remplacement ou la réparation du produit, ou à défaut, la réduction du prix ou la résolution du contrat.</p>
                    <h3>8.3 Limitation de responsabilité</h3>
                    <p>La Plateforme ne saurait être tenue responsable :</p>
                    <ul>
                        <li>Des dommages résultant d'une utilisation inappropriée des produits</li>
                        <li>Des variations artisanales inhérentes à la fabrication manuelle</li>
                        <li>Des retards d'acheminement imputables aux transporteurs ou aux autorités douanières</li>
                        <li>De l'indisponibilité temporaire du site pour maintenance technique</li>
                    </ul>
                </div>

                <div class="legal-section" id="article9">
                    <h2><span class="number">9</span> Propriété intellectuelle</h2>
                    <p>L'ensemble des éléments du site racinebyganda.com (textes, images, photographies, logos, marques, chartes graphiques, vidéos, sons) sont protégés par le droit d'auteur, le droit des marques et le droit des bases de données.</p>
                    <p>Toute reproduction, représentation, modification, publication ou adaptation, totale ou partielle, des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite sans l'autorisation écrite préalable de {{ config('company.name') }}.</p>
                    <p>Les créations proposées par les Créateurs restent leur propriété intellectuelle. Toute utilisation commerciale non autorisée des visuels ou designs présents sur la Plateforme est passible de poursuites.</p>
                </div>

                <div class="legal-section" id="article10">
                    <h2><span class="number">10</span> Données personnelles</h2>
                    <p>Les données personnelles collectées lors de la commande sont nécessaires au traitement de celle-ci et à la gestion de la relation client. Elles sont traitées conformément à la réglementation congolaise sur la protection des données et, pour la clientèle de la diaspora, au Règlement Général sur la Protection des Données (RGPD) de l'Union Européenne.</p>
                    <p>Le Client dispose d'un droit d'accès, de rectification, de suppression, de portabilité et d'opposition au traitement de ses données personnelles.</p>
                    <p>Pour plus d'informations sur la collecte et l'utilisation de vos données, consultez notre <a href="{{ route('frontend.privacy') }}" style="color:#ED5F1E;text-decoration:underline;">Politique de Confidentialité</a>.</p>
                </div>

                <div class="legal-section" id="article11">
                    <h2><span class="number">11</span> Litiges et droit applicable</h2>
                    <h3>11.1 Droit applicable</h3>
                    <p>Les présentes CGV sont soumises aux Actes Uniformes de l'OHADA et au droit congolais en vigueur. Elles sont rédigées en langue française. Dans l'hypothèse d'une traduction en une ou plusieurs langues, seul le texte français ferait foi en cas de litige.</p>
                    <h3>11.2 Règlement amiable des litiges</h3>
                    <p>En cas de litige, le Client est invité à contacter en priorité le service client de {{ config('company.name') }} à l'adresse {{ config('company.support_email') }} afin de rechercher une solution amiable.</p>
                    <h3>11.3 Médiation</h3>
                    <p>Le Client a la possibilité de recourir à une médiation en cas de litige. Pour les litiges relatifs aux communications électroniques et au commerce en ligne, l'ARPTC (Agence de Régulation des Postes et des Communications Électroniques) peut être saisie. Le service client de {{ config('company.name') }} reste l'interlocuteur privilégié pour rechercher une solution amiable.</p>
                    <h3>11.4 Juridiction compétente</h3>
                    <p>À défaut de résolution amiable, tout litige relatif à l'interprétation ou à l'exécution des présentes CGV sera soumis au Tribunal de Commerce de Pointe-Noire (République du Congo), conformément aux Actes Uniformes de l'OHADA et au droit congolais en vigueur.</p>
                </div>

                <div class="highlight-box" style="margin-top:3rem;text-align:center;">
                    <p><strong>Pour toute question concernant nos Conditions Générales de Vente :</strong></p>
                    <p>Email : <a href="mailto:{{ config('company.email') }}" style="color:#ED5F1E;">{{ config('company.email') }}</a></p>
                    @if(config('company.phone'))
                    <p>Téléphone : {{ config('company.phone') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
