<?php
// fichier : modules/prospects/views/index.php — v0.47
declare(strict_types=1);

use App\Core\View;

 $filtres      = $filtres ?? ['statut' => '', 'q' => ''];
 $stats        = $stats ?? ['nouveau' => 0, 'traite' => 0, 'archive' => 0, 'convertis' => 0];
 $commentaires = $commentaires ?? [];
 $requete      = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);

 $libellesStatut = ['nouveau' => 'Nouveau', 'traite' => 'Traité', 'archive' => 'Archivé'];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Prospects</h1>
        <p class="page-subtitle"><?= (int)$total ?> prospect<?= (int)$total > 1 ? 's' : '' ?> dans votre périmètre.</p>
    </div>
    <?php if (can('prospects.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/prospects/creer') ?>">+ Créer un prospect</a>
    <?php endif; ?>
</div>

<div class="prospect-stats">
    <a class="pstat<?= $filtres['statut'] === '' ? ' is-active' : '' ?>" href="<?= url('/prospects') ?>">
        <span class="pstat-value"><?= (int)($stats['nouveau'] + $stats['traite']) ?></span>
        <span class="pstat-label">Tous</span>
    </a>
    <a class="pstat<?= $filtres['statut'] === 'nouveau' ? ' is-active' : '' ?>" href="<?= url('/prospects?statut=nouveau') ?>">
        <span class="pstat-value"><?= (int)$stats['nouveau'] ?></span>
        <span class="pstat-label">Nouveaux</span>
    </a>
    <a class="pstat<?= $filtres['statut'] === 'traite' ? ' is-active' : '' ?>" href="<?= url('/prospects?statut=traite') ?>">
        <span class="pstat-value"><?= (int)$stats['traite'] ?></span>
        <span class="pstat-label">Traités</span>
    </a>
    <a class="pstat<?= $filtres['statut'] === 'archive' ? ' is-active' : '' ?>" href="<?= url('/prospects?statut=archive') ?>">
        <span class="pstat-value"><?= (int)$stats['archive'] ?></span>
        <span class="pstat-label">Archivés</span>
    </a>
    <span class="pstat pstat-muted">
        <span class="pstat-value"><?= (int)$stats['convertis'] ?></span>
        <span class="pstat-label">Convertis</span>
    </span>
</div>

<form class="filter-bar" method="get" action="<?= url('/prospects') ?>">
    <input type="hidden" name="statut" value="<?= e($filtres['statut']) ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Nom, prénom, email, téléphone…" value="<?= e($filtres['q']) ?>">
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/prospects') ?>">Réinitialiser</a>
</form>

<?php if ($liste === []): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <p class="empty-title">Aucun prospect</p>
            <p class="empty-text">Aucun prospect ne correspond à ces critères dans votre périmètre.</p>
        </div>
    </div></div>
<?php else: ?>
    <div class="prospect-grid">
        <?php foreach ($liste as $p):
            $nomComplet    = trim((string)($p['prenom'] ?? '') . ' ' . (string)$p['nom']);
            $conversations = $commentaires[(int)$p['id']] ?? [];
            $nbComm        = count($conversations);
            $telBrut       = (string)($p['telephone'] ?? '');
            $telLien       = preg_replace('#[\s.\-()]#', '', $telBrut);
            $couleurAvatar = !empty($p['domaine_couleur']) ? (string)$p['domaine_couleur'] : couleur_pastel($nomComplet);
        ?>
            <article class="card prospect-card">
                <div class="card-body">
                    <div class="prospect-card-head">
                        <div class="cell-user">
                            <span class="avatar" style="background-color:<?= e($couleurAvatar) ?>"><?= e(user_initials(['prenom' => $p['prenom'], 'nom' => $p['nom']])) ?></span>
                            <span class="cell-user-id">
                                <strong><?= e($nomComplet) ?></strong>
                                <small>Créé le <?= e((string)$p['ajout_le']) ?></small>
                            </span>
                        </div>
                        <span class="badge <?= (string)$p['statut'] === 'nouveau' ? 'badge-brand' : ((string)$p['statut'] === 'traite' ? 'badge-ok' : 'badge-muted') ?>">
                            <?= e($libellesStatut[(string)$p['statut']] ?? (string)$p['statut']) ?>
                        </span>
                    </div>

                    <div class="prospect-badges-top">
                        <?php if ($p['domaine_nom'] !== null): ?>
                            <span class="badge-entite" style="background-color:<?= e($p['domaine_couleur'] ?: '#64748b') ?>"><?= e($p['domaine_nom']) ?></span>
                        <?php endif; ?>
                        <?php if ($p['agence_nom'] !== null): ?>
                            <span class="badge-entite" style="background-color:<?= e($p['agence_couleur'] ?: '#64748b') ?>"><?= e($p['agence_nom']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="prospect-lines">
                        <?php if ((string)($p['email'] ?? '') !== ''): ?>
                            <a class="badge-link badge-mail" href="mailto:<?= e($p['email']) ?>" title="Écrire à <?= e($p['email']) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span><?= e($p['email']) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($telBrut !== ''): ?>
                            <a class="badge-link badge-tel" href="tel:<?= e($telLien) ?>" title="Appeler : <?= e($telBrut) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                <span><?= e($telBrut) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php /* Naissance + lieu : même ligne. */ ?>
                        <span class="prospect-line">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>
                                <?= e($p['date_naissance'] ?? '—') ?>
                                <?php if ((string)($p['lieu_naissance'] ?? '') !== ''): ?>
                                    · <?= e($p['lieu_naissance']) ?>
                                <?php endif; ?>
                            </span>
                        </span>

                        <span class="prospect-line">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h6M7 13h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span><?= e($p['type_permis_nom'] ?? 'Formation non précisée') ?></span>
                        </span>

                        <span class="prospect-line">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12h4l2-6 4 12 2-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>Provenance : <?= e($p['provenance'] ?? '—') ?></span>
                        </span>
                    </div>

                    <?php if ((int)$p['convertis'] === 1): ?>
                        <div class="prospect-converted">Converti<?= e($p['convertis_date'] !== null ? ' le ' . (string)$p['convertis_date'] : '') ?></div>
                    <?php endif; ?>
                </div>

                <div class="card-body prospect-actions">
                    <button type="button" class="icon-btn comm-btn" data-modal="#modal-comm-<?= (int)$p['id'] ?>"
                            title="Commentaires<?= $nbComm > 0 ? ' (' . $nbComm . ')' : '' ?>" aria-label="Commentaires">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        <?php if ($nbComm > 0): ?><span class="comm-count"><?= $nbComm > 99 ? '99+' : (string)$nbComm ?></span><?php endif; ?>
                    </button>
                    <a class="btn btn-sm btn-primary" href="<?= url('/prospects/' . (int)$p['id']) ?>">Fiche</a>
                    <?php if ((string)$p['statut'] !== 'archive' && can('prospects.traiter')): ?>
                        <button type="button" class="btn btn-sm btn-ghost" data-modal="#modal-traiter"
                                data-modal-action="<?= url('/prospects/' . (int)$p['id'] . '/traiter') ?>">Traiter</button>
                    <?php endif; ?>
                    <?php if ((string)$p['statut'] !== 'archive' && can('prospects.archiver')): ?>
                        <button type="button" class="btn btn-sm btn-ghost" data-modal="#modal-archiver"
                                data-modal-action="<?= url('/prospects/' . (int)$p['id'] . '/archiver') ?>">Archiver</button>
                    <?php endif; ?>
                    <?php if ((int)$p['convertis'] !== 1 && (string)$p['statut'] !== 'archive' && can('prospects.convertir')): ?>
                        <form method="post" action="<?= url('/prospects/' . (int)$p['id'] . '/convertir') ?>"
                              data-confirm="Convertir « <?= e($nomComplet) ?> » en élève ? Le prospect sera archivé automatiquement.">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-ghost" type="submit">Convertir</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>

            <div class="modal-overlay" id="modal-comm-<?= (int)$p['id'] ?>">
                <div class="modal modal-comm" role="dialog" aria-modal="true" aria-labelledby="modal-comm-<?= (int)$p['id'] ?>-titre">
                    <div class="modal-header">
                        <span id="modal-comm-<?= (int)$p['id'] ?>-titre">Commentaires — <?= e($nomComplet) ?></span>
                        <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php
                        View::partial('partials/commentaires', [
                            'compact'           => true,
                            'objetType'         => 'prospect',
                            'objetId'           => (int)$p['id'],
                            'commentaires'      => $conversations,
                            'avecKm'            => false,
                            'optionsPartenaires' => [],
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php View::partial('@prospects/modales'); ?>

<?php
View::partial('partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'baseUrl' => '/prospects',
    'query'   => $requete,
]);
?>
<!-- AE-EOF : le fichier prospects/index.php doit se terminer exactement ici -->