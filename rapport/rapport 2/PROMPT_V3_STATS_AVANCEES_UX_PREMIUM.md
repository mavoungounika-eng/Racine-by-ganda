# 🧠 PROMPT MASTER V3 — STATS AVANCÉES & UX PREMIUM CRÉATEUR

**Projet :** RACINE BY GANDA — Espace Créateur  
**Version :** v3.0  
**Date :** 29 novembre 2025

---

## 📋 CONTEXTE

* **V1 :** Auth créateur, statut compte, dashboard de base, séparation Client/Créateur → ✅
* **V2 :** Mini back-office créateur : produits, commandes, finances → ✅

**Objectif V3 :**

Transformer l'espace créateur en **vrai cockpit premium** avec :

* Statistiques avancées
* Graphiques (courbes, barres, donuts)
* Filtres par période
* Notifications internes simples
* UX améliorée (cartes, badges, micro-copy claire)

---

## 1️⃣ CONTEXTE TECHNIQUE À RESPECTER

* Framework : **Laravel 12 + Blade + Tailwind**
* Layout créateur existant : `layouts/creator.blade.php`
* Routes créateur déjà présentes sous `Route::prefix('createur')->name('creator.')`
* Middlewares : `auth`, `role.creator`, `creator.active`
* Modèles déjà existants :
  * `User`
  * `CreatorProfile`
  * `Product`
  * `Order`
  * `OrderItem`

⚠️ **À ne pas casser :**

* L'auth client & admin
* Les routes v1/v2 déjà en place
* La structure du backend existant

---

## 2️⃣ OBJECTIFS FONCTIONNELS V3

### 1. Statistiques avancées côté créateur

Pour chaque créateur, fournir une **vue analytique claire** :

* Évolution des ventes sur le temps (par jour / semaine / mois)
* Top produits (par CA ou quantité vendue)
* Répartition des statuts de commandes (new, in_production, shipped, delivered)
* Répartition des ventes par type de produit ou catégorie (si dispo)
* Comparatif période actuelle vs période précédente (ex : ce mois vs mois dernier)

### 2. Graphiques visuels

* Courbe des ventes dans le temps (CA ou nombre de commandes)
* Graphique barres : top produits
* Donut / pie chart : répartition statuts de commandes ou catégories

👉 **IMPORTANT :**

Back-end en Laravel + Blade, mais prévois une intégration simple côté front avec un lib JS type **Chart.js**.

(Si Chart.js est déjà utilisé ailleurs, le réutiliser. Sinon, le configurer proprement dans le layout créateur.)

### 3. Filtres par période

Sur la page de stats :

* Périodes courtes proposées :
  * 7 derniers jours
  * 30 derniers jours
  * Ce mois-ci
  * Personnalisé : `date_debut` / `date_fin`

Les stats et graphiques doivent **se recalculer** en fonction de la période choisie.

### 4. Notifications & alertes simples

Mettre en place une **première version** de notifications internes créateur :

* Badge dans la barre de navigation créateur indiquant :
  * Nouvelles commandes à traiter
  * Produits en attente de validation (status `pending_review`)
* Page ou panneau "Notifications" listant :
  * Nouveaux événements importants :
    * commande reçue
    * commande livrée
    * produit publié / refusé
* Pas besoin de temps réel Pusher dans v3 : simple affichage basé sur la base de données.

---

## 3️⃣ ROUTES À AJOUTER / COMPLÉTER

Dans le groupe créateur :

```php
Route::prefix('createur')->name('creator.')->middleware(['auth', 'role.creator', 'creator.active'])->group(function () {
    
    // Dashboard existant
    Route::get('dashboard', [CreatorDashboardController::class, 'index'])->name('dashboard');

    // Stats avancées
    Route::get('stats', [CreatorStatsController::class, 'index'])->name('stats.index');

    // Notifications
    Route::get('notifications', [CreatorNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/marquer-lu', [CreatorNotificationController::class, 'markAsRead'])
        ->name('notifications.markAsRead');
});
```

---

## 4️⃣ CONTRÔLEURS À CRÉER / METTRE À JOUR

### 4.1. `CreatorStatsController`

**Objectif :** Fournir toutes les données nécessaires aux graphiques & cartes de stats.

#### Méthode principale : `index(Request $request)`

* Récupérer :
  * Période sélectionnée via query : `period=7d|30d|month|custom`
  * Pour `custom`, accepter `start_date`, `end_date`

* Calculer pour le créateur connecté (`auth()->id()`) :

**1. Série temporelle des ventes**

* Groupement par jour (ou semaine / mois selon la période)
* Somme de `OrderItem.total_price` pour les commandes livrées dans la période

**2. Top produits**

* Ranking des produits par CA ou par quantité sur la période

**3. Répartition statuts de commandes** (dans la période)

* Nombre de commandes par `status`

**4. Comparatif période précédente**

* Calculer la même chose pour la période immédiatement précédente
* Exemple :
  * Période actuelle = 1er au 30 juin
  * Période précédente = 1er au 31 mai
* Fournir des pourcentages d'évolution (+/- %)

* Retourner à la vue :

