<?php
// fichier : views/dashboard/index.php — CRM Auto-École, v0.31
declare(strict_types=1);

 $user           = $user ?? [];
 $system         = $system ?? ['dbOk' => false, 'database' => '', 'userCount' => 0, 'migrationCount' => 0];
 $modules        = $modules ?? [];
 $roadmap        = $roadmap ?? [];
 $anniversaires  = $anniversaires ?? [];

 $fullName       = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
 $roleLibelle    = $user['role_nom'] ?? role_label((string)($user['role'] ?? ''));

/* v0.31 : libellé du périmètre ACTIF (sélecteur topbar), pas l'agence de rattachement. */
 $active         = agence_active();
 $agenceLibelle  = $active !== null ? (string)$active['agence_nom'] : 'Toutes les agences';
 $nMig           = (int)$system['migrationCount'];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-subtitle">
            Bonjour <?= e($fullName ?: ($user['login'] ?? '')) ?> —
            <?= e($roleLibelle) ?> ·
            <?= e($agenceLibelle) ?>
        </p>
    </div>
</div>

<?php /* Anniversaires du jour (CDC §26) — si activés dans les paramètres. */ ?>
<?php if ($anniversaires !== []): ?>
    <div class="status-banner is-ok">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="flex:0 0 auto">
            <path d="M4 20h16M6 20v-6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M12 12V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="6.5" r="2" stroke="currentColor" stroke-width="2"/>
        </svg>
        <span>Anniversaires du jour : <strong><?= e(implode(', ', $anniversaires)) ?></strong></span>
    </div>
<?php endif; ?>

<div class="status-banner <?= $system['dbOk'] ? 'is-ok' : 'is-ko' ?>">
    <span class="status-dot" aria-hidden="true"></span>
    <span>
        <?php if ($system['dbOk']): ?>
            Socle technique opérationnel — <?= $nMig ?> migration<?= $nMig > 1 ? 's' : '' ?> appliquée<?= $nMig > 1 ? 's' : '' ?> · installation via <code>migrate.php</code>.
        <?php else: ?>
            Base de données inaccessible — vérifiez la configuration et exécutez <code>migrate.php</code>.
        <?php endif; ?>
    </span>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-head">
            <span class="stat-label">Base de données</span>
            <?php if ($system['dbOk']): ?>
                <span class="badge badge-ok">Connectée</span>
            <?php else: ?>
                <span class="badge badge-danger">Inaccessible</span>
            <?php endif; ?>
        </div>
        <div class="stat-value"><?= e($system['database'] !== '' ? $system['database'] : '—') ?></div>
        <div class="stat-foot">MySQL / MariaDB · PDO · requêtes préparées</div>
    </div>

    <div class="stat-card">
        <div class="stat-head">
            <span class="stat-label">Comptes utilisateurs</span>
        </div>
        <div class="stat-value"><?= e((string)(int)$system['userCount']) ?></div>
        <div class="stat-foot">comptes actifs non supprimés</div>
    </div>

    <div class="stat-card">
        <div class="stat-head">
            <span class="stat-label">Migrations appliquées</span>
        </div>
        <div class="stat-value"><?= e((string)$nMig) ?></div>
        <div class="stat-foot"><code>database/migrations</code></div>
    </div>
</div>

<div class="dash-grid">
    <section class="card">
        <div class="card-header">
            <span>Modules métier détectés</span>
            <span class="card-count"><?= count($modules) ?></span>
        </div>
        <div class="card-body">
            <?php if ($modules !== []): ?>
                <?php foreach ($modules as $module): ?>
                    <div class="module-row">
                        <div class="module-main">
                            <span class="module-name"><?= e($module['nom']) ?></span>
                            <?php if ($module['description'] !== ''): ?>
                                <span class="module-meta"><?= e($module['description']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-brand">v<?= e($module['version']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p class="empty-title">Aucun module métier détecté dans <code>/modules</code></p>
                    <p class="empty-text">Les modules livrés aux prochains jalons apparaîtront automatiquement ici.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <aside class="card">
        <div class="card-header"><span>Feuille de route</span></div>
        <div class="card-body card-body-scroll">
            <?php foreach ($roadmap as $item): ?>
                <div class="roadmap-item <?= $item['etat'] === 'livre' ? 'is-done' : '' ?>">
                    <span class="roadmap-dot"><?= e((string)$item['jalon']) ?></span>
                    <span class="roadmap-label"><?= e($item['nom']) ?></span>
                    <?php if ($item['etat'] === 'livre'): ?>
                        <span class="badge badge-brand">Livré — à valider</span>
                    <?php else: ?>
                        <span class="badge badge-muted">À venir</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
</div>
<!-- AE-EOF : le fichier dashboard/index.php doit se terminer exactement ici -->