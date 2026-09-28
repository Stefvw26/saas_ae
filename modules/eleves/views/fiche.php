<?php
// fichier : modules/eleves/views/fiche.php — v0.48
// LAYOUT :
//   1. CARTE IDENTITÉ : badge genre (bleu/rose) AVANT le nom + badges
//      domaine/agence à droite.
//   2. CARTE ACTIONS : sous l'identité (boutons seuls).
//   3. COLONNES 40/60 : GAUCHE = Informations (naissance, LIEU DE
//      NAISSANCE en ligne propre, contact, adresse, PROVENANCE dessous)
//      → CARTE « BOÎTE & NIVEAU » (switches BA/BM + B1-B5) →
//      COMMENTAIRES ; DROITE = ONGLETS (dossiers réels — J7).
declare(strict_types=1);

use App\Core\View;

 $eleve      = $eleve ?? [];
 $nomComplet = trim((string)($eleve['prenom'] ?? '') . ' ' . (string)$eleve['nom']);
 $estArchive = (int)$eleve['supprimer'] === 0 && (int)$eleve['actif'] === 0;
 $telPretty  = telephone_pretty((string)($eleve['telephone'] ?? ''));
 $telLien    = $telPretty['complet'] !== '' ? preg_replace('#[\s.\-()]#', '', $telPretty['complet']) : '';
 $adresseComplete = trim(
    trim((string)($eleve['adresse'] ?? '')) . ' ' .
    trim((string)($eleve['code_postal'] ?? '')) . ' ' .
    trim((string)($eleve['ville'] ?? '')) . ' ' .
    trim((string)($eleve['pays'] ?? ''))
);

/* Badge genre : bleu = Monsieur, rose = Madame. */
 $genreBadge = '';
if ((string)($eleve['civilite'] ?? '') === 'Monsieur') {
    $genreBadge = '<span class="genre-badge genre-m" title="Masculin">&#9794;</span>';
} elseif ((string)($eleve['civilite'] ?? '') === 'Madame') {
    $genreBadge = '<span class="genre-badge genre-f" title="Féminin">&#9792;</span>';
}

 $agencesTransfert = [];
foreach (agences_accessibles() as $a) {
    if ((int)$a['id'] !== (int)$eleve['agence_id']) {
        $agencesTransfert[(int)$a['id']] = (string)$a['agence_nom'];
    }
}

 $dossiers = $dossiers ?? [];
 $evenements = $evenements ?? [];
