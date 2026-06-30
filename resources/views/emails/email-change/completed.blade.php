<x-mail::message>
# Changement d'email confirmé

Bonjour {{ $user->name }},

Votre adresse email a été **mise à jour avec succès** sur votre compte RACINE BY GANDA.

**Ancien email :** {{ $oldEmail }}
**Nouveau email :** {{ $newEmail }}

Vous pouvez maintenant vous connecter avec votre nouvelle adresse email.

<x-mail::button :url="route('login')">
Se connecter
</x-mail::button>

**Mesure de sécurité :**
Si vous n'êtes pas à l'origine de ce changement, contactez immédiatement notre support pour bloquer votre compte.

Cordialement,
L'équipe RACINE BY GANDA
</x-mail::message>
