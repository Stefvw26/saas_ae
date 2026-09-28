<?php
// fichier : views/recherche/index.php — CRM Auto-École, J4
declare(strict_types=1);

 $q        = $q ?? '';
 $sections = $sections ?? [];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Recherche</h1>
        <p class="page-subtitle">Recherche globale — résultats limités à vos permissions et à votre périmètre.</p>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= url('/recherche') ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Rechercher dans le CRM…" value="<?= e($q) ?>" autofocus>
    <button class="btn btn-primary" type="submit">Rechercher</button>
</form>

<?php if ($q === ''): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <p class="empty-title">Saisissez un terme de recherche</p>
                <p class="empty-text">Utilisateurs, agences, véhicules, centres, partenaires, prestations, référentiels…</p>
            </div>
        </div>
    </div>
<?php elseif ($sections === []): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <p class="empty-title">Aucun résultat pour « <?= e($q) ?> »</p>
                <p class="empty-text">Aucun élément visible ne correspond à ce terme dans votre périmètre.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($sections as $section): ?>
        <div class="card result-section">
            <div class="card-header">
                <span><?= e((string)$section['label']) ?></span>
                <span class="card-count"><?= (int)$section['total'] ?></span>
            </div>
            <div class="card-body">
                <?php foreach ($section['resultats'] as $resultat): ?>
                    <a class="result-item" href="<?= e((string)$resultat['url']) ?>">
                        <span>
                            <strong><?= e((string)$resultat['titre']) ?></strong>
                            <?php if (($resultat['sous'] ?? '') !== ''): ?>
                                <small> — <?= e((string)$resultat['sous']) ?></small>
                            <?php endif; ?>
                        </span>
                        <span aria-hidden="true">→</span>
                    </a>
                <?php endforeach; ?>
                <?php if ((int)$section['total'] > count($section['resultats'])): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e((string)$section['urlListe']) ?>">
                        Voir les <?= (int)$section['total'] ?> résultats
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<!-- AE-EOF : le fichier recherche/index.php doit se terminer exactement ici -->