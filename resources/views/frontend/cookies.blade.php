@extends('layouts.frontend')

@section('title', 'Politique Cookies - RACINE BY GANDA')
@section('meta-description', 'Politique d\'utilisation des cookies sur RACINE BY GANDA. Découvrez comment nous utilisons les cookies et comment gérer vos préférences.')

@push('styles')
<style>
    .cookies-hero {
        background: linear-gradient(135deg, #160D0C 0%, #2a1810 100%);
        padding: 5rem 0;
        color: white;
        text-align: center;
    }

    .cookies-hero h1 {
        font-size: 3.5rem;
        font-family: var(--racine-font-heading);
        color: #FFB800;
        margin-bottom: 1rem;
    }

    .cookies-hero p {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .cookies-content {
        padding: 4rem 0;
    }

    .cookies-body {
        background: white;
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 8px 32px rgba(22, 13, 12, 0.08);
    }

    .cookies-section {
        margin-bottom: 2.5rem;
    }

    .cookies-section h2 {
        font-family: var(--racine-font-heading);
        color: #160D0C;
        font-size: 2rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .cookies-section h2 i {
        color: #ED5F1E;
        font-size: 1.5rem;
    }

    .cookies-section h3 {
        font-family: var(--racine-font-accent);
        color: #160D0C;
        font-size: 1.4rem;
        margin: 1.5rem 0 0.75rem 0;
    }

    .cookies-section p {
        line-height: 1.8;
        color: #666;
        margin-bottom: 1rem;
    }

    .cookies-section ul, .cookies-section ol {
        padding-left: 1.5rem;
        line-height: 2;
        color: #666;
    }

    .cookies-section li {
        margin-bottom: 0.75rem;
    }

    .cookies-section strong {
        color: #160D0C;
        font-weight: 600;
    }

    .cookies-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        background: #fafafa;
        border-radius: 12px;
        overflow: hidden;
    }

    .cookies-table th, .cookies-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    .cookies-table th {
        background: #160D0C;
        color: #FFB800;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.05em;
    }

    .cookies-table tr:last-child td {
        border-bottom: none;
    }

    .cookies-table tr:hover {
        background: #fff;
    }

    .highlight-box {
        background: linear-gradient(135deg, #FFB800 0%, #ED5F1E 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin: 2rem 0;
    }

    .highlight-box p {
        color: white;
        margin: 0;
        font-size: 1.1rem;
    }

    .contact-box {
        background: #160D0C;
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-top: 3rem;
        text-align: center;
    }

    .contact-box h3 {
        color: #FFB800;
        margin-top: 0;
    }

    .contact-box a {
        color: #FFB800;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .cookies-hero h1 { font-size: 2.5rem; }
        .cookies-body { padding: 1.5rem; }
        .cookies-table { font-size: 0.9rem; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="cookies-hero">
    <div class="container">
        <h1>Politique Cookies</h1>
        <p>Dernière mise à jour : Juin 2026</p>
    </div>
</section>

<!-- CONTENT -->
<section class="cookies-content">
    <div class="container">
        <div class="cookies-body">
            <div class="cookies-section">
                <h2><i class="fas fa-cookie-bite"></i> Qu'est-ce qu'un cookie ?</h2>
                <p>
                    Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur, tablette, smartphone) lors de votre visite sur notre site.
                    Il permet de mémoriser des informations relatives à votre navigation et de vous offrir une expérience personnalisée.
                </p>
                <p>
                    Les cookies ne peuvent ni exécuter de programmes ni transmettre de virus. Ils sont propres au navigateur que vous utilisez
                    et peuvent être désactivés à tout moment.
                </p>
            </div>

            <div class="cookies-section">
                <h2><i class="fas fa-list"></i> Types de cookies utilisés</h2>

                <h3>1. Cookies essentiels (obligatoires)</h3>
                <p>
                    Ces cookies sont nécessaires au fonctionnement du site et ne peuvent être désactivés. Ils vous permettent de naviguer sur le site
                    et d'utiliser ses fonctionnalités essentielles comme le panier d'achat et l'authentification.
                </p>
                <table class="cookies-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Finalité</th>
                            <th>Durée</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>XSRF-TOKEN</strong></td>
                            <td>Protection contre les attaques CSRF</td>
                            <td>Session</td>
                        </tr>
                        <tr>
                            <td><strong>racine_session</strong></td>
                            <td>Maintien de la session utilisateur</td>
                            <td>2 heures</td>
                        </tr>
                        <tr>
                            <td><strong>cart_id</strong></td>
                            <td>Mémorisation du panier</td>
                            <td>30 jours</td>
                        </tr>
                        <tr>
                            <td><strong>currency</strong></td>
                            <td>Devise sélectionnée (XAF/EUR)</td>
                            <td>365 jours</td>
                        </tr>
                    </tbody>
                </table>

                <h3>2. Cookies de performance et analytics</h3>
                <p>
                    Ces cookies nous permettent de comprendre comment les visiteurs utilisent notre site, quelles pages sont les plus consultées
                    et s'ils rencontrent des erreurs. Les données collectées sont anonymisées et agrégées.
                </p>
                <p><strong>Finalités :</strong></p>
                <ul>
                    <li>Mesure d'audience et statistiques de visite</li>
                    <li>Amélioration de l'expérience utilisateur</li>
                    <li>Détection et résolution des erreurs techniques</li>
                </ul>

                <h3>3. Cookies fonctionnels</h3>
                <p>
                    Ces cookies permettent d'améliorer votre confort de navigation en mémorisant vos préférences et choix (langue, filtres, liste de souhaits).
                </p>
                <p><strong>Exemples :</strong></p>
                <ul>
                    <li>Préférences de langue et de région</li>
                    <li>Produits ajoutés aux favoris</li>
                    <li>Filtres et tris appliqués sur la boutique</li>
                </ul>

                <h3>4. Cookies marketing (nécessitent votre consentement)</h3>
                <p>
                    Ces cookies nous aident à vous proposer des publicités pertinentes et à mesurer l'efficacité de nos campagnes marketing.
                </p>
                <p><strong>Utilisation :</strong></p>
                <ul>
                    <li>Personnalisation des publicités selon vos centres d'intérêt</li>
                    <li>Limitation de la fréquence d'affichage des publicités</li>
                    <li>Mesure de l'efficacité des campagnes publicitaires</li>
                </ul>
                <div class="highlight-box">
                    <p>
                        <strong><i class="fas fa-info-circle"></i> Important :</strong>
                        Vous pouvez refuser les cookies marketing sans que cela n'affecte votre navigation sur le site.
                        Seuls les cookies essentiels resteront actifs.
                    </p>
                </div>
            </div>

            <div class="cookies-section">
                <h2><i class="fas fa-clock"></i> Durée de conservation</h2>
                <p>Les cookies que nous utilisons ont des durées de conservation variables :</p>
                <ul>
                    <li><strong>Cookies de session :</strong> Supprimés dès la fermeture de votre navigateur</li>
                    <li><strong>Cookies essentiels :</strong> De 2 heures à 365 jours selon leur fonction</li>
                    <li><strong>Cookies analytics/fonctionnels :</strong> Maximum 13 mois</li>
                    <li><strong>Cookies marketing :</strong> Maximum 13 mois</li>
                </ul>
                <p>
                    Conformément à la réglementation, la durée de conservation ne peut excéder 13 mois pour les cookies
                    non essentiels, et un nouveau consentement vous sera demandé à l'expiration de ce délai.
                </p>
            </div>

            <div class="cookies-section">
                <h2><i class="fas fa-cog"></i> Gérer vos préférences de cookies</h2>

                <h3>Via notre bandeau de consentement</h3>
                <p>
                    Lors de votre première visite, un bandeau vous permet de choisir les cookies que vous acceptez.
                    Vous pouvez modifier vos préférences à tout moment en cliquant sur le lien "Gestion des cookies"
                    présent en bas de chaque page.
                </p>

                <h3>Via votre navigateur</h3>
                <p>Vous pouvez également paramétrer votre navigateur pour bloquer les cookies :</p>
                <ul>
                    <li><strong>Chrome :</strong> Paramètres → Confidentialité et sécurité → Cookies</li>
                    <li><strong>Firefox :</strong> Options → Vie privée et sécurité → Cookies et données de sites</li>
                    <li><strong>Safari :</strong> Préférences → Confidentialité → Cookies et données de sites web</li>
                    <li><strong>Edge :</strong> Paramètres → Confidentialité → Cookies et autorisations de site</li>
                </ul>
                <p>
                    <strong>⚠️ Attention :</strong> Le blocage de tous les cookies peut limiter l'accès à certaines fonctionnalités du site
                    (panier, connexion, préférences).
                </p>
            </div>

            <div class="cookies-section">
                <h2><i class="fas fa-shield-alt"></i> Cookies tiers</h2>
                <p>
                    Certains cookies sont déposés par des services tiers que nous utilisons pour améliorer votre expérience :
                </p>
                <ul>
                    <li><strong>Stripe :</strong> Traitement sécurisé des paiements (cookies essentiels)</li>
                    <li><strong>Google Maps :</strong> Affichage de cartes interactives (cookies fonctionnels)</li>
                    <li><strong>Réseaux sociaux :</strong> Partage de contenu sur Facebook, Instagram, TikTok (cookies marketing, nécessitent consentement)</li>
                </ul>
                <p>
                    Ces services tiers ont leurs propres politiques de confidentialité et d'utilisation des cookies. Nous vous invitons à les consulter :
                </p>
                <ul>
                    <li><a href="https://stripe.com/fr/privacy" target="_blank" rel="noopener noreferrer" style="color:#ED5F1E;">Politique de confidentialité Stripe</a></li>
                    <li><a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" style="color:#ED5F1E;">Politique de confidentialité Google</a></li>
                    <li><a href="https://www.facebook.com/privacy/explanation" target="_blank" rel="noopener noreferrer" style="color:#ED5F1E;">Politique de confidentialité Facebook</a></li>
                </ul>
            </div>

            <div class="cookies-section">
                <h2><i class="fas fa-balance-scale"></i> Base légale et conformité</h2>
                <p>
                    Notre utilisation des cookies est conforme à :
                </p>
                <ul>
                    <li>Règlement Général sur la Protection des Données (RGPD)</li>
                    <li>Loi Informatique et Libertés modifiée</li>
                    <li>Directive ePrivacy (Directive 2002/58/CE modifiée)</li>
                    <li>Lignes directrices de la CNIL sur les cookies et traceurs</li>
                </ul>
                <p>
                    Les cookies essentiels sont déposés sur la base de notre intérêt légitime à assurer le bon fonctionnement du site.
                    Les cookies non essentiels nécessitent votre consentement préalable, libre et éclairé.
                </p>
            </div>

            <div class="contact-box">
                <h3><i class="fas fa-envelope"></i> Nous contacter</h3>
                <p>Pour toute question concernant notre politique cookies :</p>
                <p>
                    <strong>Email :</strong> <a href="mailto:{{ config('company.dpo_email') }}">{{ config('company.dpo_email') }}</a><br>
                    <strong>Téléphone :</strong> @if(config('company.phone')){{ config('company.phone') }}@else(à renseigner)@endif
                </p>
                <p style="margin-top:1rem;">
                    Pour en savoir plus sur le traitement de vos données personnelles, consultez notre
                    <a href="{{ route('frontend.privacy') }}">Politique de Confidentialité</a>.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
