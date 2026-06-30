# SKILL — API-CONTRACT
# Usage : invoquer quand on modifie routes/api_pos.php ou posClient.js

## PRINCIPE
Le POS Electron consomme l API Laravel via posClient.js.
Tout changement cote Laravel sans mise a jour du client = casse silencieuse.

## FICHIERS A VERIFIER EN PARALLELE
routes/api_pos.php                          → cote Laravel
racine-pos-electron/src/services/posClient.js → cote Electron

## REGLES DE NON-BREAKING CHANGE
- Ne jamais renommer un endpoint existant sans versioning
- Ne jamais supprimer un champ de reponse JSON sans verifier posClient
- Ne jamais changer le type dun parametre (string → int)
- Ajouter des champs est OK (backward compatible)

## PROTOCOLE AVANT MODIFICATION
1. Identifier lendpoint concerne dans api_pos.php
2. Chercher son usage dans posClient.js :
   grep -n "nom-endpoint" racine-pos-electron/src/services/posClient.js
3. Si trouve : mettre a jour les deux fichiers ensemble
4. Si non trouve : verifier que le endpoint nest pas utilise ailleurs

## VERSIONING SI BREAKING CHANGE INEVITABLE
Route::prefix("v2")->group(function() {
    // nouveau contrat
});
// garder v1 actif pendant la transition

## CHECKLIST APRES MODIFICATION API
- [ ] posClient.js mis a jour si necessaire
- [ ] Types de parametres inchanges ou documentes
- [ ] Reponse JSON backward compatible
- [ ] Test Electron manuel sur le flux concerne
