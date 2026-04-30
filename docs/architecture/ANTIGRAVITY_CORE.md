# ANTIGRAVITY CORE — Cadre de Gouvernance

Ce document définit les règles de fer pour l'évolution technique de RACINE BY GANDA.

## 1. Philosophie & Standards
- **Modularité-First** : Aucun nouveau métier ne doit être ajouté dans `app/`. Tout nouveau domaine doit être un module autonome dans `modules/`.
- **Minimalisme Défensif** : Ne jamais sur-architecturer. Préférer une solution simple et lisible à une abstraction complexe.
- **Transparence AI** : Toute intervention agentique doit être tracée via le registre ADR (Architectural Decision Records).

## 2. Red Flag System (RFS)
Système d'alerte pour les interventions AI et humaines :
- **L1 (Vert)** : Refactoring mineur, ajout de tests, doc. → Auto-validation possible.
- **L2 (Jaune)** : Ajout de module, modification de schéma DB mineure. → Revue recommandée.
- **L3 (Rouge)** : Modification du Core (`User.php`, `AuthServiceProvider`), changement de version Laravel. → **Approbation User Obligatoire**.

## 3. Skills Registry (Outils)
- **Runtime** : Node 20 (via NVM).
- **Package Manager** : npm (avec `CYPRESS_INSTALL_BINARY=0` si blocage réseau).
- **Backend** : PHP 8.2+ / Laravel 12.

## 4. Architectural Locking
Les fichiers suivants sont considérés comme "verrouillés" et nécessitent une justification ADR pour toute modification structurelle :
- `app/Models/User.php`
- `app/Providers/ModulesServiceProvider.php`
- `app/Providers/AuthServiceProvider.php`
- `composer.json`
- `package.json`

---
*Dernière mise à jour : 2026-02-21*
