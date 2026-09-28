<?php
// fichier : modules/prospects/views/fiche.php — v0.36
declare(strict_types=1);

use App\Core\View;

 $prospect         = $prospect ?? [];
 $parcoursLibelles = $parcoursLibelles ?? [];
 $estArchive       = (string)$prospect['statut'] === 'archive';
 $estConverti      = (int)$prospect['convertis'] === 1;
 $nomComplet       = trim((string)($prospect['prenom'] ?? '') . ' ' . (string)$prospect['nom']);

 $libellesStatut = ['nouveau' => 'Nouveau', 'traite' => 'Traité', 'archive' => 'Archivé'];

 $adresseComplete = trim(
    trim((string)($prospect['adresse'] ?? '')) . ' ' .
    trim((string)($prospect['code_postal'] ?? '')) . ' ' .
    trim((string)($prospect['ville'] ?? '')) . ' ' .
    trim((string)($prospect['pays'] ?? ''))
);
 $telPretty = telephone_pretty((string)($prospect['telephone'] ?? ''));
?>
<div class="page-form">
    <div class="page-head">
        <div>
            <h1 class="page-title"><?= e($nomComplet) ?></h1>
            <p class="page-subtitle">Prospect créé le <?= e((string)$prospect['ajout_le']) ?></p>
        </div>
        <div class="footer-actions">
            <span class="badge <?= (string)$prospect['statut'] === 'nouveau' ? 'badge-brand' : ((string)$prospect['statut'] === 'traite' ? 'badge-ok' : 'badge-muted') ?>">
                <?= e($libellesStatut[(string)$prospect['statut']] ?? (string)$prospect['statut']) ?>
            </span>
            <?php if ($estConverti): ?>
                <span class="badge badge-ok">Converti<?= e($prospect['convertis_date'] !== null ? ' le ' . (string)$prospect['convertis_date'] : '') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <a class="link-back" href="<?= url('/prospects') ?>">← Retour à la liste</a>

    <div class="card form-sheet">
        <section class="sheet-section">
            <div class="sheet-title">Actions</div>
            <div class="form-actions">
                <?php if (can('prospects.modifier') && !$estArchive): ?>
                    <a class="btn btn-ghost" href="<?= url('/prospects/' . (int)$prospect['id'] . '/modifier') ?>">Modifier</a>
                <?php endif; ?>
                <?php if (can('prospects.traiter') && !$estArchive && (string)$prospect['statut'] === 'nouveau'): ?>
                    <button type="button" class="btn btn-primary" data-modal="#modal-traiter"
                            data-modal-action="<?= url('/prospects/' . (int)$prospect['id'] . '/traiter') ?>">Marquer comme traité…</button>
                <?php endif; ?>
                <?php if (can('prospects.archiver') && !$estArchive): ?>
                    <button type="button" class="btn btn-danger" data-modal="#modal-archiver"
                            data-modal-action="<?= url('/prospects/' . (int)$prospect['id'] . '/archiver') ?>">Archiver…</button>
                <?php endif; ?>
                <?php if (can('prospects.convertir') && !$estConverti && !$estArchive): ?>
                    <form method="post" action="<?= url('/prospects/' . (int)$prospect['id'] . '/convertir') ?>"
                          data-confirm="Convertir « <?= e($nomComplet) ?> » en élève ?">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary" type="submit">Convertir en élève</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($estArchive && $prospect['motif_archivage'] !== null): ?>
                <p class="form-text">Commentaire d'archivage : <?= e((string)$prospect['motif_archivage']) ?></p>
            <?php endif; ?>
        </section>
    </div>

    <?php /* v0.36 : Informations | Commentaires côte à côte. */ ?>
    <div class="fiche-duo">
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Informations</div>
                <div class="form-grid">
                    <div class="field"><label class="form-label">Nom</label><p class="wizard-val"><?= e((string)$prospect['nom']) ?></p></div>
                    <div class="field"><label class="form-label">Prénom</label><p class="wizard-val"><?= e((string)($prospect['prenom'] ?? '—')) ?></p></div>
                    <div class="field"><label class="form-label">Date de naissance</label><p class="wizard-val"><?= e((string)($prospect['date_naissance'] ?? '—')) ?></p></div>
                    <div class="field"><label class="form-label">Email</label><p class="wizard-val"><?= e((string)($prospect['email'] ?? '—')) ?></p></div>
                    <div class="field field-full">
                        <label class="form-label">Téléphone</label>
                        <p class="wizard-val tel-pretty">
                            <?php if ($telPretty['complet'] !== ''): ?>
                                <?php if ($telPretty['iso'] !== ''): ?>
                                    <img class="phone-flag" src="https://flagcdn.com/w20/<?= e($telPretty['iso']) ?>.png" alt="">
                                <?php endif; ?>
                                <a href="tel:<?= e(preg_replace('#[\s.\-()]#', '', $telPretty['complet'])) ?>"><?= e($telPretty['complet']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="field"><label class="form-label">Type de permis</label><p class="wizard-val"><?= e((string)($prospect['type_permis_nom'] ?? '—')) ?></p></div>
                    <div class="field">
                        <label class="form-label">Provenance</label>
                        <p class="wizard-val">
                            <?php if (($prospect['provenance'] ?? null) !== null): ?>
                                <span class="badge badge-muted"><?= e((string)$prospect['provenance']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="field">
                        <label class="form-label">Lieu de préférence</label>
                        <p class="wizard-val">
                            <?php if (($prospect['domaine_nom'] ?? null) !== null): ?>
                                <span class="badge-entite" style="background-color:<?= e($prospect['domaine_couleur'] ?: '#64748b') ?>"><?= e((string)$prospect['domaine_nom']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="field">
                        <label class="form-label">Agence</label>
                        <p class="wizard-val">
                            <?php if (($prospect['agence_nom'] ?? null) !== null): ?>
                                <span class="badge-entite" style="background-color:<?= e($prospect['agence_couleur'] ?: '#64748b') ?>"><?= e((string)$prospect['agence_nom']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="field field-full"><label class="form-label">Adresse</label><p class="wizard-val"><?= e($adresseComplete !== '' ? $adresseComplete : '—') ?></p></div>
                    <?php if ((string)($prospect['commentaire'] ?? '') !== ''): ?>
                        <div class="field field-full"><label class="form-label">Commentaire initial</label><p class="wizard-val"><?= e((string)$prospect['commentaire']) ?></p></div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($parcoursLibelles !== []): ?>
            <section class="sheet-section">
                <div class="sheet-title">Parcours</div>
                <dl class="wizard-resume">
                    <?php foreach ($parcoursLibelles as [$libelleQ, $libelleR]): ?>
                        <div class="wizard-resume-row">
                            <dt><?= e($libelleQ) ?></dt>
                            <dd><?= e($libelleR) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
            <?php endif; ?>
        </div>

        <?php /* Colonne droite : commentaires (§33). */ ?>
        <?php if (isset($commentaires)): ?>
            <?php
            View::partial('partials/commentaires', [
                'objetType'          => $objetType ?? 'prospect',
                'objetId'            => $objetId ?? 0,
                'commentaires'       => $commentaires,
                'avecKm'             => false,
                'optionsPartenaires' => [],
            ]);
            ?>
        <?php endif; ?>
    </div>
</div>

<?php View::partial('@prospects/modales'); ?>
<!-- AE-EOF : le fichier prospects/fiche.php doit se terminer exactement ici -->