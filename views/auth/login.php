<?php
// fichier : views/auth/login.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Core\Session;

 $old     = Session::getFlash('old', []);
if (!is_array($old)) {
    $old = [];
}
 $oldLogin = (string)($old['login'] ?? '');
 $phrase   = (string)($phraseOuverture ?? '');
?>
<div class="auth-brand">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
        <circle cx="12" cy="12" r="3.2" fill="currentColor"/>
        <path d="M12 2v6.5M12 15.5V22M2 12h6.5M15.5 12H22" stroke="currentColor" stroke-width="2"/>
    </svg>
    <h1>Gestion de votre auto-école</h1>
    <p>Accédez à votre espace de travail.</p>
    <?php if ($phrase !== ''): ?>
        <p><em>« <?= e($phrase) ?> »</em></p>
    <?php endif; ?>
</div>

<form method="post" action="<?= url('/connexion') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label class="form-label" for="login">Identifiant</label>
        <input class="input" type="text" id="login" name="login"
               value="<?= e($oldLogin) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
        <label class="form-label" for="password">Mot de passe</label>
        <input class="input" type="password" id="password" name="password"
               autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
</form>