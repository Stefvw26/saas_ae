<?php
// fichier : modules/formules/views/form.php — v0.54
// Card « Ajouter une prestation » DÉDIÉE, AU-DESSUS de la liste.
// Menus PLEINE LARGEUR. Quantité MAJ auto + message. Doublons refusés.
// Nom de la formule + Domaine sur la MÊME LIGNE (directive).
declare(strict_types=1);

use App\Core\View;

$formule            = $formule ?? null;
$optionsDomaines   = $optionsDomaines ?? [];
$optionsPrestations = $optionsPrestations ?? [];
$prestationsFormule = $prestationsFormule ?? [];
$erreurs          = form_errors();
$edition         = $formule !== null;

$chemin = $edition ? '/formules/' . (int)$formule['id'] : '/formules';

$v = static function (string $cle) use ($formule): string {
    return old($cle, (string)($formule[$cle] ?? ''));
};
$cocheActif = old('actif', (string)($formule['actif'] ?? '1')) === '1';

$libellePrincipal = '';
foreach (['nom'] as $cle) { $libellePrincipal = $v($cle); break; }

/* Ids déjà dans la formule (options désactivées dans le select). */
$idsFormule = [];

foreach ($prestationsFormule as $p) {

    if(!isset($p['id'])) {$p['id']= $p['fp_id'];}
    $idsFormule[(int)$p['id']] = true;
}

