# RAPPORT UX LOGIN POS - 18 Mars 2026

## Demande

Structurer la page login POS avec meilleure ergonomie, sans scroll, responsive, footer infos marque, et charte Racine BY GANDA.

## Livrables

1. Layout ergonomique sans scroll (ecran login)
- grille fixe: topbar + zone centrale + footer
- hauteur: `100dvh`
- `overflow: hidden` sur la page login

2. Responsive
- topbar compacte sur petit ecran
- footer qui passe en colonne sur mobile
- taille typo/champs adaptee

3. Footer marque (selon capture)
- © 2026 RACINE BY GANDA. Tous droits reserves.
- Developpe par NIKA DIGITAL HUB | Solutions Web & Communication CG Republique du Congo
- CGV • Confidentialite • Cookies | Paiement securise

4. Charte graphique Racine appliquee
- palette dark + or (primary: #f2ca50 / #d4af37)
- variables globales harmonisees dans App.vue
- typographie Manrope prioritaire

## Fichiers modifies

- `racine-pos-electron/src/views/LoginView.vue`
- `racine-pos-electron/src/App.vue`

## Validation

- `npm run build` => OK
