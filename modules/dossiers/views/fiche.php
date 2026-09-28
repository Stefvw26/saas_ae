<?php
// fichier : modules/dossiers/views/fiche.php — v1.0 (refonte complète, Jalon 7)
declare(strict_types=1);

use App\Core\View;

 $dossier    = $dossier ?? [];
 $evenements = $evenements ?? [];
 $panier     = $panier ?? [];
 $declarations = $declarations ?? [];
 $echeances  = $echeances ?? [];
 $checklistObligatoires = $checklistObligatoires ?? [];
 $documentsTous = $documentsTous ?? [];
 $documentsLibres = array_values(array_filter($documentsTous, static fn($d) => $d['type'] === 'libre'));
 $optionsModesPaiement = $optionsModesPaiement ?? [];

 $eleveNom = trim((string)($dossier['eleve_prenom'] ?? '') . ' ' . (string)($dossier['eleve_nom'] ?? ''));
 $etatContrat = (string)($dossier['etat_contrat'] ?? 'a_confirmer');
 $panierValide = $etatContrat !== 'a_confirmer';
 $contratGenere = $etatContrat === 'contrat_genere';
 $estParis = mb_strtolower(trim((string)($dossier['domaine_nom'] ?? ''))) === 'paris';

 $euro = static function ($x): string {
    return number_format((float)($x ?? 0), 2, ',', ' ') . ' €';
};

/* Récap : nom de la formule si mode=formule, sinon "Prestation à la carte (x)". */
 $recapLabel = ((string)($dossier['mode'] ?? 'a_la_carte') === 'formule' && !empty($dossier['formule_nom']))
    ? (string)$dossier['formule_nom']
    : 'Prestation à la carte (' . (int)($dossier['nb_prestations'] ?? 0) . ')';

/* Icônes d'état — accumulation progressive (directive utilisateur). */
 $icones = [];
if (!$panierValide) {
    $icones[] = ['label' => 'Panier non validé', 'ok' => false];
} else {
    $icones[] = ['label' => 'Panier validé', 'ok' => true];
    $icones[] = $contratGenere
        ? ['label' => 'Contrat généré', 'ok' => true]
        : ['label' => 'Contrat non généré (à confirmer)', 'ok' => false];
}
foreach (['cerfa' => 'CERFA', 'ediser' => 'EDISER', 'neph' => 'NEPH'] as $cleDecl => $libelleDecl) {
    $fait = isset($declarations[$cleDecl]);
    $icones[] = ['label' => $libelleDecl . ' ' . ($fait ? 'déclaré' : 'non déclaré'), 'ok' => $fait];
}
/* Devis (tant que le contrat n'est pas généré) et Contrat signé (une fois le contrat généré). */
 $devisAjoute = null;
foreach ($documentsTous as $docDevis) {
    if ($docDevis['type'] === 'devis') { $devisAjoute = $docDevis; break; }
}
 $contratSigne = null;
foreach ($documentsTous as $docCtr) {
    if ($docCtr['type'] === 'contrat_signe') { $contratSigne = $docCtr; break; }
}
if (!$contratGenere) {
    $icones[] = ['label' => 'Devis ' . ($devisAjoute !== null ? 'ajouté' : 'non ajouté'), 'ok' => $devisAjoute !== null];
} else {
    $icones[] = ['label' => 'Contrat signé ' . ($contratSigne !== null ? 'ajouté' : 'non ajouté'), 'ok' => $contratSigne !== null];
}

 $genreBadge = '';
if ((string)($dossier['civilite'] ?? '') === 'Monsieur') {
    $genreBadge = '<span class="genre-badge genre-m" title="Masculin">&#9794;</span>';
} elseif ((string)($dossier['civilite'] ?? '') === 'Madame') {
    $genreBadge = '<span class="genre-badge genre-f" title="Féminin">&#9792;</span>';
}

 $libellesJustificatif = [
    'geste_commercial'    => 'Geste commercial',
    'promotion'           => 'Promotion',
    'remise_exceptionnelle' => 'Remise exceptionnelle',
];

