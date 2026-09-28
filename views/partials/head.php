<?php
// fichier : views/partials/head.php — CRM Auto-École (renvoi complet)
declare(strict_types=1);

use App\Core\Config;

 $appName   = $appName ?? (string)Config::get('app.name', 'CRM Auto-École');
 $title     = $title ?? 'Accueil';
 $bodyClass = $bodyClass ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='12' r='10' fill='%23101c30'/%3E%3Ccircle cx='12' cy='12' r='4' fill='%23fff'/%3E%3C/svg%3E">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::currentToken() ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<!-- AE-EOF : le fichier partials/head.php doit se terminer exactement ici -->