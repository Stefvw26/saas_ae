<?php
// fichier : views/layouts/app.php — CRM Auto-École (renvoi complet)
declare(strict_types=1);

use App\Core\Config;
use App\Core\View;

/** @var string $content */
 $user    = $user ?? [];
 $appName = (string)Config::get('app.name', 'CRM Auto-École');
?>
<?php View::partial('partials/head', ['title' => $title ?? '', 'appName' => $appName, 'bodyClass' => 'app-body']); ?>
<?php View::partial('partials/sidebar', ['user' => $user]); ?>
<div class="main-area">
    <?php View::partial('partials/topbar', ['title' => $title ?? '']); ?>
    <main class="content">
        <?php View::partial('partials/flash'); ?>
        <?= $content ?? '' ?>
    </main>
    <?php View::partial('partials/footer', ['appName' => $appName]); ?>
</div>
<!-- AE-EOF : le fichier layouts/app.php doit se terminer exactement ici -->