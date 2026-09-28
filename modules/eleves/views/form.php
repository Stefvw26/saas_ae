<?php
// fichier : modules/eleves/views/form.php — v0.44
// Civilité en premier ; naissance + lieu sur la même ligne ; email + tél
// sur la même ligne ; autocomplete ; téléphone indicatif.
declare(strict_types=1);

use App\Core\View;

 $eleve              = $eleve ?? null;
 $optionsAgences     = $optionsAgences ?? [];
 $optionsDomaines    = $optionsDomaines ?? [];
 $optionsTypesPermis = $optionsTypesPermis ?? [];
 $erreurs            = form_errors();
 $edition            = $eleve !== null;

 $chemin = $edition ? '/eleves/' . (int)$eleve['id'] : '/eleves';

 $v = static function (string $cle) use ($eleve): string {
    return old($cle, (string)($eleve[$cle] ?? ''));
};
 $cocheActif = old('actif', (string)($eleve['actif'] ?? '1')) === '1';
?>
<div class="page-form">
    <h1 class="page-title"><?= $edition ? 'Modifier l\'élève' : 'Créer un élève' ?></h1>
    <p class="page-subtitle">Fiche élève (CDC §50-54).</p>
    <a class="link-back" href="<?= $edition ? url('/eleves/' . (int)$eleve['id']) : url('/eleves') ?>">← Retour</a>

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

            <?php
            View::partial('partials/adresse-autocomplete', [
                'champs' => [
                    'adresse'     => 'adresse',
                    'code_postal' => 'code_postal',
                    'ville'       => 'ville',
                    'pays'        => 'pays',
                ],
            ]);
            ?>

            <section class="sheet-section">
                <div class="sheet-title">Identité</div>
                <div class="form-grid">
                    <?php /* Directive : civilité EN PREMIER. */ ?>
                    <div class="field">
                        <label class="form-label" for="civilite">Civilité</label>
                        <select class="input" id="civilite" name="civilite">
                            <option value="">— Non précisé —</option>
                            <option value="Monsieur" <?= $v('civilite') === 'Monsieur' ? 'selected' : '' ?>>Monsieur</option>
                            <option value="Madame" <?= $v('civilite') === 'Madame' ? 'selected' : '' ?>>Madame</option>
                        </select>
                        <?php if (form_error('civilite')): ?><p class="form-error"><?= e(form_error('civilite')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="nom">Nom <span class="req">*</span></label>
                        <input class="input" type="text" id="nom" name="nom" maxlength="80" required value="<?= e($v('nom')) ?>">
                        <?php if (form_error('nom')): ?><p class="form-error"><?= e(form_error('nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="prenom">Prénom <span class="req">*</span></label>
                        <input class="input" type="text" id="prenom" name="prenom" maxlength="80" required value="<?= e($v('prenom')) ?>">
                        <?php if (form_error('prenom')): ?><p class="form-error"><?= e(form_error('prenom')) ?></p><?php endif; ?>
                    </div>

                    <?php /* Directive : naissance + lieu de naissance même ligne. */ ?>
                    <div class="field-duo">
                        <div class="field">
                            <label class="form-label" for="date_naissance">Date de naissance</label>
                            <input class="input" type="date" id="date_naissance" name="date_naissance" value="<?= e($v('date_naissance')) ?>">
                            <?php if (form_error('date_naissance')): ?><p class="form-error"><?= e(form_error('date_naissance')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="form-label" for="lieu_naissance">Lieu de naissance</label>
                            <input class="input" type="text" id="lieu_naissance" name="lieu_naissance" maxlength="120" value="<?= e($v('lieu_naissance')) ?>">
                            <?php if (form_error('lieu_naissance')): ?><p class="form-error"><?= e(form_error('lieu_naissance')) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <?php /* Directive : email + téléphone même ligne. */ ?>
                    <div class="field-duo">
                        <div class="field">
                            <label class="form-label" for="email">Email</label>
                            <input class="input" type="email" id="email" name="email" maxlength="190" value="<?= e($v('email')) ?>">
                            <?php if (form_error('email')): ?><p class="form-error"><?= e(form_error('email')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="form-label">Téléphone</label>
                            <?php
                            View::partial('partials/telephone', [
                                'nom'    => 'telephone',
                                'valeur' => $v('telephone'),
                                'id'     => 'telephone',
                            ]);
                            ?>
                            <?php if (form_error('telephone')): ?><p class="form-error"><?= e(form_error('telephone')) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="field field-full">
                        <label class="form-label" for="adresse">Adresse</label>
                        <input class="input" type="text" id="adresse" name="adresse" maxlength="255" value="<?= e($v('adresse')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="code_postal">Code postal</label>
                        <input class="input" type="text" id="code_postal" name="code_postal" maxlength="10" value="<?= e($v('code_postal')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="ville">Ville</label>
                        <input class="input" type="text" id="ville" name="ville" maxlength="80" value="<?= e($v('ville')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="pays">Pays</label>
                        <input class="input" type="text" id="pays" name="pays" maxlength="80" value="<?= e($v('pays')) ?>">
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Formation</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="type_permis_id">Type de permis</label>
                        <select class="input" id="type_permis_id" name="type_permis_id">
                            <option value="">— Non défini —</option>
                            <?php $tpChoisi = $v('type_permis_id'); ?>
                            <?php foreach ($optionsTypesPermis as $idTp => $nomTp): ?>
                                <option value="<?= (int)$idTp ?>" <?= $tpChoisi === (string)$idTp ? 'selected' : '' ?>><?= e($nomTp) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('type_permis_id')): ?><p class="form-error"><?= e(form_error('type_permis_id')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="type_boite">Type de boîte (PAS / BA / BM)</label>
                        <select class="input" id="type_boite" name="type_boite">
                            <option value="">— Non défini —</option>
                            <?php foreach (['PAS', 'BA', 'BM'] as $boite): ?>
                                <option value="<?= e($boite) ?>" <?= $v('type_boite') === $boite ? 'selected' : '' ?>><?= e($boite) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('type_boite')): ?><p class="form-error"><?= e(form_error('type_boite')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="type_b">Type B (B1 – B5)</label>
                        <select class="input" id="type_b" name="type_b">
                            <option value="">— Non défini —</option>
                            <?php foreach (['B1', 'B2', 'B3', 'B4', 'B5'] as $b): ?>
                                <option value="<?= e($b) ?>" <?= $v('type_b') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('type_b')): ?><p class="form-error"><?= e(form_error('type_b')) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Rattachement</div>
                <div class="field-duo">
                    <div class="field">
                        <label class="form-label" for="domaine_id">Lieu de préférence (domaine)</label>
                        <select class="input" id="domaine_id" name="domaine_id">
                            <option value="">— Non défini —</option>
                            <?php $domChoisi = $v('domaine_id'); ?>
                            <?php foreach ($optionsDomaines as $idDom => $dom): ?>
                                <option value="<?= (int)$idDom ?>" <?= $domChoisi === (string)$idDom ? 'selected' : '' ?>><?= e($dom['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('domaine_id')): ?><p class="form-error"><?= e(form_error('domaine_id')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence_id">Agence</label>
                        <select class="input" id="agence_id" name="agence_id">
                            <option value="">— Non définie —</option>
                            <?php $agChoisie = $v('agence_id'); ?>
                            <?php foreach ($optionsAgences as $idAg => $nomAg): ?>
                                <option value="<?= (int)$idAg ?>" <?= $agChoisie === (string)$idAg ? 'selected' : '' ?>><?= e($nomAg) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('agence_id')): ?><p class="form-error"><?= e(form_error('agence_id')) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Contact en cas d'élève mineur (§54)</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="responsable_nom">Responsable — nom</label>
                        <input class="input" type="text" id="responsable_nom" name="responsable_nom" maxlength="80" value="<?= e($v('responsable_nom')) ?>">
                        <?php if (form_error('responsable_nom')): ?><p class="form-error"><?= e(form_error('responsable_nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="responsable_telephone">Responsable — téléphone</label>
                        <input class="input" type="tel" id="responsable_telephone" name="responsable_telephone" maxlength="30" value="<?= e($v('responsable_telephone')) ?>">
                        <?php if (form_error('responsable_telephone')): ?><p class="form-error"><?= e(form_error('responsable_telephone')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field-full">
                        <label class="form-label" for="responsable_email">Responsable — email</label>
                        <input class="input" type="email" id="responsable_email" name="responsable_email" maxlength="190" value="<?= e($v('responsable_email')) ?>">
                        <?php if (form_error('responsable_email')): ?><p class="form-error"><?= e(form_error('responsable_email')) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="form-footer">
                <label class="check-item">
                    <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                    <span>Actif</span>
                </label>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $edition ? 'Enregistrer' : 'Créer l\'élève' ?></button>
                    <a class="btn btn-ghost" href="<?= $edition ? url('/eleves/' . (int)$eleve['id']) : url('/eleves') ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- AE-EOF : le fichier eleves/views/form.php doit se terminer exactement ici -->