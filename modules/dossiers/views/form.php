<?php
// fichier : modules/dossiers/views/form.php — v0.55
// CARTE IDENTITÉ ÉLÈVE en tête (identique fiche élève) + 3 CARDS DISTINCTES :
// « Sélection » (lieu de formation, catégorie, type de formation en switch),
// « Ajouter au panier », « Panier ». Panier 100% AJAX (aucun rechargement de page).
declare(strict_types=1);

use App\Core\View;

 $dossier             = $dossier ?? null;
 $elevePreselectionne = $elevePreselectionne ?? null;
 $optionsDomaines   = $optionsDomaines ?? [];
 $optionsTypesPermis = $optionsTypesPermis ?? [];
 $optionsPrestations = $optionsPrestations ?? [];
 $formulesDomaine = $formulesDomaine ?? [];
 $panier           = $panier ?? [];
 $panierTotal     = (float)($panierTotal ?? 0);
 $actifOffert    = (bool)($panierActifOffert ?? true);
 $actifCpf      = (bool)($panierActifCpf ?? true);
 $erreurs       = form_errors();
 $edition     = $dossier !== null;

 $chemin = $edition ? '/dossiers/' . (int)$dossier['id'] : '/dossiers';
 $retourEleveId = (int)($elevePreselectionne['id'] ?? $dossier['eleve_id'] ?? 0);
 $retourEleve  = $retourEleveId > 0 ? '/eleves/' . $retourEleveId : '/eleves';

 $v = static function (string $cle) use ($dossier): string {
    return old($cle, (string)($dossier[$cle] ?? ''));
};

 $domaineChoisi   = $v('domaine_id');
 $typePermisChoisi = $v('type_permis_id');
 $modeChoisi = old('mode', 'a_la_carte');

 $idsPanier = [];
foreach ($panier as $ligne) {
    $idsPanier[(int)$ligne['prestation_id']] = true;
}

 $euro = static function ($x): string {
    return number_format((float)($x ?? 0), 2, ',', ' ') . ' €';
};

/* Carte identité élève (même pattern que modules/eleves/views/fiche.php). */
 $eleveNomComplet = trim((string)($elevePreselectionne['prenom'] ?? '') . ' ' . (string)($elevePreselectionne['nom'] ?? ''));
 $genreBadge = '';
if ((string)($elevePreselectionne['civilite'] ?? '') === 'Monsieur') {
    $genreBadge = '<span class="genre-badge genre-m" title="Masculin">&#9794;</span>';
} elseif ((string)($elevePreselectionne['civilite'] ?? '') === 'Madame') {
    $genreBadge = '<span class="genre-badge genre-f" title="Féminin">&#9792;</span>';
}
?>
<style>
/* v0.53 — styles intégrés : card ajout + combos PLEINE LARGEUR + panier (directive). */
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
.combo-xxl .combo-caret {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b; pointer-events: none;
}

.ajout-ligne { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 12px; }

/* Recherche + quantité + bouton sur une seule ligne, largeur bien répartie. */
.ajout-ligne-combo { margin-top: 8px; }
.ajout-ligne-combo .combo-grow { flex: 1 1 320px; min-width: 240px; }
.ajout-ligne-combo .combo-grow .combo-input { min-width: 0; width: 100%; }
.ajout-ligne-combo input[type="number"] { width: 90px; flex: 0 0 90px; text-align: center; }
.ajout-ligne-combo button { flex: 0 0 auto; white-space: nowrap; }

