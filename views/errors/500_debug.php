<?php
// fichier : views/errors/500_debug.php
declare(strict_types=1);

/** @var Throwable|null $exception */
 $exception = $exception ?? null;
?>
<p class="error-code">500</p>
<h1 class="error-title">Erreur interne — mode debug</h1>
<?php if ($exception !== null): ?>
    <div class="debug-box">
        <p class="debug-class"><?= e(get_class($exception)) ?></p>
        <p class="debug-msg"><?= e($exception->getMessage()) ?></p>
        <p class="debug-meta"><?= e($exception->getFile()) ?> : ligne <?= e((string)$exception->getLine()) ?></p>
        <pre><?= e($exception->getTraceAsString()) ?></pre>
    </div>
<?php else: ?>
    <p class="error-text">Erreur inconnue.</p>
<?php endif; ?>