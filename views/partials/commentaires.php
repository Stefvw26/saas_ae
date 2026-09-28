<?php
// fichier : views/partials/commentaires.php — composant conversationnel (CDC §33)
// v0.35 : $compact = true → sans enveloppe .card (usage en modale).
declare(strict_types=1);

use App\Core\Session;

 $objetType          = $objetType ?? '';
 $objetId           = (int)($objetId ?? 0);
 $commentaires      = $commentaires ?? [];
 $avecKm            = $avecKm ?? false;
 $optionsPartenaires = $optionsPartenaires ?? [];
 $compact           = $compact ?? false;
 $monId             = (int)Session::get('user_id', 0);
 $peutCommenter     = can('commentaires.creer');
?>
<?php if (!$compact): ?>
<div class="card conv-bloc">
    <div class="card-header">
        <span>Commentaires</span>
        <span class="card-count"><?= count($commentaires) ?></span>
    </div>
    <div class="card-body">
<?php else: ?>
<div class="conv-compact">
<?php endif; ?>

        <?php if ($commentaires === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun commentaire</p>
                <p class="empty-text">La conversation apparaîtra ici (gauche = les autres, droite = vous).</p>
            </div>
        <?php else: ?>
            <div class="conv<?= $compact ? ' conv-modal' : '' ?>">
                <?php foreach ($commentaires as $c):
                    $estMoi   = (int)($c['utilisateur_id'] ?? 0) === $monId;
                    $auteur   = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?: (string)($c['login'] ?? '—');
                    $initiales = user_initials(['prenom' => $c['prenom'] ?? '', 'nom' => $c['nom'] ?? '']);
                    ?>
                    <div class="conv-msg<?= $estMoi ? ' conv-moi' : '' ?>">
                        <?php if (!empty($c['photographie'])): ?>
                            <img class="avatar avatar-sm" src="<?= url('/fichiers/utilisateurs/' . rawurlencode((string)$c['photographie'])) ?>" alt="">
                        <?php else: ?>
                            <span class="avatar avatar-sm"<?= !empty($c['couleur']) ? ' style="background-color:' . e((string)$c['couleur']) . '"' : '' ?>><?= e($initiales) ?></span>
                        <?php endif; ?>
                        <div class="conv-bubble">
                            <div class="conv-meta">
                                <strong><?= e($auteur) ?></strong> · <?= e((string)$c['cree_le']) ?>
                            </div>
                            <div class="conv-texte"><?= e((string)$c['commentaire']) ?></div>
                            <?php if ($c['km'] !== null || ($c['partenaire_nom'] ?? null) !== null): ?>
                                <div class="conv-extra">
                                    <?php if ($c['km'] !== null): ?>
                                        <span class="badge badge-muted"><?= (int)$c['km'] ?> km</span>
                                    <?php endif; ?>
                                    <?php if (($c['partenaire_nom'] ?? null) !== null): ?>
                                        <span class="badge badge-brand"><?= e((string)$c['partenaire_nom']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($peutCommenter): ?>
            <form method="post" action="<?= url('/commentaires') ?>" class="conv-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="objet_type" value="<?= e($objetType) ?>">
                <input type="hidden" name="objet_id" value="<?= $objetId ?>">
                <div class="field">
                    <label class="form-label" for="conv_commentaire_<?= $objetId ?>">Nouveau commentaire <span class="req">*</span></label>
                    <textarea class="input" id="conv_commentaire_<?= $objetId ?>" name="commentaire" rows="3" maxlength="2000" required><?= e(old('commentaire')) ?></textarea>
                    <?php if (form_error('commentaire')): ?><p class="form-error"><?= e(form_error('commentaire')) ?></p><?php endif; ?>
                </div>
                <?php if ($avecKm): ?>
                    <div class="field">
                        <label class="form-label" for="conv_km_<?= $objetId ?>">Kilométrage</label>
                        <input class="input input-sm" type="number" min="0" step="1" id="conv_km_<?= $objetId ?>" name="km" value="<?= e(old('km')) ?>">
                        <?php if (form_error('km')): ?><p class="form-error"><?= e(form_error('km')) ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($optionsPartenaires !== []): ?>
                    <div class="field">
                        <label class="form-label" for="conv_partenaire_<?= $objetId ?>">Partenaire</label>
                        <select class="input input-sm" id="conv_partenaire_<?= $objetId ?>" name="partenaire_id">
                            <option value="">— Aucun —</option>
                            <?php foreach ($optionsPartenaires as $pid => $pnom): ?>
                                <option value="<?= (int)$pid ?>" <?= old('partenaire_id') === (string)$pid ? 'selected' : '' ?>><?= e((string)$pnom) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('partenaire_id')): ?><p class="form-error"><?= e(form_error('partenaire_id')) ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button class="btn btn-primary btn-sm" type="submit">Ajouter le commentaire</button>
                </div>
            </form>
        <?php else: ?>
            <p class="form-text">Vous ne disposez pas du droit d'ajouter des commentaires.</p>
        <?php endif; ?>

<?php if (!$compact): ?>
    </div>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<!-- AE-EOF : le fichier partials/commentaires.php doit se terminer exactement ici -->