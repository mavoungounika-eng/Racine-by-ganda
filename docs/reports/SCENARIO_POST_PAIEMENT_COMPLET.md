# 📋 SCÉNARIO COMPLET - POST-PAIEMENT

**Date :** 8 décembre 2025  
**Version :** 1.0

---

## 🎯 VUE D'ENSEMBLE

Après qu'un paiement soit validé (tous moyens confondus) ou qu'un paiement en espèces soit encaissé, plusieurs actions automatiques sont déclenchées pour compléter le processus de vente.

---

## 💵 SCÉNARIO : PAIEMENT EN ESPÈCES ENCAISSÉ

### Workflow complet

```
1. Admin valide la vente dans le POS
   ↓
2. Commande créée avec :
   - status: 'completed'
   - payment_status: 'paid'
   - user_id: null (client boutique)
   ↓
3. Payment créé avec :
   - provider: 'cash'
   - status: 'paid'
   - paid_at: now()
   ↓
4. Stock décrémenté IMMÉDIATEMENT
   ↓
5. Mouvements stock créés (raison: "Vente en boutique")
   ↓
6. Actions post-paiement déclenchées :
   ├─ Email de confirmation envoyé (si email fourni)
   ├─ Notification équipe (staff & admin)
   ├─ Recherche client par email/téléphone
   ├─ Si client trouvé :
   │  ├─ user_id mis à jour sur la commande
   │  ├─ Points de fidélité attribués (1% du montant)
   │  └─ Notification client envoyée
   └─ Log des actions
   ↓
7. Commande terminée ✅
```

### Actions automatiques

#### 1. Email de confirmation
- **Condition** : Si `customer_email` est fourni
- **Contenu** : Email de confirmation de commande
- **Template** : `OrderConfirmationMail`

#### 2. Notification équipe
- **Destinataires** : Staff et Admin
- **Message** : "Nouvelle vente boutique ! Commande {order_number} - {montant} FCFA"
- **Type** : Broadcast team

#### 3. Recherche client
- **Par email** : Si `customer_email` fourni
- **Par téléphone** : Si email non trouvé et `customer_phone` fourni
- **Objectif** : Lier la commande à un compte client existant

#### 4. Attribution points de fidélité
- **Condition** : Client trouvé ET `user_id` mis à jour
- **Calcul** : 1% du montant total (1 FCFA = 1 point)
- **Exemple** : Commande de 50 000 FCFA = 500 points
- **Actions** :
  - Création/mise à jour `LoyaltyPoint`
  - Création `LoyaltyTransaction` (type: 'earned')
  - Mise à jour du tier (bronze/silver/gold)

#### 5. Notification client
- **Condition** : Client trouvé
- **Message** : "Paiement reçu ! Le paiement de votre commande {order_number} a été confirmé."
- **Type** : Success notification

---

## 💳 SCÉNARIO : PAIEMENT PAR CARTE CONFIRMÉ

### Workflow complet

```
1. Admin confirme le paiement TPE
   ↓
2. POST /admin/pos/order/{order}/confirm-payment
   Body: { transaction_id: '...', receipt_number: '...' }
   ↓
3. Payment mis à jour :
   - status: 'paid'
   - paid_at: now()
   - provider_payment_id: transaction_id
   ↓
4. Commande mise à jour :
   - payment_status: 'paid'
   - status: 'completed'
   ↓
5. Stock décrémenté
   ↓
6. Mouvements stock créés (raison: "Vente en boutique")
   ↓
7. Actions post-paiement déclenchées :
   ├─ Email de confirmation
   ├─ Notification équipe
   ├─ Recherche client
   ├─ Points de fidélité (si client trouvé)
   └─ Notification client
   ↓
8. Commande terminée ✅
```

---

## 📱 SCÉNARIO : PAIEMENT MOBILE MONEY CONFIRMÉ

### Workflow complet

