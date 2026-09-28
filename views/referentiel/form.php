<?php
// fichier : views/referentiel/form.php — vue générique (v0.38)
// + bloc parcoursAdmin (générateur embarqué — types de permis, directive v0.37/38)
declare(strict_types=1);

use App\Core\View;

 $entite             = $entite ?? null;
 $champs             = $champs ?? [];
 $options            = $options ?? [];
 $erreurs            = form_errors();
 $creation           = $entite === null;
 $routeBase          = $routeBase ?? '/';
 $prefix             = $prefixPermission ?? '';
 $suppressionLogique = $suppressionLogique ?? true;
 $article            = $article ?? 'un';
 $titreSingulier     = $titreSingulier ?? '';
 $titrePluriel       = $titrePluriel ?? '';
 $conditionCartes    = $conditionCartes ?? [];
 $autocompleteAdresse = $autocompleteAdresse ?? null;
 $parcoursAdmin      = $parcoursAdmin ?? null; /* v0.38 : générateur embarqué (types de permis) */

 $aPhoto = false;
foreach ($champs as $def) {
    if (($def['type'] ?? '') === 'photo') { $aPhoto = true; break; }
}

/* Regroupement des champs par section (défaut : Informations). */
 $groupes = [];
foreach ($champs as $nom => $def) {
    $groupes[$def['groupe'] ?? 'Informations'][$nom] = $def;
}

 $chemin = $creation ? $routeBase : $routeBase . '/' . (int)$entite['id'];

 $valeur = static function (string $nom) use ($entite): string {
    return old($nom, (string)($entite[$nom] ?? ''));
};
 $cocheActif = old('actif', (string)($entite['actif'] ?? '1')) === '1';

 $libellePrincipal = '';
foreach ($champs as $nom => $def) {
    $libellePrincipal = $valeur($nom);
    break;
}

 $titrePage = $creation
    ? 'Créer ' . $article . ' ' . $titreSingulier
    : 'Modifier ' . $article . ' ' . $titreSingulier;

/* Condition d'affichage D'UN CHAMP (select : contient ; checkbox : coché). */
 $attrsCondition = static function (array $def): string {
    if (empty($def['condition'])) {
        return '';
    }
    $cond = $def['condition'];
    return ' data-cond-champ="' . e((string)($cond['champ'] ?? '')) . '"'
        . ' data-cond-contient="' . e((string)($cond['contient'] ?? '')) . '"';
};

