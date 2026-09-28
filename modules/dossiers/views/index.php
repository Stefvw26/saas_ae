<?php
// fichier : modules/dossiers/views/index.php — Jalon 7
declare(strict_types=1);

use App\Core\View;

 $filtres = $filtres ?? ['q' => '', 'eleve_id' => '', 'statut' => ''];
 $requete = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);

 $libellesStatut = ['en_cours' => 'En cours', 'valide' => 'Panier validé', 'confirme' => 'À confirmer'];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Dossiers</h1>
        <p class="page-subtitle"><?= (int)$total ?> dossier<?= (int)$total > 1 ? 's' : '' ?> dans votre périmètre.</p>
    </div>
    <?php if (can('dossiers.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/dossiers/creer') ?>">+ Créer un dossier</a>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get" action="<?= url('/dossiers') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Nom, prénom de l'élève…" value="<?= e($filtres['q']) ?>">
    <select class="input" name="statut" aria-label="Statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($libellesStatut as $code => $lib): ?>
            <option value="<?= e($code) ?>" <?= $filtres['statut'] === $code ? 'selected' : '' ?>><?= e($lib) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/dossiers') ?>">Réinitialiser</a>
</form>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun dossier</p>
                <p class="empty-text">Aucun dossier ne correspond à ces critères dans votre périmètre.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Dossier</th><th>Élève</th><th>Statut</th><th>Agence</th><th>Événements</th><th>Ajouté le</th>
                    <?php if (can('dossiers.modifier') || can('dossiers.supprimer')): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $dossier): ?>
                    <tr>
                        <td><strong><a href="<?= url('/dossiers/' . (int)$dossier['id']) ?>">#<?= (int)$dossier['id'] ?></a></strong></td>
                        <td>
                            <a href="<?= url('/eleves/' . (int)$dossier['eleve_id']) ?>">
                                <?= e(trim((string)($dossier['eleve_prenom'] ?? '') . ' ' . (string)$dossier['eleve_nom'])) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge <?= (string)$dossier['statut'] === 'valide' ? 'badge-ok' : ((string)$dossier['statut'] === 'confirme' ? 'badge-brand' : 'badge-muted') ?>">
                                <?= e($libellesStatut[(string)$dossier['statut']] ?? (string)$dossier['statut']) ?>
                            </span>
                        </td>
                        <td><?= e($dossier['agence_nom'] ?? '—') ?></td>
                        <td><?= (int)($dossier['nb_evenements'] ?? 0) ?></td>
                        <td><?= e((string)$dossier['ajout_le']) ?></td>
                        <?php if (can('dossiers.modifier') || can('dossiers.supprimer')): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <?php if (can('dossiers.modifier')): ?>
                                        <a class="btn btn-sm btn-ghost" href="<?= url('/dossiers/' . (int)$dossier['id'] . '/modifier') ?>">Modifier</a>
                                    <?php endif; ?>
                                    <?php if (can('dossiers.supprimer')): ?>
                                        <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/archiver') ?>"
                                              data-confirm="Archiver le dossier #<?= (int)$dossier['id'] ?> ?">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger" type="submit">Archiver</button>
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
    'baseUrl' => '/dossiers',
    'query'   => $requete,
]);
?>
<!-- AE-EOF : le fichier dossiers/views/index.php doit se terminer exactement ici -->