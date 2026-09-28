<?php
// fichier : modules/administration/views/agences/form.php — v0.31
// (adresse complète + email + lat/lng même ligne + auto-complétion CDC §34)
declare(strict_types=1);

use App\Core\View;

 $agence   = $agence ?? null;
 $erreurs  = form_errors();
 $creation = $agence === null;

 $chemin = $creation ? '/administration/agences' : '/administration/agences/' . (int)$agence['id'];

 $v = static function (string $cle) use ($agence): string {
    return old($cle, (string)($agence[$cle] ?? ''));
};
 $cocheActif = old('actif', (string)($agence['actif'] ?? '1')) === '1';
?>
<div class="page-form">
    <h1 class="page-title"><?= $creation ? 'Créer une agence' : 'Modifier « ' . e($v('agence_nom')) . ' »' ?></h1>
    <p class="page-subtitle">Structure conforme au cahier des charges §8 (adaptée : sans domaine, adresse complète).</p>
    <a class="link-back" href="<?= url('/administration/agences') ?>">← Retour à la liste</a>

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
                <div class="sheet-title">Identité</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="agence_nom">Nom de l'agence <span class="req">*</span></label>
                        <input class="input" type="text" id="agence_nom" name="agence_nom" maxlength="150" required value="<?= e($v('agence_nom')) ?>">
                        <?php if (form_error('agence_nom')): ?><p class="form-error"><?= e(form_error('agence_nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_initiale">Initiale de l'agence</label>
                        <input class="input" type="text" id="agence_initiale" name="agence_initiale" maxlength="10" value="<?= e($v('agence_initiale')) ?>">
                        <?php if (form_error('agence_initiale')): ?><p class="form-error"><?= e(form_error('agence_initiale')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_couleur">Couleur</label>
                        <input class="input input-color" type="color" id="agence_couleur" name="agence_couleur" value="<?= e(old('agence_couleur', (string)($agence['agence_couleur'] ?? '#2563eb'))) ?>">
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Coordonnées</div>

                <?php /* Auto-complétion d'adresse (CDC §34 — directive v0.31). */ ?>
                <?php
                View::partial('partials/adresse-autocomplete', [
                    'champs' => [
                        'adresse'     => 'agence_adresse',
                        'code_postal' => 'code_postal',
                        'ville'       => 'ville',
                        'pays'        => 'pays',
                        'latitude'    => 'latitude',
                        'longitude'   => 'longitude',
                    ],
                ]);
                ?>

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="form-label" for="agence_adresse">Adresse</label>
                        <input class="input" type="text" id="agence_adresse" name="agence_adresse" maxlength="255" value="<?= e($v('agence_adresse')) ?>">
                        <?php if (form_error('agence_adresse')): ?><p class="form-error"><?= e(form_error('agence_adresse')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="code_postal">Code postal</label>
                        <input class="input" type="text" id="code_postal" name="code_postal" maxlength="10" value="<?= e($v('code_postal')) ?>">
                        <?php if (form_error('code_postal')): ?><p class="form-error"><?= e(form_error('code_postal')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="ville">Ville</label>
                        <input class="input" type="text" id="ville" name="ville" maxlength="80" value="<?= e($v('ville')) ?>">
                        <?php if (form_error('ville')): ?><p class="form-error"><?= e(form_error('ville')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="pays">Pays</label>
                        <input class="input" type="text" id="pays" name="pays" maxlength="80" value="<?= e($v('pays')) ?>">
                        <?php if (form_error('pays')): ?><p class="form-error"><?= e(form_error('pays')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label">Téléphone</label>
                        <?php
                        View::partial('partials/telephone', [
                            'nom'    => 'agence_telephone',
                            'valeur' => $v('agence_telephone'),
                            'id'     => 'agence_telephone',
                        ]);
                        ?>
                        <?php if (form_error('agence_telephone')): ?><p class="form-error"><?= e(form_error('agence_telephone')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_mail">Email</label>
                        <input class="input" type="email" id="agence_mail" name="agence_mail" maxlength="190" value="<?= e($v('agence_mail')) ?>">
                        <?php if (form_error('agence_mail')): ?><p class="form-error"><?= e(form_error('agence_mail')) ?></p><?php endif; ?>
                    </div>

                    <?php /* Directive v0.31 : latitude et longitude sur la MÊME ligne. */ ?>
                    <div class="field-duo">
                        <div class="field">
                            <label class="form-label" for="latitude">Latitude</label>
                            <input class="input" type="number" step="any" id="latitude" name="latitude" value="<?= e($v('latitude')) ?>">
                            <?php if (form_error('latitude')): ?><p class="form-error"><?= e(form_error('latitude')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="form-label" for="longitude">Longitude</label>
                            <input class="input" type="number" step="any" id="longitude" name="longitude" value="<?= e($v('longitude')) ?>">
                            <?php if (form_error('longitude')): ?><p class="form-error"><?= e(form_error('longitude')) ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Agrément et assurance</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="agence_agreement">Agrément</label>
                        <input class="input" type="text" id="agence_agreement" name="agence_agreement" maxlength="100" value="<?= e($v('agence_agreement')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_agreement_date">Date d'agrément</label>
                        <input class="input" type="date" id="agence_agreement_date" name="agence_agreement_date" value="<?= e($v('agence_agreement_date')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_agreement_exploitant">Agrément exploitant</label>
                        <input class="input" type="text" id="agence_agreement_exploitant" name="agence_agreement_exploitant" maxlength="150" value="<?= e($v('agence_agreement_exploitant')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_assurance">Assurance</label>
                        <input class="input" type="text" id="agence_assurance" name="agence_assurance" maxlength="150" value="<?= e($v('agence_assurance')) ?>">
                    </div>
                </div>
            </section>

            <div class="form-footer">
                <label class="check-item">
                    <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                    <span>Agence active</span>
                </label>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $creation ? 'Créer l\'agence' : 'Enregistrer' ?></button>
                    <a class="btn btn-ghost" href="<?= url('/administration/agences') ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>

    <?php if (!$creation && can('agences.supprimer')): ?>
        <form class="card form-stack" method="post" action="<?= url('/administration/agences/' . (int)$agence['id'] . '/supprimer') ?>"
              data-confirm="Supprimer (archiver) l'agence « <?= e($v('agence_nom')) ?> » ?">
            <?= csrf_field() ?>
            <div class="card-body form-actions">
                <button class="btn btn-danger" type="submit">Supprimer (archiver) cette agence</button>
                <span class="form-text">Suppression logique — refusée si des utilisateurs actifs y sont rattachés.</span>
            </div>
        </form>
    <?php endif; ?>
</div>
<!-- AE-EOF : le fichier agences/form.php doit se terminer exactement ici -->