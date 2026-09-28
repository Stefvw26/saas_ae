<?php
// fichier : views/components/flash.php
declare(strict_types=1);

use App\Core\Session;

 $flashError   = Session::getFlash('error');
 $flashSuccess = Session::getFlash('success');
?>
<?php if (is_string($flashError) && $flashError !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>
<?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>