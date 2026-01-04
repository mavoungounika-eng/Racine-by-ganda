# BIBLE ERP RACINE BY GANDA
## Document Officiel - Version 1.0

> **Statut :** OFFICIEL - IMMUABLE
> **Date :** 2026-01-04
> **Propriétaire :** Direction RACINE BY GANDA
> **Révision :** Annuelle uniquement

---

# I. PRINCIPES FONDAMENTAUX (IMMUABLES)

## Principe 1 : Single Source of Truth

**Définition :**
L'ERP est la SEULE source de vérité pour les données de production, stock et coûts.

**Applications :**
- **Stock** = Somme des mouvements (`stock_movements`), jamais un cache
- **Coût** = Calculé depuis logs réels, jamais saisi manuellement
- **Production** = Par variante (taille/couleur), jamais agrégée

**Interdictions :**
- ❌ Tenir un fichier Excel parallèle pour le stock
- ❌ Saisir manuellement un coût de revient
- ❌ Agréger les quantités produites sans détail par variante

---

## Principe 2 : Immutabilité Post-Clôture

**Définition :**
Un Ordre de Fabrication (OF) clôturé devient une vérité comptable figée.

**Applications :**
- OF `status = completed` → Modification interdite (R6)
- OF `status = completed` → Suppression interdite (R7)
- `bom_snapshot` → Modification interdite après création (R8)

**Justification :**
- Intégrité comptable (coûts historiques cohérents)
- Traçabilité audit
- Prévention falsification rétroactive

**Exceptions :**
AUCUNE. Même la direction ne peut modifier un OF completed.

---

## Principe 3 : Traçabilité Totale

**Définition :**
Chaque mouvement, chaque action doit répondre à : WHO, WHAT, WHEN, WHY.

**Applications :**
- Mouvement stock → `user_id`, `source_type`, `source_id`, `movement_date`
- Consommation matière → `logged_by`, `production_order_id`, `timestamp`
- Modification données → Audit trail automatique

**Interdictions :**
- ❌ Correction silencieuse (sans justification tracée)
- ❌ Suppression de logs historiques
- ❌ Modification timestamp

---

## Principe 4 : Séparation Humain/Système

**Définition :**
L'humain DÉCLARE, le système CALCULE.

**Applications :**
- Humain → Scanne matière, logue temps, valide outputs
- Système → Calcule coût, valorise stock, génère KPIs

**Interdictions :**
- ❌ Humain saisit un coût calculé
- ❌ Humain corrige un total calculé
- ❌ Humain modifie une variance

---

# II. RÈGLES BLOQUANTES (11 RÈGLES)

| ID | Règle | Justification Business | Exception |
|:---|:------|:-----------------------|:----------|
| **R1** | Clôture sans matière interdite | Coût faux si pas de consommation tracée | `MissingProductionDataException` |
| **R2** | Clôture sans temps interdite | Coût main d'œuvre faux | `MissingProductionDataException` |
| **R3** | Clôture sans outputs interdite | Stock PF impossible à incrémenter | `MissingProductionDataException` |
| **R4** | Output qty=0 interdit | Erreur de saisie évidente | `InvalidProductionOutputException` |
| **R5** | Clôture si status ≠ in_progress | Évite doubles clôtures | `InvalidOrderStateException` |
| **R6** | Modification OF completed interdite | Intégrité comptable | `ImmutableOrderException` |
| **R7** | Suppression OF completed interdite | Traçabilité audit | `ImmutableOrderException` |
| **R8** | Modification BOM snapshot interdite | Coûts historiques cohérents | `ImmutableOrderException` |
| **R9** | Calcul via snapshot uniquement | Pas de coûts rétroactifs | `MissingBOMSnapshotException` |
| **R11** | Calcul coût OF non completed | Estimation interdite | `InvalidOrderStateException` |
| **R12** | Consommation > stock interdite | Vérité physique | `InsufficientStockException` |

---

# III. INTERDICTIONS ABSOLUES

## Interdiction 1 : Modifier un OF Completed
**Pourquoi :** Un OF clôturé est une vérité comptable. Le modifier casserait la cohérence des coûts historiques.
**Sanction :** Système bloque (R6). Aucune exception.

## Interdiction 2 : Saisir Manuellement un Coût
**Pourquoi :** Les coûts sont calculés depuis les consommations réelles. Une saisie manuelle = falsification.
**Sanction :** Champ non disponible dans l'UI.

## Interdiction 3 : Corriger le Stock Sans Mouvement
**Pourquoi :** Le stock est la somme des mouvements. Une correction directe casse la traçabilité.
**Alternative :** Créer un mouvement `ADJUSTMENT` avec justification.

## Interdiction 4 : Agréger les Outputs
**Pourquoi :** La production textile est par variante (taille/couleur). Une agrégation perd l'information critique.
**Sanction :** Table `production_outputs` impose la granularité.

## Interdiction 5 : Utiliser BOM Courante pour Calcul Coût
**Pourquoi :** La BOM peut changer. Le coût d'un OF doit être calculé avec la recette figée au moment de sa création.
**Sanction :** Système utilise uniquement `bom_snapshot` (R9).

