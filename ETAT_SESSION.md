Projet : RACINE BY GANDA — Laravel 12 / PHP 8.3
DB : MySQL Ubuntu 127.0.0.1:3306, user=laravel, db=racine
Serveur : php artisan serve via ./start.sh

Bugs corrigés :
- Routes notifications (marquer-lu + marquer-tout-lu)
- connectStripe() + 8 autres méthodes PaymentPreferencesController
- Font-icons, pagination SVG, modal orphelin, Amira admin
- CreatorProductController::create() return type View|RedirectResponse

Reste à faire :
- Configurer clés API Monetbil dans .env
  MONETBIL_SERVICE_KEY=xxx
  MONETBIL_SECRET_KEY=xxx
- Tester toutes les pages créateur (en cours avec Claude Code)
- Corriger les 500 restants découverts par le scan des routes

Dernière action : ./start.sh relancé, page /createur/produits/nouveau OK
Prochaine action : scan automatique des pages créateur via curl + cookies