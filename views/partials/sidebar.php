<?php
// fichier : views/partials/sidebar.php — CRM Auto-École, v0.22 (renvoi complet)
declare(strict_types=1);

use App\Core\Config;

 $user        = $user ?? [];
 $appName     = (string)Config::get('app.name', 'CRM Auto-École');
 $path        = current_path();
 $roleLibelle = $user['role_nom'] ?? role_label((string)($user['role'] ?? ''));

/* Sections ouvertes : celle de la page active ; sinon Administration par défaut. */
 $prefixesAdmin = [
    '/administration/', '/domaines', '/vehicules', '/centres', '/partenaires', '/prestations',
];
 $prefixesRef = [
    '/provenances', '/types-permis', '/types-prestations', '/phrases-ouverture',
    '/formules', '/documents-obligatoires', '/modes-paiement',
];
 $adminOuvert = false;
foreach ($prefixesAdmin as $prefixe) {
    if (strpos($path, $prefixe) === 0) { $adminOuvert = true; break; }
}
 $refOuvert = false;
foreach ($prefixesRef as $prefixe) {
    if (strpos($path, $prefixe) === 0) { $refOuvert = true; break; }
}
if (!$adminOuvert && !$refOuvert) {
    $adminOuvert = true; /* par défaut */
}

 $afficheAdmin = can('administration.acceder') || can('domaines.consulter') || can('vehicules.consulter')
    || can('centres.consulter') || can('partenaires.consulter') || can('prestations.consulter');
 $afficheRef   = can('provenances.consulter') || can('types-permis.consulter')
    || can('types-prestations.consulter') || can('phrases-ouverture.consulter')
    || can('formules.consulter') || can('documents-obligatoires.consulter') || can('modes-paiement.consulter');