$euro = static function ($x): string {
    return number_format((float)($x ?? 0), 2, ',', ' ') . ' €';
};
?>
<style>
    /* v0.54 — intégrés : combos pleins, message, total, tableau. */
    .combo-xxl { position: relative; width: 100%; }
    .combo-xxl .combo-input { width: 100%; min-width: 600px; padding: 12px 16px; font-size: 1rem; }
    .combo-xxl .combo-list {
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        z-index: 40;
        max-height: 300px;
        overflow-y: auto;
        margin: 0;
        padding: 8px;
        list-style: none;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(15,23,42,.15);
        display: none;
        font-size: .95rem;
    }
    .combo-xxl.open .combo-list { display: block; }
    .combo-xxl .combo-list li {
        padding: 12px 18px;
        border-radius: 8px;
        cursor: pointer;
    }
    .combo-xxl .combo-list li:hover { background: #e8effd; }
    .combo-xxl .combo-list li.is-selected { font-weight: 700; color: #1d4ed8; }
    .combo-xxl .combo-list li.is-disabled { color: #94a3b8; cursor: not-allowed; background: none; }
    .combo-xxl .combo-native { display: none; }

    .ajout-ligne { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 12px; }
    #formule-msg {
        display: none;
        padding: 10px 16px;
        margin-bottom: 12px;
        border-radius: 10px;
        font-size: .88rem;
        font-weight: 600;
    }
    .formule-msg-ok { background: #e7f6ec; color: #14532d; border: 1px solid #cdeed8; }
    .formule-msg-ko { background: #fdeaea; color: #991b1b; border: 1px solid #f6cfcf; }
    input.quantite-formule { width: 82px; text-align: center; }
    .total-ttc {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
        margin-top: 14px;
        padding: 12px 16px;
        background: #e8effd;
        border: 1px solid #cfdcf8;
        border-radius: 10px;
    }
    .total-ttc span {
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #1d4ed8;
    }
    .total-ttc strong { font-size: 1.25rem; font-weight: 800; color: #0f172a; }

    @media (max-width: 640px) {
        .combo-xxl .combo-input { min-width: 0; width: 100%; font-size: .95rem; }
    .combo-xxl .combo-list { font-size: .9rem; }
    .combo-xxl .combo-list li { padding: 11px 14px; }
    .ajout-ligne { width: 100%; }
    }
</style>

<div class="page-form">
    <h1 class="page-title"><?= $edition ? 'Formule « ' . e((string)$formule['nom']) . ' »' : 'Créer une formule' ?></h1>
    <p class="page-subtitle">Une formule regroupe des prestations ; son montant TTC est la somme des prestations (quantités comprises).</p>
    <a class="link-back" href="<?= url('/formules') ?>">← Retour à la liste</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

    <?php /* ═══ CARD 1 : IDENTITÉ (Nom + Domaine MÊME LIGNE — directive) ═══ */ ?>
    <form method="post" action="<?= url($chemin) ?>">
        <?= csrf_field() ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Identité</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="nom">Nom de la formule <span class="req">*</span></label>
                        <input class="input" type="text" id="nom" name="nom" maxlength="150" required
                            value="<?= e($v('nom')) ?>" placeholder="ex. Permis B — Pack complet">
                        <?php if (form_error('nom')): ?><p class="form-error"><?= e(form_error('nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="domaine_id">Domaine (lieu de formation)</label>
                        <select class="input" id="domaine_id" name="domaine_id">
                            <option value="">— Non défini —</option>
                            <?php $domaineChoisi = $v('domaine_id'); ?>
                            <?php foreach ($optionsDomaines as $idDom => $dom): ?>
                                <option value="<?= (int)$idDom ?>" <?= $domaineChoisi === (string)$idDom ? 'selected' : '' ?>><?= e($dom['nom']) ?></option>
                                <?php endforeach; ?>
                        </select>
                        <?php if (form_error('domaine_id')): ?><p class="form-error"><?= e(form_error('domaine_id')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field-full">
                        <label class="form-label" for="descriptif">Descriptif</label>
                        <textarea class="input" id="descriptif" name="descriptif" rows="3" maxlength="500"><?= e($v('descriptif')) ?></textarea>
                    </div>
                </div>
            </section>

            <div class="form-footer">
                <label class="check-item">
                    <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                    <span>Active</span>
                </label>
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $edition ? 'Enregistrer' : 'Créer la formule' ?></button>
                    <a class="btn btn-ghost" href="<?= url('/formules') ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>

    <?php if ($edition): ?>
        <?php /* ═══ CARD 2 : AJOUTER UNE PRESTATION — DÉDIÉE, AU-DESSUS de la liste ═══ */ ?>
        <div class="card form-sheet" style="margin-top:16px">
            <section class="sheet-section">
                <div class="sheet-title">Ajouter une prestation</div>

                <div id="formule-msg" role="status" aria-live="polite"></div>

                <?php if ($optionsPrestations === []): ?>
                    <p class="form-text">Aucune prestation active disponible — créez d'abord des prestations (Référentiels → Prestations).</p>
                    <?php else: ?>
                    <div class="field">
                        <label class="form-label" for="paq-recherche">Prestation (recherche)</label>
                        <div class="combo combo-xxl" data-combo>
                            <input class="input combo-input" type="text" id="paq-recherche"
                                placeholder="Tapez pour rechercher une prestation…" autocomplete="off">
                            <ul class="combo-list" role="listbox"></ul>
                            <select class="combo-native" id="paq-select" aria-label="Prestation">
                                <option value="">— Choisir —</option>
                                <?php foreach ($optionsPrestations as $p): $deja = isset($idsFormule[(int)$p['id']]); ?>
                                    <option value="<?= (int)$p['id'] ?>"<?= $deja ? ' disabled' : '' ?>>
                                        <?= e((string)$p['titre']) ?> — <?= e($euro($p['prix_vente'])) ?><?= $deja ? ' (déjà dans la formule)' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="ajout-ligne">
                        <input class="input" type="number" min="1" step="1" value="1" id="paq-quantite" style="width:90px" title="Quantité">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-ajout-prestation">+ Ajouter à la formule</button>
                    </div>
                    <?php endif; ?>
            </section>

            <?php /* ═══ CARD 2 (suite) : PRESTATIONS DE LA FORMULE ═══ */ ?>
            <section class="sheet-section">
                <div class="sheet-title">Prestations de la formule</div>

                <div class="table-overflow">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Prestation</th><th>Prix</th><th class="center">Qté</th><th>Total</th>
                                <?php if (can('formules.modifier')): ?><th class="cell-actions"></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($prestationsFormule === []): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <p class="empty-title">Aucune prestation</p>
                                            <p class="empty-text">Ajoutez ci-dessus les prestations composant cette formule.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($prestationsFormule as $p): $fpId = (int)$p['fp_id']; ?>
                                    <tr>
                                        <td>
                                            <strong><?= e((string)$p['titre']) ?></strong>
                                            <?php if ((string)($p['sku'] ?? '') !== ''): ?>
                                            <small style="display:block;color:#64748b;font-size:.74rem"><?= e((string)$p['sku']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($euro($p['prix_vente'])) ?></td>
                                        <td class="center">
                                            <?php if (can('formules.modifier')): ?>
                                                <input class="input quantite-formule" type="number" min="1" step="1"
                                                    value="<?= (int)$p['quantite'] ?>"
                                                    data-url="<?= url('/formules/prestation/' . $fpId . '/quantite') ?>">
                                                <?php else: ?>
                                                <?= (int)$p['quantite'] ?>
                                                <?php endif; ?>
                                        </td>
                                        <td><strong><?= e($euro((float)$p['prix_vente'] * (int)$p['quantite'])) ?></strong></td>
                                        <?php if (can('formules.modifier')): ?>
                                            <td class="cell-actions">
                                                <button type="button" class="btn btn-sm btn-danger prestation-retirer"
                                                    data-url="<?= url('/formules/prestation/' . $fpId . '/retirer') ?>"
                                                    data-libelle="<?= e((string)$p['titre']) ?>">&times;</button>
                                            </td>
                                            <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="total-ttc">
                    <span>Montant TTC de la formule</span>
                    <strong><?= e($euro($formule['montant_ttc'] ?? 0)) ?></strong>
                </div>
            </section>
        </div>
        <?php endif; ?>
</div>

<?php /* ═══ JS : combo + ajout + quantité auto + retrait ═══ */ ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        var jeton = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        var msg = document.getElementById('formule-msg');

        function message(texte, ko) {
            if (!msg) { return; }
            msg.textContent = texte;
            msg.className = ko ? 'formule-msg-ko' : 'formule-msg-ok';
            msg.style.display = 'block';
            clearTimeout(message._t);
            message._t = setTimeout(function () { msg.style.display = 'none'; }, 3500);
        }

        /* ---------- Combo pleine largeur ---------- */
        var input = document.getElementById('paq-recherche');
        var select = document.getElementById('paq-select');
        if (input && select) {
            var conteneur = input.closest('.combo');
            var liste = conteneur ? conteneur.querySelector('.combo-list') : null;

            if (conteneur && liste) {
                var construire = function (filtre) {
                    filtre = (filtre || '').toLowerCase();
                    liste.innerHTML = '';
                    Array.prototype.forEach.call(select.options, function (opt) {
                        var texte = opt.textContent || '';
                        if (opt.value === '' || (filtre !== '' && texte.toLowerCase().indexOf(filtre) === -1)) { return; }
                        var li = document.createElement('li');
                        li.textContent = texte;
                        li.className = opt.disabled ? 'is-disabled' : '';
                        li.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            if (opt.disabled) { return; }
                            select.value = opt.value;
                            input.value = texte;
                            conteneur.classList.remove('open');
                        });
                        liste.appendChild(li);
                    });
                };

                input.addEventListener('focus', function () { construire(input.value); conteneur.classList.add('open'); });
                input.addEventListener('input', function () { conteneur.classList.add('open'); construire(input.value); });
                input.addEventListener('blur', function () { setTimeout(function () { conteneur.classList.remove('open'); }, 180); });
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { conteneur.classList.remove('open'); }
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var premier = liste.querySelector('li:not(.is-disabled)');
                        if (premier) { premier.dispatchEvent(new MouseEvent('mousedown', { cancelable: true })); }
                    }
                });
            }
        }

        /* ---------- POST AJAX ---------- */
        function post(url, donnees, succes) {
            donnees._token = jeton;
            fetch(url, {
                method: 'POST',
                body: new URLSearchParams(donnees),
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(function (r) { return r.json(); })
            .then(function (rep) {
                if (!rep || !rep.ok) {
                    message((rep && (rep.erreur || rep.message)) || 'Modification impossible.', true);
                    return;
                }
                succes(rep);
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .catch(function () { message('Modification impossible (serveur).', true); });
        }

        /* ---------- Ajout d'une prestation ---------- */
        var btnAjout = document.getElementById('btn-ajout-prestation');
        if (btnAjout) {
            btnAjout.addEventListener('click', function () {
                var pid = select ? select.value : '';
                if (!pid) { message('Choisissez une prestation.', true); return; }
                var quantite = document.getElementById('paq-quantite');
                post('<?= url('/formules/' . (int)($formule['id'] ?? 0) . '/prestation') ?>',
                    { prestation_id: pid, quantite: quantite ? quantite.value : 1 },
                    function (rep) { message(rep.message || 'Prestation ajoutée.', false); });
            });
        }

        /* ---------- Quantité : MAJ AUTOMATIQUE ---------- */
        document.querySelectorAll('input.quantite-formule[data-url]').forEach(function (input) {
            input.addEventListener('change', function () {
                post(input.getAttribute('data-url'),
                    { quantite: input.value },
                    function (rep) { message(rep.message || 'Quantité mise à jour.', false); });
            });
        });

        /* ---------- Retrait ---------- */
        document.querySelectorAll('.prestation-retirer').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var libelle = btn.getAttribute('data-libelle') || 'cette prestation';
                if (!window.confirm('Retirer « ' + libelle + ' » de la formule ?')) { return; }
                post(btn.getAttribute('data-url'), {},
                    function (rep) { message(rep.message || 'Prestation retirée.', false); });
            });
        });
    });
</script>
<!-- AE-EOF : le fichier formules/views/form.php doit se terminer exactement ici -->