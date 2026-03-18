# RAPPORT SIMPLIFICATION AUTH POS - 18 Mars 2026

## Demande

Supprimer la saisie manuelle des infos terminal pour le staff et garder un ecran auth minimal.

## Implementations

1. Auto-generation terminal
- `Machine ID` genere automatiquement (UUID)
- nom terminal genere automatiquement (`POS-xxxxxxxx`)
- valeurs stockees localement (`pos_machine_id`, `pos_terminal_name`)

2. Auto-registration terminal
- au chargement de la page login, appel auto de l'enregistrement device
- retry automatique avec nouvel UUID si collision `machine_id`

3. UI simplifiee
- suppression du bloc "Enregistrer le terminal"
- formulaire minimal:
  - Email
  - Mot de passe
  - bouton Entrer
- affichage status:
  - "Initialisation du terminal..."
  - "Terminal pret"

## Fichiers modifies

- `racine-pos-electron/src/stores/auth.js`
- `racine-pos-electron/src/views/LoginView.vue`
- `racine-pos-electron/src/i18n/fr.js`
- `racine-pos-electron/src/i18n/en.js`

## Validation

- `npm run build` => OK

## Note operationnelle

Le premier lancement enregistre le terminal automatiquement.
Si le backend garde le device en `pending`, l'operateur peut se connecter mais les appels proteges seront bloques jusqu'a activation device.