/* Date de base pour le calcul des échéances (+1 mois par échéance) — utilisée côté JS. */
 $dateBase = substr((string)($dossier['panier_valide_le'] ?? $dossier['ajout_le'] ?? date('Y-m-d')), 0, 10);
?>
<style>
.icones-etat { display: flex; flex-wrap: wrap; gap: 8px; margin: 4px 0 16px; }
.icones-etat .badge { font-size: .8rem; }
.recap-ligne { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.recap-infos { display: flex; flex-wrap: wrap; gap: 22px; }
.recap-infos .fi-label { display: block; font-size: .74rem; color: var(--ink-3); }
.recap-infos .fi-value { font-weight: 700; }
.badge-montant { font-size: .95rem; font-weight: 800; padding: 8px 16px; }
.checklist-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line-soft); }
.checklist-item:last-child { border-bottom: none; }
.checklist-nom { flex: 1; font-weight: 600; }
.doc-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--line-soft); }
.doc-item:last-child { border-bottom: none; }
.placeholder-tab { padding: 30px 10px; text-align: center; color: var(--ink-3); }

/* Récapitulatif en cartouches, sur une ligne. */
.stats-row { display: flex; flex-wrap: wrap; gap: 12px; }
.stat-cartouche {
    background: var(--line-soft, #f1f5f9); border-radius: 12px; padding: 12px 20px; min-width: 160px;
}
.stat-cartouche .stat-label { display: block; font-size: .74rem; color: var(--ink-3); margin-bottom: 4px; }
.stat-cartouche .stat-value { font-weight: 800; font-size: 1.05rem; }
.stat-cartouche.stat-warning { background: var(--warn-soft, #fef3c7); }
.stat-cartouche.stat-warning .stat-value { color: var(--warn-ink, #92400e); }
.stat-cartouche.stat-ok { background: var(--ok-soft, #dcfce7); }
.stat-cartouche.stat-ok .stat-value { color: var(--ok-ink, #166534); }

/* Lignes d'échéances générées dynamiquement. */
.echeance-ligne { padding: 14px 0; border-top: 1px solid var(--line-soft); }
.echeance-ligne:first-child { border-top: none; }
</style>

<div class="page-form">
    <div class="card identite-card">
        <div class="identite-body">
            <div class="identite-nom">
                <h1 class="page-title"><?= $genreBadge ?><?= e($eleveNom) ?></h1>
                <p class="page-subtitle">Dossier #<?= (int)$dossier['id'] ?></p>
            </div>
            <div class="identite-badges">
                <?php if (!empty($dossier['domaine_nom'])): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($dossier['domaine_couleur'] ?? '#64748b') ?>"><?= e((string)$dossier['domaine_nom']) ?></span>
                <?php endif; ?>
                <?php if (!empty($dossier['agence_nom'])): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($dossier['agence_couleur'] ?? '#64748b') ?>"><?= e((string)$dossier['agence_nom']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a class="link-back" href="<?= url('/eleves/' . (int)$dossier['eleve_id']) ?>">← Retour à la fiche élève</a>

    <?php /* ═══ CARD RÉCAP ═══ */ ?>
    <div class="card form-sheet">
        <section class="sheet-section">
            <div class="recap-ligne">
                <div class="recap-infos">
                    <div>
                        <span class="fi-label">Type de permis</span>
                        <span class="fi-value"><?= e((string)($dossier['type_permis_nom'] ?? '—')) ?></span>
                    </div>
                    <div>
                        <span class="fi-label">Sélection</span>
                        <span class="fi-value"><?= e($recapLabel) ?></span>
                    </div>
                </div>
                <span class="badge badge-ok badge-montant">Montant TTC : <?= e($euro($dossier['montant_ttc'] ?? 0)) ?></span>
            </div>
        </section>
    </div>

    <?php /* ═══ ICÔNES D'ÉTAT ═══ */ ?>
    <div class="icones-etat">
        <?php foreach ($icones as $ic): ?>
            <span class="badge <?= $ic['ok'] ? 'badge-ok' : 'badge-warning' ?>"><?= e($ic['label']) ?></span>
        <?php endforeach; ?>
    </div>

    <?php /* ═══ CARD ACTIONS ═══ */ ?>
    <div class="card form-sheet actions-card-top">
        <section class="sheet-section">
            <div class="sheet-title">Actions</div>
            <div class="form-actions">
                <?php if (can('dossiers.modifier')): ?>

                    <?php if (!$contratGenere): ?>
                        <button type="button" class="btn btn-ghost" data-modal="#modal-devis">
                            <?= $devisAjoute !== null ? 'Remplacer le devis' : 'Ajouter le devis' ?>
                        </button>
                    <?php endif; ?>

                    <?php if (!$panierValide): ?>
                        <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/panier/valider') ?>"
                              data-confirm="Valider le panier de ce dossier ? Cette action est irréversible.">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary" type="submit">Valider panier</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($panierValide && !$contratGenere): ?>
                        <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/contrat/generer') ?>"
                              data-confirm="Générer le contrat de ce dossier ?">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary" type="submit">Générer contrat</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($contratGenere): ?>
                        <button type="button" class="btn btn-ghost" data-modal="#modal-contrat-signe">
                            <?= $contratSigne !== null ? 'Remplacer le contrat signé' : 'Ajouter le contrat signé' ?>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-ghost" data-modal="#modal-decl-cerfa">
                        <?= isset($declarations['cerfa']) ? 'Modifier' : 'Déclarer' ?> envoi CERFA
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal="#modal-decl-ediser">
                        <?= isset($declarations['ediser']) ? 'Modifier' : 'Déclarer' ?> EDISER
                    </button>
                    <button type="button" class="btn btn-ghost" data-modal="#modal-decl-neph">
                        <?= isset($declarations['neph']) ? 'Modifier' : 'Déclarer' ?> NEPH
                    </button>

                <?php endif; ?>

                <a class="btn btn-ghost" href="<?= url('/eleves/' . (int)$dossier['eleve_id']) ?>">Fiche élève</a>

                <?php if (can('dossiers.supprimer')): ?>
                    <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/archiver') ?>"
                          data-confirm="Archiver le dossier #<?= (int)$dossier['id'] ?> ?">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost" type="submit">Archiver</button>
                    </form>
                    <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/supprimer') ?>"
                          data-confirm="Supprimer le dossier #<?= (int)$dossier['id'] ?> ? Cette action est irréversible.">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php /* ═══ ONGLETS ═══ */ ?>
    <div class="card form-sheet eleve-onglets">
        <section class="sheet-section">
            <div class="tabs">
                <button type="button" class="tab-btn active" data-tab="tab-panier" role="tab" aria-selected="true">Panier</button>
                <button type="button" class="tab-btn <?= $panierValide ? '' : 'is-disabled' ?>" data-tab="tab-remise" role="tab" aria-selected="false">Remise et règlements</button>
                <button type="button" class="tab-btn <?= $contratGenere ? '' : 'is-disabled' ?>" data-tab="tab-consommations" role="tab" aria-selected="false">Consommations</button>
                <button type="button" class="tab-btn <?= $contratGenere ? '' : 'is-disabled' ?>" data-tab="tab-formations" role="tab" aria-selected="false">Formations</button>
                <?php if (!$estParis): ?>
                    <button type="button" class="tab-btn <?= $contratGenere ? '' : 'is-disabled' ?>" data-tab="tab-sejour" role="tab" aria-selected="false">Séjour</button>
                <?php endif; ?>
                <button type="button" class="tab-btn" data-tab="tab-obligatoires" role="tab" aria-selected="false">Documents obligatoires</button>
                <button type="button" class="tab-btn" data-tab="tab-documents" role="tab" aria-selected="false">Documents</button>
                <button type="button" class="tab-btn" data-tab="tab-commentaires" role="tab" aria-selected="false">Commentaires</button>
            </div>

            <?php /* ---- TAB PANIER ---- */ ?>
            <div class="tab-panel active" id="tab-panier" role="tabpanel">
                <?php if (!$panierValide && can('dossiers.modifier')): ?>
                    <p class="form-text">
                        La composition du panier (ajout de prestations, import de formule, quantités) se fait
                        depuis la fiche de modification du dossier.
                        <a href="<?= url('/dossiers/' . (int)$dossier['id'] . '/modifier') ?>">Gérer le panier →</a>
                    </p>
                <?php endif; ?>
                <?php if ($panier === []): ?>
                    <div class="empty-state">
                        <p class="empty-title">Panier vide</p>
                        <p class="empty-text">Aucune prestation sélectionnée pour l'instant.</p>
                    </div>
                <?php else: ?>
                    <div class="table-overflow">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Prestation</th><th>Prix unitaire</th><th class="center">Qté</th><th>Total</th>
                                    <th class="center">Offert</th><th class="center">CPF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($panier as $ligne): ?>
                                    <tr>
                                        <td><strong><?= e((string)$ligne['titre']) ?></strong></td>
                                        <td><?= e($euro($ligne['prix_vente'])) ?></td>
                                        <td class="center"><?= (int)$ligne['quantite'] ?></td>
                                        <td><strong><?= e($euro($ligne['total_ligne'])) ?></strong></td>
                                        <td class="center"><?= (int)$ligne['offert'] === 1 ? '✔' : '—' ?></td>
                                        <td class="center"><?= (int)$ligne['cpf'] === 1 ? '✔' : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="text-align:right;margin-top:10px">
                        <span class="badge badge-ok badge-montant">Total : <?= e($euro($panierTotal ?? 0)) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <?php /* ---- TAB REMISE ET RÈGLEMENTS ---- */ ?>
            <div class="tab-panel" id="tab-remise" role="tabpanel">
                <?php if (!$panierValide): ?>
                    <div class="placeholder-tab">Disponible une fois le panier validé.</div>
                <?php else: ?>

                    <?php /* ---- CARD Récapitulatif ---- */ ?>
                    <div class="card form-sheet">
                        <section class="sheet-section">
                            <div class="sheet-title">Récapitulatif</div>
                            <div class="stats-row">
                                <div class="stat-cartouche">
                                    <span class="stat-label">Montant panier</span>
                                    <span class="stat-value"><?= e($euro($panierTotal ?? 0)) ?></span>
                                </div>
                                <?php if (!empty($dossier['remise_montant'])): ?>
                                    <div class="stat-cartouche stat-warning">
                                        <span class="stat-label">Remise</span>
                                        <span class="stat-value">- <?= e($euro($dossier['remise_montant'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-cartouche stat-ok">
                                    <span class="stat-label">Montant TTC dû</span>
                                    <span class="stat-value"><?= e($euro($dossier['montant_ttc'] ?? 0)) ?></span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <?php /* ---- CARD Remise ---- */ ?>
                    <div class="card form-sheet">
                        <section class="sheet-section">
                            <div class="sheet-title">Remise</div>
                            <?php if (!$contratGenere && can('dossiers.modifier')): ?>
                                <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/remise') ?>" class="form-grid">
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label class="form-label" for="remise_justificatif">Justificatif</label>
                                        <select class="input" id="remise_justificatif" name="remise_justificatif">
                                            <option value="">— Aucune —</option>
                                            <?php foreach ($libellesJustificatif as $val => $lbl): ?>
                                                <option value="<?= e($val) ?>" <?= (string)($dossier['remise_justificatif'] ?? '') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="form-label" for="remise_montant">Montant</label>
                                        <input class="input" type="number" step="0.01" min="0" id="remise_montant" name="remise_montant"
                                               value="<?= e((string)($dossier['remise_montant'] ?? '')) ?>">
                                    </div>
                                    <div class="field field-full">
                                        <label class="form-label" for="remise_commentaire">Commentaire</label>
                                        <input class="input" type="text" id="remise_commentaire" name="remise_commentaire" maxlength="500"
                                               value="<?= e((string)($dossier['remise_commentaire'] ?? '')) ?>">
                                    </div>
                                    <div class="form-actions">
                                        <button class="btn btn-primary btn-sm" type="submit">Enregistrer la remise</button>
                                    </div>
                                </form>
                            <?php elseif (!empty($dossier['remise_montant'])): ?>
                                <p class="form-text">
                                    <?= e($libellesJustificatif[$dossier['remise_justificatif']] ?? (string)$dossier['remise_justificatif']) ?>
                                    — <strong><?= e($euro($dossier['remise_montant'])) ?></strong>
                                    <?php if (!empty($dossier['remise_commentaire'])): ?> · <?= e((string)$dossier['remise_commentaire']) ?><?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="form-text">Aucune remise appliquée.</p>
                            <?php endif; ?>
                        </section>
                    </div>

                    <?php /* ---- CARD Modalités de paiement ---- */ ?>
                    <div class="card form-sheet">
                        <section class="sheet-section">
                            <div class="sheet-title">Modalités de paiement</div>
                            <p class="form-text">
                                <?= $estParis ? '4 échéances maximum pour les formations à Paris.' : '3 échéances maximum pour les formations en Province.' ?>
                                (paramétrable par domaine dans Administration &gt; Domaines)
                            </p>

                            <?php if (!$contratGenere && can('dossiers.modifier')): ?>
                                <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/echeances/definir') ?>" id="form-echeances-definir">
                                    <?= csrf_field() ?>
                                    <div class="field" style="max-width:280px">
                                        <label class="form-label" for="ech-nombre">Nombre d'échéances</label>
                                        <select class="input" id="ech-nombre" name="nombre">
                                            <option value="">— Choisir —</option>
                                            <?php for ($n = 1; $n <= (int)($dossier['nb_echeances_max'] ?? 3); $n++): ?>
                                                <option value="<?= $n ?>" <?= count($echeances) === $n ? 'selected' : '' ?>><?= $n ?> échéance<?= $n > 1 ? 's' : '' ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div id="ech-lignes"></div>
                                    <div class="form-actions" id="ech-submit-wrap" hidden>
                                        <button class="btn btn-primary btn-sm" type="submit"><?= $echeances !== [] ? 'Mettre à jour les échéances' : 'Valider les échéances' ?></button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if ($echeances !== []): ?>
                                <div class="table-overflow" style="margin-top:16px">
                                    <table class="table">
                                        <thead>
                                            <tr><th>Date d'échéance</th><th>Mode de paiement</th><th>Montant TTC</th><th>Date de paiement</th><th>État</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($echeances as $ech): ?>
                                                <tr>
                                                    <td><?= e((string)$ech['date_echeance']) ?></td>
                                                    <td><?= e((string)($ech['mode_paiement_nom'] ?? '—')) ?></td>
                                                    <td><?= e($euro($ech['montant_ttc'])) ?></td>
                                                    <td><?= e((string)($ech['date_paiement'] ?? '—')) ?></td>
                                                    <td><span class="badge <?= $ech['etat'] === 'payee' ? 'badge-ok' : 'badge-muted' ?>"><?= e((string)$ech['etat']) ?></span></td>
                                                    <td>
                                                        <?php if (can('dossiers.modifier')): ?>
                                                            <button type="button" class="btn btn-sm btn-ghost" data-modal="#modal-echeance-<?= (int)$ech['id'] ?>">Mettre à jour</button>
                                                            <?php if (!$contratGenere): ?>
                                                                <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/echeances/' . (int)$ech['id'] . '/supprimer') ?>"
                                                                      style="display:inline" data-confirm="Supprimer cette échéance ?">
                                                                    <?= csrf_field() ?>
                                                                    <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                <?php endif; ?>
            </div>


            <?php /* ---- TABS PLACEHOLDER (jalon Consommations, non spécifié) ---- */ ?>
            <div class="tab-panel" id="tab-consommations" role="tabpanel">
                <div class="placeholder-tab">À venir — contenu défini dans un prochain jalon (Consommations).</div>
            </div>
            <div class="tab-panel" id="tab-formations" role="tabpanel">
                <div class="placeholder-tab">À venir — contenu défini dans un prochain jalon (Formations).</div>
            </div>
            <?php if (!$estParis): ?>
                <div class="tab-panel" id="tab-sejour" role="tabpanel">
                    <div class="placeholder-tab">À venir — module Séjours (Jalon 9, non encore développé).</div>
                </div>
            <?php endif; ?>

            <?php /* ---- TAB DOCUMENTS OBLIGATOIRES ---- */ ?>
            <div class="tab-panel" id="tab-obligatoires" role="tabpanel">
                <?php if ($checklistObligatoires === []): ?>
                    <div class="empty-state">
                        <p class="empty-title">Aucun document obligatoire défini</p>
                        <p class="empty-text">Configurez-les dans Administration &gt; Référentiels &gt; Documents obligatoires.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($checklistObligatoires as $item): ?>
                        <div class="checklist-item">
                            <span class="checklist-nom"><?= e((string)$item['nom']) ?></span>
                            <span class="badge <?= (int)$item['recu'] === 1 ? 'badge-ok' : 'badge-warning' ?>">
                                <?= (int)$item['recu'] === 1 ? 'Reçu' : 'Non reçu' ?>
                            </span>
                            <?php if (can('dossiers.modifier')): ?>
                                <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/obligatoires/' . (int)$item['document_obligatoire_id']) ?>"
                                      enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px">
                                    <?= csrf_field() ?>
                                    <input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png" style="max-width:180px">
                                    <input type="hidden" name="recu" value="<?= (int)$item['recu'] === 1 ? '0' : '1' ?>">
                                    <button class="btn btn-sm btn-ghost" type="submit">
                                        <?= (int)$item['recu'] === 1 ? 'Marquer non reçu' : 'Marquer reçu' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php /* ---- TAB DOCUMENTS (devis/contrat générés + libres) ---- */ ?>
            <div class="tab-panel" id="tab-documents" role="tabpanel">
                <?php if ($documentsLibres === []): ?>
                    <div class="empty-state">
                        <p class="empty-title">Aucun document</p>
                        <p class="empty-text">Devis, contrat, ou tout document libre (attestation, procuration…).</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documentsLibres as $doc): ?>
                        <div class="doc-item">
                            <span><strong><?= e((string)$doc['libelle']) ?></strong> — <?= e((string)$doc['nom_original']) ?></span>
                            <span class="form-actions">
                                <a class="btn btn-sm btn-ghost" href="<?= url('/storage/uploads/' . (string)$doc['chemin']) ?>" target="_blank" rel="noopener">Ouvrir</a>
                                <?php if (can('dossiers.modifier')): ?>
                                    <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/documents/' . (int)$doc['id'] . '/supprimer') ?>"
                                          data-confirm="Supprimer ce document ?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (can('dossiers.modifier')): ?>
                    <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/documents') ?>" enctype="multipart/form-data"
                          class="form-grid" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line-soft)">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label class="form-label" for="doc_libelle">Libellé</label>
                            <input class="input" type="text" id="doc_libelle" name="libelle" maxlength="150" required
                                   placeholder="ex. Attestation d'assurance, Procuration…">
                        </div>
                        <div class="field">
                            <label class="form-label" for="doc_fichier">Fichier (PDF, JPG, PNG)</label>
                            <input type="file" id="doc_fichier" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-primary btn-sm" type="submit">+ Ajouter le document</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <?php /* ---- TAB COMMENTAIRES ---- */ ?>
            <div class="tab-panel" id="tab-commentaires" role="tabpanel">
                <?php if (isset($commentaires)):
                    View::partial('partials/commentaires', [
                        'objetType'          => $objetType ?? 'dossier',
                        'objetId'            => $objetId ?? 0,
                        'commentaires'       => $commentaires,
                        'avecKm'             => false,
                        'optionsPartenaires' => [],
                    ]);
                endif; ?>
            </div>
        </section>
    </div>
</div>

<?php /* ═══ MODALES : déclarations CERFA / EDISER / NEPH ═══ */ ?>
<?php
 $declCerfa  = $declarations['cerfa']  ?? [];
 $declEdiser = $declarations['ediser'] ?? [];
 $declNeph   = $declarations['neph']   ?? [];
?>
<div class="modal-overlay" id="modal-devis">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Ajouter le devis</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/devis') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="devis_fichier">Fichier du devis (PDF, JPG, PNG) <span class="req">*</span></label>
                    <input type="file" id="devis_fichier" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-contrat-signe">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Ajouter le contrat signé</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/documents') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="libelle" value="Contrat signé">
                <input type="hidden" name="type" value="contrat_signe">
                <div class="field">
                    <label class="form-label" for="ctr_signe_fichier">Fichier du contrat signé (PDF, JPG, PNG) <span class="req">*</span></label>
                    <input type="file" id="ctr_signe_fichier" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-decl-cerfa">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Déclarer l'envoi CERFA</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/declaration') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="cerfa">
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="cerfa_date_envoi">Date d'envoi du formulaire <span class="req">*</span></label>
                        <input class="input" type="date" id="cerfa_date_envoi" name="date_envoi" required
                               value="<?= e((string)($declCerfa['date_envoi'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="cerfa_date_reception">Date de réception de la validation</label>
                        <input class="input" type="date" id="cerfa_date_reception" name="date_reception"
                               value="<?= e((string)($declCerfa['date_reception'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="cerfa_numero">N°</label>
                        <input class="input" type="text" id="cerfa_numero" name="numero" maxlength="100"
                               value="<?= e((string)($declCerfa['numero'] ?? '')) ?>">
                    </div>
                    <div class="field field-full">
                        <label class="form-label" for="cerfa_fichier">Joindre le fichier CERFA (PDF, JPG, PNG)</label>
                        <input type="file" id="cerfa_fichier" name="fichier_cerfa" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="field field-full">
                        <label class="form-label" for="cerfa_commentaire">Commentaire fichier</label>
                        <input class="input" type="text" id="cerfa_commentaire" name="commentaire" maxlength="500"
                               value="<?= e((string)($declCerfa['commentaire'] ?? '')) ?>">
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-decl-ediser">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Déclarer EDISER</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/declaration') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="ediser">
                <div class="field">
                    <label class="form-label" for="ediser_numero">Code élève <span class="req">*</span></label>
                    <input class="input" type="text" id="ediser_numero" name="numero" maxlength="100" required
                           value="<?= e((string)($declEdiser['numero'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label class="form-label" for="ediser_commentaire">Commentaire</label>
                    <input class="input" type="text" id="ediser_commentaire" name="commentaire" maxlength="500"
                           value="<?= e((string)($declEdiser['commentaire'] ?? '')) ?>">
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-decl-neph">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Déclarer NEPH</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/declaration') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="neph">
                <div class="field">
                    <label class="form-label" for="neph_numero">N° NEPH <span class="req">*</span></label>
                    <input class="input" type="text" id="neph_numero" name="numero" maxlength="100" required
                           value="<?= e((string)($declNeph['numero'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label class="form-label" for="neph_commentaire">Commentaire</label>
                    <input class="input" type="text" id="neph_commentaire" name="commentaire" maxlength="500"
                           value="<?= e((string)($declNeph['commentaire'] ?? '')) ?>">
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<?php /* ═══ MODALES : mise à jour d'une échéance (une par ligne, après génération du contrat) ═══ */ ?>
<?php foreach ($echeances as $ech): ?>
    <div class="modal-overlay" id="modal-echeance-<?= (int)$ech['id'] ?>">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span>Échéance n°<?= (int)$ech['numero_echeance'] ?></span>
                <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= url('/dossiers/' . (int)$dossier['id'] . '/echeances/' . (int)$ech['id']) ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <?php if (!$contratGenere): ?>
                            <div class="field">
                                <label class="form-label">Date d'échéance</label>
                                <input class="input" type="date" name="date_echeance" value="<?= e((string)$ech['date_echeance']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="field">
                                <label class="form-label">Date de paiement <span class="req">*</span></label>
                                <input class="input" type="date" name="date_paiement" value="<?= e((string)($ech['date_paiement'] ?? '')) ?>">
                            </div>
                        <?php endif; ?>
                        <div class="field">
                            <label class="form-label">Mode de paiement <span class="req">*</span></label>
                            <select class="input" name="mode_paiement_id">
                                <option value="">—</option>
                                <?php foreach ($optionsModesPaiement as $idMp => $nomMp): ?>
                                    <option value="<?= (int)$idMp ?>" <?= (int)($ech['mode_paiement_id'] ?? 0) === (int)$idMp ? 'selected' : '' ?>><?= e($nomMp) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="form-label">Montant TTC <span class="req">*</span></label>
                            <input class="input" type="number" step="0.01" min="0" name="montant_ttc" value="<?= e((string)$ech['montant_ttc']) ?>">
                        </div>
                        <?php if ($contratGenere): ?>
                            <div class="field field-full">
                                <label class="form-label">Commentaire</label>
                                <input class="input" type="text" name="commentaire" maxlength="500" value="<?= e((string)($ech['commentaire'] ?? '')) ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">Mettre à jour</button></div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php /* ═══ JS : génération dynamique des lignes d'échéances selon le nombre choisi ═══ */ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectNombre = document.getElementById('ech-nombre');
    if (!selectNombre) { return; }

    var conteneur   = document.getElementById('ech-lignes');
    var wrapSubmit  = document.getElementById('ech-submit-wrap');
    var baseDate    = '<?= e($dateBase) ?>';
    var modesOptions = <?= json_encode($optionsModesPaiement, JSON_UNESCAPED_UNICODE) ?>;
    var echeancesExistantes = <?= json_encode(array_map(static fn($e) => [
        'date_echeance'    => $e['date_echeance'],
        'mode_paiement_id' => $e['mode_paiement_id'],
        'montant_ttc'      => $e['montant_ttc'],
    ], $echeances), JSON_UNESCAPED_UNICODE) ?>;

    function ajouterMois(dateStr, n) {
        var d = new Date(dateStr + 'T00:00:00');
        d.setMonth(d.getMonth() + n);
        return d.toISOString().slice(0, 10);
    }

    function optionsModes(modeSelectionne) {
        var html = '<option value="">—</option>';
        for (var id in modesOptions) {
            if (Object.prototype.hasOwnProperty.call(modesOptions, id)) {
                var sel = (modeSelectionne && String(modeSelectionne) === String(id)) ? ' selected' : '';
                html += '<option value="' + id + '"' + sel + '>' + modesOptions[id] + '</option>';
            }
        }
        return html;
    }

    function genererLignes(n) {
        conteneur.innerHTML = '';
        if (n > 0) {
            for (var i = 0; i < n; i++) {
                var existante = echeancesExistantes[i] || null;
                var div = document.createElement('div');
                div.className = 'echeance-ligne';
                div.innerHTML =
                    '<div class="sheet-title" style="font-size:.85rem">Échéance n°' + (i + 1) + '</div>'
                    + '<div class="form-grid">'
                    + '<div class="field"><label class="form-label">Date d\'échéance</label>'
                    + '<input class="input" type="date" name="date_echeance[]" value="' + (existante ? existante.date_echeance : ajouterMois(baseDate, i)) + '"></div>'
                    + '<div class="field"><label class="form-label">Mode de paiement</label>'
                    + '<select class="input" name="mode_paiement_id[]">' + optionsModes(existante ? existante.mode_paiement_id : null) + '</select></div>'
                    + '<div class="field"><label class="form-label">Montant TTC</label>'
                    + '<input class="input" type="number" step="0.01" min="0" name="montant_ttc[]" required value="' + (existante ? existante.montant_ttc : '') + '"></div>'
                    + '</div>';
                conteneur.appendChild(div);
            }
            wrapSubmit.hidden = false;
        } else {
            wrapSubmit.hidden = true;
        }
    }

    selectNombre.addEventListener('change', function () {
        genererLignes(parseInt(this.value, 10) || 0);
    });

    /* Si un nombre d'échéances existe déjà, afficher directement les lignes pré-remplies. */
    if (selectNombre.value) {
        genererLignes(parseInt(selectNombre.value, 10) || 0);
    }
});
</script>
<!-- AE-EOF : le fichier dossiers/views/fiche.php doit se terminer exactement ici -->
