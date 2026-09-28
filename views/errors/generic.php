<?php
// fichier : views/errors/generic.php
declare(strict_types=1);

 $code    = $code ?? 404;
 $titre   = $titre ?? 'Erreur';
 $message = $message ?? '';
 $link    = $link ?? '/';
?>
<p class="error-code"><?= e((string)$code) ?></p>
<h1 class="error-title"><?= e($titre) ?></h1>
<?php if ($message !== ''): ?>
    <p class="error-text"><?= e($message) ?></p>
<?php endif; ?>
<a class="btn btn-primary" href="<?= url($link) ?>">Retour à l'accueil</a>