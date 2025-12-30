# 🧠 RAPPEL OFFICIEL DE FONCTIONNEMENT — À DESTINATION DE TOUTE IA / IDE

### *(Règles obligatoires à respecter avant toute action dans RACINE-BACKEND)*

---

# 🔥 **1. IDENTITÉ DU PROJET**

Vous travaillez sur le projet : **RACINE-BACKEND**, un écosystème modulaire complet comprenant :

* Frontend e-commerce (clients)
* Backoffice interne (équipe)
* Auth multi-rôle (5 rôles)
* ERP
* CRM
* Assistant IA Amira
* Notifications internes
* Layout professionnel interne

**L’architecture est modulaire. Aucun code ne doit être ajouté dans le monolithe sans justification.**

---

# 🔥 **2. PHILOSOPHIE DE TRAVAIL (OBLIGATOIRE)**

Avant d’écrire une seule ligne de code :
➡️ **Toujours analyser** la demande
➡️ **Toujours identifier la phase**
➡️ **Toujours expliquer l’impact**
➡️ **Toujours respecter la structure modulaire**
➡️ **Toujours produire un rapport technique clair**
➡️ **Toujours proposer la suite logique**

Aucune IA n’a le droit d’exécuter sans suivre ces règles.

---

# 🔥 **3. ORGANISATION PAR PHASES**

Le projet avance **phase par phase**, toujours sous ce format :

### 🔹 1️⃣ Objectif
### 🔹 2️⃣ Actions prévues
### 🔹 3️⃣ Fichiers modifiés/créés
### 🔹 4️⃣ Tests à effectuer
### 🔹 5️⃣ Impacts sur le système
### 🔹 6️⃣ Rapport technique final
### 🔹 7️⃣ Proposition de phase suivante

Aucune IA ne doit faire une intervention sans produire ce rapport.

---

# 🔥 **4. RÔLES ET ACCÈS À RESPECTER**

Il existe 5 rôles :

| Rôle        | Accès              |
| ----------- | ------------------ |
| super_admin | Tout               |
| admin       | ERP + CRM + Admin  |
| staff       | ERP + CRM          |
| createur    | Dashboard créateur |
| client      | Dashboard client   |

### OBLIGATION IA :

✔ Ne jamais donner accès à un rôle qui n’y a pas droit
✔ Toujours respecter la matrice
✔ Toujours contrôler les middlewares, Gates et policies

---

# 🔥 **5. ARCHITECTURE MODULAIRE (RÈGLE D’OR)**

Toute IA doit respecter la structure :

```
modules/
  ERP/
  CRM/
  Assistant/
  Auth/
  Notifications/
  Frontend/
```

### Rappels :

✔ Un module = routes + contrôleurs + vues + services
✔ Aucun mélange entre backoffice et frontend public
✔ Pas de Blade non organisé
✔ Pas de logique métier dans les vues

---

# 🔥 **6. RÈGLES POUR MODIFIER DU CODE**

Toute IA doit respecter les règles suivantes :

### ✔ Toujours analyser si le fichier appartient :

* au frontend
* au backend admin
* à un module ERP/CRM
* à Amira
* aux notifications

### ✔ Toujours utiliser les layouts existants :

* `layouts.frontend.blade.php`
* `layouts.internal.blade.php`

### ✔ Toujours utiliser les services existants

### ✔ Jamais écrire directement dans un contrôleur ce qui doit être dans un Service

### ✔ Toujours documenter les modifications

---

# 🔥 **7. AMIRA IA : RÈGLE SPÉCIALE**

Amira IA doit toujours garder :

* détection d’intentions
* commandes `/`
* accès ERP/CRM réservé équipe
* réponses contextualisées
* mode mock par défaut
* respect des limites daily

Aucune modification de sa logique sans explication.

---

# 🔥 **8. NOTIFICATIONS INTERNES (RÈGLE SPÉCIALE)**

Toute IA doit respecter :

* système de notifications push
* observers pour commandes + stocks
* widget intégré dans layout internal
* modèle + service + controller

Aucune IA ne doit casser ou dupliquer ce système.

---

# 🔥 **9. RÈGLES DE SÉCURITÉ**

Toute IA doit vérifier :

### ✔ Auth middleware
### ✔ Gate access
### ✔ Policies
### ✔ CSRF tokens
### ✔ Validation des données
### ✔ Protection contre régression

Aucun code ne doit introduire un risque de sécurité.

---

# 🔥 **10. RÈGLE ABSOLUE : AUCUNE RÉGRESSION**

Toute IA doit garantir :

* Pas de suppression de fichier essentiel
* Pas d’écrasement d’un module
* Pas de suppression de lignes sensibles
* Pas de modification du comportement existant
* Pas de migration destructive sans validation

🌟 **Toute action doit être ADDITIVE, jamais destructive.**

---

# 🔥 **11. AVANT CHAQUE INTERVENTION L’IA DOIT DIRE :**

> **“Je confirme que je vais respecter :
> – l’architecture modulaire
> – la logique des phases
> – la matrice d’accès
> – la politique de sécurité
> – le principe 0 régression
> – et le ton premium RACINE”**

Ensuite seulement elle peut commencer.

---

# 🔥 **12. DOIT TOUJOURS PRODUIRE UN RAPPORT COMME CECI :**

```
# 📌 Rapport Phase X — Titre
## 1. Objectif
## 2. Actions exécutées
## 3. Fichiers créés/modifiés
## 4. Tests recommandés
## 5. Impacts
## 6. Conclusion
## 7. Proposition Phase suivante
```

Aucune intervention de code sans ce rapport.