```
1. Client valide le paiement sur son téléphone
   ↓
2. Callback reçu du provider (MTN/Airtel)
   ↓
3. MobileMoneyPaymentService::handleCallback()
   ↓
4. Payment mis à jour :
   - status: 'paid'
   - paid_at: now()
   ↓
5. Commande mise à jour :
   - payment_status: 'paid'
   - status: 'paid'
   ↓
6. OrderObserver déclenché (car payment_status change)
   ↓
7. Stock décrémenté (via StockService)
   ↓
8. Mouvements stock créés (raison: "Vente en ligne")
   ↓
9. Points de fidélité attribués (si user_id existe)
   ↓
10. Notification client envoyée
   ↓
11. Actions post-paiement POS (si commande POS) :
    ├─ Email de confirmation
    └─ Notification équipe
   ↓
12. Commande terminée ✅
```

---

## 🔄 COMPARAISON DES ACTIONS POST-PAIEMENT

| Action | Espèces (POS) | Carte (POS) | Mobile Money (POS) | Vente en ligne |
|--------|---------------|-------------|-------------------|----------------|
| **Stock décrémenté** | ✅ Immédiat | ✅ Après confirmation | ✅ Après callback | ✅ Après paiement |
| **Email confirmation** | ✅ Si email fourni | ✅ Si email fourni | ✅ Si email fourni | ✅ Automatique |
| **Notification équipe** | ✅ Oui | ✅ Oui | ✅ Oui | ✅ Oui |
| **Points fidélité** | ✅ Si client trouvé | ✅ Si client trouvé | ✅ Si client trouvé | ✅ Si user_id existe |
| **Notification client** | ✅ Si client trouvé | ✅ Si client trouvé | ✅ Si client trouvé | ✅ Si user_id existe |
| **Raison mouvement stock** | "Vente en boutique" | "Vente en boutique" | "Vente en ligne"* | "Vente en ligne" |

*Note : Pour Mobile Money POS, le mouvement est créé par l'Observer, donc la raison est "Vente en ligne".

---

## 📊 DÉTAILS DES ACTIONS

### 1. Email de confirmation

**Template** : `App\Mail\OrderConfirmationMail`

**Contenu** :
- Numéro de commande
- Détails des produits
- Montant total
- Informations de livraison
- Instructions de suivi

**Envoi** :
- Automatique si `customer_email` fourni
- Gestion d'erreur silencieuse (log si échec)

### 2. Notification équipe

**Service** : `NotificationService::broadcastToTeam()`

**Destinataires** :
- Tous les utilisateurs avec rôle `admin`, `staff`, `super_admin`

**Message** :
```
Titre: "Nouvelle vente boutique !"
Contenu: "Commande CMD-2025-000001 - 50 000 FCFA"
Type: 'order'
```

### 3. Recherche client

**Méthode** : `handlePostPaymentActions()`

**Critères de recherche** :
1. Par email : `User::where('email', $order->customer_email)`
2. Par téléphone : `User::whereHas('profile', function($q) { $q->where('phone', ...) })`

**Si client trouvé** :
- `order->user_id` mis à jour
- Permet l'attribution de points de fidélité
- Permet les notifications client

### 4. Points de fidélité

**Service** : `LoyaltyService::awardPointsForOrder()`

**Calcul** :
```php
$points = (int) ($order->total_amount * 0.01);
// Exemple: 50 000 FCFA = 500 points
```

**Actions** :
- Création/mise à jour `LoyaltyPoint`
- Incrémentation `points` et `total_earned`
- Mise à jour du `tier` (bronze/silver/gold)
- Création `LoyaltyTransaction` (type: 'earned')

**Tiers** :
- Bronze : < 5 000 points
- Silver : 5 000 - 9 999 points
- Gold : ≥ 10 000 points

### 5. Notification client

**Service** : `NotificationService::success()`

**Message** :
```
Titre: "Paiement reçu !"
Contenu: "Le paiement de votre commande CMD-2025-000001 a été confirmé. Merci !"
Type: 'success'
```

**Condition** : Client trouvé ET `user_id` mis à jour

---

## 🔍 EXEMPLE CONCRET : VENTE EN ESPÈCES

### Scénario
- Client : "Jean Dupont"
- Email : "jean@example.com"
- Téléphone : "+242 06 123 4567"
- Montant : 75 000 FCFA
- Paiement : Espèces

