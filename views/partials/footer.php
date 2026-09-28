<?php
// fichier : views/partials/footer.php — CRM Auto-École (renvoi complet)
declare(strict_types=1);

use App\Core\Config;

 $appName = $appName ?? (string)Config::get('app.name', 'CRM Auto-École');
?>
<footer class="app-footer">
    <span><?= e($appName) ?> · <?= e(APP_VERSION) ?></span>
    <span>Jalon 4 — Services transverses</span>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
<!-- AE-EOF : le fichier partials/footer.php doit se terminer exactement ici -->