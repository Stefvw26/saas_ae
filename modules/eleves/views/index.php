<?php
// fichier : modules/eleves/views/index.php — v0.43 (cards 50% + 2 colonnes)
// Gauche : informations (SANS provenance, SANS formation — directive).
// Droite : Dossiers (Jalon 7).
declare(strict_types=1);

use App\Core\View;

 $filtres         = $filtres ?? ['q' => '', 'domaine_id' => '', 'agence_id' => '', 'statut' => 'actifs'];
 $stats           = $stats ?? ['actifs' => 0, 'archives' => 0];
 $optionsDomaines = $optionsDomaines ?? [];
 $optionsAgences  = $optionsAgences ?? [];
 $requete         = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Élèves</h1>
        <p class="page-subtitle">
            <?= (int)$stats['actifs'] ?> actif<?= (int)$stats['actifs'] > 1 ? 's' : '' ?> ·
            <?= (int)$stats['archives'] ?> archivé<?= (int)$stats['archives'] > 1 ? 's' : '' ?> —
            périmètre courant.
        </p>
    </div>
    <div class="footer-actions">
        <a class="btn btn-ghost" href="<?= url('/eleves?' . http_build_query(array_merge($requete, ['export' => 'excel']))) ?>">Exporter Excel</a>
        <?php if (can('eleves.creer')): ?>
            <a class="btn btn-primary" href="<?= url('/eleves/creer') ?>">+ Créer un élève</a>
        <?php endif; ?>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= url('/eleves') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Nom, prénom, email, téléphone…" value="<?= e($filtres['q']) ?>">
    <select class="input" name="domaine_id" aria-label="Domaine (lieu)">
        <option value="">Tous les domaines</option>
        <?php foreach ($optionsDomaines as $idDom => $dom): ?>
            <option value="<?= (int)$idDom ?>" <?= $filtres['domaine_id'] === (string)$idDom ? 'selected' : '' ?>><?= e($dom['nom']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($optionsAgences !== []): ?>
        <select class="input" name="agence_id" aria-label="Agence">
            <option value="">Toutes les agences</option>
            <?php foreach ($optionsAgences as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= $filtres['agence_id'] === (string)$a['id'] ? 'selected' : '' ?>><?= e($a['agence_nom']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
        <select class="input" name="statut" aria-label="Statut">
        <option value="actifs" <?= $filtres['statut'] === 'actifs' ? 'selected' : '' ?>>Tous les élèves actifs</option>
        <option value="sans_dossier" <?= $filtres['statut'] === 'sans_dossier' ? 'selected' : '' ?>>Sans dossier</option>
        <option value="en_cours" <?= $filtres['statut'] === 'en_cours' ? 'selected' : '' ?>>Inscrits en cours</option>
        <option value="valide" <?= $filtres['statut'] === 'valide' ? 'selected' : '' ?>>Panier validé</option>
        <option value="confirme" <?= $filtres['statut'] === 'confirme' ? 'selected' : '' ?>>À confirmer</option>
        <option value="archives" <?= $filtres['statut'] === 'archives' ? 'selected' : '' ?>>Archives</option>
    </select>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/eleves') ?>">Réinitialiser</a>
</form>

<?php /* §56 : filtres liés aux dossiers — désactivés jusqu'au Jalon 7. */ ?>
<p class="form-text" style="margin-top:-6px">
    Les filtres « Inscrit en cours », « Panier validé », « À confirmer » et « Sans dossier » utilisent
    désormais les informations réelles des dossiers (Jalon 7).
</p>

<?php if ($liste === []): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <p class="empty-title">Aucun élève</p>
            <p class="empty-text">Aucun élève ne correspond à ces critères dans votre périmètre.</p>
        </div>
    </div></div>
<?php else: ?>
    <div class="eleve-grid">
        <?php foreach ($liste as $el):
            $nomComplet    = trim((string)($el['prenom'] ?? '') . ' ' . (string)$el['nom']);
            $couleurAvatar = !empty($el['domaine_couleur']) ? (string)$el['domaine_couleur'] : couleur_pastel($nomComplet);
            $telBrut       = (string)($el['telephone'] ?? '');
            $telLien       = preg_replace('#[\s.\-()]#', '', $telBrut);
            $estArchive    = (int)$el['supprimer'] === 1;
        ?>
            <article class="card eleve-card">
                <div class="card-body">
                    <div class="prospect-card-head">
                        <div class="cell-user">
                            <span class="avatar" style="background-color:<?= e($couleurAvatar) ?>"><?= e(user_initials(['prenom' => $el['prenom'], 'nom' => $el['nom']])) ?></span>
                            <span class="cell-user-id">
                                <strong><?= e($nomComplet) ?></strong>
                                <small>Créé le <?= e((string)$el['ajout_le']) ?></small>
                            </span>
                        </div>
                        <?php if ($estArchive): ?>
                            <span class="badge badge-muted">Archivé</span>
                        <?php elseif ((int)$el['actif'] === 1): ?>
                            <span class="badge badge-ok">Actif</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactif</span>
                        <?php endif; ?>
                    </div>

                    <div class="prospect-badges-top">
                        <?php if ($el['domaine_nom'] !== null): ?>
                            <span class="badge-entite" style="background-color:<?= e($el['domaine_couleur'] ?: '#64748b') ?>"><?= e($el['domaine_nom']) ?></span>
                        <?php endif; ?>
                        <?php if ($el['agence_nom'] !== null): ?>
                            <span class="badge-entite" style="background-color:<?= e($el['agence_couleur'] ?: '#64748b') ?>"><?= e($el['agence_nom']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php /* 2 colonnes : gauche infos / droite dossiers. */ ?>
                    <div class="eleve-cols">
                        <div class="eleve-col">
                            <p class="eleve-col-title">Informations</p>
                            <div class="prospect-lines">
                                <?php if ((string)($el['email'] ?? '') !== ''): ?>
                                    <a class="badge-link badge-mail" href="mailto:<?= e($el['email']) ?>" title="Écrire à <?= e($el['email']) ?>">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span><?= e($el['email']) ?></span>
                                    </a>
                                <?php endif; ?>

                                <?php if ($telBrut !== ''): ?>
                                    <a class="badge-link badge-tel" href="tel:<?= e($telLien) ?>" title="Appeler : <?= e($telBrut) ?>">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                        <span><?= e($telBrut) ?></span>
                                    </a>
                                <?php endif; ?>

                                <?php if ($el['date_naissance'] !== null): ?>
                                    <span class="prospect-line">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        <span>Né(e) le <?= e((string)$el['date_naissance']) ?></span>
                                    </span>
                                <?php endif; ?>

                                <?php if ((string)($el['ville'] ?? '') !== ''): ?>
                                    <span class="prospect-line">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                                        <span><?= e($el['ville']) ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="eleve-col eleve-col-dossiers">
                            <p class="eleve-col-title">Dossiers</p>
                            <div class="dossiers-j7">
                                <p class="dossiers-count">0 dossier</p>
                                <p class="form-text">La gestion des dossiers (un élève peut en posséder plusieurs) sera disponible au <strong>Jalon 7</strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body prospect-actions">
                    <a class="btn btn-sm btn-primary" href="<?= url('/eleves/' . (int)$el['id']) ?>">Fiche</a>
                    <?php if (can('eleves.modifier') && !$estArchive): ?>
                        <a class="btn btn-sm btn-ghost" href="<?= url('/eleves/' . (int)$el['id'] . '/modifier') ?>">Modifier</a>
                    <?php endif; ?>
                    <?php if (can('eleves.supprimer') && !$estArchive): ?>
                        <form method="post" action="<?= url('/eleves/' . (int)$el['id'] . '/archiver') ?>"
                              data-confirm="Archiver « <?= e($nomComplet) ?> » ?">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" type="submit">Archiver</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
View::partial('partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'baseUrl' => '/eleves',
    'query'   => $requete,
]);
?>
<!-- AE-EOF : le fichier eleves/views/index.php doit se terminer exactement ici -->