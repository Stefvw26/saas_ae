<?php
// fichier : views/referentiel/index.php — vue générique (v0.22 : étoiles)
declare(strict_types=1);

use App\Core\View;

 $champs            = $champs ?? [];
 $options           = $options ?? [];
 $optionsCouleurs   = $optionsCouleurs ?? [];
 $icones            = $icones ?? [];
 $colonnesCalculees = $colonnesCalculees ?? [];
 $colonneIdentite   = $colonneIdentite ?? [];
 $listeStatut       = $listeStatut ?? true;
 $listeDate         = $listeDate ?? true;
 $filtres           = $filtres ?? ['q' => ''];
 $liste             = $liste ?? [];
 $routeBase         = $routeBase ?? '/';
 $prefix            = $prefixPermission ?? '';
 $colonneDate       = $colonneDate ?? 'ajout_le';
 $requete           = array_filter($filtres, static fn ($v) => $v !== '' && $v !== null);

/* Colonnes : champs 'liste' (tri par position), sinon tous sauf photos. */
 $marquees = [];
foreach ($champs as $nom => $def) {
    if (($def['type'] ?? '') !== 'photo' && !empty($def['liste'])) {
        $marquees[$nom] = $def;
    }
}
 $colonnes = $marquees;
if ($colonnes === []) {
    foreach ($champs as $nom => $def) {
        if (($def['type'] ?? '') !== 'photo') {
            $colonnes[$nom] = $def;
        }
    }
}
uasort($colonnes, static fn (array $a, array $b): int => (int)($a['position'] ?? 100) <=> (int)($b['position'] ?? 100));
 $premier = array_key_first($colonnes);

 $urlExport = url($routeBase) . '?' . http_build_query(array_merge($requete, ['export' => 'excel']));
?>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= e(ucfirst((string)$titrePluriel)) ?></h1>
        <p class="page-subtitle">
            <?= (int)$total ?> <?= e((int)$total > 1 ? (string)$titrePluriel : (string)$titreSingulier) ?> — référentiel du client.
        </p>
    </div>
    <?php if (can($prefix . '.creer')): ?>
        <a class="btn btn-primary" href="<?= url($routeBase . '/creer') ?>">+ Créer <?= e((string)$article) ?> <?= e((string)$titreSingulier) ?></a>
    <?php endif; ?>
</div>

<div class="print-header">
    <strong><?= e(ucfirst((string)$titrePluriel)) ?></strong>
    <span> — <?= (int)$total ?> élément<?= (int)$total > 1 ? 's' : '' ?> — édité le <?= e(date('d/m/Y à H:i')) ?></span>
</div>

<form class="filter-bar" method="get" action="<?= url($routeBase) ?>">
    <input class="input filter-grow" type="search" name="q" placeholder="Rechercher…" value="<?= e($filtres['q']) ?>">
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="<?= url($routeBase) ?>">Réinitialiser</a>
    <span class="filter-side">
        <a class="btn btn-ghost btn-sm" href="<?= e($urlExport) ?>">Exporter Excel</a>
        <button type="button" class="btn btn-ghost btn-sm" data-print>Imprimer</button>
    </span>
</form>

