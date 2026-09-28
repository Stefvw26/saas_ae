<?php
// fichier : modules/types-permis/views/parcours-questions.php — v0.41
// GÉNÉRATEUR — la règle d'affichage se définit SUR LA QUESTION DÉCLENCHEUSE :
// « On affiche la question [X] si la réponse est égale à [valeur du type de
// CETTE question] ». Valeurs : Oui/Non ou les options de la question en
// cours — rendues côté serveur (aucun JS de condition).
declare(strict_types=1);

 $pa        = $parcoursAdmin ?? null;
 $type      = $pa['type'] ?? null;
 $questions = $pa['questions'] ?? [];
 $peutGerer = $pa['peutGerer'] ?? false;
 $courant   = $pa['utilisateurCourant'] ?? '';

 $libellesReponse = [
    'oui_non' => 'Oui / Non',
    'choix'   => 'Choix (options)',
    'nombre'  => 'Nombre',
    'texte'   => 'Texte libre',
];

/* Valeurs possibles de CHAQUE question — de SON PROPRE type. */
 $valeursPossibles = [];
foreach ($questions as $q) {
    $qid = (int)$q['id'];
    $t   = (string)$q['type_reponse'];
    if ($t === 'oui_non') {
        $valeursPossibles[$qid] = [['oui', 'Oui'], ['non', 'Non']];
    } elseif ($t === 'choix') {
        $vals = [];
        foreach (($q['options_detail'] ?? []) as $o) {
            $vals[] = [(string)$o['valeur'], (string)$o['libelle']];
        }
        $valeursPossibles[$qid] = $vals;
    } else {
        $valeursPossibles[$qid] = [];
    }
}

 $questionParId = [];
foreach ($questions as $q) {
    $questionParId[(int)$q['id']] = $q;
}

/* Règles existantes : parent => enfants conditionnés. */
 $regles = [];
foreach ($questions as $q) {
    if (!empty($q['condition_question_id'])) {
        $regles[(int)$q['condition_question_id']][] = $q;
    }
}

