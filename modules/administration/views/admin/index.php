<?php
// fichier : modules/administration/views/admin/index.php (renvoi complet)
declare(strict_types=1);

 $active = agence_active();
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Administration</h1>
        <p class="page-subtitle">
            Périmètre : <?= $active !== null ? e($active['agence_nom']) : 'toutes les agences' ?>
        </p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-head"><span class="stat-label">Utilisateurs</span></div>
        <div class="stat-value"><?= (int)$nbUtilisateurs ?></div>
        <div class="stat-foot">comptes actifs non supprimés</div>
    </div>
    <div class="stat-card">
        <div class="stat-head"><span class="stat-label">Agences</span></div>
        <div class="stat-value"><?= (int)$nbAgences ?></div>
        <div class="stat-foot">dans le périmètre actif</div>
    </div>
    <div class="stat-card">
        <div class="stat-head"><span class="stat-label">Journal du jour</span></div>
        <div class="stat-value"><?= (int)$nbJournauxJour ?></div>
        <div class="stat-foot">événements enregistrés aujourd'hui</div>
    </div>
</div>

<div class="tiles">
    <?php if (can('utilisateurs.consulter')): ?>
        <a class="tile" href="<?= url('/administration/utilisateurs') ?>">
            <span class="tile-title">Utilisateurs</span>
            <span class="tile-text">Créer, modifier, archiver les comptes</span>
        </a>
    <?php endif; ?>
    <?php if (can('agences.consulter')): ?>
        <a class="tile" href="<?= url('/administration/agences') ?>">
            <span class="tile-title">Agences</span>
            <span class="tile-text">Gérer les agences du client</span>
        </a>
    <?php endif; ?>
    <?php if (can('droits.consulter')): ?>
        <a class="tile" href="<?= url('/administration/droits') ?>">
            <span class="tile-title">Rôles et droits</span>
            <span class="tile-text">Matrice rôles / permissions</span>
        </a>
    <?php endif; ?>
    <?php if (can('parametres.consulter')): ?>
        <a class="tile" href="<?= url('/administration/parametres') ?>">
            <span class="tile-title">Paramètres</span>
            <span class="tile-text">Réglages généraux du client</span>
        </a>
    <?php endif; ?>
    <?php if (can('journaux.consulter')): ?>
        <a class="tile" href="<?= url('/administration/journaux') ?>">
            <span class="tile-title">Journaux</span>
            <span class="tile-text">Historique d'activité</span>
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><span>Derniers événements</span></div>
    <div class="card-body table-overflow">
        <?php if ($derniers === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun événement journalisé</p>
                <p class="empty-text">Les actions importantes (connexions, CRUD, droits, paramètres) apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Résultat</th></tr>
                </thead>
                <tbody>
                <?php foreach ($derniers as $log): ?>
                    <tr>
                        <td><?= e($log['cree_le']) ?></td>
                        <td><?= e($log['login'] ?? '—') ?></td>
                        <td><span class="badge badge-brand"><?= e($log['action']) ?></span></td>
                        <td><?= e($log['objet'] ?? '—') ?><?= $log['objet_id'] !== null ? ' #' . (int)$log['objet_id'] : '' ?></td>
                        <td><?= $log['resultat'] === 'succes'
                            ? '<span class="badge badge-ok">Succès</span>'
                            : '<span class="badge badge-danger">Refusé</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<!-- AE-EOF : le fichier admin/index.php doit se terminer exactement ici -->