```php
return view('creator.stats.index', [
    'period' => $period,
    'dateRange' => [...],
    'salesTimeSeries' => $salesTimeSeries, // format prêt pour Chart.js
    'topProducts' => $topProducts,
    'orderStatusDistribution' => $orderStatusDistribution,
    'summary' => [
        'current' => [
            'gross' => ...,
            'ordersCount' => ...,
        ],
        'previous' => [
            'gross' => ...,
            'ordersCount' => ...,
        ],
        'evolution' => [
            'gross_percent' => ...,
            'orders_percent' => ...,
        ],
    ],
]);
```

---

### 4.2. Notifications — `CreatorNotificationController`

Tu peux t'appuyer sur :

* Une table Laravel native `notifications` (si tu utilises le système de notifications Laravel)
* Ou une table simple `creator_notifications` :

```php
id, user_id, type, title, message, is_read, created_at, ...
```

#### Méthodes :

**`index()`**

* Liste des notifications du créateur connecté
* Possibilité de filtrer : toutes / non lues

**`markAsRead($id)`**

* Vérifie que la notif appartient bien à `auth()->id()`
* Passe `is_read` à `true`
* Retour JSON ou redirection avec message

#### Types de notifications recommandés :

* `new_order` → "Nouvelle commande reçue #XXXX"
* `order_status_changed` → "Commande #XXXX passée à 'Livrée'"
* `product_published` → "Votre produit [Nom] a été publié"
* `product_rejected` → "Votre produit [Nom] a été refusé"

> V3 = simple listing + badge.
> Le système de **création des notifications** peut être rudimentaire (ex : hooks dans les events Order/Product déjà existants).

---

## 5️⃣ VUES BLADE À CRÉER / METTRE À JOUR

### 5.1. `resources/views/creator/stats/index.blade.php`

Contenu attendu :

* **Header :**
  * Titre : "Statistiques & performances"
  * Sélecteur de période (7 jours, 30 jours, ce mois, personnalisé)

* **Bloc de cartes :**
  * CA période actuelle + variation vs période précédente
  * Nombre de commandes
  * Panier moyen (facultatif)

* **Section Graphique 1 : Courbe des ventes**
  * Intégration Chart.js

* **Section Graphique 2 : Top produits**
  * Graphique barres + table des 5 meilleurs produits

* **Section Graphique 3 : Répartition statuts commandes**
  * Donut (new / in_production / shipped / delivered)

**Style :**

* Utiliser le layout `layouts/creator`
* Garder la vibe RACINE : cartes arrondies, ombres légères, texte lisible, icônes discrètes.

---

### 5.2. `resources/views/creator/notifications/index.blade.php`

Contenu :

* Liste des notifications :
  * Titre
  * Texte court
  * Date
  * Badge "Nouveau" pour `is_read = false`
* Bouton "Marquer comme lu" pour chaque notification ou pour toutes (optionnel)
* Pagination si nécessaire

Mettre aussi un **petit badge dans la navbar créateur** (layout) indiquant le nombre de notifs non lues.

---

## 6️⃣ INTÉGRATION FRONT — CHARTS

Utiliser **Chart.js** (ou librairie similaire) côté front.

* Inclure le script (CDN ou compilé via Vite) dans `layouts/creator.blade.php`
* Chaque graphique :
  * A un `<canvas id="..."></canvas>`
  * Reçoit ses données via un `@json($variable)` depuis le contrôleur

**Exemple :**

```blade
<script>
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = @json($salesTimeSeries);

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: [{
                label: 'Ventes',
                data: salesData.values,
            }]
        },
    });
</script>
```

---

## 7️⃣ SÉCURITÉ & QUALITÉ

* Tous les calculs sont **filtrés par `auth()->id()`**
* Les notifications ne peuvent être lues / marquées que par leur propriétaire
* Les routes stats et notifications sont protégées par :
  * `auth`, `role.creator`, `creator.active`
* Code propre, méthodes du contrôleur pas surchargées → créer des méthodes privées / services si nécessaire

---

## 8️⃣ LIVRABLES ATTENDUS

À la fin de l'implémentation V3, fournir :

1. **Liste des fichiers créés/modifiés** avec chemins exacts

2. **Code des contrôleurs :**
   * `CreatorStatsController`
   * `CreatorNotificationController`
   * Éventuelles mises à jour de `CreatorDashboardController`

3. **Migrations** pour la table de notifications si tu ne réutilises pas la table native

4. **Vues Blade :**
   * `creator/stats/index.blade.php`
   * `creator/notifications/index.blade.php`
   * Modifications du layout `layouts/creator.blade.php` (badge notifs, lien stats, lien notifications)

---

## 📋 INSTRUCTIONS D'UTILISATION

Ce prompt peut être copié-collé directement dans Antigravity / Cursor pour implémenter le module v3.

**Après implémentation :**

1. Faire un **mini audit qualité** du code généré
2. Tester les fonctionnalités avec la checklist V2 (stats et notifications)
3. Préparer le **RAPPORT GLOBAL MODULE CRÉATEUR V1–V3** qui résume tout l'univers créateur de RACINE BY GANDA

---

**Date de création :** 29 novembre 2025  
**Généré par :** Cursor AI Assistant


