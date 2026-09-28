<?php
// fichier : modules/administration/views/utilisateurs/index.php — v0.16 (renvoi complet)
declare(strict_types=1);

use App\Core\Session;
use App\Core\View;

 $filtres     = $filtres ?? [];
 $active      = agence_active();
 $requete     = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);
 $monId       = (int)Session::get('user_id', 0);
 $peutModif   = can('utilisateurs.modifier');
 $rolesMap    = $rolesMap ?? [];
 $politiqueMdp = $politiqueMdp ?? ['longueur' => 12, 'majuscules' => true, 'chiffres' => true, 'speciaux' => true];

/* Modale mot de passe rouverte en cas d'erreur de validation (flash). */
 $pwModal = Session::getFlash('pw_modal');
 $pwOuvert = is_array($pwModal) && form_errors() !== [];
 $pwCibleNom   = $pwOuvert ? (string)($pwModal['nom'] ?? '') : '';
 $pwCibleLogin = $pwOuvert ? (string)($pwModal['login'] ?? '') : '';
 $pwCibleId    = $pwOuvert ? (int)($pwModal['id'] ?? 0) : 0;
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-subtitle">
            <?= (int)$total ?> utilisateur<?= (int)$total > 1 ? 's' : '' ?> —
            périmètre : <?= $active !== null ? e($active['agence_nom']) : 'toutes les agences' ?>
        </p>
    </div>
    <?php if (can('utilisateurs.creer')): ?>
        <a class="btn btn-primary" href="<?= url('/administration/utilisateurs/creer') ?>">+ Créer un utilisateur</a>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get" action="<?= url('/administration/utilisateurs') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Nom, prénom, identifiant, email, téléphone…"
           value="<?= e($filtres['q']) ?>">
    <select class="input" name="role" aria-label="Rôle">
        <option value="">Tous les rôles</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?= e($role['code']) ?>" <?= $filtres['role'] === $role['code'] ? 'selected' : '' ?>>
                <?= e($role['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($agences !== []): ?>
        <select class="input" name="agence" aria-label="Agence">
            <option value="">Toutes les agences</option>
            <?php foreach ($agences as $agence): ?>
                <option value="<?= (int)$agence['id'] ?>" <?= $filtres['agence'] === (string)$agence['id'] ? 'selected' : '' ?>>
                    <?= e($agence['agence_nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <select class="input" name="actif" aria-label="Statut">
        <option value="">Tous les statuts</option>
        <option value="1" <?= $filtres['actif'] === '1' ? 'selected' : '' ?>>Actifs</option>
        <option value="0" <?= $filtres['actif'] === '0' ? 'selected' : '' ?>>Inactifs</option>
    </select>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url('/administration/utilisateurs') ?>">Réinitialiser</a>
</form>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun utilisateur</p>
                <p class="empty-text">Aucun compte ne correspond à ces critères dans votre périmètre.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Utilisateur</th><th>Contact</th><th>Rôle</th><th>Agence</th><th>Statut</th>
                    <?php if ($peutModif): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $u): ?>
                    <tr>
                        <td>
                            <div class="cell-user">
                                <?php if (!empty($u['photographie'])): ?>
                                    <img class="avatar avatar-sm" src="<?= url('/fichiers/utilisateurs/' . rawurlencode((string)$u['photographie'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="avatar avatar-sm"<?= !empty($u['couleur']) ? ' style="background-color:' . e($u['couleur']) . '"' : '' ?>><?= e(user_initials(['prenom' => $u['prenom'], 'nom' => $u['nom']])) ?></span>
                                <?php endif; ?>
                                <span class="cell-user-id">
                                    <strong><?= e(trim(($u['prenom'] ?? '') . ' ' . $u['nom'])) ?></strong>
                                    <small><?= e($u['login']) ?><?= $u['civilite'] !== null ? ' · ' . e($u['civilite']) : '' ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="cell-user-id">
                                <strong><?= e($u['mail'] ?? '—') ?></strong>
                                <small><?= e($u['telephone'] ?? '—') ?></small>
                            </span>
                        </td>
                        <td><span class="badge badge-muted"><?= e($rolesMap[(string)$u['role']] ?? role_label((string)$u['role'])) ?></span></td>
                        <td>
                            <?php if ($u['agence_nom'] !== null): ?>
                                <span class="dot" style="background-color:<?= e($u['agence_couleur'] ?: '#64748b') ?>"></span>
                                <?= e($u['agence_nom']) ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= (int)$u['actif'] === 1
                            ? '<span class="badge badge-ok">Actif</span>'
                            : '<span class="badge badge-danger">Inactif</span>' ?></td>
                        <?php if ($peutModif): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-ghost" href="<?= url('/administration/utilisateurs/' . (int)$u['id'] . '/modifier') ?>">Modifier</a>
                                    <button type="button" class="btn btn-sm btn-ghost" data-modal="#modal-mdp"
                                            data-user-id="<?= (int)$u['id'] ?>"
                                            data-user-nom="<?= e(trim(($u['prenom'] ?? '') . ' ' . $u['nom'])) ?>"
                                            data-user-login="<?= e($u['login']) ?>">Mot de passe</button>
                                    <?php if ((int)$u['id'] !== $monId): ?>
                                        <form method="post" action="<?= url('/administration/utilisateurs/' . (int)$u['id'] . '/basculer-actif') ?>"
                                              data-confirm="<?= (int)$u['actif'] === 1
                                                  ? 'Désactiver le compte « ' . e($u['login']) . ' » ? Il ne pourra plus se connecter.'
                                                  : 'Activer le compte « ' . e($u['login']) . ' » ?' ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm <?= (int)$u['actif'] === 1 ? 'btn-ghost' : 'btn-primary' ?>" type="submit">
                                                <?= (int)$u['actif'] === 1 ? 'Désactiver' : 'Activer' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
View::partial('partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'baseUrl' => $baseUrl,
    'query'   => $requete,
]);
?>

<?php if ($peutModif): ?>
<div class="modal-overlay<?= $pwOuvert ? ' open' : '' ?>" id="modal-mdp">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-mdp-titre">
        <div class="modal-header">
            <span id="modal-mdp-titre">Modifier le mot de passe</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">
                <strong data-modal-cible><?= e($pwCibleNom !== '' ? $pwCibleNom . ' (' . $pwCibleLogin . ')' : '') ?></strong>
            </p>
            <form method="post"
                  action="<?= $pwOuvert ? url('/administration/utilisateurs/' . $pwCibleId . '/mot-de-passe') : url('/administration/utilisateurs') ?>"
                  data-action-base="<?= url('/administration/utilisateurs') . '/' ?>"
                  novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="mdp_modal">Nouveau mot de passe <span class="req">*</span></label>
                    <div class="pw-field">
                        <input class="input" type="password" id="mdp_modal" name="mot_de_passe"
                               autocomplete="new-password" required minlength="<?= (int)$politiqueMdp['longueur'] ?>">
                        <button type="button" class="pw-btn" data-toggle-password tabindex="-1" title="Afficher / masquer" aria-label="Afficher ou masquer le mot de passe">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        <button type="button" class="pw-btn pw-btn-gen" data-generate-password tabindex="-1"
                                data-length="<?= (int)$politiqueMdp['longueur'] ?>"
                                data-upper="<?= !empty($politiqueMdp['majuscules']) ? '1' : '0' ?>"
                                data-digits="<?= !empty($politiqueMdp['chiffres']) ? '1' : '0' ?>"
                                data-special="<?= !empty($politiqueMdp['speciaux']) ? '1' : '0' ?>"
                                title="Générer un mot de passe conforme" aria-label="Générer un mot de passe">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11A8 8 0 1 0 12.7 20a8 8 0 0 1 0-16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <p class="form-text">
                        <?= (int)$politiqueMdp['longueur'] ?> caractères minimum
                        <?php if (!empty($politiqueMdp['majuscules'])): ?>· majuscule<?php endif; ?>
                        <?php if (!empty($politiqueMdp['chiffres'])): ?>· chiffre<?php endif; ?>
                        <?php if (!empty($politiqueMdp['speciaux'])): ?>· caractère spécial<?php endif; ?>
                        — politique configurable dans les paramètres.
                    </p>
                    <?php if (form_error('mot_de_passe')): ?><p class="form-error"><?= e(form_error('mot_de_passe')) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="form-label" for="mdp_modal_conf">Confirmation <span class="req">*</span></label>
                    <input class="input" type="password" id="mdp_modal_conf" name="confirmation_mot_de_passe"
                           autocomplete="new-password" required minlength="<?= (int)$politiqueMdp['longueur'] ?>">
                    <?php if (form_error('confirmation_mot_de_passe')): ?><p class="form-error"><?= e(form_error('confirmation_mot_de_passe')) ?></p><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Modifier le mot de passe</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- AE-EOF : le fichier utilisateurs/index.php doit se terminer exactement ici -->