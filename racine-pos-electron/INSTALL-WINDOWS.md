# Installation Racine POS sur Windows

Guide d'installation pour les techniciens terrain.

---

## Prerequis

- Windows 10 ou 11 (64 bits)
- Fichier d'installation : `Racine POS Setup X.X.X.exe`

---

## Etapes d'installation

### 1. Lancer l'installeur

Double-cliquer sur le fichier `Racine POS Setup X.X.X.exe`.

### 2. Passer l'avertissement Windows SmartScreen

Windows affiche un ecran bleu avec le message :
**"Windows a protege votre ordinateur"**

Cet avertissement apparait parce que l'application n'a pas encore de certificat
de signature de code. L'application est sans danger.

**Procedure :**

1. Cliquer sur le lien **"Informations complementaires"** (en petit, sous le
   message principal)
2. Un bouton **"Executer quand meme"** apparait en bas de l'ecran
3. Cliquer sur **"Executer quand meme"**

L'installeur NSIS se lance normalement.

### 3. Choisir le dossier d'installation

L'installeur propose un dossier par defaut (generalement
`C:\Program Files\Racine POS`). Vous pouvez le modifier si necessaire.

Cliquer sur **"Installer"**.

### 4. Lancer l'application

Une fois l'installation terminee :
- Un raccourci **Racine POS** est cree sur le Bureau
- Un raccourci est egalement dans le Menu Demarrer

Double-cliquer sur le raccourci pour lancer l'application.

Au premier lancement, Windows peut de nouveau afficher un avertissement
SmartScreen — suivre la meme procedure (etape 2).

---

## Configuration

Au premier lancement, se connecter avec les identifiants du createur
(email + mot de passe du compte racinebyganda.com).

L'application fonctionne en mode hors-ligne : les ventes sont enregistrees
localement et synchronisees automatiquement des que la connexion internet
est retablie.

---

## Mises a jour

L'application verifie automatiquement les mises a jour au demarrage. Quand
une nouvelle version est disponible, une notification apparait en haut de
l'ecran. Cliquer sur **"Telecharger"** puis **"Installer et redemarrer"**.

Note : l'installation de la mise a jour peut declencher un nouvel
avertissement SmartScreen — suivre la meme procedure que ci-dessus.

---

## Desinstallation

Menu Demarrer > Parametres > Applications > Racine POS > Desinstaller
