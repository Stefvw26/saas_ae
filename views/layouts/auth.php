<?php
// fichier : views/layouts/auth.php
declare(strict_types=1);

use App\Core\Config;
use App\Core\View;

 $title   = $title ?? '';
 $appName = (string)Config::get('app.name', 'CRM Auto-École');
?>
<?php View::partial('partials/head', ['title' => $title, 'appName' => $appName, 'bodyClass' => 'auth-body']); ?>
<main class="auth-shell">
    <div class="auth-brand">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="3.2" fill="currentColor"/>
            <path d="M12 2v6.5M12 15.5V22M2 12h6.5M15.5 12H22" stroke="currentColor" stroke-width="2"/>
        </svg>
        <h1><?= e($appName) ?></h1>
        <p>Gestion de votre auto-école</p>
    </div>
    <?php View::partial('partials/flash'); ?>
    <div class="auth-card"><?= $content ?></div>
    <p class="auth-foot">Socle MVC · Jalon 1</p>
</main>
<?php View::partial('partials/footer', ['appName' => $appName]); ?>