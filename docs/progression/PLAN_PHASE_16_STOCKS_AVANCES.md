# 📅 PLAN PHASE 16 — GESTION AVANCÉE DES STOCKS

**Objectif :** Permettre la gestion fine des stocks au-delà des achats fournisseurs (Ajustements manuels, Inventaires, Gestion des pertes/vols).

---

## 1. Objectifs Détaillés

### A. Mouvements de Stock Manuels
Permettre aux administrateurs de créer des mouvements de stock manuels pour :
*   **Correction d'inventaire** (Erreur de comptage).
*   **Perte / Vol / Casse** (Sortie de stock sans vente).
*   **Don / Cadeau** (Sortie marketing).
*   **Retour Client** (Réintégration en stock).

### B. Interface de Gestion
*   Formulaire simple pour "Ajuster le stock" depuis la liste des produits ou la fiche produit.
*   Historique clair des mouvements avec le motif (Raison).

---

## 2. Actions Techniques

### 🔹 Backend (Contrôleurs & Modèles)
*   **`ErpStockController`** : Ajouter les méthodes `adjust()` et `storeAdjustment()`.
*   **`ErpStockMovement`** : Vérifier que le champ `reason` ou `description` existe pour justifier le mouvement.
*   **Validation** : S'assurer qu'on ne peut pas sortir plus de stock que disponible (sauf si autorisé).

### 🔹 Frontend (Vues)
*   **Modal ou Page d'ajustement** : Un formulaire simple :
    *   Produit (Select ou pré-rempli).
    *   Type (Ajout / Retrait).
    *   Quantité.
    *   Raison (Select : Inventaire, Casse, Autre...).
    *   Note (Texte libre).

---

## 3. Fichiers Concernés

*   `modules/ERP/Http/Controllers/ErpStockController.php` (MODIFICATION)
*   `modules/ERP/Resources/views/stocks/index.blade.php` (MODIFICATION - Ajout bouton "Ajuster")
*   `modules/ERP/Resources/views/stocks/adjust.blade.php` (NOUVEAU - Formulaire)
*   `modules/ERP/routes/web.php` (MODIFICATION - Nouvelles routes)

---

## 4. Tests à prévoir
1.  **Ajustement Positif** : Ajouter 5 unités pour "Correction Inventaire" -> Vérifier Stock +5.
2.  **Ajustement Négatif** : Retirer 2 unités pour "Casse" -> Vérifier Stock -2.
3.  **Historique** : Vérifier que le mouvement apparaît bien dans l'historique avec la bonne raison.

---

**Statut :** ⏳ EN ATTENTE DE VALIDATION