?>
<div class="page-form">

    <?php /* ═══ 1. CARTE IDENTITÉ ═══ */ ?>
    <div class="card identite-card">
        <div class="identite-body">
            <div class="identite-nom">
                <h1 class="page-title"><?= $genreBadge ?><?= e($nomComplet) ?></h1>
                <p class="page-subtitle">
                    Élève créé le <?= e((string)$eleve['ajout_le']) ?>
                    <?php if ($estArchive): ?> · <span class="badge badge-muted">Archivé</span>
                    <?php elseif ((int)$eleve['actif'] === 1): ?> · <span class="badge badge-ok">Actif</span><?php endif; ?>
                </p>
            </div>
            <div class="identite-badges">
                <?php if ($eleve['domaine_nom'] !== null): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($eleve['domaine_couleur'] ?: '#64748b') ?>"><?= e((string)$eleve['domaine_nom']) ?></span>
                <?php endif; ?>
                <?php if ($eleve['agence_nom'] !== null): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($eleve['agence_couleur'] ?: '#64748b') ?>"><?= e((string)$eleve['agence_nom']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a class="link-back" href="<?= url('/eleves') ?>">← Retour à la liste</a>

    <?php /* ═══ 2. CARTE ACTIONS (boutons seuls) ═══ */ ?>
    <div class="card form-sheet actions-card-top">
        <section class="sheet-section">
            <div class="sheet-title">Actions</div>
            <div class="form-actions">
                <?php if (can('eleves.modifier') && (int)$eleve['actif'] === 1): ?>
                    <a class="btn btn-ghost" href="<?= url('/eleves/' . (int)$eleve['id'] . '/modifier') ?>">Modifier</a>
                <?php endif; ?>
                <?php if ((string)($eleve['email'] ?? '') !== ''): ?>
                    <a class="btn btn-ghost" href="mailto:<?= e($eleve['email']) ?>">Écrire un email</a>
                <?php endif; ?>
                <?php if ($telLien !== ''): ?>
                    <a class="btn btn-ghost" href="tel:<?= e($telLien) ?>">Appeler</a>
                <?php endif; ?>
                <button type="button" class="btn btn-ghost" disabled title="Disponible au Jalon 7 (dossiers)">Rapport complet (J7)</button>
                <?php if (can('eleves.agences.transfert') && (int)$eleve['actif'] === 1 && $agencesTransfert !== []): ?>
                    <button type="button" class="btn btn-primary" data-modal="#modal-transfert">Transférer d'agence…</button>
                <?php endif; ?>
                <?php if (can('eleves.supprimer') && (int)$eleve['actif'] === 1): ?>
                    <button type="button" class="btn btn-ghost" data-modal="#modal-archiver-eleve">Archiver…</button>
                <?php endif; ?>
                <?php if (can('eleves.supprimer')): ?>
                    <button type="button" class="btn btn-danger" data-modal="#modal-supprimer">Supprimer…</button>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php /* ═══ 3. COLONNES 40 / 60 ═══ */ ?>
    <div class="eleve-fiche-cols">

        <?php /* ---- GAUCHE : INFORMATIONS → BOÎTE & NIVEAU → COMMENTAIRES ---- */ ?>
        <div class="eleve-col-gauche">

            <?php /* Informations */ ?>
            <div class="card form-sheet">
                <section class="sheet-section">
                    <div class="sheet-title">Informations</div>
                    <div class="field-inline-list">

                        <div class="field-inline">
                            <span class="fi-label">Date de naissance</span>
                            <span class="fi-value"><?= e((string)($eleve['date_naissance'] ?? '—')) ?></span>
                        </div>

                        <?php /* Directive : lieu de naissance en ligne PROPRE. */ ?>
                        <div class="field-inline">
                            <span class="fi-label">Lieu de naissance</span>
                            <span class="fi-value"><?= e((string)($eleve['lieu_naissance'] ?? '—')) ?></span>
                        </div>

                        <div class="field-inline">
                            <span class="fi-label">Contact</span>
                            <span class="fi-value fi-contact">
                                <?php if ((string)($eleve['email'] ?? '') !== ''): ?>
                                    <a class="fi-link" href="mailto:<?= e($eleve['email']) ?>"><?= e($eleve['email']) ?></a>
                                <?php endif; ?>
                                <?php if ($telPretty['complet'] !== ''): ?>
                                    <?php if ((string)($eleve['email'] ?? '') !== ''): ?><span class="fi-sep">·</span><?php endif; ?>
                                    <span class="tel-pretty">
                                        <?php if ($telPretty['iso'] !== ''): ?>
                                            <img class="phone-flag" src="https://flagcdn.com/w20/<?= e($telPretty['iso']) ?>.png" alt="">
                                        <?php endif; ?>
                                        <a class="fi-link" href="tel:<?= e($telLien) ?>"><?= e($telPretty['complet']) ?></a>
                                    </span>
                                <?php endif; ?>
                                <?php if ((string)($eleve['email'] ?? '') === '' && $telPretty['complet'] === ''): ?>—<?php endif; ?>
                            </span>
                        </div>

                        <div class="field-inline">
                            <span class="fi-label">Adresse</span>
                            <span class="fi-value"><?= e($adresseComplete !== '' ? $adresseComplete : '—') ?></span>
                        </div>

                        <?php /* Directive : provenance SOUS l'adresse. */ ?>
                        <div class="field-inline">
                            <span class="fi-label">Provenance</span>
                            <span class="fi-value">
                                <?php if ((string)($eleve['provenance'] ?? '') !== ''): ?>
                                    <span class="badge badge-muted"><?= e((string)$eleve['provenance']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </span>
                        </div>

                        <?php if ((string)($eleve['responsable_nom'] ?? '') !== ''): ?>
                        <div class="field-inline">
                            <span class="fi-label">Responsable (mineur)</span>
                            <span class="fi-value">
                                <?= e((string)$eleve['responsable_nom']) ?>
                                <?php if ((string)($eleve['responsable_telephone'] ?? '') !== ''): ?>
                                    <span class="fi-sep">·</span> <?= e((string)$eleve['responsable_telephone']) ?>
                                <?php endif; ?>
                                <?php if ((string)($eleve['responsable_email'] ?? '') !== ''): ?>
                                    <span class="fi-sep">·</span> <?= e((string)$eleve['responsable_email']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <?php /* Directive : CARTE « BOÎTE & NIVEAU » entre Informations et Commentaires. */ ?>
            <?php if (can('eleves.modifier')): ?>
            <div class="card form-sheet boite-niveau-card">
                <section class="sheet-section">
                    <div class="sheet-title">Boîte &amp; niveau</div>

                    <div class="switches-bloc switches-solo">
                        <div class="switch-row">
                            <span class="switch-row-label">Boîte</span>
                            <div class="switch-group" data-switch-group="boite"
                                 data-url="<?= url('/eleves/' . (int)$eleve['id'] . '/boite') ?>">
                                <button type="button" class="sw<?= (string)$eleve['type_boite'] === 'BA' ? ' on' : '' ?>" data-value="BA" data-libelle="Boîte automatique (BA)">BA</button>
                                <button type="button" class="sw<?= (string)$eleve['type_boite'] === 'BM' ? ' on' : '' ?>" data-value="BM" data-libelle="Boîte manuelle (BM)">BM</button>
                                <button type="button" class="sw sw-off<?= $eleve['type_boite'] === null ? ' on' : '' ?>" data-value="" data-libelle="Aucune boîte" title="Aucun">&times;</button>
                            </div>
                            <span class="switch-libelle" data-switch-libelle="boite">
                                <?= e((string)$eleve['type_boite'] === 'BA' ? 'Boîte automatique' : ((string)$eleve['type_boite'] === 'BM' ? 'Boîte manuelle' : 'Non définie')) ?>
                            </span>
                        </div>

                        <div class="switch-row">
                            <span class="switch-row-label">Niveau élève</span>
                            <div class="switch-group" data-switch-group="niveauB"
                                 data-url="<?= url('/eleves/' . (int)$eleve['id'] . '/niveau-b') ?>">
                                <?php foreach (['B1', 'B2', 'B3', 'B4', 'B5'] as $b): ?>
                                    <button type="button" class="sw<?= (string)$eleve['type_b'] === $b ? ' on' : '' ?>" data-value="<?= e($b) ?>"><?= e($b) ?></button>
                                <?php endforeach; ?>
                                <button type="button" class="sw sw-off<?= $eleve['type_b'] === null ? ' on' : '' ?>" data-value="" data-libelle="Aucun niveau" title="Aucun">&times;</button>
                            </div>
                            <span class="switch-libelle" data-switch-libelle="niveauB">
                                <?= e((string)($eleve['type_b'] ?? '') !== '' ? 'Niveau ' . (string)$eleve['type_b'] : 'Non défini') ?>
                            </span>
                        </div>
                    </div>
                </section>
            </div>
            <?php endif; ?>

            <?php /* COMMENTAIRES */ ?>
            <?php if (isset($commentaires)): ?>
                <?php
                View::partial('partials/commentaires', [
                    'objetType'          => $objetType ?? 'eleve',
                    'objetId'            => $objetId ?? 0,
                    'commentaires'       => $commentaires,
                    'avecKm'             => false,
                    'optionsPartenaires' => [],
                ]);
                ?>
            <?php endif; ?>
        </div>

        <?php /* ---- DROITE : ONGLETS (dossiers réels — J7) ---- */ ?>
        <div class="eleve-col-droite">
            <div class="card form-sheet eleve-onglets">
                <section class="sheet-section">
                    <div class="sheet-title">Dossiers &amp; suivi</div>
                    <div class="tabs">
                        <button type="button" class="tab-btn active" data-tab="tab-dossiers" role="tab" aria-selected="true">Dossiers</button>
                        <button type="button" class="tab-btn" data-tab="tab-planning" role="tab" aria-selected="false">Planning des événements</button>
                        <button type="button" class="tab-btn" data-tab="tab-documents" role="tab" aria-selected="false">Documents</button>
                        <button type="button" class="tab-btn" data-tab="tab-outils" role="tab" aria-selected="false">Outils / Compte</button>
                    </div>

                    <?php /* Onglet DOSSIERS — données réelles (J7). */ ?>
                    <div class="tab-panel active" id="tab-dossiers" role="tabpanel">
                        <?php if (can('dossiers.creer') && !$estArchive): ?>
                            <div class="form-actions" style="margin-bottom:12px">
                                <a class="btn btn-primary btn-sm" href="<?= url('/dossiers/creer?eleve_id=' . (int)$eleve['id']) ?>">+ Créer un dossier</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($dossiers === []): ?>
                            <div class="empty-state">
                                <p class="empty-title">Aucun dossier</p>
                                <p class="empty-text">Cet élève ne possède pas encore de dossier.</p>
                            </div>
                        <?php else: ?>
                            <div class="dossiers-list">
                                <?php foreach ($dossiers as $dossier): ?>
                                    <a class="dossier-item" href="<?= url('/dossiers/' . (int)$dossier['id']) ?>">
                                        <span class="dossier-ref">Dossier #<?= (int)$dossier['id'] ?></span>
                                        <span class="badge <?= (string)$dossier['statut'] === 'valide' ? 'badge-ok' : ((string)$dossier['statut'] === 'confirme' ? 'badge-brand' : 'badge-muted') ?>">
                                            <?= e([
                                                'en_cours' => 'En cours',
                                                'valide'   => 'Panier validé',
                                                'confirme' => 'À confirmer',
                                            ][(string)$dossier['statut']] ?? (string)$dossier['statut']) ?>
                                        </span>
                                        <span class="dossier-meta">
                                            <?= e((string)($dossier['nb_evenements'] ?? 0)) ?> événement<?= (int)($dossier['nb_evenements'] ?? 0) > 1 ? 's' : '' ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php /* Onglet PLANNING — événements réels (J7). */ ?>
                    <div class="tab-panel" id="tab-planning" role="tabpanel">
                        <?php if ($evenements === []): ?>
                            <div class="empty-state">
                                <p class="empty-title">Aucun événement</p>
                                <p class="empty-text">Créez des événements depuis un dossier de l'élève.</p>
                            </div>
                        <?php else: ?>
                            <div class="evenements-list">
                                <?php foreach ($evenements as $ev): ?>
                                    <div class="evenement-item">
                                        <span class="ev-date"><?= e((string)$ev['date_heure']) ?></span>
                                        <span class="ev-titre"><?= e((string)$ev['titre']) ?></span>
                                        <?php if ((string)($ev['type'] ?? '') !== ''): ?>
                                            <span class="badge badge-muted"><?= e((string)$ev['type']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-panel" id="tab-documents" role="tabpanel">
                        <div class="empty-state">
                            <p class="empty-title">Documents — à venir</p>
                            <p class="empty-text">Les documents liés à l'élève apparaîtront ici lorsque le module sera développé.</p>
                        </div>
                    </div>
                    <div class="tab-panel" id="tab-outils" role="tabpanel">
                        <div class="empty-state">
                            <p class="empty-title">Outils / Compte — à définir</p>
                            <p class="empty-text">Contenu à définir avec vous avant développement.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php /* ═══ MODALES ═══ */ ?>
<?php if (can('eleves.agences.transfert') && (int)$eleve['actif'] === 1 && $agencesTransfert !== []): ?>
<div class="modal-overlay" id="modal-transfert">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-transfert-titre">
        <div class="modal-header">
            <span id="modal-transfert-titre">Transférer « <?= e($nomComplet) ?> »</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">
                Agence actuelle : <strong><?= e((string)($eleve['agence_nom'] ?? '—')) ?></strong>.
                Sélectionnez l'agence de destination (parmi celles qui vous sont accessibles).
            </p>
            <form method="post" action="<?= url('/eleves/' . (int)$eleve['id'] . '/transferer') ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="transfert_agence">Agence de destination <span class="req">*</span></label>
                    <select class="input" id="transfert_agence" name="agence_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($agencesTransfert as $idAg => $nomAg): ?>
                            <option value="<?= (int)$idAg ?>"><?= e($nomAg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Transférer l'élève</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (can('eleves.supprimer') && (int)$eleve['actif'] === 1): ?>
<div class="modal-overlay" id="modal-archiver-eleve">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-arch-eleve-titre">
        <div class="modal-header">
            <span id="modal-arch-eleve-titre">Archiver « <?= e($nomComplet) ?> »</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">
                L'archivage retire l'élève de la liste active (et archive ses dossiers) tout en le
                conservant — il restera consultable via le filtre « Archives ».
            </p>
            <form method="post" action="<?= url('/eleves/' . (int)$eleve['id'] . '/archiver') ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="commentaire_archivage">Commentaire (facultatif)</label>
                    <textarea class="input" id="commentaire_archivage" name="commentaire_archivage" rows="3" maxlength="2000"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Archiver l'élève</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (can('eleves.supprimer')): ?>
<div class="modal-overlay" id="modal-supprimer">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-suppr-titre">
        <div class="modal-header">
            <span id="modal-suppr-titre">Supprimer « <?= e($nomComplet) ?> »</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">
                Suppression logique — <strong>aucune donnée n'est effacée physiquement</strong> :
                l'élève est conservé (supprimer/supprimer_par/supprimer_date) avec le motif ci-dessous,
                mais il n'apparaîtra plus dans aucune liste ni fiche.
            </p>
            <form method="post" action="<?= url('/eleves/' . (int)$eleve['id'] . '/supprimer') ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="motif_suppression">Motif de la suppression <span class="req">*</span></label>
                    <textarea class="input" id="motif_suppression" name="motif_suppression" rows="3" maxlength="255" required></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-danger" type="submit">Supprimer l'élève</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- AE-EOF : le fichier eleves/views/fiche.php doit se terminer exactement ici -->