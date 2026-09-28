<?php
// fichier : modules/administration/views/parametres/index.php — v0.16 (onglets)
declare(strict_types=1);

 $categories = $categories ?? [];
 $groupes    = $groupes ?? [];
 $valeurs    = $valeurs ?? [];
 $modifiable = can('parametres.modifier');

/* Ne conserver que les catégories réellement peuplées. */
 $categoriesPleines = [];
foreach ($categories as $cle => $libelle) {
    if (!empty($groupes[$cle])) {
        $categoriesPleines[$cle] = $libelle;
    }
}
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Paramètres</h1>
        <p class="page-subtitle">Réglages généraux du client (par tenant), classés par catégorie.</p>
    </div>
</div>

<form class="card form-stack" method="post" action="<?= url('/administration/parametres') ?>">
    <?= csrf_field() ?>

    <div class="card-body">
        <div class="tabs" role="tablist">
            <?php $premiere = true; foreach ($categoriesPleines as $cleCat => $libelleCat): ?>
                <button type="button"
                        class="tab-btn<?= $premiere ? ' active' : '' ?>"
                        data-tab="tab-<?= e($cleCat) ?>"
                        role="tab"
                        <?= $premiere ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
                    <?= e($libelleCat) ?>
                </button>
            <?php $premiere = false; endforeach; ?>
        </div>

        <?php $premiere = true; foreach ($categoriesPleines as $cleCat => $libelleCat): ?>
            <div class="tab-panel<?= $premiere ? ' active' : '' ?>" id="tab-<?= e($cleCat) ?>" role="tabpanel">
                <div class="table-overflow">
                    <table class="table">
                        <tbody>
                        <?php foreach ($groupes[$cleCat] as $cle => [$libelle, $type]):
                            $valeur = $valeurs[$cle] ?? null; ?>
                            <tr>
                                <td>
                                    <?= e($libelle) ?>
                                    <small class="perm-code"><?= e($cle) ?></small>
                                </td>
                                <td class="cell-actions">
                                    <?php if ($type === 'booleen'): ?>
                                        <label class="switch-item">
                                            <input type="checkbox" name="<?= e($cle) ?>" value="1" <?= $valeur === true ? 'checked' : '' ?>>
                                            <span><?= $valeur === true ? 'Activé' : 'Désactivé' ?></span>
                                        </label>
                                    <?php elseif ($type === 'entier'): ?>
                                        <input class="input input-sm" type="number" min="1" name="<?= e($cle) ?>"
                                               value="<?= e((string)(int)$valeur) ?>" <?= $modifiable ? '' : 'disabled' ?>>
                                    <?php else: ?>
                                        <input class="input input-sm" type="text" name="<?= e($cle) ?>"
                                               value="<?= e((string)$valeur) ?>" <?= $modifiable ? '' : 'disabled' ?>>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php $premiere = false; endforeach; ?>

        <p class="form-text">
            Un seul bouton « Enregistrer » sauvegarde l'ensemble des onglets. Certaines cleurs concernent des
            fonctionnalités livrées aux jalons prévus (phrases d'ouverture : J3, anniversaires : J4, IA : J12) —
            elles sont conservées dès maintenant pour rester centralisées.
        </p>
    </div>

    <?php if ($modifiable): ?>
        <div class="card-body form-actions">
            <button class="btn btn-primary" type="submit">Enregistrer les paramètres</button>
        </div>
    <?php endif; ?>
</form>