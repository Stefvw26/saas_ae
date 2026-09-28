<?php
// fichier : views/errors/419.php
declare(strict_types=1);

 $titre   = $titre   ?? 'Session expirée';
 $message = $message ?? 'Votre session a expiré ou le formulaire est invalide.';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 · <?= e($titre) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center">
<div class="container text-center py-5" style="max-width:520px">
    <p class="display-5 fw-bold text-primary mb-0">419</p>
    <h1 class="h4 mb-3"><?= e($titre) ?></h1>
    <p class="text-muted mb-4"><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= url('/connexion') ?>">Retour à la connexion</a>
</div>
</body>
</html>