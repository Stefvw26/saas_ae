<?php
// fichier : modules/prospects/views/form.php — v0.47
declare(strict_types=1);

use App\Core\View;

 $prospect           = $prospect ?? null;
 $optionsAgences     = $optionsAgences ?? [];
 $optionsDomaines    = $optionsDomaines ?? [];
 $optionsTypesPermis = $optionsTypesPermis ?? [];
 $parcoursParType    = $parcoursParType ?? [];
 $parcoursValeurs    = $parcoursValeurs ?? [];
 $erreurs            = form_errors();
 $edition            = $prospect !== null;

 $chemin = $edition ? '/prospects/' . (int)$prospect['id'] : '/prospects';

 $v = static function (string $cle) use ($prospect): string {
    return old($cle, (string)($prospect[$cle] ?? ''));
};

 $pv = [];
foreach ($parcoursValeurs as $k => $vv) {
    $pv[(int)$k] = (string)$vv;
}
?>
<div class="page-form">
    <h1 class="page-title"><?= $edition ? 'Modifier le prospect' : 'Créer un prospect' ?></h1>
    <p class="page-subtitle">Assistant en 5 étapes — provenance enregistrée automatiquement : « Passant ».</p>
    <a class="link-back" href="<?= url('/prospects') ?>">← Retour à la liste</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="prospect-wizard" method="post" action="<?= url($chemin) ?>">
        <?= csrf_field() ?>

        <ol class="wizard-steps" data-wizard-steps>
            <li class="is-active" data-step-cible="1"><span>1</span>Informations personnelles</li>
            <li data-step-cible="2"><span>2</span>Formation souhaitée</li>
            <li data-step-cible="3"><span>3</span>Parcours</li>
            <li data-step-cible="4"><span>4</span>Commentaire</li>
            <li data-step-cible="5"><span>5</span>Résumé</li>
        </ol>

        <div class="card form-sheet">

            <?php /* ---- Étape 1 ---- */ ?>
            <section class="sheet-section wizard-pane" data-wizard-pane="1">
                <div class="sheet-title">Informations personnelles</div>

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

                <div class="form-grid">
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

                    <div class="field-duo">
                        <div class="field">
                            <label class="form-label" for="email">Email <span class="req">*</span></label>
                            <input class="input" type="email" id="email" name="email" maxlength="190" required value="<?= e($v('email')) ?>">
                            <?php if (form_error('email')): ?><p class="form-error"><?= e(form_error('email')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="form-label">Téléphone <span class="req">*</span></label>
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
                        <?php if (form_error('adresse')): ?><p class="form-error"><?= e(form_error('adresse')) ?></p><?php endif; ?>
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

                    <div class="field-duo">
                        <div class="field">
                            <label class="form-label" for="domaine_id">Lieu de préférence (domaine)</label>
                            <select class="input" id="domaine_id" name="domaine_id">
                                <option value="">— Non défini —</option>
                                <?php $domaineChoisi = $v('domaine_id'); ?>
                                <?php foreach ($optionsDomaines as $idDom => $dom): ?>
                                    <option value="<?= (int)$idDom ?>" <?= $domaineChoisi === (string)$idDom ? 'selected' : '' ?>><?= e($dom['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (form_error('domaine_id')): ?><p class="form-error"><?= e(form_error('domaine_id')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="form-label" for="agence_id">Agence de référence</label>
                            <select class="input" id="agence_id" name="agence_id">
                                <option value="">— Non définie —</option>
                                <?php $agenceChoisie = $v('agence_id'); ?>
                                <?php foreach ($optionsAgences as $idAg => $nomAg): ?>
                                    <option value="<?= (int)$idAg ?>" <?= $agenceChoisie === (string)$idAg ? 'selected' : '' ?>><?= e($nomAg) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (form_error('agence_id')): ?><p class="form-error"><?= e(form_error('agence_id')) ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <?php /* ---- Étape 2 ---- */ ?>
            <section class="sheet-section wizard-pane" data-wizard-pane="2" hidden>
                <div class="sheet-title">Formation souhaitée</div>
                <?php $typeChoisi = $v('type_permis_id'); ?>
                <div class="permis-cards">
                    <?php foreach ($optionsTypesPermis as $tp): ?>
                        <label class="permis-card">
                            <input type="radio" name="type_permis_id" value="<?= (int)$tp['id'] ?>"
                                   <?= $typeChoisi === (string)$tp['id'] ? 'checked' : '' ?>>
                            <?php if ($tp['initiale'] !== ''): ?>
                                <span class="permis-init"><?= e($tp['initiale']) ?></span>
                            <?php endif; ?>
                            <span class="permis-nom"><?= e($tp['nom']) ?></span>
                            <?php if ($tp['descriptif'] !== ''): ?>
                                <small><?= e($tp['descriptif']) ?></small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (form_error('type_permis_id')): ?><p class="form-error"><?= e(form_error('type_permis_id')) ?></p><?php endif; ?>
                <p class="form-text">Sélectionnez le type de permis souhaité par le prospect.</p>
            </section>

            <?php /* ---- Étape 3 : PARCOURS pré-rendu ---- */ ?>
            <section class="sheet-section wizard-pane" data-wizard-pane="3" hidden>
                <div class="sheet-title">Parcours</div>

                <?php foreach ($optionsTypesPermis as $tp): $tidType = (int)$tp['id']; ?>
                    <div class="parcours-type-bloc" data-parcours-type="<?= $tidType ?>" hidden>
                        <?php $questionsType = $parcoursParType[$tidType] ?? []; ?>
                        <?php if ($questionsType === []): ?>
                            <p class="form-text">Aucune question définie pour « <?= e($tp['nom']) ?> » —
                                gérez le questionnaire dans Référentiels → Types de permis → Modifier.</p>
                        <?php else: ?>
                            <div class="form-grid">
                                <?php foreach ($questionsType as $q): $qid = (int)$q['id'];
                                    $nomChamp = 'parcours[' . $qid . ']';
                                    $val = $pv[$qid] ?? '';
                                    $siAttrs = '';
                                    $siClass = '';
                                    if (!empty($q['condition_question_id'])) {
                                        $siAttrs = ' data-si-cle="' . (int)$q['condition_question_id'] . '"'
                                                 . ' data-si-egal="' . e((string)$q['condition_valeur']) . '"';
                                        $siClass = ' si-cache';
                                    }
                                ?>
                                    <?php if ((string)$q['type_reponse'] === 'oui_non'): ?>
                                        <div class="field<?= $siClass ?>"<?= $siAttrs ?>>
                                            <label class="form-label"><?= e((string)$q['libelle']) ?></label>
                                            <div class="parcours-choix">
                                                <label class="parcours-opt">
                                                    <input type="radio" name="<?= e($nomChamp) ?>" value="oui" <?= $val === 'oui' ? 'checked' : '' ?>>
                                                    <span>Oui</span>
                                                </label>
                                                <label class="parcours-opt">
                                                    <input type="radio" name="<?= e($nomChamp) ?>" value="non" <?= $val === 'non' ? 'checked' : '' ?>>
                                                    <span>Non</span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php elseif ((string)$q['type_reponse'] === 'choix'): ?>
                                        <div class="field<?= $siClass ?>"<?= $siAttrs ?>>
                                            <label class="form-label" for="pq_<?= $qid ?>"><?= e((string)$q['libelle']) ?></label>
                                            <select class="input" id="pq_<?= $qid ?>" name="<?= e($nomChamp) ?>">
                                                <option value="">— Non défini —</option>
                                                <?php foreach (($q['options'] ?? []) as $ov => $ol): ?>
                                                    <option value="<?= e((string)$ov) ?>" <?= $val === (string)$ov ? 'selected' : '' ?>><?= e((string)$ol) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif ((string)$q['type_reponse'] === 'nombre'): ?>
                                        <div class="field<?= $siClass ?>"<?= $siAttrs ?>>
                                            <label class="form-label" for="pq_<?= $qid ?>"><?= e((string)$q['libelle']) ?></label>
                                            <input class="input" type="number" min="0" step="1" id="pq_<?= $qid ?>" name="<?= e($nomChamp) ?>" value="<?= e($val) ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="field<?= $siClass ?>"<?= $siAttrs ?>>
                                            <label class="form-label" for="pq_<?= $qid ?>"><?= e((string)$q['libelle']) ?></label>
                                            <input class="input" type="text" maxlength="500" id="pq_<?= $qid ?>" name="<?= e($nomChamp) ?>" value="<?= e($val) ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <p class="form-text" data-parcours-vide>Sélectionnez d'abord une formation (étape 2) — ses questions s'afficheront ici.</p>
            </section>

            <?php /* ---- Étape 4 ---- */ ?>
            <section class="sheet-section wizard-pane" data-wizard-pane="4" hidden>
                <div class="sheet-title">Commentaire</div>
                <div class="field">
                    <label class="form-label" for="commentaire">Commentaire</label>
                    <textarea class="input" id="commentaire" name="commentaire" rows="4" maxlength="2000"><?= e($v('commentaire')) ?></textarea>
                    <?php if (form_error('commentaire')): ?><p class="form-error"><?= e(form_error('commentaire')) ?></p><?php endif; ?>
                </div>
                <p class="form-text">Code promotionnel : système non défini — saisie ajoutée lors de sa mise en place.</p>
            </section>

            <?php /* ---- Étape 5 ---- */ ?>
            <section class="sheet-section wizard-pane" data-wizard-pane="5" hidden>
                <div class="sheet-title">Résumé et validation</div>
                <dl class="wizard-resume" data-wizard-resume></dl>
                <p class="form-text">Vérifiez les informations avant de valider (provenance : « Passant »).</p>
            </section>

            <div class="form-footer">
                <button class="btn btn-ghost" type="button" data-wizard-prev hidden>← Précédent</button>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="button" data-wizard-next>Suivant →</button>
                    <button class="btn btn-primary" type="submit" data-wizard-submit hidden><?= $edition ? 'Enregistrer' : 'Valider la création' ?></button>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- AE-EOF : le fichier prospects/form.php doit se terminer exactement ici -->