/* Condition d'affichage D'UNE SECTION : 'un' des champs cochés. */
 $attrsSection = static function (string $nomGroupe) use ($conditionCartes): string {
    $cond = $conditionCartes[$nomGroupe] ?? null;
    if (!is_array($cond)) {
        return '';
    }
    $champsDeclencheurs = $cond['champs'] ?? [];
    if (!is_array($champsDeclencheurs) || $champsDeclencheurs === []) {
        return '';
    }
    return ' data-cond-carte="' . e(implode('|', $champsDeclencheurs)) . '"';
};
?>
<div class="page-form">
    <h1 class="page-title"><?= e($titrePage) ?><?= (!$creation && $libellePrincipal !== '') ? ' — ' . e($libellePrincipal) : '' ?></h1>
    <p class="page-subtitle">Référentiel : <?= e((string)$titrePluriel) ?>.</p>
    <a class="link-back" href="<?= url($routeBase) ?>">← Retour à la liste</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="referentiel-form" method="post" action="<?= url($chemin) ?>"<?= $aPhoto ? ' enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>

        <div class="card form-sheet">

            <?php /* Auto-complétion d'adresse (CDC §34) — configurable par module. */ ?>
            <?php if (is_array($autocompleteAdresse) && $autocompleteAdresse !== []): ?>
                <div class="sheet-tool">
                    <?php View::partial('partials/adresse-autocomplete', ['champs' => $autocompleteAdresse]); ?>
                </div>
            <?php endif; ?>

            <?php foreach ($groupes as $nomGroupe => $champsGroupe): ?>
                <?php
                $enGrille = [];
                $booleens = [];
                foreach ($champsGroupe as $nom => $def) {
                    if (($def['type'] ?? '') === 'booleen') {
                        $booleens[$nom] = $def;
                    } else {
                        $enGrille[$nom] = $def;
                    }
                }
                $condSection = $attrsSection((string)$nomGroupe);
                ?>
                <section class="sheet-section"<?= $condSection ?>>
                    <div class="sheet-title"><?= e((string)$nomGroupe) ?></div>

                    <?php if ($enGrille !== []): ?>
                    <div class="form-grid">
                        <?php foreach ($enGrille as $nom => $def):
                            $type   = $def['type'] ?? 'text';
                            $val    = $valeur($nom);
                            $idChamp = 'champ_' . e($nom);
                            $pleineLargeur = in_array($type, ['textarea', 'photo'], true);
                            $cond = $attrsCondition($def);
                            ?>
                            <?php if ($type === 'photo'):
                                $photo = (string)($entite[$nom] ?? ''); ?>
                                <div class="field<?= $pleineLargeur ? ' field-full' : '' ?>"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?></label>
                                    <?php if ($photo !== ''): ?>
                                        <div class="photo-current">
                                            <img class="avatar avatar-xl" src="<?= url('/fichiers/' . e((string)($def['dossier'] ?? 'divers')) . '/' . rawurlencode($photo)) ?>" alt="">
                                            <label class="check-item">
                                                <input type="checkbox" name="retirer_<?= e($nom) ?>" value="1">
                                                <span>Retirer l'image</span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <input class="input input-file" type="file" id="<?= $idChamp ?>" name="<?= e($nom) ?>" accept="image/jpeg,image/png,image/webp">
                                    <p class="form-text">JPG, PNG ou WebP — 2 Mo maximum. (Prévisualisation après sélection.)</p>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'telephone'): ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <?php
                                    View::partial('partials/telephone', [
                                        'nom'    => $nom,
                                        'valeur' => $val,
                                        'id'     => 'champ_' . $nom,
                                    ]);
                                    ?>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'agence' || $type === 'referer' || ($type === 'select' && empty($def['images']))):
                                $choix = $options[$nom] ?? []; ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <select class="input" id="<?= $idChamp ?>" name="<?= e($nom) ?>" <?= !empty($def['requis']) ? 'required' : '' ?>>
                                        <?php if (empty($def['requis'])): ?>
                                            <option value="" <?= $val === '' ? 'selected' : '' ?>>— Non défini —</option>
                                        <?php endif; ?>
                                        <?php foreach ($choix as $v => $lib): ?>
                                            <option value="<?= e((string)$v) ?>" <?= $val === (string)$v ? 'selected' : '' ?>><?= e((string)$lib) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'select'):
                                $choix = $def['options'] ?? [];
                                $imagesUrls = [];
                                foreach ($def['images'] ?? [] as $vImg => $fImg) {
                                    $imagesUrls[(string)$vImg] = icone_url((string)$fImg);
                                }
                                $urlCourante = null;
                                if ($val !== '' && isset($imagesUrls[$val])) {
                                    $urlCourante = $imagesUrls[$val];
                                } elseif ($imagesUrls !== []) {
                                    $urlCourante = reset($imagesUrls);
                                }
                                ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <div class="select-img">
                                        <select class="input" id="<?= $idChamp ?>" name="<?= e($nom) ?>" <?= !empty($def['requis']) ? 'required' : '' ?>
                                                data-images='<?= e(json_encode($imagesUrls, JSON_UNESCAPED_UNICODE)) ?>'>
                                            <?php if (empty($def['requis'])): ?>
                                                <option value="" <?= $val === '' ? 'selected' : '' ?>>— Non défini —</option>
                                            <?php endif; ?>
                                            <?php foreach ($choix as $v => $lib): ?>
                                                <option value="<?= e((string)$v) ?>" <?= $val === (string)$v ? 'selected' : '' ?>><?= e((string)$lib) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($urlCourante !== null && $urlCourante !== false): ?>
                                            <img class="img-ref" src="<?= e((string)$urlCourante) ?>" alt="" data-preview-for="<?= $idChamp ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'textarea'): ?>
                                <div class="field field-full"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <textarea class="input" id="<?= $idChamp ?>" name="<?= e($nom) ?>" rows="3"
                                              maxlength="<?= (int)($def['max'] ?? 255) ?>" <?= !empty($def['requis']) ? 'required' : '' ?>><?= e($val) ?></textarea>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'color'):
                                $valCouleur = $val !== '' ? $val : '#64748b'; ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?></label>
                                    <input class="input input-color" type="color" id="<?= $idChamp ?>" name="<?= e($nom) ?>" value="<?= e($valCouleur) ?>">
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'date'): ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <input class="input" type="date" id="<?= $idChamp ?>" name="<?= e($nom) ?>" value="<?= e($val) ?>">
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'etoiles'): ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?></label>
                                    <input class="input input-sm" type="number" min="0" max="5" step="1" id="<?= $idChamp ?>" name="<?= e($nom) ?>" value="<?= e($val) ?>">
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'entier' || $type === 'decimal'): ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <input class="input" type="number" <?= $type === 'decimal' ? 'step="any"' : 'step="1"' ?>
                                           id="<?= $idChamp ?>" name="<?= e($nom) ?>" value="<?= e($val) ?>"
                                           <?= !empty($def['requis']) ? 'required' : '' ?>>
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="field"<?= $cond ?>>
                                    <label class="form-label" for="<?= $idChamp ?>"><?= e($def['label']) ?><?= !empty($def['requis']) ? ' <span class="req">*</span>' : '' ?></label>
                                    <input class="input" type="text" id="<?= $idChamp ?>" name="<?= e($nom) ?>"
                                           maxlength="<?= (int)($def['max'] ?? 255) ?>" <?= !empty($def['requis']) ? 'required' : '' ?> value="<?= e($val) ?>">
                                    <?php if (form_error($nom)): ?><p class="form-error"><?= e(form_error($nom)) ?></p><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($booleens !== []): ?>
                        <div class="check-grid">
                            <?php foreach ($booleens as $nom => $def):
                                $coche = old($nom, (string)($entite[$nom] ?? '0')) === '1'; ?>
                                <label class="check-item" for="champ_<?= e($nom) ?>">
                                    <input type="checkbox" id="champ_<?= e($nom) ?>" name="<?= e($nom) ?>" value="1" <?= $coche ? 'checked' : '' ?>>
                                    <span><?= e($def['label']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <div class="form-footer">
                <label class="check-item">
                    <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                    <span>Actif</span>
                </label>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $creation ? 'Créer' : 'Enregistrer' ?></button>
                    <a class="btn btn-ghost" href="<?= url($routeBase) ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>

    <?php /* ============================================================
       v0.38 — GÉNÉRATEUR DE PARCOURS EMBARQUÉ (types de permis).
       HORS du formulaire principal (formulaires imbriqués interdits) :
       carte dédiée, après </form>, avant les commentaires.
       Alimenté UNIQUEMENT par TypesPermisController::edit().
       ============================================================ */ ?>
    <?php if (is_array($parcoursAdmin) && isset($parcoursAdmin['type'])): ?>
        <?php View::partial('@types-permis/parcours-questions', ['parcoursAdmin' => $parcoursAdmin]); ?>
    <?php endif; ?>

    <?php /* Modale « fournisseur » (prestations). */ ?>
    <?php if (isset($modalFournisseur) && is_array($modalFournisseur)): ?>
    <div class="modal-overlay" id="modal-fournisseur">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-fournisseur-titre">
            <div class="modal-header">
                <span id="modal-fournisseur-titre">Fournisseur de la prestation</span>
                <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
            </div>
            <div class="modal-body">
                <p class="form-sub">
                    Sélectionnez le partenaire fournisseur et le prix d'achat :
                    les champs « Fournisseur (partenaire) » et « Prix d'achat » du formulaire seront mis à jour.
                </p>
                <div class="field">
                    <label class="form-label" for="modal_f_partenaire">Partenaire fournisseur</label>
                    <select class="input" id="modal_f_partenaire">
                        <option value="">— Aucun —</option>
                        <?php foreach (($modalFournisseur['options'] ?? []) as $v => $lib): ?>
                            <option value="<?= e((string)$v) ?>"><?= e((string)$lib) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="form-label" for="modal_f_prix">Prix d'achat</label>
                    <input class="input" type="number" step="any" min="0" id="modal_f_prix">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" data-fournisseur-ok>Valider</button>
                    <button type="button" class="btn btn-ghost" data-fournisseur-annuler>Annuler</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php /* Commentaires conversationnels (CDC §33). */ ?>
    <?php if (!$creation && isset($commentaires)): ?>
        <?php
        View::partial('partials/commentaires', [
            'objetType'          => $objetType ?? '',
            'objetId'            => $objetId ?? 0,
            'commentaires'       => $commentaires,
            'avecKm'             => $avecKm ?? false,
            'optionsPartenaires' => $optionsPartenaires ?? [],
        ]);
        ?>
    <?php endif; ?>

    <?php if (!$creation && can($prefix . '.supprimer')): ?>
        <?php
        $verbeSuppression = $suppressionLogique ? 'Supprimer (archiver) ' : 'Supprimer définitivement ';
        $confirmation = $verbeSuppression . $article . ' ' . $titreSingulier
            . ($libellePrincipal !== '' ? ' « ' . $libellePrincipal . ' »' : '') . ' ?';
        ?>
        <form class="card form-stack" method="post" action="<?= url($routeBase . '/' . (int)$entite['id'] . '/supprimer') ?>"
              data-confirm="<?= e($confirmation) ?>">
            <?= csrf_field() ?>
            <div class="card-body form-actions">
                <button class="btn btn-danger" type="submit"><?= e($verbeSuppression . $article . ' ' . $titreSingulier) ?></button>
                <span class="form-text"><?= $suppressionLogique
                    ? 'Suppression logique — l\'historique est conservé.'
                    : 'Suppression définitive — irréversible.' ?></span>
            </div>
        </form>
    <?php endif; ?>
</div>
<!-- AE-EOF : le fichier referentiel/form.php doit se terminer exactement ici -->