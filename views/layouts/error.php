<?php
// fichier : views/layouts/error.php
declare(strict_types=1);

use App\Core\View;

 $title = $title ?? 'Erreur';
?>
<?php View::partial('partials/head', ['title' => $title, 'bodyClass' => 'error-body']); ?>
<main class="error-shell"><?= $content ?></main>
<?php View::partial('partials/footer'); ?>