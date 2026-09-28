<?php
// fichier : views/notifications/index.php — CRM Auto-École, J4
declare(strict_types=1);

 $liste    = $liste ?? [];
 $nbNonLus = (int)($nbNonLus ?? 0);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">
            <?= count($liste) ?> notification<?= count($liste) > 1 ? 's' : '' ?>
            <?= $nbNonLus > 0 ? ' — ' . $nbNonLus . ' non lue' . ($nbNonLus > 1 ? 's' : '') : '' ?>
        </p>
    </div>
    <?php if ($nbNonLus > 0): ?>
        <form method="post" action="<?= url('/notifications/tout-lu') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-ghost" type="submit">Tout marquer comme lu</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucune notification</p>
                <p class="empty-text">Les nouveaux commentaires sur vos conversations apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <?php foreach ($liste as $notification): ?>
                <?php $nonLu = (int)($notification['lu'] ?? 0) === 0; ?>
                <div class="notif-item<?= $nonLu ? ' notif-unread' : ' notif-lu' ?>">
                    <span class="notif-dot" aria-hidden="true"></span>
                    <div class="notif-main">
                        <div class="notif-titre"><?= e((string)$notification['titre']) ?></div>
                        <?php if ((string)($notification['message'] ?? '') !== ''): ?>
                            <div class="notif-msg"><?= e((string)$notification['message']) ?></div>
                        <?php endif; ?>
                        <div class="notif-date"><?= e((string)$notification['cree_le']) ?></div>
                    </div>
                    <?php if ((string)($notification['url'] ?? '') !== ''): ?>
                        <a class="btn btn-sm btn-ghost" href="<?= url('/notifications/' . (int)$notification['id'] . '/lire') ?>">Ouvrir</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<!-- AE-EOF : le fichier notifications/index.php doit se terminer exactement ici -->