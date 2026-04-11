# 🔄 MISE À JOUR DU MODÈLE USER POUR OAUTH ACCOUNTS

## 📝 Instructions

Quand vous implémenterez le module Social Auth v2, ajoutez cette relation au modèle `User` :

### Fichier : `app/Models/User.php`

**Ajouter dans la classe User :**

```php
/**
 * Get the OAuth accounts for this user.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function oauthAccounts()
{
    return $this->hasMany(OauthAccount::class);
}

/**
 * Get the primary OAuth account for this user.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function primaryOauthAccount()
{
    return $this->hasOne(OauthAccount::class)->where('is_primary', true);
}

/**
 * Get OAuth account by provider.
 * 
 * @param string $provider
 * @return OauthAccount|null
 */
public function getOauthAccount(string $provider): ?OauthAccount
{
    return $this->oauthAccounts()->where('provider', $provider)->first();
}

/**
 * Check if user has OAuth account for provider.
 * 
 * @param string $provider
 * @return bool
 */
public function hasOAuthAccount(string $provider): bool
{
    return $this->oauthAccounts()->where('provider', $provider)->exists();
}
```

**Note :** Ces méthodes sont **optionnelles** et peuvent être ajoutées progressivement selon les besoins.

---

## 🔗 Relation avec l'existant

Le modèle User garde sa colonne `google_id` pour compatibilité avec le module Google Auth v1.

Les deux systèmes peuvent coexister :
- `users.google_id` → Module Google Auth v1 (existant)
- `oauth_accounts` → Module Social Auth v2 (nouveau)

