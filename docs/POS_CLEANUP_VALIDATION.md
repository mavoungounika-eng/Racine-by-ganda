# Validation Cleanup Paiements POS

Date: 18 mars 2026  
Projet: `racine-backend`

## Contexte
Validation du cleanup automatique des paiements POS non confirmés après timeout.

## Résultats de validation
- Test ciblé: `tests/Feature/Pos/PosCleanupPendingPaymentsTest.php` -> **5/5 PASS**
- Suite POS complète: `php artisan test tests/Feature/Pos` -> **114/114 PASS (344 assertions)**
- Cas critique validé: `stale mobile payment is cancelled after threshold`

## Logique confirmée
Fichier: `app/Jobs/CleanupPendingPosPayments.php`

Le job annule les paiements stale pour:
- `card`
- `mobile_money`

Conditions:
- `status = pending`
- `created_at < now() - threshold`

Actions:
1. `PosPayment::cancel('timeout')`
2. `PosSale::cancel(..., 'timeout')` sur la vente associée

Exclusion attendue et validée:
- `cash` n'est pas annulé par ce cleanup
- paiements déjà `confirmed` inchangés
- paiements récents `pending` inchangés

## Scheduler
Commande exécutée: `php artisan schedule:list`

État confirmé:
- `App\Jobs\CleanupPendingPosPayments` est planifié: `*/5 * * * *`
- Référence trouvée dans `routes/console.php`

## Cron système (serveur)
Commande exécutée: `crontab -l | grep artisan`

Résultat actuel:
- **Aucune entrée cron détectée** pour `artisan schedule:run` sur cet environnement.

Entrée recommandée en production:
```cron
* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
```

## Commandes exécutées
```bash
php artisan test tests/Feature/Pos/PosCleanupPendingPaymentsTest.php --stop-on-failure
php artisan test tests/Feature/Pos --stop-on-failure
php artisan schedule:list
crontab -l | grep artisan
php artisan queue:restart
```

## Conclusion
Le cleanup des paiements POS est **fonctionnel et validé** pour `card` et `mobile_money`, avec annulation de la vente associée.  
Point opérationnel restant pour la prod: garantir l'exécution continue du scheduler via cron (ou supervisor équivalent).