## Interdiction 6 : Consommer Sans Stock Disponible
**Pourquoi :** On ne peut pas consommer ce qu'on n'a pas physiquement.
**Sanction :** Système bloque (R12). Production impossible si stock insuffisant.

---

# IV. FLUX DE DONNÉES (CANONIQUES)

## Flux 1 : Création OF

```
1. Sélection produit → Récupération BOM courante
2. Création OF → Snapshot BOM (immuable)
3. Définition opérations → Gamme de fabrication
4. Status → 'draft'
```

**Données Figées :**
- `bom_snapshot` (recette au moment T)
- `target_quantity` (objectif production)
- `deadline_date` (échéance)

---

## Flux 2 : Production

```
1. Démarrage OF → Status = 'in_progress'
2. Consommation MP → R12 check → Stock OUT
3. Temps opérateurs → Time logs par opération
4. Contrôle qualité → Inspection, décision
```

**Vérifications Automatiques :**
- R12 : Stock disponible avant consommation
- Traçabilité : WHO logged, WHEN, WHAT material

---

## Flux 3 : Clôture OF

```
1. Validation outputs par variante (taille/couleur)
2. Vérifications R1-R5
3. Création production_outputs (vérité granulaire)
4. Création stock_movements (PF IN)
5. Génération production_cost_summaries (coût réel)
6. Valorisation stock PF au coût réel
7. Status → 'completed'
```

**Données Figées (Immuables) :**
- `production_outputs` (qty par variante)
- `production_cost_summaries` (coût réel)
- `stock_movements` (mouvements PF)

---

## Flux 4 : Stock

**Mouvements IN :**
- `PURCHASE` : Réception matière première
- `PRODUCTION` : Production PF
- `RETURN` : Retour client/fournisseur
- `ADJUSTMENT` : Correction inventaire (justifiée)
- `INITIAL` : Initialisation stock

**Mouvements OUT :**
- `PRODUCTION` : Consommation MP
- `SALE` : Vente PF
- `ADJUSTMENT` : Correction inventaire (justifiée)
- `WASTE` : Perte/casse

**Calcul Stock Disponible :**
```
Stock = SUM(quantity WHERE direction='IN') - SUM(quantity WHERE direction='OUT')
```

**Valorisation :**
```
Coût Moyen Pondéré = SUM(total_value IN) / SUM(quantity IN)
Valeur Stock = Stock Disponible * Coût Moyen Pondéré
```

---

# V. ARCHITECTURE TECHNIQUE

## Tables de Vérité (Source Unique)

| Table | Rôle | Immuable ? |
|:------|:-----|:-----------|
| `production_outputs` | Vérité par variante | ✅ (si OF completed) |
| `stock_movements` | Vérité stock | ✅ (jamais modifié) |
| `production_cost_summaries` | Vérité coût | ✅ (généré une fois) |

## Services Métier (Business Logic)

| Service | Responsabilité | Règles Appliquées |
|:--------|:---------------|:------------------|
| `ProductionService` | Gestion OF + Gouvernance | R1-R9, R11 |
| `StockService` | Gestion stock + R12 | R12 |
| `ProductionCostingService` | Calcul coûts + Valorisation | R9, R11 |

## Exceptions Métier (Traçabilité Erreurs)

- `InvalidOrderStateException` : Opération sur OF dans mauvais état
- `MissingProductionDataException` : Données requises manquantes
- `InvalidProductionOutputException` : Output invalide
- `ImmutableOrderException` : Tentative modification immuable
- `MissingBOMSnapshotException` : BOM snapshot absent
- `InsufficientStockException` : Stock insuffisant (R12)

---

# VI. GOUVERNANCE DES DONNÉES

## Responsabilités Humaines

### Opérateur Atelier
**Responsabilité :** Exactitude saisie matière et temps
**Actions :**
- Scanner rouleau tissu (QR code)
- Logger quantité utilisée
- Logger temps opération

**Interdictions :**
- ❌ Saisir "à peu près" (scan obligatoire)
- ❌ Gonfler/minorer temps
- ❌ Modifier logs après validation

---

### Chef d'Atelier
**Responsabilité :** Validation outputs et cohérence
**Actions :**
- Créer OF
- Valider outputs par variante
- Interpréter alertes (R12, retards)

**Interdictions :**
- ❌ Clôturer OF sans vérification physique
- ❌ Modifier outputs après clôture
- ❌ Forcer clôture si règle bloquante

---

### Contrôle Qualité
**Responsabilité :** Inspection et décision
**Actions :**
- Inspecter production
- Décider : approve / rework / reject
- Tracer défauts

**Interdictions :**
- ❌ Approuver sans inspection
- ❌ Modifier décision après clôture OF

---

### Admin ERP
**Responsabilité :** Cohérence globale, JAMAIS correction
**Actions :**
- Initialiser stock MP (mouvement `INITIAL`)
- Consulter KPIs
- Exporter données comptables
- Valider clôture OF (lecture seule)

**Interdictions :**
- ❌ Modifier coût calculé
- ❌ Corriger stock directement
- ❌ Modifier OF completed
- ❌ Supprimer logs historiques