?>
<aside class="sidebar" id="sidebar" aria-label="Navigation principale">
    <div class="sidebar-brand">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="3.2" fill="currentColor"/>
            <path d="M12 2v6.5M12 15.5V22M2 12h6.5M15.5 12H22" stroke="currentColor" stroke-width="2"/>
        </svg>
        <span><?= e($appName) ?></span>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item <?= $path === '/' ? 'active' : '' ?>" href="<?= url('/') ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Tableau de bord</span>
        </a>
         
                    <?php if (can('prospects.consulter') || can('eleves.consulter')): ?>
            <p class="nav-group-label">Métier</p>
            <?php if (can('prospects.consulter')): ?>
                <a class="nav-item <?= strpos($path, '/prospects') === 0 ? 'active' : '' ?>" href="<?= url('/prospects') ?>">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="10" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/>
                        <path d="M3.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M17 14v6M14 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Prospects</span>
                </a>
            <?php endif; ?>
            <?php if (can('eleves.consulter')): ?>
                <a class="nav-item <?= strpos($path, '/eleves') === 0 ? 'active' : '' ?>" href="<?= url('/eleves') ?>">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6z" stroke="currentColor" stroke-width="2"/>
                        <path d="M6.5 21c.8-3.2 2.9-5 5.5-5s4.7 1.8 5.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M3 8l2.5 2L8 5M16 5l2.5 5L21 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Élèves</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($afficheAdmin): ?>
        <div class="nav-section<?= $adminOuvert ? ' open' : '' ?>">
            <button type="button" class="nav-group-toggle" data-nav-group aria-expanded="<?= $adminOuvert ? 'true' : 'false' ?>">
                <span>Administration</span>
                <svg class="nav-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="nav-group-items">
                <?php if (can('utilisateurs.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/administration/utilisateurs') === 0 ? 'active' : '' ?>" href="<?= url('/administration/utilisateurs') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/>
                            <path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="17" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/>
                            <path d="M16 14.5c2.5 0 4.5 1.5 5.5 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Utilisateurs</span>
                    </a>
                <?php endif; ?>
                <?php if (can('agences.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/administration/agences') === 0 ? 'active' : '' ?>" href="<?= url('/administration/agences') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 21h18M5 21V8l7-5 7 5v13M10 21v-5h4v5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span>Agences</span>
                    </a>
                <?php endif; ?>
                <?php if (can('domaines.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/domaines') === 0 ? 'active' : '' ?>" href="<?= url('/domaines') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3l9 5-9 5-9-5 9-5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 13l9 5 9-5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span>Domaines</span>
                    </a>
                <?php endif; ?>
                <?php if (can('vehicules.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/vehicules') === 0 ? 'active' : '' ?>" href="<?= url('/vehicules') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="7" width="18" height="9" rx="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="7.5" cy="18.5" r="1.8" stroke="currentColor" stroke-width="2"/>
                            <circle cx="16.5" cy="18.5" r="1.8" stroke="currentColor" stroke-width="2"/>
                            <path d="M7 11h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Véhicules</span>
                    </a>
                <?php endif; ?>
                <?php if (can('centres.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/centres') === 0 ? 'active' : '' ?>" href="<?= url('/centres') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Centres</span>
                    </a>
                <?php endif; ?>
                <?php if (can('partenaires.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/partenaires') === 0 ? 'active' : '' ?>" href="<?= url('/partenaires') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="8" cy="8" r="3" stroke="currentColor" stroke-width="2"/>
                            <circle cx="16" cy="8" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M2 20c1-3 3.5-5 6-5s5 2 6 5M14 20c1-3 3.5-5 6-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Partenaires</span>
                    </a>
                <?php endif; ?>
                <?php if (can('prestations.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/prestations') === 0 ? 'active' : '' ?>" href="<?= url('/prestations') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20.6 13.4L11 3H4v7l9.6 10.4a2 2 0 0 0 2.8 0l4.2-4.2a2 2 0 0 0 0-2.8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="7.5" cy="6.5" r="1.2" fill="currentColor"/>
                        </svg>
                        <span>Prestations</span>
                    </a>
                <?php endif; ?>
                                <?php if (can('formules.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/formules') === 0 ? 'active' : '' ?>" href="<?= url('/formules') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 7h8M8 11h8M8 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Formules</span>
                    </a>
                <?php endif; ?>
                
                <?php if (can('droits.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/administration/droits') === 0 ? 'active' : '' ?>" href="<?= url('/administration/droits') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="15" r="1.6" fill="currentColor"/>
                        </svg>
                        <span>Rôles et droits</span>
                    </a>
                <?php endif; ?>
                <?php if (can('parametres.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/administration/parametres') === 0 ? 'active' : '' ?>" href="<?= url('/administration/parametres') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Paramètres</span>
                    </a>
                <?php endif; ?>
                <?php if (can('journaux.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/administration/journaux') === 0 ? 'active' : '' ?>" href="<?= url('/administration/journaux') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 4h14v17l-7-4-7 4V4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span>Journaux</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($afficheRef): ?>
        <div class="nav-section<?= $refOuvert ? ' open' : '' ?>">
            <button type="button" class="nav-group-toggle" data-nav-group aria-expanded="<?= $refOuvert ? 'true' : 'false' ?>">
                <span>Référentiels</span>
                <svg class="nav-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="nav-group-items">
                <?php if (can('provenances.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/provenances') === 0 ? 'active' : '' ?>" href="<?= url('/provenances') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 12h4l2-6 4 12 2-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Provenances</span>
                    </a>
                <?php endif; ?>
                <?php if (can('types-permis.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/types-permis') === 0 ? 'active' : '' ?>" href="<?= url('/types-permis') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M7 9h4M7 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Types de permis</span>
                    </a>
                <?php endif; ?>
                <?php if (can('types-prestations.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/types-prestations') === 0 ? 'active' : '' ?>" href="<?= url('/types-prestations') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Types de prestations</span>
                    </a>
                <?php endif; ?>
                
                         
                
                
                <?php if (can('phrases-ouverture.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/phrases-ouverture') === 0 ? 'active' : '' ?>" href="<?= url('/phrases-ouverture') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 5h16v10H8l-4 4V5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span>Phrases d'ouverture</span>
                    </a>
                <?php endif; ?>
                <?php if (can('formules.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/formules') === 0 ? 'active' : '' ?>" href="<?= url('/formules') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 19V5M4 19h16M8 15l3-4 3 3 4-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Formules</span>
                    </a>
                <?php endif; ?>
                <?php if (can('documents-obligatoires.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/documents-obligatoires') === 0 ? 'active' : '' ?>" href="<?= url('/documents-obligatoires') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 3h9l3 3v15H6V3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Documents obligatoires</span>
                    </a>
                <?php endif; ?>
                <?php if (can('modes-paiement.consulter')): ?>
                    <a class="nav-item <?= strpos($path, '/modes-paiement') === 0 ? 'active' : '' ?>" href="<?= url('/modes-paiement') ?>">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="2" y="6" width="20" height="13" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M2 10h20" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Modes de paiement</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <a class="nav-item <?= $path === '/mon-compte' ? 'active' : '' ?>" href="<?= url('/mon-compte') ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Mon compte</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <?php if (!empty($user['photographie'])): ?>
            <img class="avatar" src="<?= url('/fichiers/utilisateurs/' . rawurlencode((string)$user['photographie'])) ?>" alt="">
        <?php else: ?>
            <span class="avatar"<?= !empty($user['couleur']) ? ' style="background-color:' . e($user['couleur']) . '"' : '' ?>><?= e(user_initials($user)) ?></span>
        <?php endif; ?>
        <div class="sidebar-user-id">
            <strong><?= e(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?: ($user['login'] ?? '')) ?></strong>
            <small><?= e($roleLibelle) ?></small>
        </div>
        <form method="post" action="<?= url('/deconnexion') ?>" class="sidebar-logout">
            <?= csrf_field() ?>
            <button type="submit" class="icon-btn icon-btn-dark" title="Déconnexion" aria-label="Déconnexion">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M6.3 6.5a8 8 0 1 0 11.4 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
<!-- AE-EOF : le fichier sidebar.php doit se terminer exactement ici -->