### Actions déclenchées

1. **Commande créée**
   ```
   Order #123
   - order_number: CMD-2025-000123
   - status: completed
   - payment_status: paid
   - total_amount: 75 000
   ```

2. **Payment créé**
   ```
   Payment #45
   - provider: cash
   - status: paid
   - paid_at: 2025-12-08 14:30:00
   ```

3. **Stock décrémenté**
   - Produit A : 10 → 8 (quantité: 2)
   - Produit B : 5 → 4 (quantité: 1)

4. **Mouvements stock créés**
   ```
   ErpStockMovement #100
   - type: out
   - quantity: 2
   - reason: "Vente en boutique"
   - from_location: "Boutique"
   - to_location: "Client"
   ```

5. **Email envoyé**
   - Destinataire : jean@example.com
   - Sujet : "Confirmation de votre commande CMD-2025-000123"

6. **Notification équipe**
   - Broadcast : "Nouvelle vente boutique ! Commande CMD-2025-000123 - 75 000 FCFA"

7. **Client recherché**
   - Trouvé par email : User #15 (jean@example.com)
   - `order->user_id` mis à jour : 15

8. **Points de fidélité**
   - Points calculés : 750 (1% de 75 000)
   - `LoyaltyPoint` mis à jour :
     - points: 1 250 → 2 000
     - total_earned: 5 000 → 5 750
     - tier: silver (maintenu)
   - `LoyaltyTransaction` créé :
     - type: earned
     - points: 750
     - description: "Points gagnés pour la commande #123"

9. **Notification client**
   - User #15 reçoit : "Paiement reçu ! Le paiement de votre commande CMD-2025-000123 a été confirmé."

---

## ⚠️ CAS PARTICULIERS

### Client sans compte

**Scénario** : Client boutique sans compte utilisateur

**Actions** :
- ✅ Email envoyé (si email fourni)
- ✅ Notification équipe
- ❌ Points de fidélité (pas de user_id)
- ❌ Notification client (pas de user_id)

**Recommandation** : Inviter le client à créer un compte pour bénéficier des points de fidélité.

### Client avec compte mais email différent

**Scénario** : Client a un compte mais utilise un autre email en boutique

**Actions** :
- ✅ Email envoyé à l'adresse fournie
- ✅ Recherche par téléphone si email non trouvé
- ✅ Points attribués si client trouvé par téléphone

### Paiement échoué

**Scénario** : Paiement Mobile Money échoué

**Actions** :
- ❌ Stock non décrémenté
- ❌ Points non attribués
- ✅ Notification d'échec envoyée (si user_id existe)
- ⚠️ Commande reste en `pending`

---

## 📝 LOGS ET TRAÇABILITÉ

### Logs créés

```php
// Après paiement espèces
Log::info('POS post-payment actions completed', [
    'order_id' => 123,
    'payment_id' => 45,
    'payment_method' => 'cash',
]);

// Points de fidélité
Log::info('Loyalty points awarded', [
    'user_id' => 15,
    'order_id' => 123,
    'points' => 750,
]);
```

### Traçabilité

Toutes les actions sont traçables via :
- Table `orders` : Statut et historique
- Table `payments` : Détails du paiement
- Table `erp_stock_movements` : Mouvements de stock
- Table `loyalty_transactions` : Historique des points
- Table `notifications` : Notifications envoyées
- Logs Laravel : Actions et erreurs

---

## ✅ CHECKLIST POST-PAIEMENT

Après chaque paiement validé, vérifier :

- [ ] Commande créée avec statut correct
- [ ] Payment créé avec statut `paid`
- [ ] Stock décrémenté correctement
- [ ] Mouvements stock créés
- [ ] Email de confirmation envoyé (si email fourni)
- [ ] Notification équipe envoyée
- [ ] Client recherché et lié (si possible)
- [ ] Points de fidélité attribués (si client trouvé)
- [ ] Notification client envoyée (si client trouvé)
- [ ] Logs créés pour traçabilité

---

**Scénario post-paiement complet implémenté ! ✅**

