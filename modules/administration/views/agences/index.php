<?php
// fichier : modules/administration/views/agences/index.php — v0.16
declare(strict_types=1);

use App\Core\View;

 $active = agence_active();
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Agences</h1>
        <p class="page-subtitle">
            <?= (int)$total ?> agence<?= (int)$total > 1 ? 's' : '' ?> —
            périmètre : <?= $active !== null ? e($active['agence_nom']) : 'toutes les agences' ?>
        </p>
    </div>
    <?php if (can('agences.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/administration/agences/creer') ?>">+ Créer une agence</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucune agence</p>
                <p class="empty-text">Aucune agence ne correspond à votre périmètre.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Agence</th><th>Initiale</th><th>Adresse</th><th>Téléphone</th><th>Statut</th>
                    <?php if (can('agences.modifier')): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $a): ?>
                    <tr>
                        <td>
                            <span class="dot" style="background-color:<?= e($a['agence_couleur'] ?: '#64748b') ?>"></span>
                            <strong><?= e($a['agence_nom']) ?></strong>
                        </td>
                        <td><?= $a['agence_initiale'] !== null ? '<span class="badge badge-muted">' . e($a['agence_initiale']) . '</span>' : '—' ?></td>
                        <td><?= e($a['agence_adresse'] ?? '—') ?></td>
                        <td><?= e($a['agence_telephone'] ?? '—') ?></td>
                        <td><?= (int)$a['actif'] === 1
                            ? '<span class="badge badge-ok">Active</span>'
                            : '<span class="badge badge-danger">Inactive</span>' ?></td>
                        <?php if (can('agences.modifier')): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-ghost" href="<?= url('/administration/agences/' . (int)$a['id'] . '/modifier') ?>">Modifier</a>
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