<?php
// fichier : modules/administration/views/logs/index.php
declare(strict_types=1);

use App\Core\View;

 $filtres = $filtres ?? [];
 $requete = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Journaux d'activité</h1>
        <p class="page-subtitle"><?= (int)$total ?> événement<?= (int)$total > 1 ? 's' : '' ?> journalisés dans votre périmètre.</p>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= url('/administration/journaux') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Login, objet…" value="<?= e($filtres['q']) ?>">
    <select class="input" name="action" aria-label="Action">
        <option value="">Toutes les actions</option>
        <?php foreach ($actions as $action): ?>
            <option value="<?= e($action) ?>" <?= $filtres['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="input" type="date" name="de" value="<?= e($filtres['de']) ?>" aria-label="Du">
    <input class="input" type="date" name="a" value="<?= e($filtres['a']) ?>" aria-label="Au">
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/administration/journaux') ?>">Réinitialiser</a>
</form>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun événement</p>
                <p class="empty-text">Aucun événement ne correspond à ces critères.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Agence</th><th>Résultat</th><th>Infos</th></tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $log):
                    $infos = (string)($log['infos'] ?? '');
                    $resume = mb_strlen($infos) > 60 ? mb_substr($infos, 0, 60) . '…' : $infos; ?>
                    <tr>
                        <td><?= e($log['cree_le']) ?></td>
                        <td><?= e($log['login'] ?? '—') ?></td>
                        <td><span class="badge badge-brand"><?= e($log['action']) ?></span></td>
                        <td><?= e($log['objet'] ?? '—') ?><?= $log['objet_id'] !== null ? ' #' . (int)$log['objet_id'] : '' ?></td>
                        <td><?= $log['agence_id'] !== null ? '#' . (int)$log['agence_id'] : '—' ?></td>
                        <td><?= $log['resultat'] === 'succes'
                            ? '<span class="badge badge-ok">Succès</span>'
                            : '<span class="badge badge-danger">Refusé</span>' ?></td>
                        <td><?= $resume !== '' ? '<code title="' . e($infos) . '">' . e($resume) . '</code>' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
View::partial('partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'baseUrl' => $baseUrl,
    'query'   => $requete,
]);
?>