# SKILL — ATOMIC-EDIT
# Usage : invoquer pour toute modification de fichier PHP/JS complexe

## RÈGLE FONDAMENTALE
Chaque édition est atomique. Jamais de continuer sans vérifier.

## PROTOCOLE OBLIGATOIRE
1. Lire le fichier COMPLET avant toute modification
2. Identifier les lignes exactes a modifier (numero de ligne)
3. Backup mental : noter letat avant
4. Appliquer le patch Python
5. Relire 10 lignes avant/apres le point modifie
6. php -l FICHIER si PHP
7. Seulement alors passer a la suite

## PATCH PYTHON — SEULE METHODE AUTORISEE SOUS WSL
lines = open("FICHIER").readlines()
# verifier que la ligne est bien celle attendue
print(repr(lines[INDEX]))
# modifier
lines[INDEX] = "    nouveau contenu
"
open("FICHIER", "w").writelines(lines)

## SI LE PATCH ECHOUE
- Tentative 1 : relire le fichier, recalculer les indices
- Tentative 2 : approche alternative
- Apres 2 echecs : STOP + escalade orchestrateur
- JAMAIS continuer avec un fichier dans un etat incertain

## VERIFICATION APRES CHAQUE FICHIER
php -l FICHIER                    # syntaxe PHP
grep -n "function" FICHIER | head # structure intacte
# si doute : afficher les 10 lignes autour du changement
sed -n "START,ENDp" FICHIER