<?php if (isset($cartePoints)): ?>
<div class="card carte-bloc no-print">
    <div class="card-header">
        <span>Carte — <?= e(ucfirst((string)$titrePluriel)) ?></span>
        <span class="card-count"><?= count($cartePoints) ?> géolocalisé<?= count($cartePoints) > 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <div id="carte-referentiel" class="carte" data-points='<?= e(json_encode($cartePoints, JSON_UNESCAPED_UNICODE)) ?>'></div>
        <p class="carte-note">
            Fond de carte © OpenStreetMap. Seuls les éléments renseignés en latitude/longitude apparaissent.
        </p>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        (function () {
            var conteneur = document.getElementById('carte-referentiel');
            if (!conteneur || !window.L) { return; }

            function esc(s) {
                return String(s || '').replace(/[&<>"']/g, function (m) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
                });
            }

            var points = [];
            try { points = JSON.parse(conteneur.getAttribute('data-points') || '[]'); } catch (e) { points = []; }

            var carte = L.map(conteneur).setView([46.6, 2.4], 5);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(carte);

            points.forEach(function (p) {
                L.marker([p.lat, p.lon]).addTo(carte)
                    .bindPopup('<strong>' + esc(p.titre) + '</strong>' + (p.sous ? '<br>' + esc(p.sous) : ''));
            });

            if (points.length === 1) {
                carte.setView([points[0].lat, points[0].lon], 10);
            }
        })();
        </script>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body table-overflow">
        <?php if ($liste === []): ?>
            <div class="empty-state">
                <p class="empty-title">Aucun <?= e((string)$titreSingulier) ?></p>
                <p class="empty-text">Aucun élément ne correspond à ces critères dans votre périmètre.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <?php if ($colonneIdentite !== []): ?><th class="cell-icon-th"></th><?php endif; ?>
                    <?php foreach ($colonnes as $nom => $def): ?>
                        <th><?= e($def['label']) ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($colonnesCalculees as $col): ?>
                        <th><?= e((string)($col['label'] ?? '')) ?></th>
                    <?php endforeach; ?>
                    <?php if ($listeStatut): ?><th>Statut</th><?php endif; ?>
                    <?php if ($listeDate): ?><th>Ajouté le</th><?php endif; ?>
                    <?php if (can($prefix . '.modifier')): ?><th class="cell-actions">Actions</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($liste as $entite): ?>
                    <tr>
                        <?php if ($colonneIdentite !== []):
                            $imgIdentite = $colonneIdentite['images'][(string)($entite[$colonneIdentite['champ']] ?? '')] ?? null; ?>
                            <td class="cell-icon">
                                <?php if ($imgIdentite !== null): ?>
                                    <img class="img-ref img-ref-md" src="<?= e(icone_url((string)$imgIdentite)) ?>" alt="">
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php foreach ($colonnes as $nom => $def):
                            $type = $def['type'] ?? 'text';
                            $brut = $entite[$nom] ?? null;
                            ?>
                            <td>
                                <?php if ($type === 'color'): ?>
                                    <?php if ($brut !== null && $brut !== ''): ?>
                                        <span class="dot" style="background-color:<?= e((string)$brut) ?>"></span>
                                    <?php else: ?>—<?php endif; ?>
                                <?php elseif ($type === 'agence'): ?>
                                    <?php if ($brut !== null && (string)$brut !== ''):
                                        $couleurAgence = $optionsCouleurs[$nom][(string)$brut] ?? ''; ?>
                                        <?php if ($couleurAgence !== ''): ?>
                                            <span class="badge badge-agence" style="background-color:<?= e($couleurAgence) ?>"><?= e((string)($options[$nom][(string)$brut] ?? $brut)) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-muted"><?= e((string)($options[$nom][(string)$brut] ?? $brut)) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>—<?php endif; ?>
                                <?php elseif ($type === 'etoiles'):
                                    $n = max(0, min(5, (int)($brut ?? 0))); ?>
                                    <?= $n > 0
                                        ? '<span class="etoiles" title="' . $n . ' étoile' . ($n > 1 ? 's' : '') . '">' . str_repeat('★', $n) . str_repeat('☆', 5 - $n) . '</span>'
                                        : '—' ?>
                                <?php elseif (!empty($def['images']) && $brut !== null && isset($def['images'][(string)$brut])): ?>
                                    <span class="cell-with-img">
                                        <img class="img-ref img-ref-sm" src="<?= e(icone_url((string)$def['images'][(string)$brut])) ?>" alt="<?= e((string)$brut) ?>">
                                        <small><?= e((string)$brut) ?></small>
                                    </span>
                                <?php elseif (($icones[$nom] ?? []) !== [] && $brut !== null && isset($icones[$nom][(string)$brut])): ?>
                                    <img class="img-ref img-ref-md" src="<?= e(icone_url((string)$icones[$nom][(string)$brut])) ?>"
                                         alt="" title="<?= e((string)($options[$nom][(string)$brut] ?? $brut)) ?>">
                                <?php elseif ($type === 'booleen'): ?>
                                    <?= (int)$brut === 1 ? '<span class="badge badge-ok">Oui</span>' : '<span class="badge badge-muted">—</span>' ?>
                                <?php elseif ($type === 'referer' || $type === 'select'): ?>
                                    <?= ($brut !== null && (string)$brut !== '')
                                        ? e((string)($options[$nom][(string)$brut] ?? $brut))
                                        : '—' ?>
                                <?php else: ?>
                                    <?php
                                    $valeurTxt = (string)($brut ?? '');
                                    $apercu = mb_strlen($valeurTxt) > 60 ? mb_substr($valeurTxt, 0, 60) . '…' : $valeurTxt;
                                    ?>
                                    <?php if ($nom === $premier && $apercu !== ''): ?>
                                        <strong><?= e($apercu) ?></strong>
                                    <?php else: ?>
                                        <?= e($apercu !== '' ? $apercu : '—') ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php foreach ($colonnesCalculees as $col): ?>
                            <td><?= e((string)($entite[$col['cle']] ?? '—')) ?></td>
                        <?php endforeach; ?>
                        <?php if ($listeStatut): ?>
                            <td><?= (int)($entite['actif'] ?? 0) === 1
                                ? '<span class="badge badge-ok">Actif</span>'
                                : '<span class="badge badge-danger">Inactif</span>' ?></td>
                        <?php endif; ?>
                        <?php if ($listeDate): ?>
                            <td><?= e((string)($entite[$colonneDate] ?? '—')) ?></td>
                        <?php endif; ?>
                        <?php if (can($prefix . '.modifier')): ?>
                            <td class="cell-actions">
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-ghost" href="<?= url($routeBase . '/' . (int)$entite['id'] . '/modifier') ?>">Modifier</a>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
View::partial('partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'baseUrl' => $baseUrl,
    'query'   => $requete,
]);
?>