<?php
// fichier : modules/domaines/views/form.php — v0.29 (sheet)
declare(strict_types=1);

 $domaine  = $domaine ?? null;
 $erreurs  = form_errors();
 $creation = $domaine === null;

 $chemin = $creation ? '/domaines' : '/domaines/' . (int)$domaine['id'];

 $nom        = old('nom', (string)($domaine['nom'] ?? ''));
 $initiale   = old('initiale', (string)($domaine['initiale'] ?? ''));
 $descriptif = old('descriptif', (string)($domaine['descriptif'] ?? ''));
 $couleur    = old('couleur', (string)($domaine['couleur'] ?? '#2563eb'));
 $nbEcheancesMax = old('nb_echeances_max', (string)($domaine['nb_echeances_max'] ?? '3'));
 $cocheActif = old('actif', (string)($domaine['actif'] ?? '1')) === '1';
?>
<div class="page-form">
    <h1 class="page-title"><?= $creation ? 'Créer un domaine' : 'Modifier « ' . e($nom) . ' »' ?></h1>
    <p class="page-subtitle">Référentiel des domaines.</p>
    <a class="link-back" href="<?= url('/domaines') ?>">← Retour à la liste</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url($chemin) ?>">
        <?= csrf_field() ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Informations</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="nom">Nom <span class="req">*</span></label>
                        <input class="input" type="text" id="nom" name="nom" maxlength="150" required value="<?= e($nom) ?>">
                        <?php if (form_error('nom')): ?><p class="form-error"><?= e(form_error('nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="initiale">Initiale</label>
                        <input class="input" type="text" id="initiale" name="initiale" maxlength="10" value="<?= e($initiale) ?>">
                        <?php if (form_error('initiale')): ?><p class="form-error"><?= e(form_error('initiale')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="couleur">Couleur</label>
                        <input class="input input-color" type="color" id="couleur" name="couleur" value="<?= e($couleur) ?>">
                        <?php if (form_error('couleur')): ?><p class="form-error"><?= e(form_error('couleur')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="descriptif">Descriptif</label>
                        <input class="input" type="text" id="descriptif" name="descriptif" maxlength="255" value="<?= e($descriptif) ?>">
                        <?php if (form_error('descriptif')): ?><p class="form-error"><?= e(form_error('descriptif')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="nb_echeances_max">Nombre d'échéances max <span class="req">*</span></label>
                        <input class="input" type="number" id="nb_echeances_max" name="nb_echeances_max" min="1" max="5" required
                               value="<?= e($nbEcheancesMax) ?>">
                        <p class="form-text">Nombre maximum d'échéances de paiement autorisées pour un dossier de ce domaine.</p>
                        <?php if (form_error('nb_echeances_max')): ?><p class="form-error"><?= e(form_error('nb_echeances_max')) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="form-footer">
                <label class="check-item">
                    <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                    <span>Domaine actif</span>
                </label>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $creation ? 'Créer le domaine' : 'Enregistrer' ?></button>
                    <a class="btn btn-ghost" href="<?= url('/domaines') ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>

    <?php if (!$creation && can('domaines.supprimer')): ?>
        <form class="card form-stack" method="post" action="<?= url('/domaines/' . (int)$domaine['id'] . '/supprimer') ?>"
              data-confirm="Supprimer (archiver) le domaine « <?= e($nom) ?> » ?">
            <?= csrf_field() ?>
            <div class="card-body form-actions">
                <button class="btn btn-danger" type="submit">Supprimer (archiver) ce domaine</button>
                <span class="form-text">Suppression logique — refusée si des utilisateurs y sont rattachés.</span>
            </div>
        </form>
    <?php endif; ?>
</div>
<!-- AE-EOF : le fichier domaines/form.php doit se terminer exactement ici -->