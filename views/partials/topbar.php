<?php
// fichier : views/partials/topbar.php — CRM Auto-École, v0.31
declare(strict_types=1);

use App\Core\Session;
use App\Repositories\NotificationsRepository;
use Throwable;

 $active      = agence_active();
 $accessibles = agences_accessibles();
 $tousDroits  = tous_droit();

/* Cloche notifications : compteur non-lus (jamais bloquant). */
 $nbNonLus = 0;
try {
    if (Session::has('user_id')) {
        $nbNonLus = (new NotificationsRepository())->compterNonLus((int)Session::get('user_id'), (int)Session::get('tenant_id', 0));
    }
} catch (Throwable) {
    /* table absente (avant migration) : silencieux */
}
?>
<header class="topbar">
    <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="Ouvrir le menu" aria-controls="sidebar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>
    <h1 class="topbar-title"><?= e($title ?? '') ?></h1>

    <?php /* Recherche globale — TOUJOURS disponible (CDC §12). */ ?>
    <form class="topbar-search" method="get" action="<?= url('/recherche') ?>" role="search">
        <input class="input" type="search" name="q" placeholder="Rechercher…" aria-label="Rechercher dans le CRM">
        <button class="icon-btn" type="submit" title="Rechercher" aria-label="Rechercher">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </form>

    <div class="topbar-side">
        <?php /* Notifications (J4). */ ?>
        <a class="icon-btn topbar-bell" href="<?= url('/notifications') ?>" title="Notifications" aria-label="Notifications<?= $nbNonLus > 0 ? ' (' . $nbNonLus . ' non lues)' : '' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M10 19a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <?php if ($nbNonLus > 0): ?>
                <span class="bell-badge"><?= $nbNonLus > 99 ? '99+' : (string)$nbNonLus ?></span>
            <?php endif; ?>
        </a>

        <?php /* v0.31 (directive) : sélecteur d'agence REMIS pour l'admin
               (défaut « toutes ») + porteurs de agences.changer. */ ?>
        <?php if ($accessibles !== [] && ($tousDroits || can('agences.changer'))): ?>
            <form method="post" action="<?= url('/agence/activer') ?>" class="agency-select">
                <?= csrf_field() ?>
                <label class="sr-only" for="agence_id_topbar">Agence active</label>
                <select name="agence_id" id="agence_id_topbar" class="input input-sm" onchange="this.form.submit()">
                    <option value="0" <?= $active === null ? 'selected' : '' ?>>Toutes les agences</option>
                    <?php foreach ($accessibles as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" <?= $active !== null && (int)$a['id'] === (int)$active['id'] ? 'selected' : '' ?>>
                            <?= e($a['agence_nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php elseif ($active !== null): ?>
            <span class="agency-pill">
                <span class="dot" style="background-color:<?= e($active['agence_couleur'] ?: '#64748b') ?>"></span>
                <?= e($active['agence_nom']) ?>
            </span>
        <?php endif; ?>
        <span class="badge badge-muted"><?= e(APP_VERSION) ?></span>
    </div>
</header>
<!-- AE-EOF : le fichier partials/topbar.php doit se terminer exactement ici -->