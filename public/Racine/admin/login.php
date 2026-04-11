<?php
session_start();
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $motdepasse = $_POST['motdepasse'] ?? '';

    if ($email === 'admin@racine.com' && $motdepasse === '15363615@Bdp') {
        $_SESSION['admin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $erreur = "Identifiants invalides.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Connexion Admin</title></head>
<body>
<h2>Connexion Administrateur</h2>
<form method="post">
    <input type="email" name="email" placeholder="Email admin" required><br>
    <input type="password" name="motdepasse" placeholder="Mot de passe" required><br>
    <button type="submit">Se connecter</button>
</form>
<?php if ($erreur): ?><p style="color:red"><?= $erreur ?></p><?php endif; ?>
</body>
</html>
