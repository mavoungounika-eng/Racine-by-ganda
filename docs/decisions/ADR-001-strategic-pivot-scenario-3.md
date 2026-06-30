# ADR-001 : Adoption de la Trajectoire "Plateforme Durable" (Scénario 3)

- **Date** : 2026-02-21
- **Statut** : Accepté
- **Décideur** : Antigravity + User (Approbation du rapport d'ambition)

## Context
L'audit du backend RACINE a révélé une congestion critique du noyau (`app/`). Bien que modulaire, le projet souffre d'un "God Core" où 70% de l'intelligence métier réside dans le namespace central, rendant la maintenance risquée pour une équipe en croissance (échéance 12-24 mois).

## Décision
Nous adoptons officiellement le **Scénario 3 (Plateforme Durable)** comme standard de développement. Cela implique :
1. **Isolation Stricte** : Interdiction d'ajouter des domaines métier dans `app/`.
2. **Décapitation du Core** : Extraction progressive des domaines existants (Creator, Staff, Billing) vers des modules indépendants.
3. **Contrat d'Interface** : Le Core ne doit plus fournir que des primitives techniques. Le métier vit dans les modules.

## Conséquences
- **Positives** : Scalabilité infinie de la base de code, réduction radicale des conflits de merge, préparation réelle au multi-tenancy.
- **Négatives** : Effort initial de refactoring important (Phase 1 : Domaine Créateur), augmentation temporaire de la complexité des imports (namespaces longs).
