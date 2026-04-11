# Phase 3 — Test Strategy

## Objectif
Renforcer la qualité sans introduire de dette ou de tests artificiels.

## État actuel
- Large couverture des parcours critiques
- Forte dominance de tests métier et sécurité
- Couverture quantitative non mesurée volontairement

## Pourquoi pas de coverage automatique
- Pas de Xdebug / PCOV installé
- Environnement Windows local
- Valeur métier > pourcentage abstrait
- Décision assumée et documentée

## Priorités Phase 3
🔴 Paiements / Checkout  
🔴 Auth / RBAC  
🟠 Orders / Stock  
🟡 Dashboards

## Ce que nous ne testerons pas
- HTML / Blade statique
- CSS / UI
- Tests redondants sur logique déjà couverte

## Risques acceptés
- Absence de % coverage
- Warnings PHPUnit doc-comments

## Plan Phase 3
- J2 : Renforcement ciblé Paiements & Auth
- J3 : Non-régression & tests adversariaux manquants
