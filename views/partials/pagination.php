<?php
// fichier : views/partials/pagination.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

 $page    = $page ?? 1;
 $pages   = $pages ?? 1;
 $total   = $total ?? 0;
 $baseUrl = $baseUrl ?? '/';
 $query   = $query ?? [];

if ($pages <= 1) {
    return;
}

 $lien = static function (int $numero) use ($baseUrl, $query): string {
    $parametres = $query;
    $parametres['page'] = $numero;
    return url($baseUrl) . '?' . http_build_query($parametres);
};

 $debut = max(1, $page - 2);
 $fin   = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="Pagination">
    <span class="pagination-info">
        <?= (int)$total ?> résultat<?= (int)$total > 1 ? 's' : '' ?> — page <?= (int)$page ?> / <?= (int)$pages ?>
    </span>
    <div class="pagination-links">
        <?php if ($page > 1): ?>
            <a class="page-link" href="<?= e($lien($page - 1)) ?>">‹ Précédent</a>
        <?php endif; ?>

        <?php if ($debut > 1): ?>
            <a class="page-link" href="<?= e($lien(1)) ?>">1</a>
            <?php if ($debut > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $debut; $i <= $fin; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="page-link is-active" aria-current="page"><?= (int)$i ?></span>
            <?php else: ?>
                <a class="page-link" href="<?= e($lien($i)) ?>"><?= (int)$i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($fin < $pages): ?>
            <?php if ($fin < $pages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <a class="page-link" href="<?= e($lien($pages)) ?>"><?= (int)$pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
            <a class="page-link" href="<?= e($lien($page + 1)) ?>">Suivant ›</a>
        <?php endif; ?>
    </div>
</nav>