/* Libellé d'une valeur d'une question (affichage des règles). */
 $libelleValeur = static function (int $qid, string $valeur) use ($valeursPossibles): string {
    foreach (($valeursPossibles[$qid] ?? []) as [$v, $l]) {
        if ($v === $valeur) {
            return $l;
        }
    }
    return $valeur;
};
?>
<?php if ($type !== null): ?>
<div class="card form-stack parcours-bloc">
    <div class="card-header">Parcours prospect — questionnaire</div>
    <div class="card-body">

        <?php if (!$peutGerer): ?>
            <p class="form-text">Vous ne disposez pas du droit de gérer le parcours.</p>
        <?php else: ?>

            <?php if ($questions === []): ?>
                <div class="empty-state">
                    <p class="empty-title">Aucune question</p>
                    <p class="empty-text">Créez ci-dessous le questionnaire de « <?= e((string)$type['nom']) ?> ».</p>
                </div>
            <?php endif; ?>

            <?php /* ============ QUESTIONS ============ */ ?>
            <?php foreach ($questions as $q): $qid = (int)$q['id'];
                $auteur   = trim((string)($q['auteur_prenom'] ?? '') . ' ' . (string)($q['auteur_nom'] ?? ''))
                    ?: (string)($q['auteur_login'] ?? '');
                $estChoix = (string)$q['type_reponse'] === 'choix';
                $sesValeurs = $valeursPossibles[$qid];
                $parentInfo = null;
                if (!empty($q['condition_question_id'])) {
                    $parentInfo = $questionParId[(int)$q['condition_question_id']] ?? null;
                }
            ?>
            <div class="q-block">
                <form method="post" action="<?= url('/parcours/question/' . $qid) ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field field-full">
                            <label class="form-label" for="q_libelle_<?= $qid ?>">Question <span class="req">*</span></label>
                            <input class="input" type="text" id="q_libelle_<?= $qid ?>" name="libelle" maxlength="500" required
                                   value="<?= e(old('libelle', (string)$q['libelle'])) ?>">
                        </div>
                        <div class="field">
                            <label class="form-label" for="q_type_<?= $qid ?>">Type de réponse</label>
                            <select class="input" id="q_type_<?= $qid ?>" name="type_reponse" data-toggle-options>
                                <?php foreach ($libellesReponse as $code => $lib): ?>
                                    <option value="<?= e($code) ?>" <?= (string)$q['type_reponse'] === $code ? 'selected' : '' ?>><?= e($lib) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="form-label" for="q_pos_<?= $qid ?>">Position</label>
                            <input class="input input-sm" type="number" min="0" step="1" id="q_pos_<?= $qid ?>" name="position"
                                   value="<?= e(old('position', (string)$q['position'])) ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <label class="check-item">
                            <input type="checkbox" name="actif" value="1" <?= (int)$q['actif'] === 1 ? 'checked' : '' ?>>
                            <span>Active</span>
                        </label>
                        <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
                        <button class="btn btn-danger btn-sm" type="submit"
                                formaction="<?= url('/parcours/question/' . $qid . '/supprimer') ?>"
                                data-confirm="Supprimer (archiver) cette question ?">Supprimer</button>
                    </div>
                </form>

                <?php /* « par qui » : sous les boutons, aligné droite. */ ?>
                <p class="q-meta">Ajouté le <?= e((string)$q['ajout_le']) ?><?= $auteur !== '' ? ' par ' . e($auteur) : '' ?></p>

                <?php /* Options (Choix) — formulaires frères. */ ?>
                <div class="q-options-manage"<?= $estChoix ? '' : ' hidden' ?>>
                    <p class="form-label">Options de réponse</p>
                    <div class="opt-badges">
                        <?php foreach (($q['options_detail'] ?? []) as $opt): ?>
                            <span class="opt-badge">
                                <?= e($opt['libelle']) ?> <small><?= e($opt['valeur']) ?></small>
                                <form method="post" action="<?= url('/parcours/option/' . (int)$opt['id'] . '/supprimer') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="opt-del-btn" title="Supprimer l'option" aria-label="Supprimer l'option">&times;</button>
                                </form>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <form class="opt-add" method="post" action="<?= url('/parcours/question/' . $qid . '/option') ?>">
                        <?= csrf_field() ?>
                        <input class="input input-sm" type="text" name="valeur" placeholder="valeur (ex. moins_4)" maxlength="100" required>
                        <input class="input input-sm" type="text" name="libelle" placeholder="Libellé affiché" maxlength="255" required>
                        <button class="btn btn-ghost btn-sm" type="submit">+ Option</button>
                    </form>
                </div>

                <?php /* Note sur la question conditionnée (info seule). */ ?>
                <?php if ($parentInfo !== null): ?>
                    <p class="regle-info">
                        S'affiche si la réponse à « <?= e(mb_substr((string)$parentInfo['libelle'], 0, 60)) ?> »
                        est « <?= e($libelleValeur((int)$parentInfo['id'], (string)$q['condition_valeur'])) ?> »
                        <small>(règle gérée sur cette question déclencheuse)</small>
                    </p>
                <?php endif; ?>

                <?php /* ============ RÈGLES D'AFFICHAGE (côté question en cours) ============ */ ?>
                <div class="q-regles">
                    <p class="form-label">Affichage conditionnel — selon la réponse à CETTE question</p>

                    <?php if ($sesValeurs === []): ?>
                        <p class="form-text">
                            Le type « <?= e($libellesReponse[(string)$q['type_reponse']]) ?> » ne permet pas
                            de conditionner l'affichage d'une autre question.
                        </p>
                    <?php elseif ($estChoix && count($sesValeurs) === 0): ?>
                        <p class="form-text">Ajoutez d'abord des options à cette question.</p>
                    <?php else: ?>

                        <?php /* Règles existantes. */ ?>
                        <?php foreach (($regles[$qid] ?? []) as $enfant): ?>
                            <div class="regle-item">
                                <span>
                                    On affiche « <?= e(mb_substr((string)$enfant['libelle'], 0, 60)) ?> »
                                    si la réponse est « <?= e($libelleValeur($qid, (string)$enfant['condition_valeur'])) ?> »
                                </span>
                                <form method="post" action="<?= url('/parcours/regle/' . (int)$enfant['id'] . '/retirer') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-sm" type="submit">Retirer</button>
                                </form>
                            </div>
                        <?php endforeach; ?>

                        <?php /* Nouvelle règle : phrase à compléter. */ ?>
                        <form class="regle-add" method="post" action="<?= url('/parcours/regle') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type_permis_id" value="<?= (int)$type['id'] ?>">
                            <input type="hidden" name="parent_id" value="<?= $qid ?>">
                            <div class="regle-ligne">
                                <span class="regle-mot">On affiche la question</span>
                                <select class="input input-sm" name="enfant_id" required>
                                    <option value="">— Choisir —</option>
                                    <?php foreach ($questions as $autre): if ((int)$autre['id'] === $qid) { continue; } ?>
                                        <option value="<?= (int)$autre['id'] ?>"><?= e(mb_substr((string)$autre['libelle'], 0, 60)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="regle-mot">si la réponse est égale à</span>
                                <select class="input input-sm" name="condition_valeur" required>
                                    <?php foreach ($sesValeurs as [$v, $l]): ?>
                                        <option value="<?= e($v) ?>"><?= e($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary btn-sm" type="submit">Ajouter la règle</button>
                            </div>
                        </form>
                        <p class="form-text">Une question ne peut dépendre que d'une seule autre question : ajouter une règle remplace sa dépendance précédente.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php /* ============ CRÉATION ============ */ ?>
            <form class="q-add" method="post" action="<?= url('/parcours/question') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type_permis_id" value="<?= (int)$type['id'] ?>">
                <p class="form-label" style="margin-top:4px">Nouvelle question</p>
                <div class="form-grid">
                    <div class="field field-full">
                        <label class="form-label" for="new_q">Question <span class="req">*</span></label>
                        <input class="input" type="text" id="new_q" name="libelle" maxlength="500" required
                               value="<?= e(old('libelle')) ?>"
                               placeholder="ex. : Le prospect a déjà effectué des heures de conduite ?">
                    </div>
                    <div class="field">
                        <label class="form-label" for="new_t">Type de réponse</label>
                        <select class="input" id="new_t" name="type_reponse" data-toggle-options>
                            <?php foreach ($libellesReponse as $code => $lib): ?>
                                <option value="<?= e($code) ?>"><?= e($lib) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="form-label" for="new_p">Position</label>
                        <input class="input input-sm" type="number" min="0" step="1" id="new_p" name="position" value="0">
                    </div>
                </div>

                <div class="q-options-create" hidden>
                    <p class="form-label">Options de réponse</p>
                    <div class="opt-lignes">
                        <div class="opt-ligne">
                            <input class="input input-sm" type="text" name="opt_valeur[]" placeholder="valeur (ex. moins_4)" maxlength="100">
                            <input class="input input-sm" type="text" name="opt_libelle[]" placeholder="Libellé affiché" maxlength="255">
                            <button type="button" class="btn btn-ghost btn-sm" data-opt-del title="Retirer la ligne">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" data-opt-add>+ Ajouter une option</button>
                </div>

                <div class="form-actions">
                    <label class="check-item">
                        <input type="checkbox" name="actif" value="1" checked>
                        <span>Active</span>
                    </label>
                    <button class="btn btn-primary btn-sm" type="submit">+ Ajouter la question</button>
                </div>
                <?php if ($courant !== ''): ?>
                    <p class="q-meta">Sera ajouté par : <?= e($courant) ?></p>
                <?php endif; ?>
                <p class="form-text">La question créée pourra ensuite conditionner l'affichage d'autres questions (règles « On affiche… » ci-dessus, une fois la question existante).</p>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<!-- AE-EOF : le fichier types-permis/views/parcours-questions.php doit se terminer exactement ici -->