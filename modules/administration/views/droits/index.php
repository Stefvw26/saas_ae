<?php
// fichier : modules/administration/views/droits/index.php — v0.25 (catégories repliables)
declare(strict_types=1);

 $roles       = $roles ?? [];
 $permissions = $permissions ?? [];
 $matrice     = $matrice ?? [];

/* Regrouper les permissions par catégorie (module). */
 $categories = [];
foreach ($permissions as $permission) {
    $categories[(string)$permission['module']][] = $permission;
}
ksort($categories);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Rôles et droits</h1>
        <p class="page-subtitle">
            Créez vos propres rôles puis attribuez-leur des droits par catégorie —
            contrôlé côté serveur à chaque action.
        </p>
    </div>
</div>

<?php if (can('roles.creer')): ?>
    <form class="card form-stack" method="post" action="<?= url('/administration/droits/role') ?>">
        <?= csrf_field() ?>
        <div class="card-header">Créer un rôle personnalisé</div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field">
                    <label class="form-label" for="role_nom">Intitulé du rôle <span class="req">*</span></label>
                    <input class="input" type="text" id="role_nom" name="nom" maxlength="50" required
                           placeholder="Ex. : Superviseur" value="<?= e(old('nom')) ?>">
                    <?php if (form_error('nom')): ?><p class="form-error"><?= e(form_error('nom')) ?></p><?php endif; ?>
                    <p class="form-text">Un code technique est dérivé automatiquement (sans accents ni espaces).</p>
                </div>
                <div class="field">
                    <label class="form-label" for="role_descriptif">Descriptif</label>
                    <input class="input" type="text" id="role_descriptif" name="descriptif" maxlength="255"
                           value="<?= e(old('descriptif')) ?>">
                    <?php if (form_error('descriptif')): ?><p class="form-error"><?= e(form_error('descriptif')) ?></p><?php endif; ?>
                </div>
            </div>
            <p class="form-text">
                Le rôle créé apparaît immédiatement comme colonne de la matrice ci-dessous et dans le
                sélecteur de rôle des fiches utilisateurs.
            </p>
        </div>
        <div class="card-body form-actions">
            <button class="btn btn-primary" type="submit">Créer le rôle</button>
        </div>
    </form>
<?php endif; ?>

<form class="card form-stack" method="post" action="<?= url('/administration/droits') ?>">
    <?= csrf_field() ?>
    <div class="card-body">
        <p class="form-text">
            Dépliez une catégorie pour afficher et cocher ses permissions.
            Un seul bouton « Enregistrer » sauvegarde l'ensemble des catégories.
        </p>

        <?php $i = 0; foreach ($categories as $categorie => $permsCat): $i++; ?>
            <div class="matrix-group<?= $i === 1 ? ' open' : '' ?>">
                <button type="button" class="matrix-toggle" data-matrix-group aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>">
                    <span><?= e(ucfirst($categorie)) ?> <small>(<?= count($permsCat) ?> permission<?= count($permsCat) > 1 ? 's' : '' ?>)</small></span>
                    <svg class="nav-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </button>
                <div class="matrix-body table-overflow">
                    <table class="table table-droits">
                        <thead>
                        <tr>
                            <th>Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="center"><?= e($role['nom']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($permsCat as $permission): ?>
                            <tr>
                                <td><?= e($permission['libelle']) ?><small class="perm-code"><?= e($permission['code']) ?></small></td>
                                <?php foreach ($roles as $role):
                                    $cochee = in_array($permission['code'], $matrice[(int)$role['id']] ?? [], true);
                                    $verrou = $role['code'] === 'administrateur'
                                        && in_array($permission['code'], ['administration.acceder', 'droits.modifier'], true);
                                    ?>
                                    <td class="center">
                                        <input type="checkbox"
                                               name="droit[<?= (int)$role['id'] ?>][<?= (int)$permission['id'] ?>]" value="1"
                                               <?= $cochee ? 'checked' : '' ?>
                                               <?= $verrou ? 'disabled title="Verrou anti-verrouillage (administrateur)"' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (can('droits.modifier')): ?>
        <div class="card-body form-actions">
            <button class="btn btn-primary" type="submit">Enregistrer la matrice</button>
        </div>
    <?php endif; ?>
</form>
<!-- AE-EOF : le fichier droits/index.php doit se terminer exactement ici -->