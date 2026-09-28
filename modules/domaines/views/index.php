<?php
// fichier : modules/domaines/views/index.php
declare(strict_types=1);

use App\Core\View;
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Domaines</h1>
        <p class="page-subtitle">Référentiel des domaines — <?= (int)$total ?> domaine<?= (int)$total > 1 ? 's' : '' ?>.</p>
    </div>
    <?php if (can('domaines.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/domaines/creer') ?>">+ Créer un domaine</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun domaine</p>
                <p class="empty-text">Créez un premier domaine pour l'affecter aux utilisateurs et agences.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Nom</th><th>Initiale</th><th>Descriptif</th><th>Statut</th><th>Ajouté le</th>
                    <?php if (can('domaines.modifier') || can('domaines.supprimer')): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $d): ?>
                    <tr>
                        <td>
                            <span class="dot" style="background-color:<?= e($d['couleur'] ?: '#64748b') ?>"></span>
                            <strong><?= e($d['nom']) ?></strong>
                        </td>
                        <td><?= $d['initiale'] !== null ? '<span class="badge badge-muted">' . e($d['initiale']) . '</span>' : '—' ?></td>
                        <td><?= e($d['descriptif'] ?? '—') ?></td>
                        <td><?= (int)$d['actif'] === 1
                            ? '<span class="badge badge-ok">Actif</span>'
                            : '<span class="badge badge-danger">Inactif</span>' ?></td>
                        <td><?= e($d['ajout_le']) ?></td>
                        <?php if (can('domaines.modifier') || can('domaines.supprimer')): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <?php if (can('domaines.modifier')): ?>
                                        <a class="btn btn-sm btn-ghost" href="<?= url('/domaines/' . (int)$d['id'] . '/modifier') ?>">Modifier</a>
                                    <?php endif; ?>
                                    <?php if (can('domaines.supprimer')): ?>
                                        <form method="post" action="<?= url('/domaines/' . (int)$d['id'] . '/supprimer') ?>"
                                              data-confirm="Supprimer (archiver) le domaine « <?= e($d['nom']) ?> » ?">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger" type="submit">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
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
    'query'   => [],
]);
?>