---

## Points de Falsification (À Surveiller)

### Point 1 : Scan Fabric Roll
**Risque :** Déclarer 100m alors que rouleau = 80m
**Contrôle :** Vérification physique réception + Audit mensuel stock

### Point 2 : Log Temps
**Risque :** Gonfler temps pour justifier retard
**Contrôle :** Comparaison temps réel vs standard + Alerte variance > 20%

### Point 3 : Outputs
**Risque :** Déclarer 50 pcs bonnes alors que 45 réelles
**Contrôle :** Vérification physique chef atelier + Cohérence matière consommée

---

# VII. PROCÉDURES EXCEPTIONNELLES

## Procédure 1 : Correction Erreur Saisie

**Cas :** Opérateur a saisi 150m au lieu de 15m

**Procédure :**
1. ❌ NE PAS modifier le log existant
2. ✅ Créer mouvement `ADJUSTMENT` inverse (-135m)
3. ✅ Justifier dans `notes` : "Correction erreur saisie OF-26-001"
4. ✅ Tracer `user_id` de la correction

---

## Procédure 2 : Inventaire Physique

**Cas :** Stock physique ≠ Stock ERP

**Procédure :**
1. Compter stock physique
2. Calculer écart (physique - ERP)
3. Créer mouvement `ADJUSTMENT` pour écart
4. Justifier : "Inventaire physique 2026-01-04"
5. Analyser cause écart (vol ? perte ? erreur saisie ?)

---

## Procédure 3 : OF Clôturé par Erreur

**Cas :** OF clôturé alors que production pas terminée

**Procédure :**
1. ❌ IMPOSSIBLE de rouvrir OF (R6)
2. ✅ Créer NOUVEL OF pour quantité restante
3. ✅ Lier les deux OF dans `notes`
4. ✅ Analyser cause erreur (formation ? process ?)

---

# VIII. AUDIT & CONFORMITÉ

## Audit Mensuel

**Vérifications :**
- Stock physique MP vs ERP (écart < 5%)
- Stock physique PF vs ERP (écart < 2%)
- Cohérence matière consommée vs outputs

**Actions si Écart > Seuil :**
1. Inventaire complet
2. Analyse cause
3. Formation si erreur humaine
4. Révision process si erreur systémique

---

## Audit Trimestriel

**Vérifications :**
- Variance coûts réels vs standard (par produit)
- Taux de non-qualité (par opération)
- Rotation stock (identification stock mort)

**Actions :**
1. Mise à jour BOM standard si variance > 10%
2. Formation opérateur si taux rebut > 5%
3. Liquidation stock mort (> 90 jours)

---

## Audit Annuel

**Vérifications :**
- Revue complète gouvernance
- Conformité règles bloquantes
- Efficacité UI (adoption terrain)
- Pertinence KPIs BI

**Actions :**
1. Mise à jour Bible ERP (si nécessaire)
2. Révision charte gouvernance
3. Formation recyclage tous rôles

---

# IX. ÉVOLUTION & MAINTENANCE

## Modification Bible ERP

**Fréquence :** Annuelle uniquement
**Approbation :** Direction + Validation externe (si audit)
**Versioning :** v1.0 → v2.0 (jamais v1.1)

## Ajout Nouvelle Règle

**Procédure :**
1. Justification business (pourquoi ?)
2. Impact analyse (qui ? quoi ?)
3. Implémentation technique (comment ?)
4. Tests unitaires (vérification)
5. Documentation (Bible ERP)
6. Formation (tous rôles concernés)

## Suppression Règle

**Procédure :**
⚠️ EXTRÊMEMENT RARE
1. Justification (pourquoi règle obsolète ?)
2. Validation direction
3. Période transition (3 mois minimum)
4. Communication large
5. Mise à jour documentation

---

# X. GLOSSAIRE

**OF (Ordre de Fabrication) :** Document de production définissant QUOI produire, COMBIEN, QUAND.

**BOM (Bill of Materials) :** Nomenclature/Recette définissant les matières et quantités nécessaires.

**BOM Snapshot :** Copie figée de la BOM au moment de création OF (immuable).

**SKU (Stock Keeping Unit) :** Référence unique produit (ex: CHEM-BLEU-M = Chemise Bleu taille M).

**Yield (Rendement) :** Ratio production / consommation (ex: 100 pcs / 150m = 0.67 pcs/m).

**Variance :** Écart entre coût réel et coût standard (Réel - Standard).

**Stock Mort :** Stock sans mouvement depuis > 90 jours.

**R12 :** Règle bloquante #12 : Consommation > stock interdite.

---

# XI. CONTACTS & SUPPORT

**Propriétaire Document :** Direction RACINE BY GANDA
**Responsable ERP :** [À définir]
**Support Technique :** [À définir]
**Audit Externe :** [À définir si applicable]

---

**FIN DE DOCUMENT**

*Ce document est la référence officielle pour toute question de gouvernance ERP RACINE BY GANDA.*
*Toute dérogation doit être approuvée par la direction et documentée.*
