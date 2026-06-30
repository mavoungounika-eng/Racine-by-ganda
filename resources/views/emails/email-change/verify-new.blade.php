<x-mail::message>
# Vérification de votre nouveau email

Bonjour {{ $user->name }},

Une demande de changement d'email a été effectuée sur votre compte RACINE BY GANDA.

**Cette adresse ({{ $emailChange->new_email }})** deviendra votre nouvelle adresse email principale.

Pour finaliser ce changement, vous devez vérifier les **deux adresses email**.

Cliquez sur le bouton ci-dessous pour vérifier votre **nouveau email** :

<x-mail::button :url="$verificationUrl">
Vérifier le nouveau email
</x-mail::button>

**Ce lien expirera dans 24 heures.**

Si vous n'avez pas demandé ce changement, ignorez cet email et contactez immédiatement notre support.

Cordialement,
L'équipe RACINE BY GANDA

---

<small>Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</small>
<small>{{ $verificationUrl }}</small>
</x-mail::message>