/* Ligne de formule dans la liste déroulante : nom à gauche, badges à droite. */
.combo-li-formule { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; }
.combo-li-nom { font-weight: 600; color: var(--ink-2, #1e293b); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.combo-li-badges { display: flex; gap: 6px; flex-shrink: 0; }
.combo-li-montant { font-weight: 800; }

/* Switch compact : Offert / CPF (cellule de tableau, mutuellement exclusifs). */
.switch-mini { position: relative; display: inline-block; width: 38px; height: 22px; vertical-align: middle; }
.switch-mini input { position: absolute; opacity: 0; width: 0; height: 0; }
.switch-mini .switch-mini-piste {
    position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px;
    cursor: pointer; transition: background .15s ease;
}
.switch-mini .switch-mini-piste::before {
    content: ""; position: absolute; left: 3px; top: 3px; width: 16px; height: 16px;
    background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(15,23,42,.35);
    transition: transform .15s ease;
}
.switch-mini input:checked + .switch-mini-piste { background: var(--brand, #1d4ed8); }
.switch-mini input:checked + .switch-mini-piste::before { transform: translateX(16px); }
.switch-mini input:disabled + .switch-mini-piste { opacity: .5; cursor: not-allowed; }

#panier-msg {
    display: none;
    padding: 10px 16px;
    margin-bottom: 12px;
    border-radius: 10px;
    font-size: .88rem;
    font-weight: 600;
}
.panier-msg-ok { background: #e7f6ec; color: #14532d; border: 1px solid #cdeed8; }
.panier-msg-ko { background: #fdeaea; color: #991b1b; border: 1px solid #f6cfcf; }
input.panier-quantite { width: 82px; text-align: center; }
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
    .ajout-ligne { width: 100%; }
    .ajout-ligne-combo { flex-direction: column; align-items: stretch; }
    .ajout-ligne-combo .combo-grow { flex: 1 1 auto; width: 100%; }
    .ajout-ligne-combo input[type="number"] { width: 100%; flex: 1 1 auto; }
    .combo-xxl .combo-list { font-size: .9rem; }
    .combo-xxl .combo-list li { padding: 11px 14px; }
}

/* Switch 2 états : Type de formation (à la carte / formule). */
.mode-switch { display: inline-flex; padding: 4px; background: var(--line-soft, #f1f5f9); border-radius: 999px; gap: 2px; }
.mode-switch input { position: absolute; opacity: 0; pointer-events: none; }
.mode-switch label {
    position: relative;
    padding: 9px 22px;
    border-radius: 999px;
    font-size: .88rem;
    font-weight: 700;
    color: var(--ink-3, #64748b);
    cursor: pointer;
    transition: background .16s ease, color .16s ease;
}
.mode-switch input:checked + label { background: #fff; color: var(--brand, #1d4ed8); box-shadow: 0 1px 4px rgba(15,23,42,.15); }
@media (max-width: 640px) {
    .mode-switch { width: 100%; }
    .mode-switch label { flex: 1; text-align: center; padding: 9px 10px; }
}
</style>

<div class="page-form">
    <?php /* ═══ CARTE IDENTITÉ ÉLÈVE (identique fiche élève : badge genre + nom + badges domaine/agence) ═══ */ ?>
    <div class="card identite-card">
        <div class="identite-body">
            <div class="identite-nom">
                <h1 class="page-title"><?= $genreBadge ?><?= e($eleveNomComplet) ?></h1>
                <p class="page-subtitle"><?= $edition ? 'Modifier le dossier #' . (int)$dossier['id'] : 'Nouveau dossier' ?></p>
            </div>
            <div class="identite-badges">
                <?php if (!empty($elevePreselectionne['domaine_nom'])): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($elevePreselectionne['domaine_couleur'] ?: '#64748b') ?>"><?= e((string)$elevePreselectionne['domaine_nom']) ?></span>
                <?php endif; ?>
                <?php if (!empty($elevePreselectionne['agence_nom'])): ?>
                    <span class="badge-entite-xl" style="background-color:<?= e($elevePreselectionne['agence_couleur'] ?: '#64748b') ?>"><?= e((string)$elevePreselectionne['agence_nom']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a class="link-back" href="<?= url($retourEleve) ?>">← Retour à la fiche élève</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url($chemin) ?>" id="dossier-form">
        <?= csrf_field() ?>

        <?php /* ═══ CARD 1 : SÉLECTIONS ═══ */ ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Sélection</div>
                <div class="form-grid">

                    <input type="hidden" name="eleve_id" value="<?= (int)($elevePreselectionne['id'] ?? 0) ?>">
                    <?php if (form_error('eleve_id')): ?><p class="form-error"><?= e(form_error('eleve_id')) ?></p><?php endif; ?>

                    <div class="field">
                        <label class="form-label" for="domaine_id">Lieu de formation (domaine) <span class="req">*</span></label>
                        <select class="input" id="domaine_id" name="domaine_id" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($optionsDomaines as $idDom => $dom): ?>
                                <option value="<?= (int)$idDom ?>" <?= $domaineChoisi === (string)$idDom ? 'selected' : '' ?>><?= e($dom['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('domaine_id')): ?><p class="form-error"><?= e(form_error('domaine_id')) ?></p><?php endif; ?>
                    </div>

                    <div class="field">
                        <label class="form-label" for="type_permis_id">Catégorie de formation <span class="req">*</span></label>
                        <select class="input" id="type_permis_id" name="type_permis_id" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($optionsTypesPermis as $idTp => $nomTp): ?>
                                <option value="<?= (int)$idTp ?>" <?= $typePermisChoisi === (string)$idTp ? 'selected' : '' ?>><?= e($nomTp) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('type_permis_id')): ?><p class="form-error"><?= e(form_error('type_permis_id')) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field" style="margin-top:14px">
                    <label class="form-label">Type de formation <span class="req">*</span></label>
                    <div class="mode-switch" role="radiogroup" aria-label="Type de formation">
                        <input type="radio" id="mode-carte" name="mode" value="a_la_carte" <?= $modeChoisi !== 'formule' ? 'checked' : '' ?>>
                        <label for="mode-carte">À la carte</label>
                        <input type="radio" id="mode-formule" name="mode" value="formule" <?= $modeChoisi === 'formule' ? 'checked' : '' ?>>
                        <label for="mode-formule">Formule</label>
                    </div>
                </div>
            </section>
        </div>

        <?php /* ═══ CARD 2 : AJOUTER AU PANIER ═══ */ ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Ajouter au panier</div>

                <?php /* ---- À LA CARTE : ajout individuel — recherche + quantité + bouton sur UNE ligne ---- */ ?>
                <div class="field" id="zone-a-la-carte" <?= $modeChoisi === 'formule' ? 'hidden' : '' ?>>
                    <label class="form-label" for="paq-recherche">Prestation (recherche)</label>
                    <div class="ajout-ligne ajout-ligne-combo">
                        <div class="combo combo-xxl combo-grow" data-combo>
                            <input class="input combo-input" type="text" id="paq-recherche"
                                   placeholder="Tapez pour rechercher une prestation…" autocomplete="off">
                            <ul class="combo-list" role="listbox"></ul>
                            <select class="combo-native" id="paq-select" aria-label="Prestation">
                                <option value="">— Choisir —</option>
                                <?php foreach ($optionsPrestations as $p): $deja = isset($idsPanier[(int)$p['id']]); ?>
                                    <option value="<?= (int)$p['id'] ?>"<?= $deja ? ' disabled' : '' ?>>
                                        <?= e((string)$p['titre']) ?> — <?= e($euro($p['prix_vente'])) ?><?= $deja ? ' (déjà dans le panier)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input class="input" type="number" min="1" step="1" value="1" id="paq-quantite" title="Quantité">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-ajout-carte">+ Ajouter au panier</button>
                    </div>
                </div>

                <?php /* ---- FORMULE : import des prestations — visible dans les deux modes ---- */ ?>
                <div class="field" id="zone-formule">
                    <label class="form-label" for="pfq-recherche">Formule (recherche)</label>
                    <?php if ($formulesDomaine === []): ?>
                        <p class="form-text">Aucune formule active pour le domaine sélectionné. Choisissez un domaine disposant de formules.</p>
                    <?php else: ?>
                        <div class="ajout-ligne ajout-ligne-combo">
                            <div class="combo combo-xxl combo-grow combo-formule" data-combo>
                                <input class="input combo-input" type="text" id="pfq-recherche"
                                       placeholder="Tapez pour rechercher une formule…" autocomplete="off">
                                <ul class="combo-list" role="listbox"></ul>
                                <select class="combo-native" id="pfq-select" aria-label="Formule">
                                    <option value="">— Choisir —</option>
                                    <?php foreach ($formulesDomaine as $f): ?>
                                        <option value="<?= (int)$f['id'] ?>"
                                                data-nom="<?= e((string)$f['nom']) ?>"
                                                data-nb="<?= (int)($f['nb_prestations'] ?? 0) ?>"
                                                data-montant="<?= e($euro($f['montant_ttc'] ?? 0)) ?>">
                                            <?= e((string)$f['nom']) ?> — <?= e($euro($f['montant_ttc'] ?? 0)) ?> (<?= (int)($f['nb_prestations'] ?? 0) ?> prestations)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-ajout-formule">+ Importer la formule</button>
                        </div>
                        <p class="form-text">
                            En mode « À la carte », les prestations de la formule s'ajoutent au panier existant (doublons exclus).
                            En mode « Formule », seuls l'offert et le CPF restent modifiables sur les lignes importées.
                        </p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php /* ═══ CARD 3 : PANIER ═══ */ ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Panier</div>

                <div id="panier-msg" role="status" aria-live="polite"></div>

                <div class="table-overflow">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Prestation</th><th>Prix</th><th class="center">Qté</th><th>Total</th>
                            <?php if ($actifOffert): ?><th class="center">Offert</th><?php endif; ?>
                            <?php if ($actifCpf): ?><th class="center">CPF</th><?php endif; ?>
                            <th class="cell-actions"></th>
                        </tr>
                        </thead>
                        <tbody id="panier-tbody">
                        <?php if ($panier === []): ?>
                            <tr>
                                <td colspan="<?= 4 + ($actifOffert ? 1 : 0) + ($actifCpf ? 1 : 0) ?>">
                                    <div class="empty-state">
                                        <p class="empty-title">Panier vide</p>
                                        <p class="empty-text">Ajoutez des prestations ou une formule ci-dessus.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($panier as $ligne): $pid = (int)$ligne['prestation_id']; ?>
                                <tr data-ligne="1">
                                    <td>
                                        <strong><?= e((string)$ligne['titre']) ?></strong>
                                        <?php if ((string)($ligne['sku'] ?? '') !== ''): ?>
                                            <small style="display:block;color:var(--ink-3);font-size:.74rem"><?= e((string)$ligne['sku']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($euro($ligne['prix_vente'])) ?></td>
                                    <td class="center">
                                        <input class="input panier-quantite" type="number" min="1" step="1"
                                               value="<?= (int)$ligne['quantite'] ?>"
                                               data-url="<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/modifier') ?>"
                                               data-prestation="<?= $pid ?>"
                                               <?= $modeChoisi === 'formule' ? 'disabled' : '' ?>
                                               style="width:82px;text-align:center">
                                    </td>
                                    <td><strong><?= e($euro($ligne['total_ligne'])) ?></strong></td>
                                    <?php if ($actifOffert): ?>
                                        <td class="center">
                                            <label class="switch-mini" title="Offert">
                                                <input type="checkbox" class="panier-check" name="offert"
                                                    <?= (int)$ligne['offert'] === 1 ? 'checked' : '' ?>
                                                       data-url="<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/modifier') ?>"
                                                       data-prestation="<?= $pid ?>">
                                                <span class="switch-mini-piste"></span>
                                            </label>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($actifCpf): ?>
                                        <td class="center">
                                            <label class="switch-mini" title="CPF">
                                                <input type="checkbox" class="panier-check" name="cpf"
                                                    <?= (int)$ligne['cpf'] === 1 ? 'checked' : '' ?>
                                                       data-url="<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/modifier') ?>"
                                                       data-prestation="<?= $pid ?>">
                                                <span class="switch-mini-piste"></span>
                                            </label>
                                        </td>
                                    <?php endif; ?>
                                    <td class="cell-actions">
                                        <button type="button" class="btn btn-sm btn-danger panier-retirer"
                                                data-url="<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/retirer') ?>"
                                                data-prestation="<?= $pid ?>"
                                                data-libelle="<?= e((string)$ligne['titre']) ?>">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="total-ttc">
                    <span>Total du panier</span>
                    <strong id="panier-total"><?= e($euro($panierTotal)) ?></strong>
                </div>
            </section>

            <div class="form-footer">
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $edition ? 'Enregistrer' : 'Créer le dossier' ?></button>
                    <a class="btn btn-ghost" href="<?= url($retourEleve) ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php /* ═══ MODALE : confirmation de changement de type de formation (vide le panier) ═══ */ ?>
<div class="modal-overlay" id="modal-changer-mode">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-changer-mode-titre">
        <div class="modal-header">
            <span id="modal-changer-mode-titre">Changer le type de formation</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">
                Le panier actuel contient des prestations. Changer le type de formation
                (à la carte / formule) videra entièrement le panier. Cette action est irréversible.
            </p>
            <div class="form-actions">
                <button type="button" class="btn btn-danger" id="btn-confirmer-vidage">Vider le panier et continuer</button>
                <button type="button" class="btn btn-ghost" data-modal-close>Annuler</button>
            </div>
        </div>
    </div>
</div>

<?php /* ═══ JS : combos + panier + actions ═══ */ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var jeton = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var msg = document.getElementById('panier-msg');

    function message(texte, ko) {
        if (!msg) { return; }
        msg.textContent = texte;
        msg.className = ko ? 'panier-msg-ko' : 'panier-msg-ok';
        msg.style.display = 'block';
        clearTimeout(message._t);
        message._t = setTimeout(function () { msg.style.display = 'none'; }, 3500);
    }

    /* ---------- Combos pleine largeur ---------- */
    function combo(idInput, idSelect, rendu) {
        var input = document.getElementById(idInput);
        var select = document.getElementById(idSelect);
        if (!input || !select) { return; }
        var conteneur = input.closest('.combo');
        var liste = conteneur ? conteneur.querySelector('.combo-list') : null;
        if (!liste) { return; }

        function construire(filtre) {
            filtre = (filtre || '').toLowerCase();
            liste.innerHTML = '';
            var visibles = 0;
            Array.prototype.forEach.call(select.options, function (opt) {
                var texte = opt.textContent || '';
                if (opt.value === '' || (filtre !== '' && texte.toLowerCase().indexOf(filtre) === -1)) { return; }
                visibles++;
                var li = document.createElement('li');
                if (typeof rendu === 'function') {
                    li.innerHTML = rendu(opt);
                } else {
                    li.textContent = texte;
                }
                li.className = opt.disabled ? 'is-disabled' : (opt.selected ? 'is-selected' : '');
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (opt.disabled) { return; }
                    select.value = opt.value;
                    input.value = opt.getAttribute('data-nom') || texte;
                    conteneur.classList.remove('open');
                });
                liste.appendChild(li);
            });
            if (visibles === 0) {
                var vide = document.createElement('li');
                vide.textContent = 'Aucun résultat.';
                vide.className = 'addr-empty';
                liste.appendChild(vide);
            }
        }

        input.addEventListener('focus', function () { construire(input.value); conteneur.classList.add('open'); });
        input.addEventListener('input', function () { conteneur.classList.add('open'); construire(input.value); });
        input.addEventListener('blur', function () { setTimeout(function () { conteneur.classList.remove('open'); }, 180); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { conteneur.classList.remove('open'); }
            if (e.key === 'Enter') {
                e.preventDefault();
                var premier = liste.querySelector('li:not(.addr-empty):not(.is-disabled)');
                if (premier) { premier.dispatchEvent(new MouseEvent('mousedown', { cancelable: true })); }
            }
        });
    }
    combo('paq-recherche', 'paq-select');
    combo('pfq-recherche', 'pfq-select', function (opt) {
        var nom = opt.getAttribute('data-nom') || opt.textContent || '';
        var nb = opt.getAttribute('data-nb') || '0';
        var montant = opt.getAttribute('data-montant') || '';
        return '<span class="combo-li-formule">'
            + '<span class="combo-li-nom">' + nom + '</span>'
            + '<span class="combo-li-badges">'
            +   '<span class="badge badge-muted">' + nb + ' prestation' + (nb > 1 ? 's' : '') + '</span>'
            +   '<span class="badge badge-ok combo-li-montant">' + montant + '</span>'
            + '</span>'
            + '</span>';
    });

    /* ---------- Bascule des modes ---------- */
    function modeFormule() {
        var r = document.querySelector('input[name="mode"][value="formule"]');
        return !!(r && r.checked);
    }
    function panierEstVide() {
        var tbody = document.getElementById('panier-tbody');
        return !tbody || tbody.querySelectorAll('tr[data-ligne]').length === 0;
    }
    function appliquerZonesMode() {
        var formule = modeFormule();
        var zoneCarte = document.getElementById('zone-a-la-carte');
        if (zoneCarte) { zoneCarte.hidden = formule; }
        /* La zone Formule reste visible dans les deux modes (import possible dans les deux cas). */
        document.querySelectorAll('.panier-quantite').forEach(function (q) { q.disabled = formule; });
    }
    var modeCarteRadio   = document.getElementById('mode-carte');
    var modeFormuleRadio = document.getElementById('mode-formule');
    var modalChangerMode = document.getElementById('modal-changer-mode');
    var modeAnterieur    = modeFormule() ? 'formule' : 'a_la_carte';
    var modeCible         = null;

    document.querySelectorAll('input[name="mode"]').forEach(function (r) {
        r.addEventListener('change', function () {
            var nouveauMode = modeFormule() ? 'formule' : 'a_la_carte';
            if (nouveauMode === modeAnterieur) { return; }
            if (!panierEstVide()) {
                /* Panier non vide : on revient temporairement à l'état précédent
                   et on demande confirmation avant de vider (directive). */
                modeCible = nouveauMode;
                (modeAnterieur === 'formule' ? modeFormuleRadio : modeCarteRadio).checked = true;
                if (modalChangerMode) { modalChangerMode.classList.add('open'); }
                return;
            }
            modeAnterieur = nouveauMode;
            appliquerZonesMode();
        });
    });

    var btnConfirmerVidage = document.getElementById('btn-confirmer-vidage');
    if (btnConfirmerVidage) {
        btnConfirmerVidage.addEventListener('click', function () {
            post('<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/vider') ?>', {},
                function (rep) {
                    message(rep.message || 'Panier vidé.', false);
                    rendrePanier(rep.panier);
                    if (modeCible) {
                        (modeCible === 'formule' ? modeFormuleRadio : modeCarteRadio).checked = true;
                        modeAnterieur = modeCible;
                        modeCible = null;
                    }
                    appliquerZonesMode();
                    if (modalChangerMode) { modalChangerMode.classList.remove('open'); }
                });
        });
    }

    /* ---------- Formatage euro (aligné sur number_format($x, 2, ',', ' ')) ---------- */
    function euroFmt(x) {
        var s = Number(x || 0).toFixed(2).replace('.', ',');
        var parts = s.split(',');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return parts.join(',') + ' €';
    }
    function echapper(s) {
        var d = document.createElement('div');
        d.textContent = s === null || s === undefined ? '' : String(s);
        return d.innerHTML;
    }

    /* ---------- Panier : rendu 100% AJAX (aucun rechargement de page) ---------- */
    var actifOffert = <?= $actifOffert ? 'true' : 'false' ?>;
    var actifCpf   = <?= $actifCpf ? 'true' : 'false' ?>;
    var urlPanierModifier = '<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/modifier') ?>';
    var urlPanierRetirer  = '<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/retirer') ?>';

    function ligneHtml(l) {
        var dis = modeFormule() ? 'disabled' : '';
        var html = '<tr data-ligne="1">';
        html += '<td><strong>' + echapper(l.titre) + '</strong>';
        if (l.sku) { html += '<small style="display:block;color:var(--ink-3);font-size:.74rem">' + echapper(l.sku) + '</small>'; }
        html += '</td>';
        html += '<td>' + euroFmt(l.prix_vente) + '</td>';
        html += '<td class="center"><input class="input panier-quantite" type="number" min="1" step="1" value="' + l.quantite + '"'
              +  ' data-url="' + urlPanierModifier + '" data-prestation="' + l.prestation_id + '" ' + dis + ' style="width:82px;text-align:center"></td>';
        html += '<td><strong>' + euroFmt(l.total_ligne) + '</strong></td>';
        if (actifOffert) {
            html += '<td class="center"><label class="switch-mini" title="Offert">'
                  +  '<input type="checkbox" class="panier-check" name="offert" ' + (l.offert === 1 ? 'checked' : '') + ''
                  +  ' data-url="' + urlPanierModifier + '" data-prestation="' + l.prestation_id + '">'
                  +  '<span class="switch-mini-piste"></span></label></td>';
        }
        if (actifCpf) {
            html += '<td class="center"><label class="switch-mini" title="CPF">'
                  +  '<input type="checkbox" class="panier-check" name="cpf" ' + (l.cpf === 1 ? 'checked' : '') + ''
                  +  ' data-url="' + urlPanierModifier + '" data-prestation="' + l.prestation_id + '">'
                  +  '<span class="switch-mini-piste"></span></label></td>';
        }
        html += '<td class="cell-actions"><button type="button" class="btn btn-sm btn-danger panier-retirer"'
              +  ' data-url="' + urlPanierRetirer + '" data-prestation="' + l.prestation_id + '" data-libelle="' + echapper(l.titre) + '">&times;</button></td>';
        html += '</tr>';
        return html;
    }

    function rendrePanier(panier) {
        if (!panier) { return; }
        var tbody = document.getElementById('panier-tbody');
        var total = document.getElementById('panier-total');
        if (tbody) {
            if (!panier.lignes || panier.lignes.length === 0) {
                var colspan = 4 + (actifOffert ? 1 : 0) + (actifCpf ? 1 : 0);
                tbody.innerHTML = '<tr><td colspan="' + colspan + '"><div class="empty-state">'
                    + '<p class="empty-title">Panier vide</p>'
                    + '<p class="empty-text">Ajoutez des prestations ou une formule ci-dessus.</p>'
                    + '</div></td></tr>';
            } else {
                tbody.innerHTML = panier.lignes.map(ligneHtml).join('');
            }
        }
        if (total) { total.textContent = euroFmt(panier.total); }

        /* Griser, dans le select « à la carte », les prestations déjà présentes dans le panier. */
        var idsPanier = {};
        (panier.lignes || []).forEach(function (l) { idsPanier[String(l.prestation_id)] = true; });
        var paqSelect = document.getElementById('paq-select');
        if (paqSelect) {
            Array.prototype.forEach.call(paqSelect.options, function (opt) {
                if (opt.value === '') { return; }
                opt.disabled = !!idsPanier[opt.value];
            });
        }
    }

    /* ---------- POST AJAX (jamais de rechargement de page) ---------- */
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
        })
        .catch(function () { message('Modification impossible (serveur).', true); });
    }

    var btnCarte = document.getElementById('btn-ajout-carte');
    if (btnCarte) {
        btnCarte.addEventListener('click', function () {
            var select = document.getElementById('paq-select');
            var quantite = document.getElementById('paq-quantite');
            var recherche = document.getElementById('paq-recherche');
            var pid = select ? select.value : '';
            if (!pid) { message('Choisissez une prestation.', true); return; }
            post('<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/ajouter') ?>',
                { prestation_id: pid, quantite: quantite ? quantite.value : 1 },
                function (rep) {
                    message(rep.message || 'Prestation ajoutée.', false);
                    rendrePanier(rep.panier);
                    if (select) { select.value = ''; }
                    if (recherche) { recherche.value = ''; }
                    if (quantite) { quantite.value = 1; }
                });
        });
    }

    var btnFormule = document.getElementById('btn-ajout-formule');
    if (btnFormule) {
        btnFormule.addEventListener('click', function () {
            var select = document.getElementById('pfq-select');
            var recherche = document.getElementById('pfq-recherche');
            var fid = select ? select.value : '';
            if (!fid) { message('Choisissez une formule.', true); return; }
            post('<?= url('/dossiers/' . (int)($dossier['id'] ?? 0) . '/panier/ajouter-formule') ?>',
                { formule_id: fid },
                function (rep) {
                    message(rep.message || 'Formule ajoutée.', false);
                    rendrePanier(rep.panier);
                    if (select) { select.value = ''; }
                    if (recherche) { recherche.value = ''; }
                });
        });
    }

    /* Délégation d'événements sur le corps du tableau : les lignes sont reconstruites
       à chaque mise à jour du panier, la délégation évite de ré-attacher des écouteurs. */
    var panierTbody = document.getElementById('panier-tbody');
    if (panierTbody) {
        panierTbody.addEventListener('change', function (e) {
            var cible = e.target;
            if (cible.matches && cible.matches('.panier-quantite[data-url]')) {
                post(cible.getAttribute('data-url'),
                    { prestation_id: cible.getAttribute('data-prestation'), quantite: cible.value },
                    function (rep) { message(rep.message || 'Quantité mise à jour.', false); rendrePanier(rep.panier); });
            } else if (cible.matches && cible.matches('.panier-check[data-url]')) {
                var ligneTr = cible.closest('tr');
                var autreNom = cible.name === 'offert' ? 'cpf' : 'offert';
                var autreCase = ligneTr ? ligneTr.querySelector('.panier-check[name="' + autreNom + '"]') : null;
                /* Offert et CPF sont mutuellement exclusifs : cocher l'un décoche l'autre. */
                if (cible.checked && autreCase) { autreCase.checked = false; }
                var offertCase = ligneTr ? ligneTr.querySelector('.panier-check[name="offert"]') : null;
                var cpfCase   = ligneTr ? ligneTr.querySelector('.panier-check[name="cpf"]')   : null;
                var donnees = {
                    prestation_id: cible.getAttribute('data-prestation'),
                    offert: offertCase ? (offertCase.checked ? 1 : 0) : 0,
                    cpf:    cpfCase   ? (cpfCase.checked   ? 1 : 0) : 0,
                };
                post(cible.getAttribute('data-url'), donnees,
                    function (rep) { message(rep.message || 'Mis à jour.', false); rendrePanier(rep.panier); });
            }
        });
        panierTbody.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.panier-retirer') : null;
            if (!btn) { return; }
            var libelle = btn.getAttribute('data-libelle') || 'cette prestation';
            if (!window.confirm('Retirer « ' + libelle + ' » du panier ?')) { return; }
            post(btn.getAttribute('data-url'),
                { prestation_id: btn.getAttribute('data-prestation') },
                function (rep) { message(rep.message || 'Prestation retirée.', false); rendrePanier(rep.panier); });
        });
    }
});
</script>
<!-- AE-EOF : le fichier dossiers/views/form.php doit se terminer exactement ici -->