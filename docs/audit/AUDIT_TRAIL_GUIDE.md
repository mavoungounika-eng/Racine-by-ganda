# Audit Trail (Compliance Logging) Guide
**Version 1.0 (Production-Ready)**

Dans le cadre de la Phase 3 (Infrastructure de monitoring), le backend intègre un système d'Audit Trail global pour enregistrer toutes les actions de création, de modification et de suppression sur les entités sensibles, afin de répondre aux exigences de conformité.

---

## 🔒 Masquage des Données Sensibles
Le système d'audit est conçu avec la sécurité par défaut. Avant d'être insérées en base (`metadata` en JSON dans `audit_logs`), les données sont filtrées par la méthode `AuditObserver::maskSensitiveAttributes()`.

**Les champs suivants sont toujours remplacés par la valeur `[REDACTED]` :**
- `password`, `remember_token`, `api_token`
- `two_factor_secret`, `two_factor_recovery_codes`
- `stripe_id`, `trusted_device_token`, `bank_account_details`
- + tout attribut défini dans le tableau `$hidden` d'un modèle Eloquent.

---

## 📊 Modèles Audités

L'observation est activée globalement dans `app/Providers/EventServiceProvider.php`.
Les modèles suivants génèrent automatiquement des logs d'audit sur les événements `created`, `updated` et `deleted` :
* `App\Models\User`
* `App\Models\Order`
* `App\Models\Payment`
* `App\Models\Product`
* `App\Models\Role`
* `App\Models\CreatorProfile`

### Comment auditer un nouveau modèle ?
Il n'y a pas besoin de modifier l'`AuditObserver`. Il suffit d'ajouter la déclaration dans `EventServiceProvider.php` dans la méthode `boot()` :
```php
if (class_exists(\App\Models\MyNewModel::class)) {
    \App\Models\MyNewModel::observe(\App\Observers\AuditObserver::class);
}
```

---

## 💻 Structure de la Table (`audit_logs`)

La migration correspondante est `2026_01_30_120000_create_audit_logs_table`.

| Colonne | Description |
|---|---|
| `action` | Type d'action (created, updated, deleted, refund_created, etc.) |
| `entity_type` | Le `class_basename()` du modèle (ex: 'User', 'Order') |
| `entity_id` | Identifiant primaire de l'entité |
| `user_id` | ID de l'utilisateur ayant déclenché l'action (via `Auth::user()`) |
| `ip_address` | Adresse IP de la requête (`Request::ip()`) |
| `user_agent` | Navigateur / Client utilisé (`Request::userAgent()`) |
| `metadata` | Charge utile JSON : `old_attributes` / `new_attributes` / `changes` |

---

## 🔍 Comment consulter les logs ?

Le modèle `AuditLog` propose différents Scopes ("Filtres") puissants pour vos requêtes :

```php
// Trouver tous les logs relatifs à l'utilisateur ID #5
AuditLog::for('User', 5)->get();

// Tous les logs générés par l'admin ID #1
AuditLog::byUser(1)->get();

// Requêtes récentes (- 24h) sur les paiements
AuditLog::entity('Payment')->recent(24)->get();
```

---

## ⏱️ Politique de Rétention

Pour éviter que la base de données ne gonfle indéfiniment tout en respectant les principes de traçabilité d'entreprise, la table est dite **"Prunable"**. Les logs de plus d'1 an peuvent être effacés de la base :

Lancer en CRON (via le scheduler Laravel) :
```bash
php artisan model:prune --model="App\Models\AuditLog"
```
