<?php
// fichier : views/partials/flash.php — CRM Auto-École (renvoi complet)
declare(strict_types=1);

use App\Core\Session;

 $flashError   = Session::getFlash('error');
 $flashSuccess = Session::getFlash('success');
?>
<?php if (is_string($flashError) && $flashError !== ''): ?>
    <div class="alert alert-danger alert-auto" role="alert">
        <span><?= e($flashError) ?></span>
        <button type="button" class="alert-close" data-dismiss aria-label="Fermer">&times;</button>
    </div>
<?php endif; ?>
<?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="alert alert-success alert-auto" role="alert">
        <span><?= e($flashSuccess) ?></span>
        <button type="button" class="alert-close" data-dismiss aria-label="Fermer">&times;</button>
    </div>
<?php endif; ?>
<!-- AE-EOF : le fichier partials/flash.php doit se terminer exactement ici -->