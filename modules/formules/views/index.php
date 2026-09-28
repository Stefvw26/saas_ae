<?php
// fichier : modules/formules/views/index.php — v0.49
declare(strict_types=1);

use App\Core\View;

 $filtres = $filtres ?? ['q' => ''];
 $requete = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);

 $euro = static function ($v): string {
    return number_format((float)($v ?? 0), 2, ',', ' ') . ' €';
};
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Formules</h1>
        <p class="page-subtitle">
            <?= (int)$total ?> formule<?= (int)$total > 1 ? 's' : '' ?> —
            le montant TTC est la somme des prestations de la formule.
        </p>
    </div>
    <?php if (can('formules.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/formules/creer') ?>">+ Créer une formule</a>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get" action="<?= url('/formules') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Nom de la formule…" value="<?= e($filtres['q']) ?>">
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/formules') ?>">Réinitialiser</a>
</form>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucune formule</p>
                <p class="empty-text">Aucune formule ne correspond à ces critères dans votre périmètre.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Formule</th><th>Domaine</th><th class="center">Prestations</th><th>Montant TTC</th><th>Statut</th><th>Ajoutée le</th>
                    <?php if (can('formules.modifier') || can('formules.supprimer')): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $f):
                    $nbP = (int)($f['nb_prestations'] ?? 0);
                    $descriptif = mb_substr((string)($f['descriptif'] ?? ''), 0, 70);
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?= url('/formules/' . (int)$f['id']) ?>"><?= e((string)$f['nom']) ?></a></strong>
                            <?php if ($descriptif !== ''): ?>
                                <small style="display:block;color:var(--ink-3);font-size:.76rem"><?= e($descriptif) ?><?= mb_strlen((string)($f['descriptif'] ?? '')) > 70 ? '…' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($f['domaine_nom'] !== null): ?>
                                <span class="badge-entite" style="background-color:<?= e($f['domaine_couleur'] ?: '#64748b') ?>"><?= e((string)$f['domaine_nom']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="center"><?= $nbP ?></td>
                        <td><strong><?= e($euro($f['montant_ttc'] ?? 0)) ?></strong></td>
                        <td><?= (int)$f['actif'] === 1
                            ? '<span class="badge badge-ok">Active</span>'
                            : '<span class="badge badge-danger">Inactive</span>' ?></td>
                        <td><?= e((string)$f['ajout_le']) ?></td>
                        <?php if (can('formules.modifier') || can('formules.supprimer')): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <?php if (can('formules.modifier')): ?>
                                        <a class="btn btn-sm btn-ghost" href="<?= url('/formules/' . (int)$f['id'] . '/modifier') ?>">Modifier</a>
                                    <?php endif; ?>
                                    <?php if (can('formules.supprimer')): ?>
                                        <form method="post" action="<?= url('/formules/' . (int)$f['id'] . '/supprimer') ?>"
                                              data-confirm="Supprimer (archiver) la formule « <?= e((string)$f['nom']) ?> » ?">
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
    'baseUrl' => '/formules',
    'query'   => $requete,
]);
?>
<!-- AE-EOF : le fichier formules/views/index.php doit se terminer exactement ici -->