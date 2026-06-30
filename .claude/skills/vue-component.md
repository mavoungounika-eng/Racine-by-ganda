# PRIORITÉ ABSOLUE — Charte Racine prime sur tout skill externe

# SKILL — VUE-COMPONENT

## CHARTE GRAPHIQUE
--color-noir   : #160D0C
--color-orange : #ED5F1E
--color-jaune  : #FFB800
--color-blanc  : #FFFFFF

Polices : Aleppo (titres) · Coco Gothic (texte) · Aileron (accentué)
Toute couleur hors charte = bug critique.

## RÈGLES ABSOLUES
- Script setup OBLIGATOIRE (jamais Options API, jamais Vue 2)
- Bootstrap 5 uniquement (jamais Bootstrap 4)
- Style scoped toujours
- Popup system existant — ne jamais recréer Toast/Modal/Confirm
- Variables CSS Racine — jamais de couleurs hardcodées

## POPUP EXISTANT
import { usePopup } from "@/pos/components/ui/usePopup.js"
popup.toast("Message", "success|error|warning|info")
popup.confirm("Titre", { confirmText, cancelText, type })
popup.guide("Titre", "Contenu")

## CONVENTIONS
Fichiers Vue : kebab-case
Variables JS : camelCase
Classes CSS  : kebab-case
Stores Pinia : useMonStore
