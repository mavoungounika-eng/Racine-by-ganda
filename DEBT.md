# Dette technique connue — Racine by Ganda

> Registre des problèmes identifiés mais volontairement non corrigés (hors périmètre courant).
> Chaque entrée doit préciser : symptôme, cause, périmètre, décision.

---

## D1 — LogoutTest::test_admin_logout_redirects_to_login (préexistant)

- **Symptôme** : le test attend une redirection vers `http://localhost/login`, mais reçoit `http://localhost/admin/login`.
- **Fichier** : `tests/Feature/Auth/LogoutTest.php:65`
- **Statut** : **préexistant** — échoue déjà au commit `3ff2098f` (avant le travail OAuth du 2026-06-13). Ce n'est **pas** une régression du refactor slug `createur` / suppression Apple-Facebook.
- **Contrainte** : `LogoutTest.php` est un **fichier protégé** (CLAUDE.md RÈGLE 4) — ne pas modifier sans décision explicite.
- **Cause probable** : divergence entre la route de logout admin (redirige vers `admin.login`) et l'attente du test (`login`). À trancher : soit le test reflète une intention obsolète, soit la logique de redirection logout admin a changé.
- **Décision (2026-06-13)** : **pas de fix maintenant**. Documenté comme dette. À reprendre dans un lot dédié « auth logout redirect ».
- **Référence run global au moment du constat** : 988 tests, 8 skipped, 1 failure (celui-ci uniquement).

> ⚠️ Note : les compteurs de référence dans `CLAUDE.md` (« 977 tests, 0 failures ») et
> `.claude/rules/tests.md` (« 926 tests, 2 failures ») sont désynchronisés de l'environnement
> réel (988 tests). À resynchroniser lors d'un prochain passage de stabilisation.
- [ ] racine_testing DB: migrations incohérentes / deadlock MySQL au reset, bloque PosDeviceAuthTest et probablement d'autres. Pré-existant, pas lié au correctif device/verify (2026-06-26).
- [ ] POS PaymentView : formulaire de paiement reste visible/cliquable en transparence derrière la modal du reçu post-vente (bouton 'Confirmer le paiement' visible en arrière-plan). Non bloquant fonctionnellement (garde-fou cart.items.length===0 empêche la double soumission), mais cosmétique à nettoyer — masquer .payment-shell pendant confirmationStage==='success'|'receipt'. (2026-06-28)
